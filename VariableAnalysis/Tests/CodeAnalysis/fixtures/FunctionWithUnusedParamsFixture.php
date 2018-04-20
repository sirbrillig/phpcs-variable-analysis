<?php

// The following line should report an unused variable
function function_with_first_unused_param($unused, $param) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

// The following line should report an unused variable
function function_with_second_unused_param($param, $unused) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_second_unused_param_ignored($param, $ignored) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

// The following line should report an unused variable
function function_with_all_unused_params($unused, $unused_two) {
    $param = 'hello';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_no_unused_params($param, $param_two) {
    echo $param;
    echo $param_two;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_try_catch_and_unused_exception() {
    try {
        doAThing();
    } catch (Exception $unused_param) {
        echo "unused";
    }
}

function function_with_multi_line_unused_params(
    $unused,
    $unused_two
) {
    $param = 'hello';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}
