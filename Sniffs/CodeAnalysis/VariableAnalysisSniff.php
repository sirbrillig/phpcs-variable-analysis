<?php
/**
 * This file is part of the VariableAnalysis addon for PHP_CodeSniffer.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011 Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Holds details of a scope.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011 Sam Graham <php-codesniffer-plugins BLAHBLAH illusori.co.uk>
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class ScopeInfo {
    public $owner;
    public $opener;
    public $closer;
    public $variables = array();

    function __construct($currScope) {
        $this->owner = $currScope;
// TODO: extract opener/closer
    }
}

/**
 * Holds details of a variable within a scope.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011 Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class VariableInfo {
    public $name;
    /**
     * What scope the variable has: local, param, static, global, bound
     */
    public $scopeType;
    public $typeHint;
    public $firstDeclared;
    public $firstInitialized;
    public $firstRead;

    static $scopeTypeDescriptions = array(
        'local'  => 'variable',
        'param'  => 'function parameter',
        'static' => 'static variable',
        'global' => 'global variable',
        'bound'  => 'bound variable',
        );

    function __construct($varName) {
        $this->name = $varName;
    }
}

/**
 * Checks the for undefined function variables.
 *
 * This sniff checks that all function variables
 * are defined in the function body.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011 Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Sniffs_CodeAnalysis_VariableAnalysisSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * The current phpcsFile being checked.
     *
     * @var phpcsFile
     */
    protected $currentFile = null;

    /**
     * A list of scopes encountered so far and the variables within them.
     */
    private $_scopes = array();

    /**
     *  Array of known pass-by-reference functions and the argument(s) which are passed
     *  by reference, the arguments are numbered starting from 1.
     */
// TODO: complete list
    private $_passByRefFunctions = array(
        'array_shift' => array(1),
        'preg_match'  => array(3),
        );

    /**
     *  Allows an install to extend the list of known pass-by-reference functions
     *  by defining generic.codeanalysis.variableanalysis.sitePassByRefFunctions.
     */
    public $sitePassByRefFunctions = null;

    /**
     * Returns an array of tokens this test wants to listen for.
     * 
     * @return array
     */
    public function register() {    
        //  Magic to modfy $_passByRefFunctions with any site-specific settings.
        if (!empty($this->sitePassByRefFunctions)) {
//echo "Site pass by ref:" . var_dump($this->sitePassByRefFunctions, true);
            foreach (preg_split('/\s+/', trim($this->sitePassByRefFunctions)) as $line) {
                list ($function, $args) = explode(':', $line);
                $this->_passByRefFunctions[$function] = explode(',', $args);
            }
//echo "Updated pass by ref:" . var_dump($this->_passByRefFunctions, true);
        }
        return array(
//            T_CLASS,
//            T_INTERFACE,
//            T_FUNCTION,
            T_VARIABLE,
            T_DOUBLE_QUOTED_STRING,
            T_CLOSE_CURLY_BRACKET,
            );
    }//end register()

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     * 
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

