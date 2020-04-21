<?php
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
