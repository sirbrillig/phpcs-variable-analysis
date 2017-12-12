<?php
function &function_with_return_by_reference_and_param($param) {
    echo $param;
    return $param;
}

function function_with_static_var() {
    static $static1, $static_num = 12, $static_neg_num = -1.5, $static_string = 'abc', $static_string2 = "def", $static_define = MYDEFINE, $static_constant = MyClass::CONSTANT, $static2;
    static $static_heredoc = <<<END_OF_HEREDOC
this is an ugly but valid way to continue after a heredoc
END_OF_HEREDOC
        , $static3;
    static $static_nowdoc = <<<'END_OF_NOWDOC'
this is an ugly but valid way to continue after a nowdoc
END_OF_NOWDOC
        , $static4;
    echo $static1;
    echo $static_num;
    echo $static2;
    echo $var;
    echo $static_heredoc;
    echo $static3;
    echo $static_nowdoc;
    echo $static4;
}

function function_with_pass_by_reference_param(&$param) {
    echo $param;
}

function function_with_pass_by_reference_calls() {
    echo $matches;
    echo $needle;
    echo $haystack;
    preg_match('/(abc)/', 'defabcghi', $matches);
    preg_match($needle,   'defabcghi', $matches);
    preg_match('/(abc)/', $haystack,   $matches);
    echo $matches;
    echo $needle;
    echo $haystack;
    $stmt = 'whatever';
    $var1 = 'one';
    $var2 = 'two';
    echo $var1;
    echo $var2;
    echo $var3;
    maxdb_stmt_bind_result($stmt, $var1, $var2, $var3);
    echo $var1;
    echo $var2;
    echo $var3;
}

function function_with_pass_by_ref_assign_only_arg(&$return_value) {
    $return_value = 42;
}
