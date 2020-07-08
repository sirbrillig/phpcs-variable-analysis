<?php

// Issue #111.
function foo() {
    $var = 'abc'; // unused variable $var
    $var = 'def'; // unused variable $var

    $var2  = 'def'; // unused variable $var2
    $var2 .= 'ghi'; // unused variable $var2

    $var3  = 10; // unused variable $var3
    $var3 += 20; // unused variable $var3

    $var4 = 20;
    $var4 += 20;
    echo $var4;
}

// Safeguard that this change doesn't influence (not) reporting on assignments to parameters passed by reference.
function bar(&$param) {
  $param .= 'foo';
}
