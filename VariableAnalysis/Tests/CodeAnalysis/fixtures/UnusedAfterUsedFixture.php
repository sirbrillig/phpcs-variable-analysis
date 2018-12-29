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
    }
    $foo();
}
