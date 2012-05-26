<?php

function function_without_param() {
    echo $var;
    echo "xxx $var xxx";
    echo "xxx {$var} xxx";
    echo "xxx ${var} xxx";
    echo "xxx $var $var2 xxx";
    echo "xxx {$var} {$var2} xxx";
    func($var);
    func(12, $var);
    func($var, 12);
    func(12, $var, 12);
    $var = 'set the var';
    echo $var;
    echo "xxx $var xxx";
    echo "xxx {$var} xxx";
    echo "xxx $var $var2 xxx";
    echo "xxx {$var} {$var2} xxx";
    func($var);
    func(12, $var);
    func($var, 12);
    func(12, $var, 12);
    return $var;
}

function function_with_param($param) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_default_defined_param($unused, $param = 12) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_default_null_param($unused, $param = null) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_global_var() {
    global $var, $var2, $unused;

    echo $var;
    echo $var3;
    return $var2;
}

function function_with_undefined_foreach() {
    foreach ($array as $element1) {
        echo $element1;
    }
    echo $element1;
    foreach ($array as &$element2) {
        echo $element2;
    }
    echo $element2;
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    echo "$key1 => $value1\n";
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
    echo "$key2 => $value2\n";
    foreach ($array as $element3) {
    }
    foreach ($array as &$element4) {
    }
    foreach ($array as $key3 => $value3) {
    }
    foreach ($array as $key4 => &$value4) {
    }
}

function function_with_defined_foreach() {
    $array = array();
    foreach ($array as $element1) {
        echo $element1;
    }
    echo $element1;
    foreach ($array as &$element2) {
        echo $element2;
    }
    echo $element2;
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    echo "$key1 => $value1\n";
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
    echo "$key2 => $value2\n";
    foreach ($array as $element3) {
    }
    foreach ($array as &$element4) {
    }
    foreach ($array as $key3 => $value3) {
    }
    foreach ($array as $key4 => &$value4) {
    }
}

class ClassWithoutMembers {
    function method_without_param() {
        echo $var;
        echo "xxx $var xxx";
        echo "xxx {$var} xxx";
        echo "xxx $var $var2 xxx";
        echo "xxx {$var} {$var2} xxx";
        func($var);
        func(12, $var);
        func($var, 12);
        func(12, $var, 12);
        $var = 'set the var';
        echo $var;
        echo "xxx $var xxx";
        echo "xxx {$var} xxx";
        echo "xxx $var $var2 xxx";
        echo "xxx {$var} {$var2} xxx";
        func($var);
        func(12, $var);
        func($var, 12);
        func(12, $var, 12);
        $this->method_with_member_var();
        return $var;
    }

    function method_with_param($param) {
        echo $param;
        echo "xxx $param xxx";
        echo "xxx {$param} xxx";
        $param = 'set the param';
        echo $param;
        echo "xxx $param xxx";
        echo "xxx {$param} xxx";
        $this->method_with_member_var();
        return $param;
    }

    function method_with_member_var() {
        echo $this->member_var;
        echo self::$static_member_var;
    }
}

class ClassWithMembers {
    public $member_var;
    static $static_member_var;

    function method_with_member_var() {
        echo $this->member_var;
        echo $this->no_such_member_var;
        echo self::$static_member_var;
        echo self::$no_such_static_member_var;
        echo SomeOtherClass::$external_static_member_var;
    }
}

function function_with_this_outside_class() {
    return $this->whatever();
}

function function_with_static_members_outside_class() {
    echo SomeOtherClass::$external_static_member_var;
    return self::$whatever;
}

function function_with_late_static_binding_outside_class() {
    echo static::$whatever;
}

function function_with_closure($outer_param) {
    $outer_var  = 1;
    $outer_var2 = 2;
    array_map(function ($inner_param) {
            $inner_var = 1;
            echo $outer_param;
            echo $inner_param;
            echo $outer_var;
            echo $outer_var2;
            echo $inner_var;
        }, array());
    array_map(function () use ($outer_var, $outer_var3, &$outer_param) {
            $inner_var2 = 2;
            echo $outer_param;
            echo $inner_param;
            echo $outer_var;
            echo $outer_var2;
            echo $outer_var3;
            echo $inner_var;
            echo $inner_var2;
        }, array());
    echo $outer_var;
    echo $outer_var2;
    echo $outer_var3;
    echo $inner_param;
    echo $inner_var;
    echo $inner_var2;
}

function &function_with_return_by_reference_and_param($param) {
    echo $param;
    return $param;
}

function function_with_static_var() {
    static $static1, $static_num = 12, $static_neg_num = -1.5, $static_string = 'abc', $static_string2 = "def", $static_define = MYDEFINE, $static_constant = MyClass::CONSTANT, $static2;
    static $static_heredoc = <<<END_OF_HEREDOC
this is an ugly but valid way to continue after a heredoc
END_OF_HEREDOC
        , $static3;
    static $static_nowdoc = <<<'END_OF_NOWDOC'
this is an ugly but valid way to continue after a nowdoc
END_OF_NOWDOC
        , $static4;
    echo $static1;
    echo $static_num;
    echo $static2;
    echo $var;
    echo $static_heredoc;
    echo $static3;
    echo $static_nowdoc;
    echo $static4;
}

