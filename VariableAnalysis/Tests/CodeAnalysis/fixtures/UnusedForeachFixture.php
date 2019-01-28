<?php

function loopWithUnusedKey() {
    $array = [];
    foreach ( $array as $key => $value ) {
        echo $value;
    }
}

function loopWithUnusedValue() {
    $array = [];
    foreach ( $array as $key => $value ) {
        echo $key;
    }
}

function loopWithUnusedKeyAndValue() {
    $array = [];
    foreach ( $array as $key => $value ) {
        echo 'hello';
    }
}
