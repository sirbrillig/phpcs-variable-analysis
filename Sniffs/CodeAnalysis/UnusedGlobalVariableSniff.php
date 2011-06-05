<?php
/**
 * This file is part of the CodeAnalysis addon for PHP_CodeSniffer.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Alan Jancic <alan.jancic@monotek.net>
 * @copyright 2010 Monotek d.o.o.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id: $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks the for unused global variables.
 *
 * This sniff checks that all global variables are used in the function body.
 * Will also diplay a warning if globals are defined within a conditional
 * statement or loops.
 * 
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Alan Jancic <alan.jancic@monotek.net>
 * @copyright 2010 Monotek d.o.o.
 * @version   Release: 0.1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Monocms_Sniffs_CodeAnalysis_UnusedGlobalVariableSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     * 
     * @return array
     */
    public function register()
    {    
        return array(T_FUNCTION);
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
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // all tokens
            $tokens = $phpcsFile->getTokens();
        // the T_FUNCTION token
           $token = $tokens[$stackPtr];
        
        // the next token
            $next = ++$token['scope_opener'];
        // the last token (function closing curly braces)
            $end  = --$token['scope_closer'];

        $globals = array();
        $global_vars = array();
        $vars = array();
        $global_line = 0;
	$others = array();
    
        // define ignored tokens
        $ignored_tokens = array("T_COMMENT", "T_CLOSE_TAG", "T_OPEN_TAG", "T_ML_COMMENT", "T_COMMENT", "T_WHITESPACE");

        // scan through code
         for (; $next <= $end; ++$next) {
            // current token
                   $token = $tokens[$next];
            // current token code
                $code  = $token['code'];
            
            // skip ignored tokens
            if (in_array($token['type'], $ignored_tokens)) {
                        continue;
            }

            // line with globals defined
            if ($token['type'] == "T_GLOBAL") {
                $global_line = $token['line'];
                if ($token['level'] > 2) {
                    // output warning for globals defined in conditional statement or loop
                    $phpcsFile->addWarning("Globals should be defined at the start of the function.", $next);
                }
            }
            // global variables on that line
            if ($token['type'] == "T_VARIABLE" && $token['line'] == $global_line) {
                $global_vars[] = $token['content'] ."|". $next;
                $globals[] = $token['content'];
            }
            // all other variables in function 
            if ($token['type'] == "T_VARIABLE" && $token['line'] != $global_line) {
                $vars[] = $token['content'];
            }

            // object var
                        if ($tokens[$next+1]['content'] == "->") {
                        $others[] = $token['content'];
                        }

        
        } // end for

        // output warnings for globals
        foreach ($global_vars as $global) {
            $global = explode("|", $global);
            if(!in_array($global[0], $vars) && !in_array($global[0], $others)) {
                 $phpcsFile->addWarning("Global " . $global[0] ." is never used.", $global[1]);
            }
        }

    } //end process()

}//end class

?>