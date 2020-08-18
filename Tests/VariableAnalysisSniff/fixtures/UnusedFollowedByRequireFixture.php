<?php
function require_file_function($param) { // unused variable $param
    $var = 'something'; // unused variable $var
    activate_code($data); // undefined variable $data
    require __DIR__ . '/views/my-view.php';
}

function require_once_file_function($param) { // unused variable $param
    $var = 'something'; // unused variable $var
    activate_code($data); // undefined variable $data
    require_once __DIR__ . '/views/my-view.php';
}

function include_file_function($param) { // unused variable $param
    $var = 'something'; // unused variable $var
    activate_code($data); // undefined variable $data
    include __DIR__ . '/views/my-view.php';
}

function include_once_file_function($param) { // unused variable $param
    $var = 'something'; // unused variable $var
    activate_code($data); // undefined variable $data
    include_once __DIR__ . '/views/my-view.php';
}
