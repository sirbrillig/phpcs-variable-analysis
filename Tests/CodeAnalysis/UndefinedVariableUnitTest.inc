<?php

function function_without_param() {
    echo $var;
    echo "$var";
    echo "{$var}";
    $var = 'set the var';
    echo $var;
    echo "$var";
    echo "{$var}";
    return $var;
}

function function_with_param($param) {
    echo $param;
    echo "$param";
    echo "{$param}";
    $param = 'set the param';
    echo $param;
    echo "$param";
    echo "{$param}";
    return $param;
}

function function_with_default_defined_param($junk, $param = 12) {
    echo $param;
    echo "$param";
    echo "{$param}";
    $param = 'set the param';
    echo $param;
    echo "$param";
    echo "{$param}";
    return $param;
}

function function_with_default_undefined_param($junk, $param = null) {
    echo $param;
    echo "$param";
    echo "{$param}";
    $param = 'set the param';
    echo $param;
    echo "$param";
    echo "{$param}";
    return $param;
}
