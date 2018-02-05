<?php

namespace VariableAnalysis\Sniffs\CodeAnalysis;

use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\VariableInfo;
use VariableAnalysis\Lib\Constants;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Checks the code for undefined function variables.
 *
 * This sniff checks that all function variables
 * are defined in the function body.
 */
class VariableAnalysisSniff implements Sniff {
  /**
   * The current phpcsFile being checked.
   *
   * @var phpcsFile
   */
  protected $currentFile = null;

  /**
   * A list of scopes encountered so far and the variables within them.
   */
  private $scopes = [];

  /**
   *  Allows an install to extend the list of known pass-by-reference functions
   *  by defining generic.codeanalysis.variableanalysis.sitePassByRefFunctions.
   */
  public $sitePassByRefFunctions = null;

  /**
   *  Allows exceptions in a catch block to be unused without provoking unused-var warning.
   *  Set generic.codeanalysis.variableanalysis.allowUnusedCaughtExceptions to a true value.
   */
  public $allowUnusedCaughtExceptions = false;

  /**
   *  Allow function parameters to be unused without provoking unused-var warning.
   *  Set generic.codeanalysis.variableanalysis.allowUnusedFunctionParameters to a true value.
   */
  public $allowUnusedFunctionParameters = false;

  /**
   *  A list of names of placeholder variables that you want to ignore from
   *  unused variable warnings, ie things like $junk.
   */
  public $validUnusedVariableNames = null;

  /**
   * Returns an array of tokens this test wants to listen for.
   *
   * @return array
   */
  public function register() {
    if (!empty($this->validUnusedVariableNames)) {
      $this->validUnusedVariableNames =
        preg_split('/\s+/', trim($this->validUnusedVariableNames));
    }
    return [
      T_VARIABLE,
      T_DOUBLE_QUOTED_STRING,
      T_HEREDOC,
      T_CLOSE_CURLY_BRACKET,
      T_STRING,
    ];
  }

  private function getPassByReferenceFunction(string $functionName) {
    $passByRefFunctions = Constants::getPassByReferenceFunctions();
    //  Magic to modfy $passByRefFunctions with any site-specific settings.
    if (!empty($this->sitePassByRefFunctions)) {
      foreach (preg_split('/\s+/', trim($this->sitePassByRefFunctions)) as $line) {
        list ($function, $args) = explode(':', $line);
        $passByRefFunctions[$function] = explode(',', $args);
      }
    }
    return isset($passByRefFunctions[$functionName]) ? $passByRefFunctions[$functionName] : null;
  }

  /**
   * Processes this test, when one of its tokens is encountered.
   *
   * @param File $phpcsFile The file being scanned.
   * @param int $stackPtr  The position of the current token in the stack passed in $tokens.
   */
  public function process(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    if ($this->currentFile !== $phpcsFile) {
      $this->currentFile = $phpcsFile;
    }

    if ($token['code'] === T_VARIABLE) {
      return $this->processVariable($phpcsFile, $stackPtr);
    }
    if (($token['code'] === T_DOUBLE_QUOTED_STRING) ||
      ($token['code'] === T_HEREDOC)) {
      return $this->processVariableInString($phpcsFile, $stackPtr);
    }
    if (($token['code'] === T_STRING) && ($token['content'] === 'compact')) {
      return $this->processCompact($phpcsFile, $stackPtr);
    }
    if (($token['code'] === T_CLOSE_CURLY_BRACKET) &&
      isset($token['scope_condition'])) {
      return $this->processScopeClose($phpcsFile, $token['scope_condition']);
    }
  }

  protected function normalizeVarName($varName) {
    $varName = preg_replace('/[{}$]/', '', $varName);
    return $varName;
  }

  protected function getScopeKey($currScope) {
    if ($currScope === false) {
      $currScope = 'file';
    }
    return ($this->currentFile ? $this->currentFile->getFilename() : 'unknown file') . ':' . $currScope;
  }

