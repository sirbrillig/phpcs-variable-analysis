<?php
/**
 * This file is part of the CodeAnalysis addon for PHP_CodeSniffer.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sam Graham <php-codesniffer-plugins BLAHBLAH illusori.co.uk>
 * @copyright 2011 Sam Graham <php-codesniffer-plugins BLAHBLAH illusori.co.uk>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks the for undefined function variables.
 *
 * This sniff checks that all function variables
 * are defined in the function body.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sam Graham <php-codesniffer-plugins BLAHBLAH illusori.co.uk>
 * @copyright 2011 Sam Graham <php-codesniffer-plugins BLAHBLAH illusori.co.uk>
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Sniffs_CodeAnalysis_UndefinedVariableSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{
    private $_scopes = array();

    //  Array of known pass-by-reference functions and the argument(s) which are passed
    //  by reference, the arguments are numbered starting from 1.
    static $pass_by_ref_functions = array(
        'array_shift' => array(1),
        'preg_match'  => array(3),
        );

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

    function markVariableAssignment($varName, $stackPtr, $currScope) {
        $scopeKey = $this->scopeKey($currScope);
        if (isset($this->_scopes[$scopeKey]) &&
            isset($this->_scopes[$scopeKey][$varName]) &&
            ($this->_scopes[$scopeKey][$varName] <= $stackPtr)) {
            return;
        }
//echo "Marking write to var {$varName} in {$scopeKey} at {$stackPtr}.\n";
        $this->_scopes[$scopeKey][$varName] = $stackPtr;
    }

    function isVariableInitialized($varName, $stackPtr, $currScope) {
//return true;
        $scopeKey = $this->scopeKey($currScope);
        if (isset($this->_scopes[$scopeKey]) &&
            isset($this->_scopes[$scopeKey][$varName]) &&
            ($this->_scopes[$scopeKey][$varName] <= $stackPtr)) {
            return true;
        }
        return false;
    }

    function findVariableScope(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens = $phpcsFile->getTokens();
        $token  = $tokens[$stackPtr];

        if (empty($token['conditions'])) {
            return null;
        }

//echo "Looking for scope for {$token['content']}.\n";
        foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
            if (($scopeCode === T_FUNCTION) || ($scopeCode === T_CLOSURE)) {
//echo "Found scope {$tokens[$scopePtr]['content']}.\n";
                return $scopePtr;
            }
//echo "Skipping scope {$tokens[$scopePtr]['content']}.\n";
        }

        return null;
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

        // Write should be recorded at the next statement to ensure we treat
        // the assign as happening after the RHS execution.
        // eg: $var = $var + 1; -> RHS could still be undef.
        $execPtr = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1, null, false, null, true);
        if ($execPtr === false) {
            // TODO: panic
            $execPtr = $stackPtr;
        }

        // TODO: Handle: echo (($var = 12) && ($var == 12));

        return $execPtr;
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
        $currScope = $this->findVariableScope($phpcsFile, $stackPtr);

//if ($varName == 'this') {
//echo "Found variable {$varName} on line {$token['line']} in scope {$currScope}.\n";// . print_r($token, true);
//}
//echo "Prev:\n" . print_r($tokens[$stackPtr - 1], true);

        // Determine if variable is being assigned or read.

        // Possible assignment methods:
        //   Is a mandatory function/closure parameter
        //   TODO: Is an optional function/closure parameter with non-null value
        //   Is closure use declaration of a variable defined within containing scope
        //   $this within a class.
        //   Assignment via =
        //   Assignment via list (...) =
        //   Declares as a global
        //   Declares as a static
        //   Assignment via foreach (... as ...) { }
        //   Pass-by-reference to known pass-by-reference function


        // Are we a function or closure parameter?
        // It would be nice to get the list of function parameters from watching for
        // T_FUNCTION, but AbstractVariableSniff and AbstractScopeSniff define everything
        // we need to do that as private or final, so we have to do it this hackish way.
        if ($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) {
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
                // TODO:   are we optional?
                // TODO:     are we default null?
                $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
                return;
            }

            // Is it a use keyword?  Use is both a read and a define, fun!
            if (($functionPtr !== false) && ($tokens[$functionPtr]['code'] === T_USE)) {
                if ($this->isVariableInitialized($varName, $stackPtr, $currScope) === false) {
                    // We haven't been defined by this point.
//echo "Uninitialized.\n";
                    $phpcsFile->addWarning("Variable \${$varName} is undefined.", $stackPtr);
                    return;
                }
                // $functionPtr is at the use, we need the function keyword for start of scope.
                $functionPtr = $phpcsFile->findPrevious(T_CLOSURE,
                    $functionPtr - 1, $currScope + 1, false, null, true);
                if ($functionPtr !== false) {
                    $this->markVariableAssignment($varName, $stackPtr, $functionPtr);
                }
                return;
            }
        }

        // Are we $this within a class?
        if (($varName == 'this') && (!empty($token['conditions']))) {
            foreach ($token['conditions'] as $scopePtr => $scopeCode) {
// TODO: $this within a closure is invalid
                if ($scopeCode == T_CLASS) {
                    return;
                }
            }
        }

        // Is the next non-whitespace an asignment?
        $assignPtr = $this->isNextThingAnAssign($phpcsFile, $stackPtr);
        if ($assignPtr !== false) {
            // Plain ol' assignment. Simpl(ish).

//echo "Next:\n" . print_r($tokens[$assignPtr], true);

            $writtenPtr = $this->findWhereAssignExecuted($phpcsFile, $assignPtr);
            $this->markVariableAssignment($varName, $writtenPtr, $currScope);
            return;
        }

        // OK, are we within a list (...) construct?
        if ($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) {
//echo "Open bracket:\n" . print_r($tokens[$openPtr], true);
            $prevPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
//echo "Prev to bracket:\n" . print_r($tokens[$prevPtr], true);
            if (($prevPtr !== false) && ($tokens[$prevPtr]['code'] === T_LIST)) {
                // OK, we're a list (...) construct... are we being assigned to?
//echo "Is list.\n";

                $closePtr = $tokens[$openPtr]['parenthesis_closer'];
                $assignPtr = $this->isNextThingAnAssign($phpcsFile, $closePtr);
                if ($assignPtr !== false) {
                    // Yes, we're being assigned.

//echo "Next after brackets:\n" . print_r($tokens[$assignPtr], true);

                    $writtenPtr = $this->findWhereAssignExecuted($phpcsFile, $assignPtr);
                    $this->markVariableAssignment($varName, $writtenPtr, $currScope);
                    return;
                }
            }
        }

        // Are we a global declaration?
        // Search backwards for first token that isn't whitespace, comma or variable.
        $globalPtr = $phpcsFile->findPrevious(
            array(T_WHITESPACE, T_VARIABLE, T_COMMA),
            $stackPtr - 1, null, true, null, true);
        if (($globalPtr !== false) && ($tokens[$globalPtr]['code'] === T_GLOBAL)) {
            // It's a global declaration.
//echo "In a global declaration.\n";
            $this->markVariableAssignment($varName, $stackPtr, $currScope);
            return;
        }

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
        if (($staticPtr !== false) && ($tokens[$staticPtr]['code'] === T_STATIC)) {
            // It's a static declaration.
//echo "In a static declaration.\n";
            $this->markVariableAssignment($varName, $stackPtr, $currScope);
            return;
        }

        // Are we a foreach loopvar?
        if ($openPtr = $this->findContainingBrackets($phpcsFile, $stackPtr)) {
            // Is there an 'as' token between us and the opening bracket?
            $asPtr = $phpcsFile->findPrevious(T_AS, $stackPtr - 1, $openPtr);
            if ($asPtr !== false) {
                $this->markVariableAssignment($varName, $stackPtr, $currScope);
                return;
            }
        }

        // Are we pass-by-reference to known pass-by-reference function?
        if (($functionPtr = $this->findFunctionCall($phpcsFile, $stackPtr)) !== false) {
            // Is our function a known pass-by-reference function?
            $functionName = $tokens[$functionPtr]['content'];
//echo "  Is a function call to {$functionName}\n";
            if (isset(self::$pass_by_ref_functions[$functionName])) {
                $refArgs = self::$pass_by_ref_functions[$functionName];
//echo "  Is a pass-by-ref function\n";
            
                if ($argPtrs = $this->findFunctionCallArguments($phpcsFile, $stackPtr)) {
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
                    if (($argPos !== false) && in_array($argPos, $refArgs)) {
                        // Our argument position matches that of a pass-by-ref argument,
                        // check that we're the only part of the argument expression.
                        $onlyMe = true;
                        foreach ($argPtrs[$argPos - 1] as $ptr) {
                            if ($ptr === $stackPtr) {
                                continue;
                            }
                            if ($tokens[$ptr]['code'] !== T_WHITESPACE) {
                                $onlyMe = false;
                                break;
                            }
                        }
                        if ($onlyMe) {
                            // Just us, we can mark it as a write.
                            $this->markVariableAssignment($varName, $stackPtr, $currScope);
                            return;
                        }
                    }
                }
            }
        }

//echo "Looks like a read.\n";

        // OK, we don't appear to be a write to the var, assume we're a read.
        if ($this->isVariableInitialized($varName, $stackPtr, $currScope) === false) {
            // We haven't been defined by this point.
//echo "Uninitialized.\n";
            $phpcsFile->addWarning("Variable \${$varName} is undefined.", $stackPtr);
        }
    }

    /**
     * Called to process variables found in duoble quoted strings.
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

        if ($token['code'] === T_FUNCTION) {
            // Bug in AbstractVariableSniff sends T_FUNCTIONs to this callback.
            // TODO: fix and submit upstream patch
            return;
        }

        $pattern = '|[^\\\]\${?([a-zA-Z0-9_]+)}?|';
        if (!preg_match_all($pattern, $token['content'], $matches)) {
            // TODO: probably should raise an error
            return;
        }

        $currScope = $this->findVariableScope($phpcsFile, $stackPtr);
        foreach ($matches[1] as $varName) {
            $varName = $this->normalizeVarName($varName);
//echo "Found variable {$varName} in string on line {$token['line']} in scope {$currScope}.\n" . print_r($token, true);
            if ($this->isVariableInitialized($varName, $stackPtr, $currScope) === false) {
//echo "Uninitialized.\n";
                $phpcsFile->addWarning("Variable \${$varName} is undefined.", $stackPtr);
            }
        }
    }

}//end class

?>