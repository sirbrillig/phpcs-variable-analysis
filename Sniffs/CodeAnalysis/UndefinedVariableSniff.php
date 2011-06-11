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
            // TODO: T_CLOSURE?
            if ($scopeCode === T_FUNCTION) {
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

//echo "Found variable {$varName} on line {$token['line']} in scope {$currScope}.\n" . print_r($token, true);
//echo "Prev:\n" . print_r($tokens[$stackPtr - 1], true);

        // TODO: determine if variable is being assigned or read.

        // Possible assignment methods:
        //   Assignment via =
        //   Assignment via list (...) =
        //   Declares as a global
        //   Assignment via foreach (... as ...) { }
        //   Is a mandatory function parameter
        //   Is an optional function parameter with non-null value
        //   Pass-by-reference to known pass-by-reference function
        //   TODO: we need to care about/ignore "use" in closures?


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
        if (isset($token['nested_parenthesis'])) {
//echo "Has brackets.\n";
            $openPtrs = array_keys($token['nested_parenthesis']);
            $openPtr = $openPtrs[count($openPtrs) - 1];
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

        // Are we a foreach loopvar?
        if (isset($token['nested_parenthesis'])) {
            $openPtrs = array_keys($token['nested_parenthesis']);
            $openPtr = $openPtrs[count($openPtrs) - 1];

            // Is there an 'as' token between us and the opening bracket?
            $asPtr = $phpcsFile->findPrevious(T_AS, $stackPtr - 1, $openPtr);
            if ($asPtr !== false) {
                $this->markVariableAssignment($varName, $stackPtr, $currScope);
                return;
            }
        }

        // Are we a function parameter?
        // It would be nice to get the list of function parameters from watching for
        // T_FUNCTION, but AbstractVariableSniff and AbstractScopeSniff define everything
        // we need to do that as private or final, so we have to do it this hackish way.
        if (isset($token['nested_parenthesis'])) {
            $openPtrs = array_keys($token['nested_parenthesis']);
            $openPtr = $openPtrs[count($openPtrs) - 1];
//echo "Prev to bracket:\n" . print_r($tokens[$openPtr - 1], true);

            // Function names are T_STRING, so we look backwards from the opening bracket
            // for the first thing that isn't a function name or whitespace and check if
            // it's a function keyword.
            $functionPtr = $phpcsFile->findPrevious(array(T_STRING, T_WHITESPACE),
                $openPtr - 1, null, true, null, true);
//echo "functionPtr:\n" . print_r($tokens[$functionPtr], true);
            if (($functionPtr !== false) && ($tokens[$functionPtr]['code'] === T_FUNCTION)) {
                // TODO:   are we optional?
                // TODO:     are we default null?
                $this->markVariableAssignment($varName, $stackPtr, $currScope);
                return;
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