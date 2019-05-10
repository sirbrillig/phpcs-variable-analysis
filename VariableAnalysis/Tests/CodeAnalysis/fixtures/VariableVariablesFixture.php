<?php

function usedVariableVariableInReturn() {
    $foo = true; // this is used but it requires execution to know that
    $varName = 'foo';
    return $$varName;
}

function usedVariableVariableInEcho() {
    $foo = true; // this is used but it requires execution to know that
    $varName = 'foo';
    echo $$varName;
}

function usedVariableVariableInLeftAssignment() {
    $foo = true; // the below is assignment, not a read, so this should be a warning
    $marName = 'foo';
    $$marName = false;
}

function usedVariableVariableInRightAssignment() {
    $foo = true; // this is used but it requires execution to know that
    $varName = 'foo';
    $bar = $$varName;
    echo $bar;
}

function usedVariableVariableInEchoWithBraces() {
    $foo = true; // this is used but it requires execution to know that
    $varName = 'foo';
    echo ${$varName};
}

function usedVariableVariableInLeftAssignmentWithBraces() {
    $foo = true; // this is used but it requires execution to know that
    $varName = 'foo';
    ${$varName} = false;
}

function usedVariableVariableInEchoWithBracesInString() {
    $foo = true; // this is used but it requires execution to know that
    $varName = 'foo';
    echo "${$varName}";
}

function undefinedVariableVariableInEcho() {
    $foo = true; // this should be a warning
    echo $$varName; // this should be a warning
}

function usedVariableVariableTwoLevels() {
    $foo = 'hello'; // this is used but it requires execution to know that
    $varNameOne = 'foo'; // this is used but it requires execution to know that
    $varNameTwo = 'varNameOne';
    return $$$varNameTwo;
}
