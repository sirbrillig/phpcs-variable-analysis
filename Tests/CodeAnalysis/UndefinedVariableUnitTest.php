<?php
/**
 * Unit test class for the UndefinedVariable sniff.
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
 * Unit test class for the UndefinedVariable sniff.
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
class Generic_Tests_CodeAnalysis_UndefinedVariableUnitTest extends AbstractSniffUnitTest
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
        return array(
                //  function_without_param()
                4   => 1,  //  $var
                5   => 1,  //  $var
                6   => 1,  //  $var
                7   => 2,  //  $var $var2
                8   => 2,  //  $var $var2
                9   => 1,  //  $var
                10  => 1,  //  $var
                11  => 1,  //  $var
                12  => 1,  //  $var
                17  => 1,  //  $var2
                18  => 1,  //  $var2
                //  function_with_param()
                //    no warnings.
                //  function_with_default_defined_param()
                //    no warnings.
                //  function_with_default_undefined_param()
                //    unimplemented, skip for now
//                49  => 1,  //  $param
//                50  => 1,  //  $param
//                60  => 1,  //  $param
                //  function_with_global_var()
                63  => 1,  //  $var3
                //  function_with_undefined_foreach()
                68  => 1,  //  $array
                71  => 1,  //  $array
                74  => 1,  //  $array
                77  => 1,  //  $array
                //  function_with_defined_foreach()
                //    no warnings.
                //  ClassWithoutMembers->method_without_param()
                100 => 1,  //  $var
                101 => 1,  //  $var
                102 => 1,  //  $var
                103 => 2,  //  $var $var2
                104 => 2,  //  $var $var2
                105 => 1,  //  $var
                106 => 1,  //  $var
                107 => 1,  //  $var
                108 => 1,  //  $var
                113 => 1,  //  $var2
                114 => 1,  //  $var2
                //  ClassWithoutMembers->method_with_param()
                //    no warnings.
                //  ClassWithoutMembers->method_with_member_var()
// TODO:                136 => 1,  //  $this->member_var
                //  ClassWithMembers->method_with_member_var()
// TODO:                145 => 1,  //  $this->no_such_member_var
                //  function_with_this_outside_class()
                150 => 1,  //  $this
                //  function_with_closure()
                158 => 1,  //  $outer_param
                160 => 1,  //  $outer_var
                161 => 1,  //  $outer_var2
                164 => 1,  //  $outer_var3
                167 => 1,  //  $inner_param
                169 => 1,  //  $outer_var2
                170 => 1,  //  $outer_var3
                171 => 1,  //  $inner_var
                176 => 1,  //  $outer_var3
                177 => 1,  //  $inner_param
                178 => 1,  //  $inner_var
                179 => 1,  //  $inner_var2
                //  function_with_return_by_reference_and_param()
                //    no warnings.
                //  function_with_static_var()
                188 => 1,  //  TODO: should not warn when implemented
                189 => 1,  //  TODO: should not warn when implemented
                191 => 1,  //  TODO: should not warn when implemented
                192 => 1,  //  $var
               );

    }//end getWarningList()


}//end class

?>