//if ($token['content'] == '$param') {
//echo "Found token on line {$token['line']}.\n" . print_r($token, true);
//}

        if ($this->currentFile !== $phpcsFile) {
            $this->currentFile = $phpcsFile;
        }

        if ($token['code'] === T_VARIABLE) {
            return $this->processVariable($phpcsFile, $stackPtr);
        }
        if ($token['code'] === T_DOUBLE_QUOTED_STRING) {
            return $this->processVariableInString($phpcsFile, $stackPtr);
        }
        if (($token['code'] === T_CLOSE_CURLY_BRACKET) &&
            isset($token['scope_condition'])) {
            return $this->processScopeClose($phpcsFile, $token['scope_condition']);
        }
    }

    function normalizeVarName($varName) {
        $varName = preg_replace('/[{}$]/', '', $varName);
        return $varName;
    }

    function scopeKey($currScope) {
        if (is_null($currScope)) {
            $currScope = 'file';
        }
        return ($this->currentFile ? $this->currentFile->getFilename() : 'unknown file') .
            ':' . $currScope;
    }

    //  Warning: this is an autovivifying get
    function getScopeInfo($currScope, $autoCreate = true) {
        $scopeKey = $this->scopeKey($currScope);
        if (!isset($this->_scopes[$scopeKey])) {
            if (!$autoCreate) {
                return null;
            }
            $this->_scopes[$scopeKey] = new ScopeInfo($currScope);
        }
        return $this->_scopes[$scopeKey];
    }

    function getVariableInfo($varName, $currScope, $autoCreate = true) {
        $scopeInfo = $this->getScopeInfo($currScope, $autoCreate);
        if (!isset($scopeInfo->variables[$varName])) {
            if (!$autoCreate) {
                return null;
            }
            $scopeInfo->variables[$varName] = new VariableInfo($varName);
        }
        return $scopeInfo->variables[$varName];
    }

    function markVariableAssignment($varName, $stackPtr, $currScope) {
        $varInfo = $this->getVariableInfo($varName, $currScope);
        if (!isset($varInfo->scopeType)) {
            $varInfo->scopeType = 'local';
        }
        if (isset($varInfo->firstInitialized) && ($varInfo->firstInitialized <= $stackPtr)) {
            return;
        }
//echo "Marking write to var {$varName} in {$scopeKey} at {$stackPtr}.\n";
        $varInfo->firstInitialized = $stackPtr;
    }

    function markVariableDeclaration($varName, $scopeType, $typeHint, $stackPtr, $currScope, $permitMatchingRedeclaration = false) {
        $varInfo = $this->getVariableInfo($varName, $currScope);
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
                    array(
                        VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
                        "\${$varName}",
                        VariableInfo::$scopeTypeDescriptions[$scopeType],
                        )
                    );
            }
        }
        $varInfo->scopeType = $scopeType;
        if (isset($varInfo->firstDeclared) && ($varInfo->firstDeclared <= $stackPtr)) {
            return;
        }
//echo "Marking declaration of var {$varName} in {$scopeKey} at {$stackPtr}.\n";
        $varInfo->firstDeclared = $stackPtr;
    }

    function markVariableRead($varName, $stackPtr, $currScope) {
        $varInfo = $this->getVariableInfo($varName, $currScope);
        if (isset($varInfo->firstRead) && ($varInfo->firstRead <= $stackPtr)) {
            return;
        }
//echo "Marking read of var {$varName} in {$scopeKey} at {$stackPtr}.\n";
        $varInfo->firstRead = $stackPtr;
    }

    function isVariableInitialized($varName, $stackPtr, $currScope) {
//return true;
        $varInfo = $this->getVariableInfo($varName, $currScope);
        if (isset($varInfo->firstInitialized) && $varInfo->firstInitialized <= $stackPtr) {
            return true;
        }
        return false;
    }

    function isVariableUndefined($varName, $stackPtr, $currScope) {
//return true;
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

    function findFunctionPrototype(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
            return false;
        }
        // Function names are T_STRING, and return-by-reference is T_BITWISE_AND,
        // so we look backwards from the opening bracket for the first thing that
        // isn't a function name, reference sigil or whitespace and check if
        // it's a function keyword.
        $functionPtr = $phpcsFile->findPrevious(array(T_STRING, T_WHITESPACE, T_BITWISE_AND),
            $openPtr - 1, null, true, null, true);
//echo "functionPtr: $functionPtr\n";// . print_r($tokens[$functionPtr], true);
        if (($functionPtr !== false) &&
            ($tokens[$functionPtr]['code'] === T_FUNCTION)) {
            return $functionPtr;
        }
        return false;
    }

    function findVariableScope(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        if (!empty($token['conditions'])) {
//echo "Looking for scope for {$token['content']}.\n";
            foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
                if (($scopeCode === T_FUNCTION) || ($scopeCode === T_CLOSURE)) {
//echo "Found scope {$tokens[$scopePtr]['content']}.\n";
                    return $scopePtr;
                }
//echo "Skipping scope {$tokens[$scopePtr]['content']}.\n";
            }
        }

        return $this->findFunctionPrototype($phpcsFile, $stackPtr);
    }

    function isNextThingAnAssign(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
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

    function findWhereAssignExecuted(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();

        //  Write should be recorded at the next statement to ensure we treat
        //  the assign as happening after the RHS execution.
        //  eg: $var = $var + 1; -> RHS could still be undef.
        //  However, if we're within a bracketed expression, we take place at
        //  the closing bracket, if that's first.
        //  eg: echo (($var = 12) && ($var == 12));
        $semicolonPtr = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1, null, false, null, true);
        $closePtr = false;
        if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) !== false) {
            if (isset($tokens[$openPtr]['parenthesis_closer'])) {
                $closePtr = $tokens[$openPtr]['parenthesis_closer'];
            }
        }

        if ($semicolonPtr === false) {
            if ($closePtr === false) {
                // TODO: panic
                return $stackPtr;
            }
            return $closePtr;
        }

        if ($closePtr < $semicolonPtr) {
            return $closePtr;
        }

        return $semicolonPtr;
    }

    function findContainingBrackets(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
            $openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
            return end($openPtrs);
        }
        return false;
    }


    function findFunctionCall(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();

        if ($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) {
            // First non-whitespace thing and see if it's a T_STRING function name
            $functionPtr = $phpcsFile->findPrevious(T_WHITESPACE,
                $openPtr - 1, null, true, null, true);
            if ($tokens[$functionPtr]['code'] === T_STRING) {
                return $functionPtr;
            }
        }
        return false;
    }

    function findFunctionCallArguments(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_STRING) {
            // Assume $stackPtr is something within the brackets, find our function call
            if (($stackPtr = $this->findFunctionCall($phpcsFile, $stackPtr)) === false) {
                return false;
            }
        }

        // $stackPtr is the function name, find our brackets after it
        $openPtr = $phpcsFile->findNext(T_WHITESPACE,
            $stackPtr + 1, null, true, null, true);
        if (($openPtr === false) || ($tokens[$openPtr]['code'] !== T_OPEN_PARENTHESIS)) {
            return false;
        }

        if (!isset($tokens[$openPtr]['parenthesis_closer'])) {
            return false;
        }
        $closePtr = $tokens[$openPtr]['parenthesis_closer'];

        $argPtrs = array();
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

    protected function checkForFunctionPrototype(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we a function or closure parameter?
        // It would be nice to get the list of function parameters from watching for
        // T_FUNCTION, but AbstractVariableSniff and AbstractScopeSniff define everything
        // we need to do that as private or final, so we have to do it this hackish way.
        if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
            return false;
        }

