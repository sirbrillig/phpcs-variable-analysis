<?php

namespace VariableAnalysis\Sniffs\CodeAnalysis;

use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\VariableInfo;
use VariableAnalysis\Lib\Constants;
use VariableAnalysis\Lib\Helpers;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

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

  public function process(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    if ($this->currentFile !== $phpcsFile) {
      $this->currentFile = $phpcsFile;
    }

    if ($token['code'] === T_VARIABLE) {
      return $this->processVariable($phpcsFile, $stackPtr);
    }
    if (($token['code'] === T_DOUBLE_QUOTED_STRING) || ($token['code'] === T_HEREDOC)) {
      return $this->processVariableInString($phpcsFile, $stackPtr);
    }
    if (($token['code'] === T_STRING) && ($token['content'] === 'compact')) {
      return $this->processCompact($phpcsFile, $stackPtr);
    }
    if (($token['code'] === T_CLOSE_CURLY_BRACKET) && isset($token['scope_condition'])) {
      return $this->processScopeClose($phpcsFile, $token['scope_condition']);
    }
  }

  protected function getScopeKey($currScope) {
    if ($currScope === false) {
      $currScope = 'file';
    }
    return ($this->currentFile ? $this->currentFile->getFilename() : 'unknown file') . ':' . $currScope;
  }

  protected function getScopeInfo($currScope) {
    $scopeKey = $this->getScopeKey($currScope);
    return isset($this->scopes[$scopeKey]) ? $this->scopes[$scopeKey] : null;
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
    return isset($scopeInfo->variables[$varName]) ? $scopeInfo->variables[$varName] : null;
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
      if (($permitMatchingRedeclaration === false) || ($varInfo->scopeType !== $scopeType)) {
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
    $varInfo = $this->getVariableInfo($varName, $currScope);
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

  protected function checkForFunctionPrototype(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a function or closure parameter?
    // It would be nice to get the list of function parameters from watching for
    // T_FUNCTION, but AbstractVariableSniff and AbstractScopeSniff define everything
    // we need to do that as private or final, so we have to do it this hackish way.
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr === false) {
      return false;
    }

    $functionPtr = Helpers::findPreviousFunctionPtr($phpcsFile, $openPtr);
    if (($functionPtr !== false)
      && (($tokens[$functionPtr]['code'] === T_FUNCTION)
      || ($tokens[$functionPtr]['code'] === T_CLOSURE))) {
      // TODO: typeHint
      $this->markVariableDeclaration($varName, 'param', null, $stackPtr, $functionPtr);
      // Are we pass-by-reference?
      $referencePtr = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true, null, true);
      if (($referencePtr !== false) && ($tokens[$referencePtr]['code'] === T_BITWISE_AND)) {
        $varInfo = $this->getOrCreateVariableInfo($varName, $functionPtr);
        $varInfo->passByReference = true;
      }
      //  Are we optional with a default?
      if (Helpers::isNextThingAnAssign($phpcsFile, $stackPtr) !== false) {
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

  protected function checkForCatchBlock(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a catch block parameter?
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr === false) {
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

    $doubleColonPtr = $stackPtr - 1;
    if ($tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
      return false;
    }
    $classNamePtr = $stackPtr - 2;
    $staticReferences = [
      T_STRING,
      T_SELF,
      T_STATIC,
    ];
    if (! in_array($tokens[$classNamePtr]['code'], $staticReferences, true)) {
      return false;
    }
    // "When calling static methods, the function call is stronger than the
    // static property operator" so look for a function call.
    $parenPointer = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr, $stackPtr + 2);
    if ($parenPointer) {
      return false;
    }
    return true;
  }

  protected function checkForStaticOutsideClass(File $phpcsFile, $stackPtr, $varName, $currScope) {
    // Are we refering to self:: outside a class?
    // TODO: not sure this is our business or should be some other sniff.

    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $doubleColonPtr = $stackPtr - 1;
    if ($tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
      return false;
    }
    $classNamePtr = $stackPtr - 2;
    $code = $tokens[$classNamePtr]['code'];
    $staticReferences = [
      T_SELF,
      T_STATIC,
    ];
    if (! in_array($code, $staticReferences, true)) {
      return false;
    }
    $errorClass = $code === T_SELF ? 'SelfOutsideClass' : 'StaticOutsideClass';
    $staticRefType = $code === T_SELF ? 'self::' : 'static::';
    if (!empty($token['conditions'])) {
      if (Helpers::areAnyConditionsAClosure($phpcsFile, $token['conditions'])) {
        $phpcsFile->addError("Use of {$staticRefType}%s inside closure.", $stackPtr, $errorClass, ["\${$varName}"]);
        return true;
      }
      if (Helpers::areAnyConditionsAClass($token['conditions'])) {
        return false;
      }
    }
    $phpcsFile->addError(
      "Use of {$staticRefType}%s outside class definition.",
      $stackPtr,
      $errorClass,
      ["\${$varName}"]
    );
    return true;
  }

  protected function checkForAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Is the next non-whitespace an assignment?
    $assignPtr = Helpers::isNextThingAnAssign($phpcsFile, $stackPtr);
    if ($assignPtr === false) {
      return false;
    }

    // Plain ol' assignment. Simpl(ish).
    $writtenPtr = Helpers::findWhereAssignExecuted($phpcsFile, $assignPtr);
    if ($writtenPtr === false) {
      $writtenPtr = $stackPtr;  // I dunno
    }
    $this->markVariableAssignment($varName, $writtenPtr, $currScope);
    return true;
  }

  protected function checkForListAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // OK, are we within a list (...) construct?
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr === false) {
      return false;
    }

    $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
    if (($prevPtr === false) || ($tokens[$prevPtr]['code'] !== T_LIST)) {
      return false;
    }

    // OK, we're a list (...) construct... are we being assigned to?
    $closePtr = $tokens[$openPtr]['parenthesis_closer'];
    $assignPtr = Helpers::isNextThingAnAssign($phpcsFile, $closePtr);
    if ($assignPtr === false) {
      return false;
    }

    // Yes, we're being assigned.
    $writtenPtr = Helpers::findWhereAssignExecuted($phpcsFile, $assignPtr);
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
    if (Helpers::isNextThingAnAssign($phpcsFile, $stackPtr) !== false) {
      $this->markVariableAssignment($varName, $stackPtr, $currScope);
    }
    return true;
  }

  protected function checkForForeachLoopVar(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we a foreach loopvar?
    $openParenPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openParenPtr === false) {
      return false;
    }
    $foreachPtr = Helpers::findParenthesisOwner($phpcsFile, $openParenPtr);
    if ($foreachPtr === false) {
      return false;
    }
    if ($tokens[$foreachPtr]['code'] === T_LIST) {
      $openParenPtr = Helpers::findContainingOpeningBracket($phpcsFile, $foreachPtr);
      if ($openParenPtr === false) {
        return false;
      }
      $foreachPtr = Helpers::findParenthesisOwner($phpcsFile, $openParenPtr);
      if ($foreachPtr === false) {
        return false;
      }
    }
    if ($tokens[$foreachPtr]['code'] !== T_FOREACH) {
      return false;
    }

    // Is there an 'as' token between us and the foreach?
    if ($phpcsFile->findPrevious(T_AS, $stackPtr - 1, $openParenPtr) === false) {
      return false;
    }

    $this->markVariableAssignment($varName, $stackPtr, $currScope);
    return true;
  }

  protected function checkForPassByReferenceFunctionCall(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we pass-by-reference to known pass-by-reference function?
    $functionPtr = Helpers::findFunctionCall($phpcsFile, $stackPtr);
    if ($functionPtr === false) {
      return false;
    }

    // Is our function a known pass-by-reference function?
    $functionName = $tokens[$functionPtr]['content'];
    $refArgs = $this->getPassByReferenceFunction($functionName);
    if (! $refArgs) {
      return false;
    }

    $argPtrs = Helpers::findFunctionCallArguments($phpcsFile, $stackPtr);
    if ($argPtrs === false) {
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
      $elipsis = array_search('...', $refArgs);
      if ($elipsis === false) {
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
   * Called to process normal member vars.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position where the token was found.
   */
  protected function processVariable(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $varName = Helpers::normalizeVarName($token['content']);
    $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
    if ($currScope === false) {
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

    // Check for static members used outside a class
    if ($this->checkForStaticOutsideClass($phpcsFile, $stackPtr, $varName, $currScope)) {
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

    $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
    foreach ($matches[1] as $varName) {
      $varName = Helpers::normalizeVarName($varName);
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
        $array_arguments = Helpers::findFunctionCallArguments($phpcsFile, $argumentPtrs[0]);
        if ($array_arguments !== false) {
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

    $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);

    $arguments = Helpers::findFunctionCallArguments($phpcsFile, $stackPtr);
    if ($arguments !== false) {
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
      $this->processScopeCloseForVariable($phpcsFile, $varInfo);
    }
  }

  protected function processScopeCloseForVariable($phpcsFile, $varInfo) {
    if ($varInfo->ignoreUnused || isset($varInfo->firstRead)) {
      return;
    }
    if ($this->allowUnusedFunctionParameters && $varInfo->scopeType === 'param') {
      return;
    }
    if ($varInfo->passByReference && isset($varInfo->firstInitialized)) {
      // If we're pass-by-reference then it's a common pattern to
      // use the variable to return data to the caller, so any
      // assignment also counts as "variable use" for the purposes
      // of "unused variable" warnings.
      return;
    }
    $stackPtr = Helpers::getStackPtrIfVariableIsUnused($varInfo);
    if ($stackPtr) {
      $phpcsFile->addWarning(
        "Unused %s %s.",
        $stackPtr,
        'UnusedVariable',
        [
          VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
          "\${$varInfo->name}",
        ]
      );
    }
  }
}
