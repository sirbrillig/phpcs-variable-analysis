<?php
/**
 * Unit test class for the VariableAnalysis sniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2007-2008 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Unit test class for the VariableAnalysis sniff.
 *
 * A sniff unit test checks a .inc file for expected violations of a single
 * coding standard. Expected errors and warnings are stored in this class.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2007-2008 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Tests_CodeAnalysis_VariableAnalysisUnitTest extends AbstractSniffUnitTest
{


    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getErrorList()
    {
        return array();

    }//end getErrorList()


    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getWarningList()
    {
        //  This is a maintainence nightmare.
        $base = 0;
        return array(
                //  function_without_param() line 3 (+3)
                ($base += 3)  => 0,
                ($base + 1)   => 1,  //  $var
                ($base + 2)   => 1,  //  $var
                ($base + 3)   => 1,  //  $var
                ($base + 4)   => 2,  //  $var $var2
                ($base + 5)   => 2,  //  $var $var2
                ($base + 6)   => 1,  //  $var
                ($base + 7)   => 1,  //  $var
                ($base + 8)   => 1,  //  $var
                ($base + 9)   => 1,  //  $var
                ($base + 14)  => 1,  //  $var2
                ($base + 15)  => 1,  //  $var2
                //  function_with_param() line 26 (+23)
                //    no warnings.
                ($base += 23) => 0,
                //  function_with_default_defined_param() line 37 (+11)
                ($base += 11) => 0,
                ($base + 0)   => 1,  //  $unused
                //  function_with_default_null_param() line 48 (+11)
                ($base += 11) => 0,
                ($base + 0)   => 1,  //  $unused
                //  function_with_global_var() line 59 (+11)
                ($base += 11) => 0,
                ($base + 1)   => 1,  //  $unused
                ($base + 4)   => 1,  //  $var3
                //  function_with_undefined_foreach() line 67 (+8)
                ($base += 8)  => 0,
                ($base + 1)   => 1,  //  $array
                ($base + 5)   => 1,  //  $array
                ($base + 9)   => 1,  //  $array
                ($base + 13)  => 1,  //  $array
                ($base + 17)  => 1, // 2,  //  $array, $element3
                ($base + 19)  => 1, // 2,  //  $array, $element4
                ($base + 21)  => 1, // 3,  //  $array, $key3, $value4
                ($base + 23)  => 1, // 3,  //  $array, $key4, $value4
                //  function_with_defined_foreach() line 94 (+27)
                ($base += 27) => 0,
                ($base + 18)  => 1,  //  $element3
                ($base + 20)  => 1,  //  $element4
                ($base + 22)  => 1, // 2,  //  $key3, $value4
                ($base + 24)  => 1, // 2,  //  $key4, $value4
                //  ClassWithoutMembers->method_without_param() line 123 (+29)
                ($base += 29) => 0,
                ($base + 1)   => 1,  //  $var
                ($base + 2)   => 1,  //  $var
                ($base + 3)   => 1,  //  $var
                ($base + 4)   => 2,  //  $var $var2
                ($base + 5)   => 2,  //  $var $var2
                ($base + 6)   => 1,  //  $var
                ($base + 7)   => 1,  //  $var
                ($base + 8)   => 1,  //  $var
                ($base + 9)   => 1,  //  $var
                ($base + 14)  => 1,  //  $var2
                ($base + 15)  => 1,  //  $var2
                //  ClassWithoutMembers->method_with_param() line 123 (+24)
                //    no warnings.
                ($base += 24) => 0,
                //  ClassWithoutMembers->method_with_member_var() line 135 (+12)
                ($base += 12) => 0,
// TODO:                136 => 1,  //  $this->member_var
                //  ClassWithMembers->method_with_member_var() line 143 (+8)
                ($base += 8)  => 0,
// TODO:                145 => 1,  //  $this->no_such_member_var
                //  function_with_this_outside_class() line 149 (+6)
                ($base += 6)  => 0,
                ($base + 1)   => 1,  //  $this
                //  function_with_closure() line 153 (+4)
                ($base += 4)  => 0,
                ($base + 5)   => 1,  //  $outer_param
                ($base + 7)   => 1,  //  $outer_var
                ($base + 8)   => 1,  //  $outer_var2
                ($base + 11)  => 1,  //  $outer_var3
                ($base + 14)  => 1,  //  $inner_param
                ($base + 16)  => 1,  //  $outer_var2
                ($base + 17)  => 1,  //  $outer_var3
                ($base + 18)  => 1,  //  $inner_var
                ($base + 23)  => 1,  //  $outer_var3
                ($base + 24)  => 1,  //  $inner_param
                ($base + 25)  => 1,  //  $inner_var
                ($base + 26)  => 1,  //  $inner_var2
                //  function_with_return_by_reference_and_param() line 182 (+29)
                //    no warnings.
                ($base += 29) => 0,
                //  function_with_static_var() line 187 (+5)
                ($base += 5)  => 0,
                ($base + 1)   => 1, // 5,  //  $static_neg_num, $static_string, $static_string2,
                                     //  $static_define, $static_constant
                ($base + 5)   => 1,  //  $var
                //  function_with_pass_by_reference_param() line 195 (+8)
                //    no warnings.
                ($base += 8)  => 0,
                //  function_with_pass_by_reference_calls() line 199 (+4)
                ($base += 4)  => 0,
                ($base + 1)   => 1,  //  $matches
                ($base + 2)   => 1,  //  $needle
                ($base + 3)   => 1,  //  $haystack
                ($base + 5)   => 1,  //  $needle
                ($base + 6)   => 1,  //  $haystack
                ($base + 8)   => 1,  //  $needle
                ($base + 9)   => 1,  //  $haystack
                //  function_with_try_catch() line 211 (+12)
                ($base += 12) => 0,
                ($base + 1)   => 1,  //  $e
                ($base + 5)   => 1,  //  $e
                //  ClassWithThisInsideClosure->method_with_this_inside_closure() line 227 (+16)
                ($base += 16) => 0,
                ($base + 3)   => 1,  //  $inner_param
                ($base + 4)   => 1,  //  $this
                ($base + 5)   => 1,  //  $this
                //  function_with_inline_assigns() line 263 (+12)
                ($base += 12) => 0,
                ($base + 1)   => 1,  //  $var
                ($base + 4)   => 1,  //  $var2
                //  function_with_global_redeclarations() line 274 (+11)
                ($base += 11) => 0,
                ($base + 5)   => 1,  //  $bound
                ($base + 12)  => 1,  //  $param
                ($base + 13)  => 1,  //  $static
                ($base + 14)  => 1,  //  $bound
                ($base + 15)  => 1,  //  $local
                ($base + 16)  => 1,  //  $e
                //  function_with_static_redeclarations() line 293 (+19)
                ($base += 19) => 0,
                ($base + 2)   => 1,  //  $static
                ($base + 5)   => 1,  //  $bound
                ($base + 12)  => 1,  //  $param
                ($base + 13)  => 1,  //  $static
                ($base + 14)  => 1,  //  $bound
                ($base + 15)  => 1,  //  $local
                ($base + 16)  => 1,  //  $e
                //  function_with_catch_redeclarations() line 312 (+19)
                //    no warnings.
                ($base += 19) => 0,
               );

    }//end getWarningList()


}//end class

?>