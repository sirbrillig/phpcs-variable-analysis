<?php

add_action( 'delete_post_meta', 'check_thumbnail_updated_post_meta', -1000, 3 );
function check_thumbnail_updated_post_meta(
    $meta_id,
    $post_id,
    $meta_key,
    $foobar
) {
    echo $post_id;
    echo $meta_key;
}

function inner_function() {
    $foo = function(
        $meta_id,
        $post_id,
        $meta_key,
        $foobar
    ) {
        echo $post_id;
        echo $meta_key;
    };
    $foo();
}

// The following line should report an unused variable (unused after used)
function function_with_one_unused_param($used, $used_two, $unused_three) {
    echo $used;
    echo $used_two;
}

// The following line should report an unused variable (unused after used)
function function_with_local_and_unused_params($used, $used_two, $unused_three) {
    $foobar = 'hello';
    echo $used;
    echo $foobar;
    echo $used_two;
}