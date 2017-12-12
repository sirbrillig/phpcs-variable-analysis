<?php

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

class ClassWithLateStaticBinding {
    static function method_with_late_static_binding($param) {
        static::some_method($param);
        static::some_method($var);
        static::some_method(static::CONSTANT, $param);
    }
}
