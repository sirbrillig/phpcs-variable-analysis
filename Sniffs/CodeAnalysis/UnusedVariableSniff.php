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
 * Checks the for unused function variables.
 *
 * This sniff checks that all function variables
 * are used  in the function body.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Alan Jancic <alan.jancic@monotek.net>
 * @copyright 2010 Monotek d.o.o.
 * @version   Release: 0.1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Monocms_Sniffs_CodeAnalysis_UnusedVariableSniff implements PHP_CodeSniffer_Sniff
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
    
        $reads = array();
        $read_vars = array();
        $writes = array();
        $write_vars = array();
        $others = array();        

        // define ignored tokens, write and read tokens, ignored variables, comparison operators
        $ignored_tokens = array("T_COMMENT", "T_CLOSE_TAG", "T_OPEN_TAG", "T_ML_COMMENT", "T_COMMENT", "T_WHITESPACE");
        $write_tokens = array("T_EQUAL", "T_LIST", "T_OBJECT_OPERATOR", "T_OPEN_SQUARE_BRACKET", "T_CLOSE_SQUARE_BRACKET", "T_SEMICOLON", "T_DOUBLE_ARROW", "T_LIST");
        $read_tokens  = array(
                              "T_COMMA", "T_DOUBLE_ARROW", "T_AS", "T_BOOLEAN_AND", "T_BOOLEAN_OR", "T_IS_EQUAL", "T_IS_GREATER_OR_EQUAL", "T_IS_IDENTICAL", "T_IS_NOT_EQUAL",
                      "T_IS_NOT_IDENTICAL", "T_IS_NOT_IDENTICAL", "T_EQUAL");

        $ignored_vars = array("\$GLOBALS", "\$_SERVER", "\$_GET", "\$_POST", "\$_FILES", "\$_COOKIE", "\$_SESSION", "\$_REQUEST", "\$_ENV", "\$this");

        $comparisons = array("T_BOOLEAN_AND", "T_BOOLEAN_OR", "T_IS_EQUAL", "T_IS_GREATER_OR_EQUAL", "T_IS_IDENTICAL", "T_IS_NOT_EQUAL", "T_IS_NOT_IDENTICAL", "T_IS_NOT_IDENTICAL", "T_EQUAL");

        // scan through code
         for (; $next <= $end; ++$next) {
            // current token
                   $token = $tokens[$next];
            // current token code
                $code  = $token['code'];
            
            // skip ignored tokens tokens
            if (in_array($token['type'], $ignored_tokens)) {
                        continue;
            }

            // line with globals defined
            if ($token['type'] == "T_GLOBAL") {
                $global_line = $token['line'];
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
        
            // check function variable context
            if ($token['type'] == "T_VARIABLE" && $token['line'] != $global_line) {
    
                // reading from a variable
                if (in_array($tokens[$next-2]['type'], $read_tokens) || in_array($tokens[$next-1]['type'], $read_tokens)) {
                    $read_vars[] = $token['content'] ."|". $token['line'];
                    $reads[] = $token['content'];
                } 
                
                // writing to a variable
                if (in_array($tokens[$next+1]['type'], $write_tokens) || in_array($tokens[$next+2]['type'], $write_tokens)) {
                    $write_vars[] = $token['content'] ."|". $token['line'];
                    $writes[] = $token['content'];
                }

                // whitespace problems, check next token
                if ($tokens[$next-1]['type'] == "T_DOUBLE_ARROW" || $tokens[$next-2]['type'] == "T_DOUBLE_ARROW" || $tokens[$next-3]['type'] == "T_DOUBLE_ARROW") {
                                        $others[] = $token['content'];
                }
                // variable as as function parameter
                if ($tokens[$next-1]['content'] == "(" && $tokens[$next+1]['content'] == ")") {
                                        $others[] = $token['content'];                    
                }

                // &$var
                if ($tokens[$next-1]['content'] == "&" && $tokens[$next+1]['content'] == "," && $tokens[$next+2]['type'] == "T_CONSTANT_ENCAPSED_STRING") {
                    $others[] = $token['content'];
                }

                // returned variable
                if ($tokens[$next-1]['type'] == "T_RETURN" || $tokens[$next-2]['type'] == "T_RETURN") {
                    $others[] = $token['content'];
                }

                // multiple variables as parameters _somefunc($a, $b)
                if (($tokens[$next-1]['content'] == "," && $tokens[$next-2]['type'] == "T_VARIABLE") || (($tokens[$next+1]['content'] == "," && $tokens[$next+2]['type'] == "T_VARIABLE"))) {
                    $others[] = $token['content'];
                }
                
                // variable concatenated between two strings
                if ($tokens[$next-1]['content'] == "." && $tokens[$next+1]['content'] == ".") {
                    $others[] = $token['content'];
                }
            
                // object var
                if ($tokens[$next+1]['content'] == "->") {
                    $others[] = $token['content'];
                }
                
                // comparing value to a variable
                if (in_array($tokens[$next+2]['type'], $comparisons)) {
                    $others[] = $token['content'];
                }

                                if ($tokens[$next-1]['content'] == "(" && $tokens[$next-3]['type'] == "T_FOREACH") {
                    $others[] = $token['content'];
                }

                //@mkdir(dirname($image['orig']), 0777, true);
                                if ($tokens[$next-1]['content'] == "(" && $tokens[$next+1]['content'] == "[") {
                    $others[] = $token['content'];
                }

                //rename($_FILES['image_file']['tmp_name'][$k], $image['orig']);
                if (($tokens[$next-1]['content'] == "," || $tokens[$next-2]['content'] == ",") && $tokens[$next+1]['content'] == "[") {
                       $others[] = $token['content'];
                                }
                
                //$news['published'] = 1;
                if ($tokens[$next+1]['content'] == "[" && $tokens[$next+2]['type'] == "T_CONSTANT_ENCAPSED_STRING" && ($tokens[$next+3]['content'] == "]" || $tokens[$next+2]['content'] == "=")) {
                       $others[] = $token['content'];
                                }
                            
                // $first[$second]
                if ($tokens[$next-1]['content'] == "[" && $tokens[$next+1]['content'] == "]") {
                    $others[] = $token['content'];
                }

                // [$portal_id."_".$old_id]
                if ($tokens[$next-2]['type'] == "T_CONSTANT_ENCAPSED_STRING" && $tokens[$next-1]['content'] == ".") {
                    $others[] = $token['content'];
                }

                // $i++, $i--, ++$i, --$i
                if ($tokens[$next+1]['type'] == "T_INC" || $tokens[$next-1]['type'] == "T_INC" || $tokens[$next+1]['type'] == "T_DEC" || $tokens[$next-1]['type'] == "T_DEC") {
                    $others[] = $token['content'];    
                }
                
    
            } // end if
        } // end for

        // output warnings for unused variables
        foreach ($write_vars as $write) {
            $write = explode("|", $write);
            if(!in_array($write[0], array_unique($reads)) && !in_array($write[0], $globals) && !in_array($write[0], $ignored_vars) && !in_array($write[0], $others)) {
                 $phpcsFile->addWarning("Variable " . $write[0] ." is never used.", $write[1]);
            }
        }
        
    } //end process()

}//end class

?>