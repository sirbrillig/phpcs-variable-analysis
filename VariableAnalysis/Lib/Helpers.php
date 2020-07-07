<?php

namespace VariableAnalysis\Lib;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\FunctionDeclarations;

class Helpers {
  /**
   * @param int|bool $value
   *
   * @return ?int
   */
  public static function getIntOrNull($value) {
    return is_int($value) ? $value: null;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function findContainingOpeningSquareBracket(File $phpcsFile, $stackPtr) {
    $previousStatementPtr = self::getPreviousStatementPtr($phpcsFile, $stackPtr);
    return self::getIntOrNull($phpcsFile->findPrevious([T_OPEN_SHORT_ARRAY], $stackPtr - 1, $previousStatementPtr));
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return int
   */
  public static function getPreviousStatementPtr(File $phpcsFile, $stackPtr) {
    $result = $phpcsFile->findPrevious([T_SEMICOLON, T_CLOSE_CURLY_BRACKET], $stackPtr - 1);
    return is_bool($result) ? 1 : $result;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function findContainingOpeningBracket(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
      $openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
      return (int)end($openPtrs);
    }
    return null;
  }

  /**
   * @param (int|string)[] $conditions
   *
   * @return bool
   */
  public static function areAnyConditionsAClass(array $conditions) {
    foreach (array_reverse($conditions, true) as $scopeCode) {
      if ($scopeCode === T_CLASS || $scopeCode === T_ANON_CLASS || $scopeCode === T_TRAIT) {
        return true;
      }
    }
    return false;
  }

  /**
   * @param (int|string)[] $conditions
   *
   * @return bool
   */
  public static function areConditionsWithinFunctionBeforeClass(array $conditions) {
    // Return true if the token conditions are within a function before
    // they are within a class.
    $classTypes = [T_CLASS, T_ANON_CLASS, T_TRAIT];
    foreach (array_reverse($conditions, true) as $scopeCode) {
      if (in_array($scopeCode, $classTypes)) {
        return false;
      }
      if ($scopeCode === T_FUNCTION) {
        return true;
      }
    }
    return false;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  public static function isTokenInsideFunctionDefinitionArgumentList(File $phpcsFile, $stackPtr) {
    return (bool) self::getFunctionIndexForFunctionArgument($phpcsFile, $stackPtr);
  }

  /**
   * Find the index of the function keyword for a token in a function definition's arguments
   *
   * Does not work for tokens inside the "use".
   *
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function getFunctionIndexForFunctionArgument(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    $nonFunctionTokenTypes = array_values(Tokens::$emptyTokens);
    $nonFunctionTokenTypes[] = T_OPEN_PARENTHESIS;
    $nonFunctionTokenTypes[] = T_VARIABLE;
    $nonFunctionTokenTypes[] = T_ELLIPSIS;
    $nonFunctionTokenTypes[] = T_COMMA;
    $nonFunctionTokenTypes[] = T_STRING;
    $nonFunctionTokenTypes[] = T_BITWISE_AND;
    $functionPtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $stackPtr - 1, null, true, null, true));
    if (! is_int($functionPtr)) {
      return null;
    }

    $functionTokenTypes = [
      T_FUNCTION,
      T_CLOSURE,
    ];
    if (!in_array($tokens[$functionPtr]['code'], $functionTokenTypes, true) && ! FunctionDeclarations::isArrowFunction($phpcsFile, $functionPtr)) {
      return null;
    }
    return $functionPtr;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  public static function isTokenInsideFunctionUseImport(File $phpcsFile, $stackPtr) {
    return (bool) self::getUseIndexForUseImport($phpcsFile, $stackPtr);
  }

  /**
   * Find the token index of the "use" for a token inside a function use import
   *
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function getUseIndexForUseImport(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    $nonUseTokenTypes = array_values(Tokens::$emptyTokens);
    $nonUseTokenTypes[] = T_VARIABLE;
    $nonUseTokenTypes[] = T_ELLIPSIS;
    $nonUseTokenTypes[] = T_COMMA;
    $nonUseTokenTypes[] = T_BITWISE_AND;
    $openParenPtr = self::getIntOrNull($phpcsFile->findPrevious($nonUseTokenTypes, $stackPtr - 1, null, true, null, true));
    if (! is_int($openParenPtr) || $tokens[$openParenPtr]['code'] !== T_OPEN_PARENTHESIS) {
      return null;
    }

    $usePtr = self::getIntOrNull($phpcsFile->findPrevious(array_values($nonUseTokenTypes), $openParenPtr - 1, null, true, null, true));
    if (! is_int($usePtr) || $tokens[$usePtr]['code'] !== T_USE) {
      return null;
    }
    return $usePtr;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function findFunctionCall(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if (is_int($openPtr)) {
      // First non-whitespace thing and see if it's a T_STRING function name
      $functionPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
      if (is_int($functionPtr) && $tokens[$functionPtr]['code'] === T_STRING) {
        return $functionPtr;
      }
    }
    return null;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return array[]
   */
  public static function findFunctionCallArguments(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    // Slight hack: also allow this to find args for array constructor.
    if (($tokens[$stackPtr]['code'] !== T_STRING) && ($tokens[$stackPtr]['code'] !== T_ARRAY)) {
      // Assume $stackPtr is something within the brackets, find our function call
      $stackPtr = Helpers::findFunctionCall($phpcsFile, $stackPtr);
      if ($stackPtr === null) {
        return [];
      }
    }

    // $stackPtr is the function name, find our brackets after it
    $openPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);
    if (($openPtr === false) || ($tokens[$openPtr]['code'] !== T_OPEN_PARENTHESIS)) {
        return [];
    }

    if (!isset($tokens[$openPtr]['parenthesis_closer'])) {
        return [];
    }
    $closePtr = $tokens[$openPtr]['parenthesis_closer'];

    $argPtrs = [];
    $lastPtr = $openPtr;
    $lastArgComma = $openPtr;
    $nextPtr = $phpcsFile->findNext([T_COMMA], $lastPtr + 1, $closePtr);
    while (is_int($nextPtr)) {
      if (Helpers::findContainingOpeningBracket($phpcsFile, $nextPtr) == $openPtr) {
        // Comma is at our level of brackets, it's an argument delimiter.
        array_push($argPtrs, range($lastArgComma + 1, $nextPtr - 1));
        $lastArgComma = $nextPtr;
      }
      $lastPtr = $nextPtr;
      $nextPtr = $phpcsFile->findNext([T_COMMA], $lastPtr + 1, $closePtr);
    }
    array_push($argPtrs, range($lastArgComma + 1, $closePtr - 1));

    return $argPtrs;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return int
   */
  public static function findWhereAssignExecuted(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    //  Write should be recorded at the next statement to ensure we treat the
    //  assign as happening after the RHS execution.
    //  eg: $var = $var + 1; -> RHS could still be undef.
    //  However, if we're within a bracketed expression, we take place at the
    //  closing bracket, if that's first.
    //  eg: echo (($var = 12) && ($var == 12));
    $semicolonPtr = $phpcsFile->findNext([T_SEMICOLON], $stackPtr + 1, null, false, null, true);
    $commaPtr = $phpcsFile->findNext([T_COMMA], $stackPtr + 1, null, false, null, true);
    $closePtr = false;
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr !== null) {
      if (isset($tokens[$openPtr]['parenthesis_closer'])) {
        $closePtr = $tokens[$openPtr]['parenthesis_closer'];
      }
    }

    // Return the first thing: comma, semicolon, close-bracket, or stackPtr if nothing else
    $assignEndTokens = [$commaPtr, $semicolonPtr, $closePtr];
    $assignEndTokens = array_filter($assignEndTokens); // remove false values
    sort($assignEndTokens);
    if (empty($assignEndTokens)) {
      return $stackPtr;
    }
    return $assignEndTokens[0];
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function getNextAssignPointer(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    // Is the next non-whitespace an assignment?
    $nextPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);
    if (is_int($nextPtr)
      && isset(Tokens::$assignmentTokens[$tokens[$nextPtr]['code']])
      // Ignore double arrow to prevent triggering on `foreach ( $array as $k => $v )`.
      && $tokens[$nextPtr]['code'] !== T_DOUBLE_ARROW
    ) {
      return $nextPtr;
    }
    return null;
  }

  /**
   * @param string $varName
   *
   * @return string
   */
  public static function normalizeVarName($varName) {
    $result = preg_replace('/[{}$]/', '', $varName);
    return $result ? $result : $varName;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function findVariableScope(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token = $tokens[$stackPtr];

    if (self::isTokenInsideArrowFunctionBody($phpcsFile, $stackPtr)) {
      // Get the list of variables defined by the arrow function
      // If this matches any of them, the scope is the arrow function,
      // otherwise, it uses the enclosing scope.
      $arrowFunctionIndex = self::getContainingArrowFunctionIndex($phpcsFile, $stackPtr);
      if ($arrowFunctionIndex) {
        $variableNames = self::getVariablesDefinedByArrowFunction($phpcsFile, $arrowFunctionIndex);
        if (in_array($token['content'], $variableNames, true)) {
          return $arrowFunctionIndex;
        }
      }
    }

    return self::findVariableScopeExceptArrowFunctions($phpcsFile, $stackPtr);
  }

  /**
   * Return the token index of the scope start for a token
   *
   * For a variable within a function body, or a variable within a function
   * definition argument list, this will return the function keyword's index.
   *
   * For a variable within a "use" import list within a function definition,
   * this will return the enclosing scope, not the function keyword. This is
   * important to note because the "use" keyword performs double-duty, defining
   * variables for the function's scope, and consuming the variables in the
   * enclosing scope. Use `getUseIndexForUseImport` to determine if this
   * token needs to be treated as a "use".
   *
   * For a variable within an arrow function definition argument list,
   * this will return the arrow function's keyword index.
   *
   * For a variable in an arrow function body, this will return the enclosing
   * function's index, which may be incorrect.
   *
   * Since a variable in an arrow function's body may be imported from the
   * enclosing scope, it's important to test to see if the variable is in an
   * arrow function and also check its enclosing scope separately.
   *
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function findVariableScopeExceptArrowFunctions(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $allowedTypes = [
      T_VARIABLE,
      T_DOUBLE_QUOTED_STRING,
      T_HEREDOC,
      T_STRING,
    ];
    if (! in_array($tokens[$stackPtr]['code'], $allowedTypes, true)) {
      throw new \Exception("Cannot find variable scope for non-variable {$tokens[$stackPtr]['type']}");
    }

    $startOfTokenScope = self::getStartOfTokenScope($phpcsFile, $stackPtr);
    if (is_int($startOfTokenScope) && $startOfTokenScope > 0) {
      return $startOfTokenScope;
    }

    // If there is no "conditions" array, this is a function definition argument.
    if (self::isTokenInsideFunctionDefinitionArgumentList($phpcsFile, $stackPtr)) {
      $functionPtr = self::getFunctionIndexForFunctionArgument($phpcsFile, $stackPtr);
      if (! is_int($functionPtr)) {
        throw new \Exception("Function index not found for function argument index {$stackPtr}");
      }
      return $functionPtr;
    }

    self::debug('Cannot find function scope for variable at', $stackPtr);
    return $startOfTokenScope;
  }

  /**
   * Return the token index of the scope start for a variable token
   *
   * This will only work for a variable within a function's body. Otherwise,
   * see `findVariableScope`, which is more complex.
   *
   * Note that if used on a variable in an arrow function, it will return the
   * enclosing function's scope, which may be incorrect.
   *
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  private static function getStartOfTokenScope(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token = $tokens[$stackPtr];

    $in_class = false;
    $conditions = isset($token['conditions']) ? $token['conditions'] : [];
    $functionTokenTypes = [
      T_FUNCTION,
      T_CLOSURE,
    ];
    foreach (array_reverse($conditions, true) as $scopePtr => $scopeCode) {
      if (in_array($scopeCode, $functionTokenTypes, true) || FunctionDeclarations::isArrowFunction($phpcsFile, $scopePtr)) {
        return $scopePtr;
      }
      if (isset(Tokens::$ooScopeTokens[$scopeCode]) === true) {
        $in_class = true;
      }
    }

    if ($in_class) {
      // If this is inside a class and not inside a function, this is either a
      // class member variable definition, or a function argument. If it is a
      // variable definition, it has no scope on its own (it can only be used
      // with an object reference). If it is a function argument, we need to do
      // more work (see `findVariableScopeExceptArrowFunctions`).
      return null;
    }

    // If we can't find a scope, let's use the first token of the file.
    return 0;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  public static function isTokenInsideArrowFunctionDefinition(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token = $tokens[$stackPtr];
    $openParenIndices = isset($token['nested_parenthesis']) ? $token['nested_parenthesis'] : [];
    if ($openParenIndices) {
      return false;
    }
    $openParenPtr = $openParenIndices[0];
    return FunctionDeclarations::isArrowFunction($phpcsFile, $openParenPtr - 1);
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  public static function isTokenInsideArrowFunctionBody(File $phpcsFile, $stackPtr) {
    return (bool) self::getContainingArrowFunctionIndex($phpcsFile, $stackPtr);
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  public static function getContainingArrowFunctionIndex(File $phpcsFile, $stackPtr) {
    $arrowFunctionIndex = self::getPreviousArrowFunctionIndex($phpcsFile, $stackPtr);
    if (! is_int($arrowFunctionIndex)) {
      return null;
    }
    $arrowFunctionInfo = FunctionDeclarations::getArrowFunctionOpenClose($phpcsFile, $arrowFunctionIndex);
    if (! $arrowFunctionInfo) {
      return null;
    }
    $arrowFunctionScopeStart = $arrowFunctionInfo['scope_opener'];
    $arrowFunctionScopeEnd = $arrowFunctionInfo['scope_closer'];
    if ($stackPtr > $arrowFunctionScopeStart && $stackPtr < $arrowFunctionScopeEnd) {
      return $arrowFunctionIndex;
    }
    return null;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return ?int
   */
  private static function getPreviousArrowFunctionIndex(File $phpcsFile, $stackPtr) {
    $enclosingScopeIndex = self::findVariableScopeExceptArrowFunctions($phpcsFile, $stackPtr);
    for ($index = $stackPtr - 1; $index > $enclosingScopeIndex; $index--) {
      if (FunctionDeclarations::isArrowFunction($phpcsFile, $index)) {
        return $index;
      }
    }
    return null;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return string[]
   */
  public static function getVariablesDefinedByArrowFunction(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $arrowFunctionToken = $tokens[$stackPtr];
    $variableNames = [];
    self::debug('looking for variables in arrow function token', $arrowFunctionToken);
    for ($index = $arrowFunctionToken['parenthesis_opener']; $index < $arrowFunctionToken['parenthesis_closer']; $index++) {
      $token = $tokens[$index];
      if ($token['code'] === T_VARIABLE) {
        $variableNames[] = $token['content'];
      }
    }
    return $variableNames;
  }

  /**
   * @return void
   */
  public static function debug(...$messages) {
    if (! defined('PHP_CODESNIFFER_VERBOSITY')) {
      return;
    }
    if (PHP_CODESNIFFER_VERBOSITY <= 3) {
      return;
    }
    $output = PHP_EOL . "VariableAnalysisSniff: DEBUG:";
    foreach ($messages as $message) {
      if (is_string($message) || is_numeric($message)) {
        $output .= ' "' . $message . '"';
        continue;
      }
      $output .= PHP_EOL . var_export($message, true) . PHP_EOL;
    }
    $output .= PHP_EOL;
    echo $output;
  }

  /**
   * @param string $pattern
   * @param string $value
   *
   * @return string[]
   */
  public static function splitStringToArray($pattern, $value) {
    $result = preg_split($pattern, $value);
    return is_array($result) ? $result : [];
  }

  /**
   * @param string $varName
   *
   * @return bool
   */
  public static function isVariableANumericVariable($varName) {
    return is_numeric(substr($varName, 0, 1));
  }
}
