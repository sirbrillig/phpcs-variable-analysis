<?php

function v() {
}

function function_without_param() {
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

function function_with_default_defined_param($junk, $param = 12) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_default_undefined_param($junk, $param = null) {
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
    global $var, $var2;

    echo $var;
    echo $var3;
    return $var2;
}

function function_with_foreach() {
    foreach ($array as $element1) {
        echo $element1;
    }
    foreach ($array as &$element2) {
        echo $element2;
    }
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
}
