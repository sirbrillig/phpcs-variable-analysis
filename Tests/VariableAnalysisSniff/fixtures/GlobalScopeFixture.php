<?php

$post = $data['post']; // Undefined variable $data, unused variable $post
$name = 'friend';
$place = 'faerie'; // unused variable $place

echo $name;
echo $activity; // undefined variable $activity

function thisIsAFunction() {
    echo $whatever; // undefined variable $whatever
}

$color = 'blue'; // used, but only by a global declaration

function anotherFunction() {
    global $color;
    echo $color;
}