//echo "Prev to bracket: " . ($openPtr - 1 ) . "\n";// . print_r($tokens[$openPtr - 1], true);

        // Function names are T_STRING, and return-by-reference is T_BITWISE_AND,
        // so we look backwards from the opening bracket for the first thing that
        // isn't a function name, reference sigil or whitespace and check if
        // it's a function keyword.
        $functionPtr = $phpcsFile->findPrevious(array(T_STRING, T_WHITESPACE, T_BITWISE_AND),
            $openPtr - 1, null, true, null, true);
//echo "functionPtr: $functionPtr\n";// . print_r($tokens[$functionPtr], true);
        if (($functionPtr !== false) &&
            (($tokens[$functionPtr]['code'] === T_FUNCTION) ||
             ($tokens[$functionPtr]['code'] === T_CLOSURE))) {
            // TODO: typeHint
            $this->markVariableDeclaration($varName, 'param', null, $stackPtr, $functionPtr);
            //  Are we optional with a default?
            if (($assignPtr = $this->isNextThingAnAssign($phpcsFile, $stackPtr)) !== false) {
                $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
            }
            return true;
        }

        // Is it a use keyword?  Use is both a read and a define, fun!
        if (($functionPtr !== false) && ($tokens[$functionPtr]['code'] === T_USE)) {
            $this->markVariableRead($varName, $stackPtr, $currScope);
            if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
                // We haven't been defined by this point.
//echo "Uninitialized.\n";
                $phpcsFile->addWarning("Variable %s is undefined.", $stackPtr,
                    'UndefinedVariable',
                    array("\${$varName}"));
                return true;
            }
            // $functionPtr is at the use, we need the function keyword for start of scope.
            $functionPtr = $phpcsFile->findPrevious(T_CLOSURE,
                $functionPtr - 1, $currScope + 1, false, null, true);
            if ($functionPtr !== false) {
                // TODO: typeHints in use?
                $this->markVariableDeclaration($varName, 'bound', null, $stackPtr, $functionPtr);
                $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
                return true;
            }
        }
        return false;
    }
    
    protected function checkForCatchBlock(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we a catch block parameter?
        if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
            return false;
        }

