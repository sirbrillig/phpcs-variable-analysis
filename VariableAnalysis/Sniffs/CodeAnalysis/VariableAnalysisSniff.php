<?php

namespace VariableAnalysis\Sniffs\CodeAnalysis;

use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\ScopeType;
use VariableAnalysis\Lib\VariableInfo;
use VariableAnalysis\Lib\Constants;
use VariableAnalysis\Lib\Helpers;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

class VariableAnalysisSniff implements Sniff {
  /**
   * The current phpcsFile being checked.
   *
   * @var File|null phpcsFile
   */
  protected $currentFile = null;

  /**
   * An associative array of scopes for variables encountered so far and the variables within them.
   *
   * Each scope is keyed by a string of the form `filename:scopeStartIndex` (see `getScopeKey`).
   *
   * @var ScopeInfo[]
   */
  private $scopes = [];

  /**
   * An associative array of a list of token index pairs which start and end scopes and will be used to check for unused variables.
   *
   * Each array of scopes is keyed by a string containing the filename (see `getFilename`).
   *
   * @var ScopeInfo[][]
   */
  private $scopeStartEndPairs = [];

  /**
   * A cache of scope end indices in the current file to improve performance.
   *
   * @var int[]
   */
  private $scopeEndIndexCache = [];

  /**
   * A list of custom functions which pass in variables to be initialized by
   * reference (eg `preg_match()`) and therefore should not require those
   * variables to be defined ahead of time. The list is space separated and
   * each entry is of the form `functionName:1,2`. The function name comes
   * first followed by a colon and a comma-separated list of argument numbers
   * (starting from 1) which should be considered variable definitions. The
   * special value `...` in the arguments list will cause all arguments after
   * the last number to be considered variable definitions.
   *
   * @var string|null
   */
  public $sitePassByRefFunctions = null;

  /**
   * If set, allows common WordPress pass-by-reference functions in addition to
   * the standard PHP ones.
   *
   * @var bool
   */
  public $allowWordPressPassByRefFunctions = false;

  /**
   *  Allow exceptions in a catch block to be unused without warning.
   *
   *  @var bool
   */
  public $allowUnusedCaughtExceptions = true;

  /**
   *  Allow function parameters to be unused without provoking unused-var warning.
   *
   *  @var bool
   */
  public $allowUnusedFunctionParameters = false;

  /**
   *  If set, ignores undefined variables in the file scope (the top-level
   *  scope of a file).
   *
   *  @var bool
   */
  public $allowUndefinedVariablesInFileScope = false;

  /**
   *  If set, ignores unused variables in the file scope (the top-level
   *  scope of a file).
   *
   *  @var bool
   */
  public $allowUnusedVariablesInFileScope = false;

  /**
   *  A space-separated list of names of placeholder variables that you want to
   *  ignore from unused variable warnings. For example, to ignore the variables
   *  `$junk` and `$unused`, this could be set to `'junk unused'`.
   *
   *  @var string|null
   */
  public $validUnusedVariableNames = null;

  /**
   *  A PHP regexp string for variables that you want to ignore from unused
   *  variable warnings. For example, to ignore the variables `$_junk` and
   *  `$_unused`, this could be set to `'/^_/'`.
   *
   *  @var string|null
   */
  public $ignoreUnusedRegexp = null;

  /**
   *  A space-separated list of names of placeholder variables that you want to
   *  ignore from undefined variable warnings. For example, to ignore the variables
   *  `$post` and `$undefined`, this could be set to `'post undefined'`.
   *
   *  @var string|null
   */
  public $validUndefinedVariableNames = null;

  /**
   *  A PHP regexp string for variables that you want to ignore from undefined
   *  variable warnings. For example, to ignore the variables `$_junk` and
   *  `$_unused`, this could be set to `'/^_/'`.
   *
   *  @var string|null
   */
  public $validUndefinedVariableRegexp = null;

  /**
   * Allows unused arguments in a function definition if they are
   * followed by an argument which is used.
   *
   *  @var bool
   */
  public $allowUnusedParametersBeforeUsed = true;

  /**
   * If set to true, unused values from the `key => value` syntax
   * in a `foreach` loop will never be marked as unused.
   *
   *  @var bool
   */
  public $allowUnusedForeachVariables = true;

  /**
   * If set to true, unused variables in a function before a require or import
   * statement will not be marked as unused because they may be used in the
   * required file.
   *
   *  @var bool
   */
  public $allowUnusedVariablesBeforeRequire = false;

  /**
   * @return (int|string)[]
   */
  public function register() {
    $types = [
      T_VARIABLE,
      T_DOUBLE_QUOTED_STRING,
      T_HEREDOC,
      T_CLOSE_CURLY_BRACKET,
      T_FUNCTION,
      T_CLOSURE,
      T_STRING,
      T_COMMA,
      T_SEMICOLON,
      T_CLOSE_PARENTHESIS,
    ];
    if (defined('T_FN')) {
      $types[] = T_FN;
    }
    return $types;
  }

