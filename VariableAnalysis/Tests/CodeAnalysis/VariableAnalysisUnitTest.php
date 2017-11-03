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
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011-2012 Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
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
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011-2012 Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Tests_CodeAnalysis_VariableAnalysisUnitTest extends AbstractSniffUnitTest
{

    private function _getWarningAndErrorList() {
        //  This is a maintainence nightmare.
        //    Value of a line is either:
        //    - an int: the number of warnings.
        //    - an array: the number of warnings and number of errors.
        //    This is chosen because we mostly warn and because maintaining _two_
        //    separate lists of line numbers would drive me insane.
        //
        //  All the fiddling with $base is to make each line number relative
        //  to the line number of the function the line is in, which in turn
        //  is relative to the line number of the previous function.
        //
        //  This makes adding new tests only moderately painful rather than
        //  a total clusterfuck of alterations.
        $base = 0;
        return array(
                //  function_without_param() line (+3)
                ($base += 3)  => 0,
                ($base + 1)   => 1,  //  $var
                ($base + 2)   => 1,  //  $var
                ($base + 3)   => 1,  //  {$var}
                ($base + 4)   => 1,  //  ${var}
                ($base + 5)   => 2,  //  $var $var2
                ($base + 6)   => 2,  //  $var $var2
                ($base + 7)   => 1,  //  $var
                ($base + 8)   => 1,  //  $var
                ($base + 9)   => 1,  //  $var
                ($base + 10)  => 1,  //  $var
                ($base + 15)  => 1,  //  $var2
                ($base + 16)  => 1,  //  $var2
                //  function_with_param() line (+24)
                //    no warnings.
                ($base += 24) => 0,
                //  function_with_default_defined_param() line (+11)
                ($base += 11) => 0,
                ($base + 0)   => 1,  //  $unused
                //  function_with_default_null_param() line (+11)
                ($base += 11) => 0,
                ($base + 0)   => 1,  //  $unused
                //  function_with_global_var() line (+11)
                ($base += 11) => 0,
                ($base + 1)   => 1,  //  $unused
                ($base + 4)   => 1,  //  $var3
                //  function_with_undefined_foreach() line (+8)
                ($base += 8)  => 0,
                ($base + 1)   => 1,  //  $array
                ($base + 5)   => 1,  //  $array
                ($base + 9)   => 1,  //  $array
                ($base + 13)  => 1,  //  $array
                ($base + 17)  => 2,  //  $array, $element3
                ($base + 19)  => 2,  //  $array, $element4
                ($base + 21)  => 3,  //  $array, $key3, $value4
                ($base + 23)  => 3,  //  $array, $key4, $value4
                //  function_with_defined_foreach() line (+27)
                ($base += 27) => 0,
                ($base + 18)  => 1,  //  $element3
                ($base + 20)  => 1,  //  $element4
                ($base + 22)  => 2,  //  $key3, $value4
                ($base + 24)  => 2,  //  $key4, $value4
                //  ClassWithoutMembers->method_without_param() line (+29)
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
                //  ClassWithoutMembers->method_with_param() line (+24)
                //    no warnings.
                ($base += 24) => 0,
                //  ClassWithoutMembers->method_with_member_var() line (+12)
                //    no warnings.
                //    We can't/don't inspect the class inheritence so we can't
                //    determine that these are undeclared:
                //      $this->member_var
                //      self::$static_member_var
                ($base += 12) => 0,
                //  ClassWithMembers->method_with_member_var() line (+10)
                //    no warnings.
                //    We can't/don't inspect the class inheritence so we can't
                //    determine that these are undeclared:
                //      $this->no_such_member_var
                //      self::$no_such_static_member_var
                ($base += 10)  => 0,
                //  function_with_this_outside_class() line (+9)
                ($base += 9)  => 0,
                ($base + 1)   => 1,  //  $this
                //  function_with_static_members_outside_class() line (+4)
                ($base += 4)  => 0,
                ($base + 2)   => array(0, 1),  //  self::$whatever
                //  function_with_late_static_binding_outside_class() line (+5)
                ($base += 5)  => 0,
                ($base + 1)   => array(0, 1),  //  static::$whatever
                //  function_with_closure() line (+4)
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
                //  function_with_return_by_reference_and_param() line (+29)
                //    no warnings.
                ($base += 29) => 0,
                //  function_with_static_var() line (+5)
                ($base += 5)  => 0,
                ($base + 1)   => 5,  //  $static_neg_num, $static_string, $static_string2,
                                     //  $static_define, $static_constant
                ($base + 13)  => 1,  //  $var
                //  function_with_pass_by_reference_param() line (+20)
                //    no warnings.
                ($base += 20) => 0,
                //  function_with_pass_by_reference_calls() line (+4)
                ($base += 4)  => 0,
                ($base + 1)   => 1,  //  $matches
                ($base + 2)   => 1,  //  $needle
                ($base + 3)   => 1,  //  $haystack
                ($base + 5)   => 1,  //  $needle
                ($base + 6)   => 1,  //  $haystack
                ($base + 8)   => 1,  //  $needle
                ($base + 9)   => 1,  //  $haystack
                ($base + 15)  => 1,  //  $var3
                //  function_with_try_catch() line (+22)
                ($base += 22) => 0,
                ($base + 1)   => 1,  //  $e
                ($base + 5)   => 1,  //  $e
                //  ClassWithThisInsideClosure->method_with_this_inside_closure() line (+16)
                ($base += 16) => 0,
                ($base + 3)   => 1,  //  $inner_param
                ($base + 4)   => 1,  //  $this
                ($base + 5)   => 1,  //  $this
                //  ClassWithSelfInsideClosure->method_with_self_inside_closure() line (+15)
                ($base += 15) => 0,
                ($base + 3)   => array(0, 1),  //  $self::$static_member
                //  function_with_inline_assigns() line (+9)
                ($base += 9)  => 0,
                ($base + 1)   => 1,  //  $var
                ($base + 4)   => 1,  //  $var2
                //  function_with_global_redeclarations() line (+11)
                ($base += 11) => 0,
                ($base + 5)   => 1,  //  $bound
                ($base + 12)  => 1,  //  $param
                ($base + 13)  => 1,  //  $static
                ($base + 14)  => 1,  //  $bound
                ($base + 15)  => 1,  //  $local
                ($base + 16)  => 1,  //  $e
                //  function_with_static_redeclarations() line (+19)
                ($base += 19) => 0,
                ($base + 2)   => 1,  //  $static
                ($base + 5)   => 1,  //  $bound
                ($base + 12)  => 1,  //  $param
                ($base + 13)  => 1,  //  $static
                ($base + 14)  => 1,  //  $bound
                ($base + 15)  => 1,  //  $local
                ($base + 16)  => 1,  //  $e
                //  function_with_catch_redeclarations() line (+19)
                //    no warnings.
                ($base += 19) => 0,
                //  function_with_superglobals() line (+11)
                ($base += 11) => 0,
                ($base + 11)  => 1,  //  $var
                //  function_with_heredoc() line (+14)
                ($base += 14) => 0,
                ($base + 6)   => 1,  //  $var2
                ($base + 7)   => 1,  //  {$var2}
                ($base + 8)   => 1,  //  ${var2}
                ($base + 10)  => 1,  //  \\$var2
                //  method_with_symbolic_ref_property() line (+17)
                ($base += 17) => 0,
                ($base + 5)   => 1,  //  $undefined_property
                ($base + 6)   => 1,  //  $undefined_property
                //  method_with_symbolic_ref_method() line (+8)
                ($base += 10) => 0,
                ($base + 5)   => 1,  //  $undefined_method
                ($base + 6)   => 1,  //  $undefined_method
                //  function_with_pass_by_ref_assign_only_arg() line (+11)
                //    no warnings.
                ($base += 11) => 0,
                //  method_with_late_static_binding() line (+5)
                ($base += 5)  => 0,
                ($base + 2)   => 1,  //  $var
                //  function_with_single_quoted_compact() line (+7)
                ($base += 7)  => 0,
                ($base + 0)   => 1,  //  unused $param2
                ($base + 5)   => 1,  //  undefined $var3
                ($base + 8)   => 1,  //  undefined $var5
                //  function_with_expression_compact() line (+12)
                ($base += 12)  => 0,
                ($base + 0)   => 1,  //  unused $param2
                ($base + 5)   => 1,  //  unused $var7
                ($base + 9)   => 1,  //  undefined $var3
                ($base + 12)  => 1,  //  undefined $var5
               );
    }//end _getWarningAndErrorList()

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
        $errorList = array();
        foreach ($this->_getWarningAndErrorList() as $line => $incidents) {
            $errors = null;
            if (is_array($incidents)) {
                list ($warnings, $errors) = $incidents;
            }
            if (!empty($errors)) {
                $errorList[$line] = $errors;
            }
        }
        return $errorList;
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
        $warningList = array();
        foreach ($this->_getWarningAndErrorList() as $line => $incidents) {
            $warnings = null;
            if (is_array($incidents)) {
                list ($warnings, $errors) = $incidents;
            } else {
                $warnings = $incidents;
            }
            if (!empty($warnings)) {
                $warningList[$line] = $warnings;
            }
        }
        return $warningList;
  }//end getWarningList()


}//end class

?>