<?php
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

function function_with_self_in_closure() {
    return function() {
        return self::$foobar; // should be an error
    }
}

function function_with_this_in_closure() {
    return function() {
        return $this->$foobar; // should be an error
    }
}

function function_with_static_in_closure() {
    return function() {
        return static::$foobar; // should be an error
    }
}

class ClassWithStaticInsideClosure {
    static $static_member;

    function method_with_self_inside_closure() {
        echo static::$static_member;
        array_map(function () {
                echo static::$static_member;
            }, array());
        echo static::$static_member;
    }
}