//echo "Prev to bracket: " . ($openPtr - 1 ) . "\n";// . print_r($tokens[$openPtr - 1], true);

        // Function names are T_STRING, and return-by-reference is T_BITWISE_AND,
        // so we look backwards from the opening bracket for the first thing that
        // isn't a function name, reference sigil or whitespace and check if
        // it's a function keyword.
        $catchPtr = $phpcsFile->findPrevious(T_WHITESPACE,
            $openPtr - 1, null, true, null, true);
//echo "catchPtr: $catchPtr\n";// . print_r($tokens[$catchPtr], true);
        if (($catchPtr !== false) &&
            ($tokens[$catchPtr]['code'] === T_CATCH)) {
            // Scope of the exception var is actually the function, not just the catch block.
            // TODO: typeHint
            $this->markVariableDeclaration($varName, 'local', null, $stackPtr, $currScope, true);
            $this->markVariableAssignment($varName, $stackPtr, $currScope);
            return true;
        }
        return false;
    }
    
    protected function checkForThisWithinClass(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we $this within a class?
        if (($varName != 'this') || empty($token['conditions'])) {
            return false;
        }

        foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
            //  $this within a closure is invalid
            //  Note: have to fetch code from $tokens, T_CLOSURE isn't set for conditions codes.
            if ($tokens[$scopePtr]['code'] === T_CLOSURE) {
                return false;
            }
            if ($scopeCode === T_CLASS) {
                return true;
            }
        }

        return false;
    }

    protected function checkForStaticMember(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we a static member?
        $doubleColonPtr = $stackPtr - 1;
        if ($tokens[$doubleColonPtr]['code'] !== T_DOUBLE_COLON) {
            return false;
        }
        $classNamePtr   = $stackPtr - 2;
        if (($tokens[$classNamePtr]['code'] !== T_STRING) &&
            ($tokens[$classNamePtr]['code'] !== T_SELF)) {
            return false;
        }

//echo "found className " . $tokens[$classNamePtr]['content'] . "\n";

        // Are we refering to self:: outside a class?
        // TODO: not sure this is our business or should be some other sniff.
        if ($tokens[$classNamePtr]['code'] === T_SELF) {
//echo "found self, like totally trippin'\n";
            if (!empty($token['conditions'])) {
                foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
                    //  self within a closure is invalid
                    //  Note: have to fetch code from $tokens, T_CLOSURE isn't set for conditions codes.
                    if ($tokens[$scopePtr]['code'] === T_CLOSURE) {
                        $phpcsFile->addError("Use of self::%s inside closure.", $stackPtr,
                            'SelfOutsideClass',
                            array("\${$varName}"));
                        return true;
                    }
                    if ($scopeCode === T_CLASS) {
                        return true;
                    }
                }
            }
            $phpcsFile->addError("Use of self::%s outside class definition.", $stackPtr,
                'SelfOutsideClass',
                array("\${$varName}"));
            return true;
        }

        return true;
    }

    protected function checkForAssignment(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Is the next non-whitespace an assignment?
        if (($assignPtr = $this->isNextThingAnAssign($phpcsFile, $stackPtr)) === false) {
            return false;
        }

        // Plain ol' assignment. Simpl(ish).
//echo "Next:\n" . print_r($tokens[$assignPtr], true);
        if (($writtenPtr = $this->findWhereAssignExecuted($phpcsFile, $assignPtr)) === false) {
            $writtenPtr = $stackPtr;  // I dunno
        }
        $this->markVariableAssignment($varName, $writtenPtr, $currScope);
        return true;
    }

    protected function checkForListAssignment(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // OK, are we within a list (...) construct?
        if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
            return false;
        }

//echo "Open bracket:\n" . print_r($tokens[$openPtr], true);
        $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
//echo "Prev to bracket:\n" . print_r($tokens[$prevPtr], true);
        if (($prevPtr === false) || ($tokens[$prevPtr]['code'] !== T_LIST)) {
            return false;
        }

        // OK, we're a list (...) construct... are we being assigned to?
//echo "Is list.\n";
        $closePtr = $tokens[$openPtr]['parenthesis_closer'];
        if (($assignPtr = $this->isNextThingAnAssign($phpcsFile, $closePtr)) === false) {
            return false;
        }

        // Yes, we're being assigned.
