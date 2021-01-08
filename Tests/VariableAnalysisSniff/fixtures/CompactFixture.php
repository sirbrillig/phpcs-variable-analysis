<?php
function function_with_literal_compact($param1, $param2, $param3, $param4) { // unused variable param2
    $var1 = 'value1';
    $var2 = 'value2';
    $var4 = 'value4';
    $squish = compact('var1');
    $squish = compact('var3'); // undefined variable var3
    $squish = compact('param1');
    $squish = compact('var2', /*comment*/ 'param3');
    $squish = compact(array('var4'), array('param4', 'var5')); // undefined variable var5
    echo $squish;
}

function function_with_expression_compact($param1, $param2, $param3, $param4) { // unused variable param2
    $var1 = "value1";
    $var2 = "value2";
    $var4 = "value4";
    $var6 = "value6";
    $var7 = "value7"; // unused variale var7 (not actually unused but it's hard to detect that line 28 uses it)
    $var8 = "value8";
    $var9 = "value9";
    $squish = compact("var1");
    $squish = compact("var3"/*comment*/ ); // undefined variable var3
    $squish = compact("param1");
    $squish = compact("var2", "param3");
    $squish = compact(array("var4"), array("param4", /*comment*/ "var5")); // undefined variable var5
    $squish = compact($var6);
    $squish = compact("var" . "7");
    $squish = compact("blah $var8");
    $squish = compact("$var9");
    echo $squish;
}

function foo() {
    $a = 'Hello';
    $c = compact( $a, $b ); // Unused variable c and undefined variable b
}