  /**
   * @param string $functionName
   *
   * @return string[]
   */
  private function getPassByReferenceFunction($functionName) {
    $passByRefFunctions = Constants::getPassByReferenceFunctions();
    if (!empty($this->sitePassByRefFunctions)) {
      $lines = Helpers::splitStringToArray('/\s+/', trim($this->sitePassByRefFunctions));
      foreach ($lines as $line) {
        list ($function, $args) = explode(':', $line);
        $passByRefFunctions[$function] = explode(',', $args);
      }
    }
    if ($this->allowWordPressPassByRefFunctions) {
      $passByRefFunctions = array_merge($passByRefFunctions, Constants::getWordPressPassByReferenceFunctions());
    }
    return isset($passByRefFunctions[$functionName]) ? $passByRefFunctions[$functionName] : [];
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return void
   */
  public function process(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    $scopeStartTokenTypes = [
      T_FUNCTION,
      T_CLOSURE,
    ];

    $token = $tokens[$stackPtr];

    if ($this->currentFile !== $phpcsFile) {
      $this->currentFile = $phpcsFile;
      $this->scopeEndIndexCache = [];
    }

    // Add the global scope
    if (empty($this->scopeStartEndPairs[$this->getFilename()])) {
      $this->recordScopeStartAndEnd($phpcsFile, 0);
    }

    $this->searchForAndProcessClosingScopesAt($phpcsFile, $stackPtr);

    if ($token['code'] === T_VARIABLE) {
      $this->processVariable($phpcsFile, $stackPtr);
      return;
    }
    if (($token['code'] === T_DOUBLE_QUOTED_STRING) || ($token['code'] === T_HEREDOC)) {
      $this->processVariableInString($phpcsFile, $stackPtr);
      return;
    }
    if (($token['code'] === T_STRING) && ($token['content'] === 'compact')) {
      $this->processCompact($phpcsFile, $stackPtr);
      return;
    }
    if ($this->isGetDefinedVars($phpcsFile, $stackPtr)) {
      Helpers::debug('get_defined_vars is being called');
      $this->markAllVariablesRead($phpcsFile, $stackPtr);
      return;
    }
    if (in_array($token['code'], $scopeStartTokenTypes, true)
      || Helpers::isArrowFunction($phpcsFile, $stackPtr)
    ) {
      Helpers::debug('found scope condition', $token);
      $this->recordScopeStartAndEnd($phpcsFile, $stackPtr);
      return;
    }
  }

  /**
   * @param File $phpcsFile
   * @param int $scopeStartIndex
   *
   * @return void
   */
  private function recordScopeStartAndEnd($phpcsFile, $scopeStartIndex) {
    $scopeEndIndex = Helpers::getScopeCloseForScopeOpen($phpcsFile, $scopeStartIndex);
    $filename = $this->getFilename();
    if (! isset($this->scopeStartEndPairs[$filename])) {
      $this->scopeStartEndPairs[$filename] = [];
    }
    Helpers::debug('recording scope for file', $filename, 'start/end', $scopeStartIndex, $scopeEndIndex);
    $this->scopeStartEndPairs[$filename][] = new ScopeInfo($scopeStartIndex, $scopeEndIndex);
    $this->scopeEndIndexCache[] = $scopeEndIndex;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return void
   */
  private function searchForAndProcessClosingScopesAt($phpcsFile, $stackPtr) {
    if (! in_array($stackPtr, $this->scopeEndIndexCache, true)) {
      return;
    }
    $scopePairsForFile = isset($this->scopeStartEndPairs[$this->getFilename()]) ? $this->scopeStartEndPairs[$this->getFilename()] : [];
    $scopeIndicesThisCloses = array_reduce($scopePairsForFile, function ($found, $scope) use ($stackPtr) {
      if (! is_int($scope->scopeEndIndex)) {
        Helpers::debug('No scope closer found for scope start', $scope->scopeStartIndex);
        return $found;
      }

      if ($stackPtr === $scope->scopeEndIndex) {
        $found[] = $scope;
      }
      return $found;
    }, []);

    foreach ($scopeIndicesThisCloses as $scopeIndexThisCloses) {
      Helpers::debug('found closing scope at index', $stackPtr, 'for scopes starting at:', $scopeIndexThisCloses);
      $this->processScopeClose($phpcsFile, $scopeIndexThisCloses->scopeStartIndex);
    }
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  protected function isGetDefinedVars(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token = $tokens[$stackPtr];
    if (! $token || $token['content'] !== 'get_defined_vars') {
      return false;
    }
    // Make sure this is a function call
    $parenPointer = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
    if (! $parenPointer || $tokens[$parenPointer]['code'] !== T_OPEN_PARENTHESIS) {
      return false;
    }
    return true;
  }

  /**
   * @param int $currScope
   *
   * @return string
   */
  protected function getScopeKey($currScope) {
    return $this->getFilename() . ':' . $currScope;
  }

  /**
   * @return string
   */
  protected function getFilename() {
    return $this->currentFile ? $this->currentFile->getFilename() : 'unknown file';
  }

  /**
   * @param int $currScope
   *
   * @return ScopeInfo|null
   */
  protected function getScopeInfo($currScope) {
    $scopeKey = $this->getScopeKey($currScope);
    return isset($this->scopes[$scopeKey]) ? $this->scopes[$scopeKey] : null;
  }

  /**
   * @param int $currScope
   *
   * @return ScopeInfo
   */
  protected function getOrCreateScopeInfo($currScope) {
    $scopeKey = $this->getScopeKey($currScope);
    if (!isset($this->scopes[$scopeKey])) {
      $this->scopes[$scopeKey] = new ScopeInfo($currScope);
    }
    return $this->scopes[$scopeKey];
  }

  /**
   * @param string $varName
   * @param int $currScope
   *
   * @return VariableInfo|null
   */
  protected function getVariableInfo($varName, $currScope) {
    $scopeInfo = $this->getScopeInfo($currScope);
    return ( $scopeInfo && isset($scopeInfo->variables[$varName]) ) ? $scopeInfo->variables[$varName] : null;
  }

  /**
   * @param string $varName
   * @param int $currScope
   *
   * @return VariableInfo
   */
  protected function getOrCreateVariableInfo($varName, $currScope) {
    Helpers::debug("getOrCreateVariableInfo: starting for '{$varName}'");
    $scopeInfo = $this->getOrCreateScopeInfo($currScope);
    if (isset($scopeInfo->variables[$varName])) {
      Helpers::debug("getOrCreateVariableInfo: found scope for '{$varName}'", $scopeInfo);
      return $scopeInfo->variables[$varName];
    }
    Helpers::debug("getOrCreateVariableInfo: creating a new variable for '{$varName}' in scope", $scopeInfo);
    $scopeInfo->variables[$varName] = new VariableInfo($varName);
    $validUnusedVariableNames = (empty($this->validUnusedVariableNames))
      ? []
      : Helpers::splitStringToArray('/\s+/', trim($this->validUnusedVariableNames));
    $validUndefinedVariableNames = (empty($this->validUndefinedVariableNames))
      ? []
      : Helpers::splitStringToArray('/\s+/', trim($this->validUndefinedVariableNames));
    if (in_array($varName, $validUnusedVariableNames)) {
      $scopeInfo->variables[$varName]->ignoreUnused = true;
    }
    if (isset($this->ignoreUnusedRegexp) && preg_match($this->ignoreUnusedRegexp, $varName) === 1) {
      $scopeInfo->variables[$varName]->ignoreUnused = true;
    }
    if ($scopeInfo->scopeStartIndex === 0 && $this->allowUndefinedVariablesInFileScope) {
      $scopeInfo->variables[$varName]->ignoreUndefined = true;
    }
    if (in_array($varName, $validUndefinedVariableNames)) {
      $scopeInfo->variables[$varName]->ignoreUndefined = true;
    }
    if (isset($this->validUndefinedVariableRegexp) && preg_match($this->validUndefinedVariableRegexp, $varName) === 1) {
      $scopeInfo->variables[$varName]->ignoreUndefined = true;
    }
    Helpers::debug("getOrCreateVariableInfo: scope for '{$varName}' is now", $scopeInfo);
    return $scopeInfo->variables[$varName];
  }

  /**
   * @param string $varName
   * @param int $stackPtr
   * @param int $currScope
   *
   * @return void
   */
  protected function markVariableAssignment($varName, $stackPtr, $currScope) {
    Helpers::debug('markVariableAssignment: starting for', $varName);
    $this->markVariableAssignmentWithoutInitialization($varName, $stackPtr, $currScope);
    Helpers::debug('markVariableAssignment: marked as assigned without initialization', $varName);
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if (isset($varInfo->firstInitialized) && ($varInfo->firstInitialized <= $stackPtr)) {
      Helpers::debug('markVariableAssignment: variable is already initialized', $varName);
      return;
    }
    $varInfo->firstInitialized = $stackPtr;
    Helpers::debug('markVariableAssignment: marked as initialized', $varName);
  }

  /**
   * @param string $varName
   * @param int $stackPtr
   * @param int $currScope
   *
   * @return void
   */
  protected function markVariableAssignmentWithoutInitialization($varName, $stackPtr, $currScope) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);

    // Is the variable referencing another variable? If so, mark that variable used also.
    if ($varInfo->referencedVariableScope !== null && $varInfo->referencedVariableScope !== $currScope) {
      // Don't do this if the referenced variable does not exist; eg: if it's going to be bound at runtime like in array_walk
      if ($this->getVariableInfo($varInfo->name, $varInfo->referencedVariableScope)) {
        Helpers::debug('markVariableAssignmentWithoutInitialization: marking referenced variable as assigned also', $varName);
        $this->markVariableAssignment($varInfo->name, $stackPtr, $varInfo->referencedVariableScope);
      }
    }

    if (!isset($varInfo->scopeType)) {
      $varInfo->scopeType = ScopeType::LOCAL;
    }
    $varInfo->allAssignments[] = $stackPtr;
  }

  /**
   * @param string $varName
   * @param string $scopeType
   * @param ?string $typeHint
   * @param int $stackPtr
   * @param int $currScope
   * @param ?bool $permitMatchingRedeclaration
   *
   * @return void
   */
  protected function markVariableDeclaration(
    $varName,
    $scopeType,
    $typeHint,
    $stackPtr,
    $currScope,
    $permitMatchingRedeclaration = false
  ) {
    Helpers::debug("marking variable '{$varName}' declared in scope starting at token", $currScope);
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);

    if (isset($varInfo->scopeType)) {
      if (($permitMatchingRedeclaration === false) || ($varInfo->scopeType !== $scopeType)) {
        //  Issue redeclaration/reuse warning
        //  Note: we check off scopeType not firstDeclared, this is so that
        //    we catch declarations that come after implicit declarations like
        //    use of a variable as a local.
        $this->addWarning(
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
      Helpers::debug("variable '{$varName}' was already marked declared", $varInfo);
      return;
    }
    $varInfo->firstDeclared = $stackPtr;
    $varInfo->allAssignments[] = $stackPtr;
    Helpers::debug("variable '{$varName}' marked declared", $varInfo);
  }

  /**
   * @param string $message
   * @param int $stackPtr
   * @param string $code
   * @param string[] $data
   *
   * @return void
   */
  protected function addWarning($message, $stackPtr, $code, $data) {
    if (! $this->currentFile) {
      throw new \Exception('Cannot add warning; current file is not set.');
    }
    $this->currentFile->addWarning(
      $message,
      $stackPtr,
      $code,
      $data
    );
  }

  /**
   * @param string $varName
   * @param int $stackPtr
   * @param int $currScope
   *
   * @return void
   */
  protected function markVariableRead($varName, $stackPtr, $currScope) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if (isset($varInfo->firstRead) && ($varInfo->firstRead <= $stackPtr)) {
      return;
    }
    $varInfo->firstRead = $stackPtr;
  }

  /**
   * @param string $varName
   * @param int $stackPtr
   * @param int $currScope
   *
   * @return bool
   */
  protected function isVariableUndefined($varName, $stackPtr, $currScope) {
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    Helpers::debug('isVariableUndefined', $varInfo);
    if ($varInfo->ignoreUndefined) {
      return false;
    }
    if (isset($varInfo->firstDeclared) && $varInfo->firstDeclared <= $stackPtr) {
      return false;
    }
    if (isset($varInfo->firstInitialized) && $varInfo->firstInitialized <= $stackPtr) {
      return false;
    }
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param string $varName
   * @param int $stackPtr
   * @param int $currScope
   *
   * @return void
   */
  protected function markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope) {
    $this->markVariableRead($varName, $stackPtr, $currScope);
    if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
      Helpers::debug("variable $varName looks undefined");

      if (Helpers::isVariableArrayPushShortcut($phpcsFile, $stackPtr)) {
        $this->warnAboutUndefinedArrayPushShortcut($phpcsFile, $varName, $stackPtr);
        // Mark the variable as defined if it's of the form `$x[] = 1;`
        $this->markVariableAssignment($varName, $stackPtr, $currScope);
        return;
      }

      if (Helpers::isVariableInsideUnset($phpcsFile, $stackPtr)) {
        $this->warnAboutUndefinedUnset($phpcsFile, $varName, $stackPtr);
        return;
      }

      $this->warnAboutUndefinedVariable($phpcsFile, $varName, $stackPtr);
    }
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return void
   */
  protected function markAllVariablesRead(File $phpcsFile, $stackPtr) {
    $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
    if ($currScope === null) {
      return;
    }
    $scopeInfo = $this->getOrCreateScopeInfo($currScope);
    $count = count($scopeInfo->variables);
    Helpers::debug("marking all $count variables in scope as read");
    foreach ($scopeInfo->variables as $varInfo) {
      $this->markVariableRead($varInfo->name, $stackPtr, $scopeInfo->scopeStartIndex);
    }
  }

  /**
   * Process a variable if it is inside a function definition
   *
   * This does not include variables imported by a "use" statement.
   *
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   *
   * @return void
   */
  protected function processVariableAsFunctionDefinitionArgument(File $phpcsFile, $stackPtr, $varName, $outerScope) {
    Helpers::debug("processVariableAsFunctionDefinitionArgument", $stackPtr, $varName);
    $tokens = $phpcsFile->getTokens();

    $functionPtr = Helpers::getFunctionIndexForFunctionArgument($phpcsFile, $stackPtr);
    if (! is_int($functionPtr)) {
      throw new \Exception("Function index not found for function argument index {$stackPtr}");
    }

    Helpers::debug("processVariableAsFunctionDefinitionArgument found function definition", $tokens[$functionPtr]);
    $this->markVariableDeclaration($varName, ScopeType::PARAM, null, $stackPtr, $functionPtr);

    // Are we pass-by-reference?
    $referencePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
    if (($referencePtr !== false) && ($tokens[$referencePtr]['code'] === T_BITWISE_AND)) {
      Helpers::debug("processVariableAsFunctionDefinitionArgument found pass-by-reference to scope", $outerScope);
      $varInfo = $this->getOrCreateVariableInfo($varName, $functionPtr);
      $varInfo->referencedVariableScope = $outerScope;
    }

    //  Are we optional with a default?
    if (Helpers::getNextAssignPointer($phpcsFile, $stackPtr) !== null) {
      Helpers::debug("processVariableAsFunctionDefinitionArgument optional with default");
      $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
    }
  }

  /**
   * Process a variable if it is inside a function's "use" import
   *
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $outerScope The start of the scope outside the function definition
   *
   * @return void
   */
  protected function processVariableAsUseImportDefinition(File $phpcsFile, $stackPtr, $varName, $outerScope) {
    $tokens = $phpcsFile->getTokens();

    Helpers::debug("processVariableAsUseImportDefinition", $stackPtr, $varName, $outerScope);

    $endOfArgsPtr = $phpcsFile->findPrevious([T_CLOSE_PARENTHESIS], $stackPtr - 1, null);
    if (! is_int($endOfArgsPtr)) {
      throw new \Exception("Arguments index not found for function use index {$stackPtr} when processing variable {$varName}");
    }
    $functionPtr = Helpers::getFunctionIndexForFunctionArgument($phpcsFile, $endOfArgsPtr);
    if (! is_int($functionPtr)) {
      throw new \Exception("Function index not found for function use index {$stackPtr} (using {$endOfArgsPtr}) when processing variable {$varName}");
    }

    // Use is both a read (in the enclosing scope) and a define (in the function scope)
    $this->markVariableRead($varName, $stackPtr, $outerScope);

    // If it's undefined in the enclosing scope, the use is wrong
    if ($this->isVariableUndefined($varName, $stackPtr, $outerScope) === true) {
      Helpers::debug("variable '{$varName}' in function definition looks undefined in scope", $outerScope);
      $this->warnAboutUndefinedVariable($phpcsFile, $varName, $stackPtr);
      return;
    }

    $this->markVariableDeclaration($varName, ScopeType::BOUND, null, $stackPtr, $functionPtr);
    $this->markVariableAssignment($varName, $stackPtr, $functionPtr);

    // Are we pass-by-reference? If so, then any assignment to the variable in
    // the function scope also should count for the enclosing scope.
    $referencePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
    if (is_int($referencePtr) && $tokens[$referencePtr]['code'] === T_BITWISE_AND) {
      Helpers::debug("variable '{$varName}' in function definition looks passed by reference");
      $varInfo = $this->getOrCreateVariableInfo($varName, $functionPtr);
      $varInfo->referencedVariableScope = $outerScope;
    }
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  protected function processVariableAsClassProperty(File $phpcsFile, $stackPtr) {
    $propertyDeclarationKeywords = [
      T_PUBLIC,
      T_PRIVATE,
      T_PROTECTED,
      T_VAR,
    ];
    $stopAtPtr = $stackPtr - 2;
    $visibilityPtr = $phpcsFile->findPrevious($propertyDeclarationKeywords, $stackPtr - 1, $stopAtPtr > 0 ? $stopAtPtr : 0);
    if ($visibilityPtr) {
      return true;
    }
    $staticPtr = $phpcsFile->findPrevious(T_STATIC, $stackPtr - 1, $stopAtPtr > 0 ? $stopAtPtr : 0);
    if (! $staticPtr) {
      return false;
    }
    $stopAtPtr = $staticPtr - 2;
    $visibilityPtr = $phpcsFile->findPrevious($propertyDeclarationKeywords, $staticPtr - 1, $stopAtPtr > 0 ? $stopAtPtr : 0);
    if ($visibilityPtr) {
      return true;
    }
    // it's legal to use `static` to define properties as well as to
    // define variables, so make sure we are not in a function before
    // assuming it's a property.
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];
    if ($token && !empty($token['conditions']) && !Helpers::areConditionsWithinFunctionBeforeClass($token['conditions'])) {
      return Helpers::areAnyConditionsAClass($token['conditions']);
    }
    return false;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsCatchBlock(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

    // Are we a catch block parameter?
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr === null) {
      return false;
    }

    $catchPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
    if (($catchPtr !== false) && ($tokens[$catchPtr]['code'] === T_CATCH)) {
      // Scope of the exception var is actually the function, not just the catch block.
      $this->markVariableDeclaration($varName, ScopeType::LOCAL, null, $stackPtr, $currScope, true);
      $this->markVariableAssignment($varName, $stackPtr, $currScope);
      if ($this->allowUnusedCaughtExceptions) {
        $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
        $varInfo->ignoreUnused = true;
      }
      return true;
    }
    return false;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   *
   * @return bool
   */
  protected function processVariableAsThisWithinClass(File $phpcsFile, $stackPtr, $varName) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Are we $this within a class?
    if (($varName !== 'this') || empty($token['conditions'])) {
      return false;
    }

    $inFunction = false;
    foreach (array_reverse($token['conditions'], true) as $scopeCode) {
      //  $this within a closure is valid
      if ($scopeCode === T_CLOSURE && $inFunction === false) {
        return true;
      }
      if ($scopeCode === T_CLASS || $scopeCode === T_ANON_CLASS || $scopeCode === T_TRAIT) {
        return true;
      }

      // Handle nested function declarations.
      if ($scopeCode === T_FUNCTION) {
        if ($inFunction === true) {
            break;
        }

        $inFunction = true;
      }
    }

    return false;
  }

  /**
   * @param string $varName
   *
   * @return bool
   */
  protected function processVariableAsSuperGlobal($varName) {
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
      'php_errormsg',
      'http_response_header',
      'HTTP_RAW_POST_DATA',
    ])) {
      return true;
    }

    return false;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   *
   * @return bool
   */
  protected function processVariableAsStaticMember(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    $doubleColonPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
    if ($doubleColonPtr === false || $tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
      return false;
    }
    $classNamePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $doubleColonPtr - 1, null, true);
    $staticReferences = [
      T_STRING,
      T_SELF,
      T_PARENT,
      T_STATIC,
      T_VARIABLE,
    ];
    if ($classNamePtr === false || ! in_array($tokens[$classNamePtr]['code'], $staticReferences, true)) {
      return false;
    }
    // "When calling static methods, the function call is stronger than the
    // static property operator" so look for a function call.
    $parenPointer = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
    if ($parenPointer !== false && $tokens[$parenPointer]['code'] === T_OPEN_PARENTHESIS) {
      return false;
    }
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   *
   * @return bool
   */
  protected function processVariableAsStaticOutsideClass(File $phpcsFile, $stackPtr, $varName) {
    // Are we refering to self:: outside a class?

    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $doubleColonPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
    if ($doubleColonPtr === false || $tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
      return false;
    }
    $classNamePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $doubleColonPtr - 1, null, true);
    if ($classNamePtr === false) {
      return false;
    }
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
    if (!empty($token['conditions']) && Helpers::areAnyConditionsAClass($token['conditions'])) {
      return false;
    }
    $phpcsFile->addError(
      "Use of {$staticRefType}%s outside class definition.",
      $stackPtr,
      $errorClass,
      ["\${$varName}"]
    );
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return void
   */
  protected function processVariableAsAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    Helpers::debug("processVariableAsAssignment: starting for '${varName}'");
    $assignPtr = Helpers::getNextAssignPointer($phpcsFile, $stackPtr);
    if (! is_int($assignPtr)) {
      return;
    }

    // If the right-hand-side of the assignment to this variable is a reference
    // variable, then this variable is a reference to that one, and as such any
    // assignment to this variable (except another assignment by reference,
    // which would change the binding) has a side effect of changing the
    // referenced variable and therefore should count as both an assignment and
    // a read.
    $tokens = $phpcsFile->getTokens();
    $referencePtr = $phpcsFile->findNext(Tokens::$emptyTokens, $assignPtr + 1, null, true, null, true);
    if (is_int($referencePtr) && $tokens[$referencePtr]['code'] === T_BITWISE_AND) {
      Helpers::debug('processVariableAsAssignment: found reference variable');
      $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
      // If the variable was already declared, but was not yet read, it is
      // unused because we're about to change the binding.
      $scopeInfo = $this->getOrCreateScopeInfo($currScope);
      $this->processScopeCloseForVariable($phpcsFile, $varInfo, $scopeInfo);
      // The referenced variable may have a different name, but we don't
      // actually need to mark it as used in this case because the act of this
      // assignment will mark it used on the next token.
      $varInfo->referencedVariableScope = $currScope;
      $this->markVariableDeclaration($varName, ScopeType::LOCAL, null, $stackPtr, $currScope, true);
      // An assignment to a reference is a binding and should not count as
      // initialization since it doesn't change any values.
      $this->markVariableAssignmentWithoutInitialization($varName, $stackPtr, $currScope);
      return;
    }

    Helpers::debug('processVariableAsAssignment: marking as assignment in scope', $currScope);
    $this->markVariableAssignment($varName, $stackPtr, $currScope);

    // If the left-hand-side of the assignment (the variable we are examining)
    // is itself a reference, then that counts as a read as well as a write.
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    if ($varInfo->isDynamicReference) {
      Helpers::debug('processVariableAsAssignment: also marking as a use because variable is a reference');
      $this->markVariableRead($varName, $stackPtr, $currScope);
    }
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsListShorthandAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    // OK, are we within a [ ... ] construct?
    $openPtr = Helpers::findContainingOpeningSquareBracket($phpcsFile, $stackPtr);
    if (! is_int($openPtr)) {
      return false;
    }

    // OK, we're a [ ... ] construct... are we being assigned to?
    $assignments = Helpers::getListAssignments($phpcsFile, $openPtr);
    if (! $assignments) {
      return false;
    }
    $matchingAssignment = array_reduce($assignments, function ($thisAssignment, $assignment) use ($stackPtr) {
      if ($assignment === $stackPtr) {
        return $assignment;
      }
      return $thisAssignment;
    });
    if (! $matchingAssignment) {
      return false;
    }

    // Yes, we're being assigned.
    $this->markVariableAssignment($varName, $stackPtr, $currScope);
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsListAssignment(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

    // OK, are we within a list (...) construct?
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr === null) {
      return false;
    }

    $prevPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
    if ((is_bool($prevPtr)) || ($tokens[$prevPtr]['code'] !== T_LIST)) {
      return false;
    }

    // OK, we're a list (...) construct... are we being assigned to?
    $assignments = Helpers::getListAssignments($phpcsFile, $prevPtr);
    if (! $assignments) {
      return false;
    }
    $matchingAssignment = array_reduce($assignments, function ($thisAssignment, $assignment) use ($stackPtr) {
      if ($assignment === $stackPtr) {
        return $assignment;
      }
      return $thisAssignment;
    });
    if (! $matchingAssignment) {
      return false;
    }

    // Yes, we're being assigned.
    $this->markVariableAssignment($varName, $stackPtr, $currScope);
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsGlobalDeclaration(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

    // Are we a global declaration?
    // Search backwards for first token that isn't whitespace/comment, comma or variable.
    $ignore             = Tokens::$emptyTokens;
    $ignore[T_VARIABLE] = T_VARIABLE;
    $ignore[T_COMMA]    = T_COMMA;

    $globalPtr = $phpcsFile->findPrevious($ignore, $stackPtr - 1, null, true, null, true);
    if (($globalPtr === false) || ($tokens[$globalPtr]['code'] !== T_GLOBAL)) {
      return false;
    }

    // It's a global declaration.
    $this->markVariableDeclaration($varName, ScopeType::GLOBALSCOPE, null, $stackPtr, $currScope);
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsStaticDeclaration(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

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
    $this->markVariableDeclaration($varName, ScopeType::STATICSCOPE, null, $stackPtr, $currScope);
    if (Helpers::getNextAssignPointer($phpcsFile, $stackPtr) !== null) {
      $this->markVariableAssignment($varName, $stackPtr, $currScope);
    }
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsForeachLoopVar(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

    // Are we a foreach loopvar?
    $openParenPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if (! is_int($openParenPtr)) {
      return false;
    }
    $foreachPtr = Helpers::getIntOrNull($phpcsFile->findPrevious(Tokens::$emptyTokens, $openParenPtr - 1, null, true));
    if (! is_int($foreachPtr)) {
      return false;
    }
    if ($tokens[$foreachPtr]['code'] === T_LIST) {
      $openParenPtr = Helpers::findContainingOpeningBracket($phpcsFile, $foreachPtr);
      if (! is_int($openParenPtr)) {
        return false;
      }
      $foreachPtr = Helpers::getIntOrNull($phpcsFile->findPrevious(Tokens::$emptyTokens, $openParenPtr - 1, null, true));
      if (! is_int($foreachPtr)) {
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
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);

    // Is this the value of a key => value foreach?
    if ($phpcsFile->findPrevious(T_DOUBLE_ARROW, $stackPtr - 1, $openParenPtr) !== false) {
      $varInfo->isForeachLoopAssociativeValue = true;
    }

    // Are we pass-by-reference?
    $referencePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
    if (($referencePtr !== false) && ($tokens[$referencePtr]['code'] === T_BITWISE_AND)) {
      Helpers::debug("processVariableAsForeachLoopVar: found foreach loop variable assigned by reference");
      $varInfo->isDynamicReference = true;
    }

    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsPassByReferenceFunctionCall(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

    // Are we pass-by-reference to known pass-by-reference function?
    $functionPtr = Helpers::findFunctionCall($phpcsFile, $stackPtr);
    if ($functionPtr === null || ! isset($tokens[$functionPtr])) {
      return false;
    }

    // Is our function a known pass-by-reference function?
    $functionName = $tokens[$functionPtr]['content'];
    $refArgs = $this->getPassByReferenceFunction($functionName);
    if (! $refArgs) {
      return false;
    }

    $argPtrs = Helpers::findFunctionCallArguments($phpcsFile, $stackPtr);

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
      $elipsis = (int)$elipsis;
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
      if (isset(Tokens::$emptyTokens[$tokens[$ptr]['code']]) === false) {
        return false;
      }
    }

    // Just us, we can mark it as a write.
    $this->markVariableAssignment($varName, $stackPtr, $currScope);
    // It's a read as well for purposes of used-variables.
    $this->markVariableRead($varName, $stackPtr, $currScope);
    return true;
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return bool
   */
  protected function processVariableAsSymbolicObjectProperty(File $phpcsFile, $stackPtr, $varName, $currScope) {
    $tokens = $phpcsFile->getTokens();

    // Are we a symbolic object property/function derefeference?
    // Search backwards for first token that isn't whitespace, is it a "->" operator?
    $objectOperatorPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true, null, true);
    if (($objectOperatorPtr === false) || ($tokens[$objectOperatorPtr]['code'] !== T_OBJECT_OPERATOR)) {
      return false;
    }

    $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
    return true;
  }

  /**
   * Process a normal variable in the code
   *
   * Most importantly, this function determines if the variable use is a "read"
   * (using the variable for something) or a "write" (an assignment) or,
   * sometimes, both at once.
   *
   * It also determines the scope of the variable (where it begins and ends).
   *
   * Using these two pieces of information, we can determine if the variable is
   * being used ("read") without having been defined ("write").
   *
   * We can also determine, once the scan has hit the end of a scope, if any of
   * the variables within that scope have been defined ("write") without being
   * used ("read"). That behavior, however, happens in the `processScopeClose`
   * function using the data gathered by this function.
   *
   * Some variables are used in more complex ways, so there are other similar
   * functions to this one, like `processVariableInString`, and
   * `processCompact`. They have the same purpose as this function, though.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position where the token was found.
   *
   * @return void
   */
  protected function processVariable(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $varName = Helpers::normalizeVarName($token['content']);
    Helpers::debug("examining token for variable '{$varName}' on line {$token['line']}", $token);
    $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
    if ($currScope === null) {
      Helpers::debug('no scope found');
      return;
    }
    Helpers::debug("start of scope for variable '{$varName}' is", $currScope);

    // Determine if variable is being assigned ("write") or used ("read").

    // Read methods that preempt assignment:
    //   Are we a $object->$property type symbolic reference?

    // Possible assignment methods:
    //   Is a mandatory function/closure parameter
    //   Is an optional function/closure parameter with non-null value
    //   Is closure use declaration of a variable defined within containing scope
    //   catch (...) block start
    //   $this within a class.
    //   $GLOBALS, $_REQUEST, etc superglobals.
    //   $var part of class::$var static member
    //   Assignment via =
    //   Assignment via list (...) =
    //   Declares as a global
    //   Declares as a static
    //   Assignment via foreach (... as ...) { }
    //   Pass-by-reference to known pass-by-reference function

    // Are we a $object->$property type symbolic reference?
    if ($this->processVariableAsSymbolicObjectProperty($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found symbolic object property');
      return;
    }

    // Are we a function or closure parameter?
    if (Helpers::isTokenInsideFunctionDefinitionArgumentList($phpcsFile, $stackPtr)) {
      Helpers::debug('found function definition argument');
      $this->processVariableAsFunctionDefinitionArgument($phpcsFile, $stackPtr, $varName, $currScope);
      return;
    }

    // Are we a variable being imported into a function's scope with "use"?
    if (Helpers::isTokenInsideFunctionUseImport($phpcsFile, $stackPtr)) {
      Helpers::debug('found use scope import definition');
      $this->processVariableAsUseImportDefinition($phpcsFile, $stackPtr, $varName, $currScope);
      return;
    }

    // Are we a catch parameter?
    if ($this->processVariableAsCatchBlock($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found catch block');
      return;
    }

    // Are we $this within a class?
    if ($this->processVariableAsThisWithinClass($phpcsFile, $stackPtr, $varName)) {
      Helpers::debug('found this usage within a class');
      return;
    }

    // Are we a $GLOBALS, $_REQUEST, etc superglobal?
    if ($this->processVariableAsSuperGlobal($varName)) {
      Helpers::debug('found superglobal');
      return;
    }

    // Check for static members used outside a class
    if ($this->processVariableAsStaticOutsideClass($phpcsFile, $stackPtr, $varName)) {
      Helpers::debug('found static usage outside of class');
      return;
    }

    // $var part of class::$var static member
    if ($this->processVariableAsStaticMember($phpcsFile, $stackPtr)) {
      Helpers::debug('found static member');
      return;
    }

    if ($this->processVariableAsClassProperty($phpcsFile, $stackPtr)) {
      Helpers::debug('found class property definition');
      return;
    }

    // Is the next non-whitespace an assignment?
    if (Helpers::isTokenInsideAssignmentLHS($phpcsFile, $stackPtr)) {
      Helpers::debug('found assignment');
      $this->processVariableAsAssignment($phpcsFile, $stackPtr, $varName, $currScope);
      if (Helpers::isTokenInsideAssignmentRHS($phpcsFile, $stackPtr) || Helpers::isTokenInsideFunctionCall($phpcsFile, $stackPtr)) {
        Helpers::debug("found assignment that's also inside an expression");
        $this->markVariableRead($varName, $stackPtr, $currScope);
        return;
      }
      return;
    }

    // OK, are we within a list (...) = construct?
    if ($this->processVariableAsListAssignment($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found list assignment');
      return;
    }

    // OK, are we within a [...] = construct?
    if ($this->processVariableAsListShorthandAssignment($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found list shorthand assignment');
      return;
    }

    // Are we a global declaration?
    if ($this->processVariableAsGlobalDeclaration($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found global declaration');
      return;
    }

    // Are we a static declaration?
    if ($this->processVariableAsStaticDeclaration($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found static declaration');
      return;
    }

    // Are we a foreach loopvar?
    if ($this->processVariableAsForeachLoopVar($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found foreach loop variable');
      return;
    }

    // Are we pass-by-reference to known pass-by-reference function?
    if ($this->processVariableAsPassByReferenceFunctionCall($phpcsFile, $stackPtr, $varName, $currScope)) {
      Helpers::debug('found pass by reference');
      return;
    }

    // Are we a numeric variable used for constructs like preg_replace?
    if (Helpers::isVariableANumericVariable($varName)) {
      Helpers::debug('found numeric variable');
      return;
    }

    if (Helpers::isVariableInsideElseCondition($phpcsFile, $stackPtr) || Helpers::isVariableInsideElseBody($phpcsFile, $stackPtr)) {
      Helpers::debug('found variable inside else condition or body');
      $this->processVaribleInsideElse($phpcsFile, $stackPtr, $varName, $currScope);
      return;
    }

    // Are we an isset or empty call?
    if (Helpers::isVariableInsideIssetOrEmpty($phpcsFile, $stackPtr)) {
      Helpers::debug('found isset or empty');
      $this->markVariableRead($varName, $stackPtr, $currScope);
      return;
    }

    // OK, we don't appear to be a write to the var, assume we're a read.
    Helpers::debug('looks like a variable read');
    $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param string $varName
   * @param int $currScope
   *
   * @return void
   */
  protected function processVaribleInsideElse(File $phpcsFile, $stackPtr, $varName, $currScope) {
    // Find all assignments to this variable inside the current scope.
    $varInfo = $this->getOrCreateVariableInfo($varName, $currScope);
    $allAssignmentIndices = array_unique($varInfo->allAssignments);
    // Find the attached 'if' and 'elseif' block start and end indices.
    $blockIndices = Helpers::getAttachedBlockIndicesForElse($phpcsFile, $stackPtr);

    // If all of the assignments are within the previous attached blocks, then warn about undefined.
    $tokens = $phpcsFile->getTokens();
    $assignmentsInsideAttachedBlocks = [];
    foreach ($allAssignmentIndices as $index) {
      foreach ($blockIndices as $blockIndex) {
        $blockToken = $tokens[$blockIndex];
        Helpers::debug('for variable inside else, looking at assignment', $index, 'at block index', $blockIndex, 'which is token', $blockToken);
        if (isset($blockToken['scope_opener']) && isset($blockToken['scope_closer'])) {
          $scopeOpener = $blockToken['scope_opener'];
          $scopeCloser = $blockToken['scope_closer'];
        } else {
          // If the `if` statement has no scope, it is probably inline, which
          // means its scope is from the end of the condition up until the next
          // semicolon
          $scopeOpener = isset($blockToken['parenthesis_closer']) ? $blockToken['parenthesis_closer'] : $blockIndex + 1;
          $scopeCloser = $phpcsFile->findNext([T_SEMICOLON], $scopeOpener);
          if (! $scopeCloser) {
            throw new \Exception("Cannot find scope for if condition block at index {$stackPtr} while examining variable {$varName}");
          }
        }
        Helpers::debug('for variable inside else, looking at scope', $index, 'between', $scopeOpener, 'and', $scopeCloser);
        if (Helpers::isIndexInsideScope($index, $scopeOpener, $scopeCloser)) {
          $assignmentsInsideAttachedBlocks[] = $index;
        }
      }
    }

    if (count($assignmentsInsideAttachedBlocks) === count($allAssignmentIndices)) {
      Helpers::debug("variable $varName inside else looks undefined");
      $this->warnAboutUndefinedVariable($phpcsFile, $varName, $stackPtr);
      return;
    }

    Helpers::debug('looks like a variable read inside else');
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
   *
   * @return void
   */
  protected function processVariableInString(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    if (!preg_match_all(Constants::getDoubleQuotedVarRegexp(), $token['content'], $matches)) {
      return;
    }
    Helpers::debug("examining token for variable in string", $token);

    foreach ($matches[1] as $varName) {
      $varName = Helpers::normalizeVarName($varName);

      // Are we $this within a class?
      if ($this->processVariableAsThisWithinClass($phpcsFile, $stackPtr, $varName)) {
        continue;
      }

      if ($this->processVariableAsSuperGlobal($varName)) {
        continue;
      }

      // Are we a numeric variable used for constructs like preg_replace?
      if (Helpers::isVariableANumericVariable($varName)) {
        continue;
      }

      $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr, $varName);
      if ($currScope === null) {
        continue;
      }

      $this->markVariableReadAndWarnIfUndefined($phpcsFile, $varName, $stackPtr, $currScope);
    }
  }

  /**
   * @param File $phpcsFile
   * @param int $stackPtr
   * @param array[] $arguments The stack pointers of each argument
   * @param int $currScope
   *
   * @return void
   */
  protected function processCompactArguments(File $phpcsFile, $stackPtr, $arguments, $currScope) {
    $tokens = $phpcsFile->getTokens();

    foreach ($arguments as $argumentPtrs) {
      $argumentPtrs = array_values(array_filter($argumentPtrs, function ($argumentPtr) use ($tokens) {
        return isset(Tokens::$emptyTokens[$tokens[$argumentPtr]['code']]) === false;
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
        $this->processCompactArguments($phpcsFile, $stackPtr, $array_arguments, $currScope);
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
   *
   * @return void
   */
  protected function processCompact(File $phpcsFile, $stackPtr) {
    $currScope = Helpers::findVariableScope($phpcsFile, $stackPtr);
    if ($currScope === null) {
      return;
    }

    $arguments = Helpers::findFunctionCallArguments($phpcsFile, $stackPtr);
    $this->processCompactArguments($phpcsFile, $stackPtr, $arguments, $currScope);
  }

  /**
   * Called to process the end of a scope.
   *
   * Note that although triggered by the closing curly brace of the scope,
   * $stackPtr is the scope conditional, not the closing curly brace.
   *
   * @param File $phpcsFile The PHP_CodeSniffer file where this token was found.
   * @param int $stackPtr  The position of the scope conditional.
   *
   * @return void
   */
  protected function processScopeClose(File $phpcsFile, $stackPtr) {
    $scopeInfo = $this->getScopeInfo($stackPtr);
    if (is_null($scopeInfo)) {
      return;
    }
    foreach ($scopeInfo->variables as $varInfo) {
      $this->processScopeCloseForVariable($phpcsFile, $varInfo, $scopeInfo);
    }
  }

  /**
   * @param File $phpcsFile
   * @param VariableInfo $varInfo
   * @param ScopeInfo $scopeInfo
   *
   * @return void
   */
  protected function processScopeCloseForVariable(File $phpcsFile, VariableInfo $varInfo, ScopeInfo $scopeInfo) {
    Helpers::debug('processScopeCloseForVariable', $varInfo);
    if ($varInfo->ignoreUnused || isset($varInfo->firstRead)) {
      return;
    }
    if ($this->allowUnusedFunctionParameters && $varInfo->scopeType === ScopeType::PARAM) {
      return;
    }
    if ($this->allowUnusedParametersBeforeUsed && $varInfo->scopeType === ScopeType::PARAM && Helpers::areFollowingArgumentsUsed($varInfo, $scopeInfo)) {
      Helpers::debug("variable {$varInfo->name} at end of scope has unused following args");
      return;
    }
    if ($this->allowUnusedForeachVariables && $varInfo->isForeachLoopAssociativeValue) {
      return;
    }
    if ($varInfo->referencedVariableScope !== null && isset($varInfo->firstInitialized)) {
      // If we're pass-by-reference then it's a common pattern to
      // use the variable to return data to the caller, so any
      // assignment also counts as "variable use" for the purposes
      // of "unused variable" warnings.
      return;
    }
    if ($varInfo->scopeType === ScopeType::GLOBALSCOPE && isset($varInfo->firstInitialized)) {
      // If we imported this variable from the global scope, any further use of
      // the variable, including assignment, should count as "variable use" for
      // the purposes of "unused variable" warnings.
      return;
    }
    if (empty($varInfo->firstDeclared) && empty($varInfo->firstInitialized)) {
      return;
    }
    if ($this->allowUnusedVariablesBeforeRequire && Helpers::isRequireInScopeAfter($phpcsFile, $varInfo, $scopeInfo)) {
      return;
    }
    if ($scopeInfo->scopeStartIndex === 0 && $this->allowUnusedVariablesInFileScope) {
      return;
    }

    $this->warnAboutUnusedVariable($phpcsFile, $varInfo);
  }

  /**
   * @param File $phpcsFile
   * @param VariableInfo $varInfo
   *
   * @return void
   */
  protected function warnAboutUnusedVariable(File $phpcsFile, VariableInfo $varInfo) {
    foreach (array_unique($varInfo->allAssignments) as $indexForWarning) {
      Helpers::debug("variable {$varInfo->name} at end of scope looks unused");
      $phpcsFile->addWarning(
        "Unused %s %s.",
        $indexForWarning,
        'UnusedVariable',
        [
          VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
          "\${$varInfo->name}",
        ]
      );
    }
  }

  /**
   * @param File $phpcsFile
   * @param string $varName
   * @param int $stackPtr
   *
   * @return void
   */
  protected function warnAboutUndefinedVariable(File $phpcsFile, $varName, $stackPtr) {
      $phpcsFile->addWarning(
        "Variable %s is undefined.",
        $stackPtr,
        'UndefinedVariable',
        ["\${$varName}"]
      );
  }

  /**
   * @param File $phpcsFile
   * @param string $varName
   * @param int $stackPtr
   *
   * @return void
   */
  protected function warnAboutUndefinedArrayPushShortcut(File $phpcsFile, $varName, $stackPtr) {
      $phpcsFile->addWarning(
        "Array variable %s is undefined.",
        $stackPtr,
        'UndefinedVariable',
        ["\${$varName}"]
      );
  }

  /**
   * @param File $phpcsFile
   * @param string $varName
   * @param int $stackPtr
   *
   * @return void
   */
  protected function warnAboutUndefinedUnset(File $phpcsFile, $varName, $stackPtr) {
      $phpcsFile->addWarning(
        "Variable %s inside unset call is undefined.",
        $stackPtr,
        'UndefinedUnsetVariable',
        ["\${$varName}"]
      );
  }
}
