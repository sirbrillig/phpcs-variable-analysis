<?php

function loopWithUnusedKey() {
    $array = [];
    foreach ( $array as $key => $value ) { // maybe marked as unused
        echo $value;
        $unused = 'foobar'; // should always be marked as unused
        echo $undefined; // should always be marked as undefined
    }
}

function loopWithUnusedValue() {
    $array = [];
    foreach ( $array as $key => $value ) { // maybe marked as unused
        echo $key;
        $unused = 'foobar'; // should always be marked as unused
        echo $undefined; // should always be marked as undefined
    }
}

function loopWithUnusedKeyAndValue() {
    $array = [];
    foreach ( $array as $key => $value ) { // maybe marked as unused
        echo 'hello';
        $unused = 'foobar'; // should always be marked as unused
        echo $undefined; // should always be marked as undefined
    }
}

function loopWithUnusedValueOnly() {
    $array = [];
    foreach ( $array as $value ) { // maybe marked as unused
        $unused = 'foobar'; // should always be marked as unused
        echo $undefined; // should always be marked as undefined
    }
}