//echo "Next after brackets:\n" . print_r($tokens[$assignPtr], true);
        $writtenPtr = $this->findWhereAssignExecuted($phpcsFile, $assignPtr);
        $this->markVariableAssignment($varName, $writtenPtr, $currScope);
        return true;
    }

    protected function checkForGlobalDeclaration(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we a global declaration?
        // Search backwards for first token that isn't whitespace, comma or variable.
        $globalPtr = $phpcsFile->findPrevious(
            array(T_WHITESPACE, T_VARIABLE, T_COMMA),
            $stackPtr - 1, null, true, null, true);
        if (($globalPtr === false) || ($tokens[$globalPtr]['code'] !== T_GLOBAL)) {
            return false;
        }

        // It's a global declaration.
//echo "In a global declaration.\n";
        $this->markVariableDeclaration($varName, 'global', null, $stackPtr, $currScope);
        return true;
    }

    protected function checkForStaticDeclaration(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we a static declaration?
        // Static declarations are a bit more complicated than globals, since they
        // can contain assignments. The assignment is compile-time however so can
        // only be constant values, which makes life manageable.
        // Valid values are:
        //   number T_MINUS T_LNUMBER T_DNUMBER
        //   string T_CONSTANT_ENCAPSED_STRING
        //   define T_STRING
        //   class constant T_STRING, T_DOUBLE_COLON, T_STRING
        // Search backwards for first token that isn't whitespace, comma, variable,
        // equals, or on the list of assignable constant values above.
        $staticPtr = $phpcsFile->findPrevious(
            array(T_WHITESPACE, T_VARIABLE, T_COMMA, T_EQUAL,
                  T_MINUS, T_LNUMBER, T_DNUMBER,
                  T_CONSTANT_ENCAPSED_STRING,
                  T_STRING,
                  T_DOUBLE_COLON),
            $stackPtr - 1, null, true, null, true);
//if ($varName == 'static2') {
//echo "Failing token:\n" . print_r($tokens[$staticPtr], true);
//}
        if (($staticPtr === false) || ($tokens[$staticPtr]['code'] !== T_STATIC)) {
            return false;
        }

        // It's a static declaration.
//echo "In a static declaration.\n";
        $this->markVariableDeclaration($varName, 'static', null, $stackPtr, $currScope);
        if ($this->isNextThingAnAssign($phpcsFile, $stackPtr) !== false) {
            $this->markVariableAssignment($varName, $stackPtr, $currScope);
        }
        return true;
    }

    protected function checkForForeachLoopVar(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we a foreach loopvar?
        if (($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) === false) {
            return false;
        }

        // Is there an 'as' token between us and the opening bracket?
        if (($asPtr = $phpcsFile->findPrevious(T_AS, $stackPtr - 1, $openPtr)) === false) {
            return false;
        }

        $this->markVariableAssignment($varName, $stackPtr, $currScope);
        return true;
    }

    protected function checkForPassByReferenceFunctionCall(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $varName,
        $currScope
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        // Are we pass-by-reference to known pass-by-reference function?
        if (($functionPtr = $this->findFunctionCall($phpcsFile, $stackPtr)) === false) {
            return false;
        }

        // Is our function a known pass-by-reference function?
        $functionName = $tokens[$functionPtr]['content'];
//echo "  Is a function call to {$functionName}\n";
        if (!isset($this->_passByRefFunctions[$functionName])) {
            return false;
        }

        $refArgs = $this->_passByRefFunctions[$functionName];
//echo "  Is a pass-by-ref function\n";
            
        if (($argPtrs = $this->findFunctionCallArguments($phpcsFile, $stackPtr)) === false) {
            return false;
        }

//echo "  Arg pointers found\n";
        // We're within a function call arguments list, find which arg we are.
        $argPos = false;
        foreach ($argPtrs as $idx => $ptrs) {
            if (in_array($stackPtr, $ptrs)) {
                $argPos = $idx + 1;
                break;
            }
        }
//echo "  We have arg position $argPos\n";
        if (($argPos === false) || !in_array($argPos, $refArgs)) {
            return false;
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

    /**
     * Called to process class member vars.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this
     *                                        token was found.
     * @param int                  $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected function processMemberVar(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];
        // TODO: don't care for now
    }

    /**
     * Called to process normal member vars.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this
     *                                        token was found.
     * @param int                  $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected function processVariable(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        $varName = $this->normalizeVarName($token['content']);
        if (($currScope = $this->findVariableScope($phpcsFile, $stackPtr)) === false) {
            return;
        }
        

//if ($varName == 'param') {
//echo "Found variable {$varName} on line {$token['line']} in scope {$currScope}.\n";// . print_r($token, true);
//}
//echo "Prev:\n" . print_r($tokens[$stackPtr - 1], true);

        // Determine if variable is being assigned or read.

        // Possible assignment methods:
        //   Is a mandatory function/closure parameter
        //   Is an optional function/closure parameter with non-null value
        //   Is closure use declaration of a variable defined within containing scope
        //   catch (...) block start
        //   $this within a class (but not within a closure).
        //   $var part of class::$var static member
        //   Assignment via =
        //   Assignment via list (...) =
        //   Declares as a global
        //   Declares as a static
        //   Assignment via foreach (... as ...) { }
        //   Pass-by-reference to known pass-by-reference function

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

//echo "Looks like a read.\n";
        $this->markVariableRead($varName, $stackPtr, $currScope);

        // OK, we don't appear to be a write to the var, assume we're a read.
        if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
            // We haven't been defined by this point.
//echo "Uninitialized.\n";
            $phpcsFile->addWarning("Variable %s is undefined.", $stackPtr,
                'UndefinedVariable',
                array("\${$varName}"));
        }
    }

    /**
     * Called to process variables found in double quoted strings.
     *
     * Note that there may be more than one variable in the string, which will
     * result only in one call for the string.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this
     *                                        token was found.
     * @param int                  $stackPtr  The position where the double quoted
     *                                        string was found.
     *
     * @return void
     */
    protected function processVariableInString(
        PHP_CodeSniffer_File
        $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        $pattern = '|[^\\\]\${?([a-zA-Z0-9_]+)}?|';
        if (!preg_match_all($pattern, $token['content'], $matches)) {
            return;
        }

        $currScope = $this->findVariableScope($phpcsFile, $stackPtr);
        foreach ($matches[1] as $varName) {
            $varName = $this->normalizeVarName($varName);
            // Are we $this within a class?
            if ($this->checkForThisWithinClass($phpcsFile, $stackPtr, $varName, $currScope)) {
                continue;
            }
//echo "Found variable {$varName} in string on line {$token['line']} in scope {$currScope}.\n" . print_r($token, true);
            $this->markVariableRead($varName, $stackPtr, $currScope);
            if ($this->isVariableUndefined($varName, $stackPtr, $currScope) === true) {
//echo "Uninitialized.\n";
                $phpcsFile->addWarning("Variable %s is undefined.", $stackPtr,
                    'UndefinedVariable',
                    array("\${$varName}"));
            }
        }
    }

    /**
     * Called to process the end of a scope.
     *
     * Note that although triggered by the closing curly brace of the scope, $stackPtr is
     * the scope conditional, not the closing curly brace.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this
     *                                        token was found.
     * @param int                  $stackPtr  The position of the scope conditional.
     *
     * @return void
     */
    protected function processScopeClose(
        PHP_CodeSniffer_File
        $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        $scopeInfo = $this->getScopeInfo($stackPtr, false);
        if (is_null($scopeInfo)) {
//            echo "Empty scope.\n";
            return;
        }
//        echo "Scope closed:\n" . var_dump($scopeInfo, true);
        foreach ($scopeInfo->variables as $varInfo) {
            if (isset($varInfo->firstRead)) {
                continue;
            }
            if (isset($varInfo->firstDeclared)) {
                $phpcsFile->addWarning(
                    "Unused %s %s.",
                    $varInfo->firstDeclared,
                    'UnusedVariable',
                    array(
                        VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
                        "\${$varInfo->name}",
                        )
                    );
            }
            if (isset($varInfo->firstInitialized)) {
                $phpcsFile->addWarning(
                    "Unused %s %s.",
                    $varInfo->firstInitialized,
                    'UnusedVariable',
                    array(
                        VariableInfo::$scopeTypeDescriptions[$varInfo->scopeType],
                        "\${$varInfo->name}",
                        )
                    );
            }
        }
    }
}//end class

?>