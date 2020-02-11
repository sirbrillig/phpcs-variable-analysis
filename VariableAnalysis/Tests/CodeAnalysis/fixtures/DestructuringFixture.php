<?php
function function_with_destructuring_assignment() {
    [$a, $b] = [1, 2];
    [$c, $d] = [3, 4]; // unused
    echo $a;
    echo $b;
    echo $c;
}

function function_with_destructuring_assignment_using_list() {
    list( $a, $b ) = [1, 2];
    list /* comment */ ( $c, $d ) = [3, 4]; // unused
    echo $a;
    echo $b;
    echo $c;
}

function function_with_nested_destructure_using_list() {
    list(
        $foo,
        list(
            $bar,
        )
    ) = [ 'foo', [ 'bar'  ]  ];
    list(
        $baz, // unused
        list(
            $bap, //unused
        )
    ) = [ 'foo', [ 'bar'  ]  ];
    echo $foo;
    echo $bar;
}
