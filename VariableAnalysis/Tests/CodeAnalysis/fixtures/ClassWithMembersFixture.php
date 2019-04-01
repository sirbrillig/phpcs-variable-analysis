<?php

class ClassWithoutMembers {
    function method_without_param() {
        echo $var; // this should be a warning
        echo "xxx $var xxx"; // this should be a warning
        echo "xxx {$var} xxx"; // this should be a warning
        echo "xxx $var $var2 xxx"; // this should be a warning
        echo "xxx {$var} {$var2} xxx"; // this should be a warning
        func($var); // this should be a warning
        func(12, $var); // this should be a warning
        func($var, 12); // this should be a warning
        func(12, $var, 12); // this should be a warning
        $var = 'set the var';
        echo $var;
        echo "xxx $var xxx";
        echo "xxx {$var} xxx";
        echo "xxx $var $var2 xxx"; // this should be a warning
        echo "xxx {$var} {$var2} xxx"; // this should be a warning
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
    private $private_member_var;
    protected $protected_member_var;
    static $static_member_var;

    function method_with_member_var() {
        echo $this->member_var;
        echo $this->no_such_member_var;
        echo self::$static_member_var;
        echo self::$no_such_static_member_var;
        echo SomeOtherClass::$external_static_member_var;
    }
}

class ClassWithLateStaticBinding {
    public static $static_member_var;

    static function method_with_late_static_binding($param) {
        static::some_method($param);
        static::some_method($var); // should report a warning // this should be a warning
        static::some_method(static::CONSTANT, $param);
        $called_class = get_called_class();
        echo $called_class::$static_member_var;
    }
}

class ChildClassWithMembers extends ClassWithMembers {
    function method_with_parent_reference() {
        echo self::$static_member_var;
        echo parent::$no_such_static_member_var;
    }
}

class ClassWithAssignedMembers {
    public $member_var = 'hello';
    private $private_member_var = 'hi';
    protected $protected_member_var = 'foo';
    static $static_member_var = 'bar';

    function method_with_member_var() {
        echo $this->member_var;
        echo $this->no_such_member_var;
        echo self::$static_member_var;
        echo self::$no_such_static_member_var;
        echo SomeOtherClass::$external_static_member_var;
    }

    function method_with_static_assigned_member_var() {
        static $new_var = null;
        if ($new_var === null) {
            echo 'it is null';
        }
    }

    function method_with_static_assigned_var_inside_block() {
        $bool = true;
        if ($bool === true) {
            static $new_var = null;
            if ($new_var === null) {
                echo 'it is null';
            }
        }
    }
}
