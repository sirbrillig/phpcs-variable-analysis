<?php

// Issue #111.
function foo() {
    $var = 'abc';
    $var = 'def';

    $var2  = 'def';
    $var2 .= 'ghi';

    $var3  = 10;
    $var3 += 20;
}

// Safeguard that this change doesn't influence (not) reporting on assignments to parameters passed by reference.
function bar(&$param) {
	$param .= 'foo';
}