function function_with_pass_by_reference_param(&$param) {
    echo $param;
}

function function_with_pass_by_reference_calls() {
    echo $matches;
    echo $needle;
    echo $haystack;
    preg_match('/(abc)/', 'defabcghi', $matches);
    preg_match($needle,   'defabcghi', $matches);
    preg_match('/(abc)/', $haystack,   $matches);
    echo $matches;
    echo $needle;
    echo $haystack;
    $stmt = 'whatever';
    $var1 = 'one';
    $var2 = 'two';
    echo $var1;
    echo $var2;
    echo $var3;
    maxdb_stmt_bind_result($stmt, $var1, $var2, $var3);
    echo $var1;
    echo $var2;
    echo $var3;
}

function function_with_try_catch() {
    echo $e;
    $var = 1;
    echo $var;
    try {
        echo $e;
        echo $var;
    } catch (Exception $e) {
        echo $e;
        echo $var;
    }
    echo $e;
    echo $var;
}

class ClassWithThisInsideClosure {
    function method_with_this_inside_closure() {
        echo $this;
        echo "$this";
        array_map(function ($inner_param) {
                echo $this;
                echo "$this";
            }, array());
        echo $this;
        echo "$this";
    }
}

class ClassWithSelfInsideClosure {
    static $static_member;

    function method_with_self_inside_closure() {
        echo self::$static_member;
        array_map(function () {
                echo self::$static_member;
            }, array());
        echo self::$static_member;
    }
}

function function_with_inline_assigns() {
    echo $var;
    ($var = 12) && $var;
    echo $var;
    echo $var2;
    while ($var2 = whatever()) {
        echo $var2;
    }
    echo $var2;
}

function function_with_global_redeclarations($param) {
    global $global;
    static $static;
    $bound = 12;
    $local = function () use ($bound) {
            global $bound;
            echo $bound;
        };
    try {
    } catch (Exception $e) {
    }
    echo "$param $global $static $bound $local $e\n"; // Stop unused var warnings.
    global $param;
    global $static;
    global $bound;
    global $local;
    global $e;
}

function function_with_static_redeclarations($param) {
    global $global;
    static $static, $static;
    $bound = 12;
    $local = function () use ($bound) {
            static$bound;
            echo $bound;
        };
    try {
    } catch (Exception $e) {
    }
    echo "$param $global $static $bound $local $e\n"; // Stop unused var warnings.
    static $param;
    static $static;
    static $bound;
    static $local;
    static $e;
}

function function_with_catch_redeclarations() {
    try {
    } catch (Exception $e) {
        echo $e;
    }
    try {
    } catch (Exception $e) {
        echo $e;
    }
}

function function_with_superglobals() {
    echo print_r($GLOBALS, true);
    echo print_r($_SERVER, true);
    echo print_r($_GET, true);
    echo print_r($_POST, true);
    echo print_r($_FILES, true);
    echo print_r($_COOKIE, true);
    echo print_r($_SESSION, true);
    echo print_r($_REQUEST, true);
    echo print_r($_ENV, true);
    echo "{$GLOBALS['whatever']}";
    echo "{$GLOBALS['whatever']} $var";
}

function function_with_heredoc() {
    $var = 10;
    echo <<<END_OF_TEXT
$var
{$var}
${var}
$var2
{$var2}
${var2}
\$var2
\\$var2
END_OF_TEXT;
}

class ClassWithSymbolicRefProperty {
    public $my_property;

    function method_with_symbolic_ref_property() {
        $properties = array('my_property');
        foreach ($properties as $property) {
            $this->$property = 'some value';
            $this -> $property = 'some value';
            $this->$undefined_property = 'some value';
            $this -> $undefined_property = 'some value';
        }
    }

    function method_with_symbolic_ref_method() {
        $methods = array('method_with_symbolic_ref_property');
        foreach ($methods as $method) {
            $this->$method();
            $this -> $method();
            $this->$undefined_method();
            $this -> $undefined_method();
        }
    }
}

function function_with_pass_by_ref_assign_only_arg(&$return_value) {
    $return_value = 42;
}

class ClassWithLateStaticBinding {
    static function method_with_late_static_binding($param) {
        static::some_method($param);
        static::some_method($var);
        static::some_method(static::CONSTANT, $param);
    }
}

function function_with_literal_compact($param1, $param2, $param3, $param4) {
    $var1 = 'value1';
    $var2 = 'value2';
    $var4 = 'value4';
    $squish = compact('var1');
    $squish = compact('var3');
    $squish = compact('param1');
    $squish = compact('var2', 'param3');
    $squish = compact(array('var4'), array('param4', 'var5'));
    echo $squish;
}

function function_with_expression_compact($param1, $param2, $param3, $param4) {
    $var1 = "value1";
    $var2 = "value2";
    $var4 = "value4";
    $var6 = "value6";
    $var7 = "value7";
    $var8 = "value8";
    $var9 = "value9";
    $squish = compact("var1");
    $squish = compact("var3");
    $squish = compact("param1");
    $squish = compact("var2", "param3");
    $squish = compact(array("var4"), array("param4", "var5"));
    $squish = compact($var6);
    $squish = compact("var" . "7");
    $squish = compact("blah $var8");
    $squish = compact("$var9");
    echo $squish;
}
