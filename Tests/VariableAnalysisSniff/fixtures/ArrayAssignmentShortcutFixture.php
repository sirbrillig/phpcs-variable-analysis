<?php

function arrayAssignmentWithDefine() {
    $ar1 = [];
    $ar1[]= 'hello';
    echo $ar1;
}

function arrayAssignmentWithDefineWithoutRead() {
    $ar1 = [];
    $ar1[]= 'hello';
}

function arrayAssignmentWithDefineWithSpace() {
    $ar1 = [];
    $ar1 []= 'hello';
    echo $ar1;
}

function arrayAssignmentWithoutDefine() {
    $ar1[]= 'hello'; // should warn about undefined variable
    echo $ar1;
    $ar1[] = 'goodbye';
}

function arrayAssignmentWithoutDefineOrRead() {
    $ar1[]= 'hello'; // should warn about unused variable and undefined variable
    $foo = 'bar'; // should warn about unused variable
    echo $bar; // should warn about undefined variable
}
