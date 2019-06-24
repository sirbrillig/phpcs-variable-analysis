<?php

function send_vars_to_method( $object, $data ) {
    $some_number = 4;
    $some_string = 'hello';
    echo $undefined_data; // should be a warning
    $new_data = $object->transform_data( $data );
    $object->continue_things( get_defined_vars() );
}

function send_vars_to_method_with_global( $object, $data ) {
    global $global_data;
    $new_data = $object->transform_data( $data );
    $object->continue_things( get_defined_vars() );
}

function send_vars_to_method_with_scope_import( $object, $data ) {
    $unused_data = 42; // should be a warning
    $imported_data = 76;
    return array_map( function( $datum ) use ( $object, $imported_data ) {
        $new_data = $object->transform_data( $datum );
        echo $undefined_data; // should be a warning
        $object->continue_things( get_defined_vars() );
    }, $data );
}

function send_var_with_var_named_get_defined_vars( $object, $data ) {
    $get_defined_vars = 'hi';
    $new_data = $object->transform_data( $data ); // should be a warning
    $object->continue_things( $get_defined_vars );
}