  protected function getScopeInfo($currScope) {
    $scopeKey = $this->getScopeKey($currScope);
    return $this->scopes[$scopeKey] ?? null;
  }

  protected function getOrCreateScopeInfo($currScope) {
    $scopeKey = $this->getScopeKey($currScope);
    if (!isset($this->scopes[$scopeKey])) {
      $this->scopes[$scopeKey] = new ScopeInfo($currScope);
    }
    return $this->scopes[$scopeKey];
  }

  protected function getVariableInfo($varName, $currScope) {
    $scopeInfo = $this->getScopeInfo($currScope);
    return $scopeInfo->variables[$varName] ?? null;
  }

  protected function getOrCreateVariableInfo($varName, $currScope) {
    $scopeInfo = $this->getOrCreateScopeInfo($currScope);
    if (!isset($scopeInfo->variables[$varName])) {
      $scopeInfo->variables[$varName] = new VariableInfo($varName);
      if ($this->validUnusedVariableNames && in_array($varName, $this->validUnusedVariableNames)) {
        $scopeInfo->variables[$varName]->ignoreUnused = true;
      }
    }
    return $scopeInfo->variables[$varName];
  }

  protected function markVariableAssignment($varName, $stackPtr, $currScope) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if (!isset($varInfo->scopeType)) {
      $varInfo->scopeType = 'local';
    }
    if (isset($varInfo->firstInitialized) && ($varInfo->firstInitialized <= $stackPtr)) {
      return;
    }
    $varInfo->firstInitialized = $stackPtr;
  }

  protected function markVariableDeclaration(
    $varName,
    $scopeType,
    $typeHint,
    $stackPtr,
    $currScope,
    $permitMatchingRedeclaration = false
  ) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if (isset($varInfo->scopeType)) {
      if (($permitMatchingRedeclaration === false) ||
        ($varInfo->scopeType !== $scopeType)) {
        //  Issue redeclaration/reuse warning
        //  Note: we check off scopeType not firstDeclared, this is so that
        //    we catch declarations that come after implicit declarations like
        //    use of a variable as a local.
        $this->currentFile->addWarning(
          "Redeclaration of %s %s as %s.",
          $stackPtr,
          'VariableRedeclaration',
          [
            VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
            "\${$varName}",
            VariableInfo::$scopeTypeDescriptions[$scopeType],
          ]
        );
      }
    }
    $varInfo->scopeType = $scopeType;
    if (isset($typeHint)) {
      $varInfo->typeHint = $typeHint;
    }
    if (isset($varInfo->firstDeclared) && ($varInfo->firstDeclared <= $stackPtr)) {
      return;
    }
    $varInfo->firstDeclared = $stackPtr;
  }

  protected function markVariableRead($varName, $stackPtr, $currScope) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if (isset($varInfo->firstRead) && ($varInfo->firstRead <= $stackPtr)) {
      return;
    }
    $varInfo->firstRead = $stackPtr;
  }

  protected function isVariableInitialized($varName, $stackPtr, $currScope) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if (isset($varInfo->firstInitialized) && $varInfo->firstInitialized <= $stackPtr) {
      return true;
    }
    return false;
  }

  protected function isVariableUndefined($varName, $stackPtr, $currScope) {
    $varInfo = $this->getVariableInfo($varName, $currScope, false);
    if (isset($varInfo->firstDeclared) && $varInfo->firstDeclared <= $stackPtr) {
      // TODO: do we want to check scopeType here?
      return false;
    }
    if (isset($varInfo->firstInitialized) && $varInfo->firstInitialized <= $stackPtr) {
      return false;
    }
    return true;
  }

  protected function markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope) {
    $this->markVariableRead($varName, $stackPtr, $currScope);
    if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
      $phpcsFile->addWarning(
        "Variable %s is undefined.",
        $stackPtr,
        'UndefinedVariable',
        ["\${$varName}"]
      );
    }
  }

  protected function findFunctionPrototype(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
      return false;
    }
    $functionPtr = $this->findPreviousFunctionPtr($phpcsFile, $openPtr);
    if (($functionPtr !== false) && ($tokens[$functionPtr]['code'] === T_FUNCTION)) {
      return $functionPtr;
    }
    return false;
  }

  protected function findVariableScope(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $in_class = false;
    if (!empty($token['conditions'])) {
      foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
        if (($scopeCode === T_FUNCTION) || ($scopeCode === T_CLOSURE)) {
          return $scopePtr;
        }
        if (($scopeCode === T_CLASS) || ($scopeCode === T_INTERFACE)) {
          $in_class = true;
        }
      }
    }

    if (($scopePtr = $this->findFunctionPrototype($phpcsFile, $stackPtr)) !== false) {
      return $scopePtr;
    }

    if ($in_class) {
      // Member var of a class, we don't care.
      return false;
    }

    // File scope, hmm, lets use first token of file?
    return 0;
  }

  protected function isNextThingAnAssign(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    // Is the next non-whitespace an assignment?
    $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true, null, true);
    if ($nextPtr !== false) {
      if ($tokens[$nextPtr]['code'] === T_EQUAL) {
        return $nextPtr;
      }
    }
    return false;
  }

  protected function findWhereAssignExecuted(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    //  Write should be recorded at the next statement to ensure we treat the
    //  assign as happening after the RHS execution.
    //  eg: $var = $var + 1; -> RHS could still be undef.
    //  However, if we're within a bracketed expression, we take place at the
    //  closing bracket, if that's first.
    //  eg: echo (($var = 12) && ($var == 12));
    $semicolonPtr = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1, null, false, null, true);
    $commaPtr = $phpcsFile->findNext(T_COMMA, $stackPtr + 1, null, false, null, true);
    $closePtr = false;
    if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) !== false) {
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

  protected function findContainingBrackets(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
      $openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
      return end($openPtrs);
    }
    return false;
  }


  protected function findFunctionCall(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    if ($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) {
      // First non-whitespace thing and see if it's a T_STRING function name
      $functionPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
      if ($tokens[$functionPtr]['code'] === T_STRING) {
        return $functionPtr;
      }
    }
    return false;
  }

  protected function findFunctionCallArguments(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    // Slight hack: also allow this to find args for array constructor.
    // TODO: probably should refactor into three functions: arg-finding and bracket-finding
    if (($tokens[$stackPtr]['code'] !== T_STRING) && ($tokens[$stackPtr]['code'] !== T_ARRAY)) {
      // Assume $stackPtr is something within the brackets, find our function call
      if (($stackPtr = $this->findFunctionCall($phpcsFile, $stackPtr)) === false) {
        return false;
      }
    }

    // $stackPtr is the function name, find our brackets after it
    $openPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true, null, true);
    if (($openPtr === false) || ($tokens[$openPtr]['code'] !== T_OPEN_PARENTHESIS)) {
      return false;
    }

    if (!isset($tokens[$openPtr]['parenthesis_closer'])) {
      return false;
    }
    $closePtr = $tokens[$openPtr]['parenthesis_closer'];

    $argPtrs = [];
    $lastPtr = $openPtr;
    $lastArgComma = $openPtr;
    while (($nextPtr = $phpcsFile->findNext(T_COMMA, $lastPtr + 1, $closePtr)) !== false) {
      if ($this->findContainingBrackets($phpcsFile, $nextPtr) == $openPtr) {
        // Comma is at our level of brackets, it's an argument delimiter.
        array_push($argPtrs, range($lastArgComma + 1, $nextPtr - 1));
        $lastArgComma = $nextPtr;
      }
      $lastPtr = $nextPtr;
    }
    array_push($argPtrs, range($lastArgComma + 1, $closePtr - 1));

    return $argPtrs;
  }

  protected function checkForFunctionPrototype(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a function or closure parameter?
    // It would be nice to get the list of function parameters from watching for
    // T_FUNCTION, but AbstractVariableSniff and AbstractScopeSniff define everything
    // we need to do that as private or final, so we have to do it this hackish way.
    if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    $functionPtr = $this->findPreviousFunctionPtr($phpcsFile, $openPtr);
    if (($functionPtr !== false) &&
      (($tokens[$functionPtr]['code'] === T_FUNCTION) ||
      ($tokens[$functionPtr]['code'] === T_CLOSURE))) {
      // TODO: typeHint
      $this->markVariableDeclaration($varName, 'param', null, $stackPtr, $functionPtr);
      // Are we pass-by-reference?
      $referencePtr = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true, null, true);
      if (($referencePtr !== false) && ($tokens[$referencePtr]['code'] === T_BITWISE_AND)) {
        $varInfo = $this->getOrCreateVariableInfo($varName, $functionPtr);
        $varInfo->passByReference = true;
      }
      //  Are we optional with a default?
      if ($this->isNextThingAnAssign($phpcsFile, $stackPtr) !== false) {
        $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
      }
      return true;
    }

    // Is it a use keyword?  Use is both a read and a define, fun!
    if (($functionPtr !== false) && ($tokens[$functionPtr]['code'] === T_USE)) {
      $this->markVariableRead($varName, $stackPtr, $currScope);
      if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
        // We haven't been defined by this point.
        $phpcsFile->addWarning("Variable %s is undefined.", $stackPtr, 'UndefinedVariable', ["\${$varName}"]);
        return true;
      }
      // $functionPtr is at the use, we need the function keyword for start of scope.
      $functionPtr = $phpcsFile->findPrevious(T_CLOSURE, $functionPtr - 1, $currScope + 1, false, null, true);
      if ($functionPtr !== false) {
        // TODO: typeHints in use?
        $this->markVariableDeclaration($varName, 'bound', null, $stackPtr, $functionPtr);
        $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
        return true;
      }
    }
    return false;
  }

  protected function findPreviousFunctionPtr($phpcsFile, $openPtr) {
    // Function names are T_STRING, and return-by-reference is T_BITWISE_AND,
    // so we look backwards from the opening bracket for the first thing that
    // isn't a function name, reference sigil or whitespace and check if it's a
    // function keyword.
    $functionPtrTypes = [T_STRING, T_WHITESPACE, T_BITWISE_AND];
    return $phpcsFile->findPrevious($functionPtrTypes, $openPtr - 1, null, true, null, true);
  }

  protected function checkForCatchBlock(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a catch block parameter?
    if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    $catchPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
    if (($catchPtr !== false) && ($tokens[$catchPtr]['code'] === T_CATCH)) {
      // Scope of the exception var is actually the function, not just the catch block.
      // TODO: typeHint
      $this->markVariableDeclaration($varName, 'local', null, $stackPtr, $currScope, true);
      $this->markVariableAssignment($varName, $stackPtr, $currScope);
      if ($this->allowUnusedCaughtExceptions) {
        $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
        $varInfo->ignoreUnused = true;
      }
      return true;
    }
    return false;
  }

  protected function checkForThisWithinClass(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we $this within a class?
    if (($varName !== 'this') || empty($token['conditions'])) {
      return false;
    }

    foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
      //  $this within a closure is valid
      //  Note: have to fetch code from $tokens, T_CLOSURE isn't set for conditions codes.
      if ($tokens[$scopePtr]['code'] === T_CLOSURE) {
        return true;
      }
      if ($scopeCode === T_CLASS || $scopeCode === T_TRAIT) {
        return true;
      }
    }

    return false;
  }

  protected function checkForSuperGlobal(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a superglobal variable?
    if (in_array($varName, [
      'GLOBALS',
      '_SERVER',
      '_GET',
      '_POST',
      '_FILES',
      '_COOKIE',
      '_SESSION',
      '_REQUEST',
      '_ENV',
      'argv',
      'argc',
    ])) {
      return true;
    }

    return false;
  }

  protected function checkForStaticMember(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a static member?
    $doubleColonPtr = $stackPtr - 1;
    if ($tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
      return false;
    }
    $classNamePtr   = $stackPtr - 2;
    if (($tokens[$classNamePtr]['code'] !== T_STRING)
      && ($tokens[$classNamePtr]['code'] !== T_SELF)
      && ($tokens[$classNamePtr]['code'] !== T_STATIC)) {
      return false;
    }

    // Are we refering to self:: outside a class?
    // TODO: not sure this is our business or should be some other sniff.
    if (($tokens[$classNamePtr]['code'] === T_SELF) || ($tokens[$classNamePtr]['code'] === T_STATIC)) {
      if ($tokens[$classNamePtr]['code'] === T_SELF) {
        $err_class = 'SelfOutsideClass';
        $err_desc  = 'self::';
      } else {
        $err_class = 'StaticOutsideClass';
        $err_desc  = 'static::';
      }
      if (!empty($token['conditions'])) {
        foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
          //  self within a closure is invalid
          //  Note: have to fetch code from $tokens, T_CLOSURE isn't set for conditions codes.
          if ($tokens[$scopePtr]['code'] === T_CLOSURE) {
            $phpcsFile->addError("Use of {$err_desc}%s inside closure.", $stackPtr, $err_class, ["\${$varName}"]);
            return true;
          }
          if ($scopeCode === T_CLASS) {
            return true;
          }
        }
      }
      $phpcsFile->addError("Use of {$err_desc}%s outside class definition.", $stackPtr, $err_class, ["\${$varName}"]);
      return true;
    }

    return true;
  }

  protected function checkForAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Is the next non-whitespace an assignment?
    if (($assignPtr = $this->isNextThingAnAssign($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    // Plain ol' assignment. Simpl(ish).
    if (($writtenPtr = $this->findWhereAssignExecuted($phpcsFile, $assignPtr)) === false) {
      $writtenPtr = $stackPtr;  // I dunno
    }
    $this->markVariableAssignment($varName, $writtenPtr, $currScope);
    return true;
  }

  protected function checkForListAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // OK, are we within a list (...) construct?
    if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
    if (($prevPtr === false) || ($tokens[$prevPtr]['code'] !== T_LIST)) {
      return false;
    }

    // OK, we're a list (...) construct... are we being assigned to?
    $closePtr = $tokens[$openPtr]['parenthesis_closer'];
    if (($assignPtr = $this->isNextThingAnAssign($phpcsFile, $closePtr)) === false) {
      return false;
    }

    // Yes, we're being assigned.
    $writtenPtr = $this->findWhereAssignExecuted($phpcsFile, $assignPtr);
    $this->markVariableAssignment($varName, $writtenPtr, $currScope);
    return true;
  }

  protected function checkForGlobalDeclaration(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a global declaration?
    // Search backwards for first token that isn't whitespace, comma or variable.
    $globalPtr = $phpcsFile->findPrevious([T_WHITESPACE, T_VARIABLE, T_COMMA], $stackPtr - 1, null, true, null, true);
    if (($globalPtr === false) || ($tokens[$globalPtr]['code'] !== T_GLOBAL)) {
      return false;
    }

    // It's a global declaration.
    $this->markVariableDeclaration($varName, 'global', null, $stackPtr, $currScope);
    return true;
  }

  protected function checkForStaticDeclaration(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a static declaration?
    // Static declarations are a bit more complicated than globals, since they
    // can contain assignments. The assignment is compile-time however so can
    // only be constant values, which makes life manageable.
    //
    // Just to complicate matters further, late static binding constants
    // take the form static::CONSTANT and are invalid within static variable
    // assignments, but we don't want to accidentally match their use of the
    // static keyword.
    //
    // Valid values are:
    //   number         T_MINUS T_LNUMBER T_DNUMBER
    //   string         T_CONSTANT_ENCAPSED_STRING
    //   heredoc        T_START_HEREDOC T_HEREDOC T_END_HEREDOC
    //   nowdoc         T_START_NOWDOC T_NOWDOC T_END_NOWDOC
    //   define         T_STRING
    //   class constant T_STRING T_DOUBLE_COLON T_STRING
    // Search backwards for first token that isn't whitespace, comma, variable,
    // equals, or on the list of assignable constant values above.
    $staticPtr = $phpcsFile->findPrevious(
      [
        T_WHITESPACE, T_VARIABLE, T_COMMA, T_EQUAL,
        T_MINUS, T_LNUMBER, T_DNUMBER,
        T_CONSTANT_ENCAPSED_STRING,
        T_STRING,
        T_DOUBLE_COLON,
        T_START_HEREDOC, T_HEREDOC, T_END_HEREDOC,
        T_START_NOWDOC, T_NOWDOC, T_END_NOWDOC,
      ],
      $stackPtr - 1,
      null,
      true,
      null,
      true
    );
    if (($staticPtr === false) || ($tokens[$staticPtr]['code'] !== T_STATIC)) {
      return false;
    }

    // Is it a late static binding static::?
    // If so, this isn't the static keyword we're looking for, but since
    // static:: isn't allowed in a compile-time constant, we also know
    // we can't be part of a static declaration anyway, so there's no
    // need to look any further.
    $lateStaticBindingPtr = $phpcsFile->findNext(T_WHITESPACE, $staticPtr + 1, null, true, null, true);
    if (($lateStaticBindingPtr !== false) && ($tokens[$lateStaticBindingPtr]['code'] === T_DOUBLE_COLON)) {
      return false;
    }

    // It's a static declaration.
    $this->markVariableDeclaration($varName, 'static', null, $stackPtr, $currScope);
    if ($this->isNextThingAnAssign($phpcsFile, $stackPtr) !== false) {
      $this->markVariableAssignment($varName, $stackPtr, $currScope);
    }
    return true;
  }

  protected function checkForForeachLoopVar(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a foreach loopvar?
    if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    // Is there an 'as' token between us and the opening bracket?
    if ($phpcsFile->findPrevious(T_AS, $stackPtr - 1, $openPtr) === false) {
      return false;
    }

    $this->markVariableAssignment($varName, $stackPtr, $currScope);
    return true;
  }

  protected function checkForPassByReferenceFunctionCall(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we pass-by-reference to known pass-by-reference function?
    if (($functionPtr = $this->findFunctionCall($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    // Is our function a known pass-by-reference function?
    $functionName = $tokens[$functionPtr]['content'];
    $refArgs = $this->getPassByReferenceFunction($functionName);
    if (! $refArgs) {
      return false;
    }

    if (($argPtrs = $this->findFunctionCallArguments($phpcsFile, $stackPtr)) === false) {
      return false;
    }

    // We're within a function call arguments list, find which arg we are.
    $argPos = false;
    foreach ($argPtrs as $idx => $ptrs) {
      if (in_array($stackPtr, $ptrs)) {
        $argPos = $idx + 1;
        break;
      }
    }
    if ($argPos === false) {
      return false;
    }
    if (!in_array($argPos, $refArgs)) {
      // Our arg wasn't mentioned explicitly, are we after an elipsis catch-all?
      if (($elipsis = array_search('...', $refArgs)) === false) {
        return false;
      }
      if ($argPos < $refArgs[$elipsis - 1]) {
        return false;
      }
    }

    // Our argument position matches that of a pass-by-ref argument,
    // check that we're the only part of the argument expression.
    foreach ($argPtrs[$argPos - 1] as $ptr) {
      if ($ptr === $stackPtr) {
        continue;
      }
      if ($tokens[$ptr]['code'] !== T_WHITESPACE) {
        return false;
      }
    }

    // Just us, we can mark it as a write.
    $this->markVariableAssignment($varName, $stackPtr, $currScope);
    // It's a read as well for purposes of used-variables.
    $this->markVariableRead($varName, $stackPtr, $currScope);
    return true;
  }

  protected function checkForSymbolicObjectProperty(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a symbolic object property/function derefeference?
    // Search backwards for first token that isn't whitespace, is it a "->" operator?
    $objectOperatorPtr = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true, null, true);
    if (($objectOperatorPtr === false) || ($tokens[$objectOperatorPtr]['code'] !== T_OBJECT_OPERATOR)) {
      return false;
    }

    $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
    return true;
  }

  /**
   * Called to process class member vars.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position where the token was found.
   */
  protected function processMemberVar(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];
    // TODO: don't care for now
  }

  /**
   * Called to process normal member vars.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position where the token was found.
   */
  protected function processVariable(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $varName = $this->normalizeVarName($token['content']);
    if (($currScope = $this->findVariableScope($phpcsFile, $stackPtr)) === false) {
      return;
    }

    // Determine if variable is being assigned or read.

    // Read methods that preempt assignment:
    //   Are we a $object->$property type symbolic reference?

    // Possible assignment methods:
    //   Is a mandatory function/closure parameter
    //   Is an optional function/closure parameter with non-null value
    //   Is closure use declaration of a variable defined within containing scope
    //   catch (...) block start
    //   $this within a class (but not within a closure).
    //   $GLOBALS, $_REQUEST, etc superglobals.
    //   $var part of class::$var static member
    //   Assignment via =
    //   Assignment via list (...) =
    //   Declares as a global
    //   Declares as a static
    //   Assignment via foreach (... as ...) { }
    //   Pass-by-reference to known pass-by-reference function

    // Are we a $object->$property type symbolic reference?
    if ($this->checkForSymbolicObjectProperty($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we a function or closure parameter?
    if ($this->checkForFunctionPrototype($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we a catch parameter?
    if ($this->checkForCatchBlock($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we $this within a class?
    if ($this->checkForThisWithinClass($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we a $GLOBALS, $_REQUEST, etc superglobal?
    if ($this->checkForSuperGlobal($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // $var part of class::$var static member
    if ($this->checkForStaticMember($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Is the next non-whitespace an assignment?
    if ($this->checkForAssignment($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // OK, are we within a list (...) = construct?
    if ($this->checkForListAssignment($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we a global declaration?
    if ($this->checkForGlobalDeclaration($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we a static declaration?
    if ($this->checkForStaticDeclaration($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we a foreach loopvar?
    if ($this->checkForForeachLoopVar($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // Are we pass-by-reference to known pass-by-reference function?
    if ($this->checkForPassByReferenceFunctionCall($phpcsFile, $stackPtr, $varName, $currScope)) {
      return;
    }

    // OK, we don't appear to be a write to the var, assume we're a read.
    $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
  }

  /**
   * Called to process variables found in double quoted strings.
   *
   * Note that there may be more than one variable in the string, which will
   * result only in one call for the string.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position where the double quoted string was found.
   */
  protected function processVariableInString(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    if (!preg_match_all(Constants::getDoubleQuotedVarRegexp(), $token['content'], $matches)) {
      return;
    }

    $currScope = $this->findVariableScope($phpcsFile, $stackPtr);
    foreach ($matches[1] as $varName) {
      $varName = $this->normalizeVarName($varName);
      // Are we $this within a class?
      if ($this->checkForThisWithinClass($phpcsFile, $stackPtr, $varName, $currScope)) {
        continue;
      }
      if ($this->checkForSuperGlobal($phpcsFile, $stackPtr, $varName, $currScope)) {
        continue;
      }
      $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
    }
  }

  protected function processCompactArguments(File $phpcsFile, $stackPtr, $arguments, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    foreach ($arguments as $argumentPtrs) {
      $argumentPtrs = array_values(array_filter($argumentPtrs, function ($argumentPtr) use ($tokens) {
        return $tokens[$argumentPtr]['code'] !== T_WHITESPACE;
      }));
      if (empty($argumentPtrs)) {
        continue;
      }
      if (!isset($tokens[$argumentPtrs[0]])) {
        continue;
      }
      $argument_first_token = $tokens[$argumentPtrs[0]];
      if ($argument_first_token['code'] === T_ARRAY) {
        // It's an array argument, recurse.
        if (($array_arguments = $this->findFunctionCallArguments($phpcsFile, $argumentPtrs[0])) !== false) {
          $this->processCompactArguments($phpcsFile, $stackPtr, $array_arguments, $currScope);
        }
        continue;
      }
      if (count($argumentPtrs) > 1) {
        // Complex argument, we can't handle it, ignore.
        continue;
      }
      if ($argument_first_token['code'] === T_CONSTANT_ENCAPSED_STRING) {
        // Single-quoted string literal, ie compact('whatever').
        // Substr is to strip the enclosing single-quotes.
        $varName = substr($argument_first_token['content'], 1, -1);
        $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $argumentPtrs[0], $currScope);
        continue;
      }
      if ($argument_first_token['code'] === T_DOUBLE_QUOTED_STRING) {
        // Double-quoted string literal.
        if (preg_match(Constants::getDoubleQuotedVarRegexp(), $argument_first_token['content'])) {
          // Bail if the string needs variable expansion, that's runtime stuff.
          continue;
        }
        // Substr is to strip the enclosing double-quotes.
        $varName = substr($argument_first_token['content'], 1, -1);
        $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $argumentPtrs[0], $currScope);
        continue;
      }
    }
  }

  /**
   * Called to process variables named in a call to compact().
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position where the call to compact() was found.
   */
  protected function processCompact(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $currScope = $this->findVariableScope($phpcsFile, $stackPtr);

    if (($arguments = $this->findFunctionCallArguments($phpcsFile, $stackPtr)) !== false) {
      $this->processCompactArguments($phpcsFile, $stackPtr, $arguments, $currScope);
    }
  }

  /**
   * Called to process the end of a scope.
   *
   * Note that although triggered by the closing curly brace of the scope, $stackPtr is
   * the scope conditional, not the closing curly brace.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position of the scope conditional.
   */
  protected function processScopeClose(File $phpcsFile, $stackPtr) {
    $scopeInfo = $this->getScopeInfo($stackPtr);
    if (is_null($scopeInfo)) {
      return;
    }
    foreach ($scopeInfo->variables as $varInfo) {
      if ($varInfo->ignoreUnused || isset($varInfo->firstRead)) {
        continue;
      }
      if ($this->allowUnusedFunctionParameters && $varInfo->scopeType == 'param') {
        continue;
      }
      if ($varInfo->passByReference && isset($varInfo->firstInitialized)) {
        // If we're pass-by-reference then it's a common pattern to
        // use the variable to return data to the caller, so any
        // assignment also counts as "variable use" for the purposes
        // of "unused variable" warnings.
        continue;
      }
      if (isset($varInfo->firstDeclared)) {
        $phpcsFile->addWarning(
          "Unused %s %s.",
          $varInfo->firstDeclared,
          'UnusedVariable',
          [
            VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
            "\${$varInfo->name}",
          ]
        );
      }
      if (isset($varInfo->firstInitialized)) {
        $phpcsFile->addWarning(
          "Unused %s %s.",
          $varInfo->firstInitialized,
          'UnusedVariable',
          [
            VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
            "\${$varInfo->name}",
          ]
        );
      }
    }
  }
}
