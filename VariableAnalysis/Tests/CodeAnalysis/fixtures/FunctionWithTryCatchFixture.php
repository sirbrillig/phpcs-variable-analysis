<?php
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
