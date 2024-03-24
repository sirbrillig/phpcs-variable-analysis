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

function function_with_nested_destructure_using_short_list() {
    [
        $foo,
        [
            $bar,
        ]
    ] = [ 'foo', [ 'bar'  ]  ];
    [
        $baz, // unused
        [
            $bap, //unused
        ]
    ] = [ 'foo', [ 'bar'  ]  ];
    echo $foo;
    echo $bar;
}

function function_with_short_destructuring_assignment_and_array_arg(int $baz) {
	[$bar] = doSomething([$baz]);
	return $bar;
}

function function_with_destructuring_assignment_and_array_arg(int $baz) {
	list($bar) = doSomething([$baz]);
	return $bar;
}
