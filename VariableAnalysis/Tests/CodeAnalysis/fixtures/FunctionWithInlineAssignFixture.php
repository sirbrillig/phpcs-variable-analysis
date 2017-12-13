<?php
function function_with_inline_assigns() {
    echo $var;
    ($var = 12) && $var;
    echo $var;
    echo $var2;
    while ($var2 = whatever()) {
        echo $var2;
    }
    echo $var2;
}

function function_with_assigns_and_usage() {
    doSomething(
        $foo = 'bar',
        $foo
    );
}
