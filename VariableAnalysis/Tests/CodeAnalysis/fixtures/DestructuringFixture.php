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
    list( $c, $d ) = [3, 4]; // unused
    echo $a;
    echo $b;
    echo $c;
}
