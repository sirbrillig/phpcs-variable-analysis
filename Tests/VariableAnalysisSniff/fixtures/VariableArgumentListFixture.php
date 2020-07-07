<?php
function functionWithVariableArgumentsAlone(...$rest) {
  echo $rest[0];
}

function functionWithVariableArgumentsAloneUnused(...$rest) { // unused variable $rest
  echo "Hello";
}

function functionWithVariableArgumentsAfterOther($first, ...$rest) {
  echo $first;
  echo $rest[0];
}

function functionWithVariableArgumentsUnusedAfterOther($first, ...$rest) { // unused variable $rest
  echo $first;
}

function functionWithVariableArgumentsAfterOtherUnused($first, ...$rest) { // unused variable $first (but before used)
  echo $rest[0];
}

function functionWithVariableArgumentsUnusedAfterOtherUnused($first, ...$rest) { // unused variable $rest and unused variable $first
  echo $first;
}

function functionWithArrowVariableArgumentsAlone($subject) {
  $arrowFunc = fn(...$foo) => $foo[0] . $subject;
  echo $arrowFunc('hello');
}

function functionWithArrowVariableArgumentsAloneUnused($subject) {
  $arrowFunc = fn(...$foo) => $subject; // unused variable $foo
  echo $arrowFunc('hello');
}

function functionWithArrowVariableArgumentsUnusedAfterOther() {
  $arrowFunc = fn($first, ...$foo) => $first; // unused variable $foo
  echo $arrowFunc('hello');
}

function functionWithArrowVariableArgumentsAfterOtherUnused() {
  $arrowFunc = fn($first, ...$foo) => $foo[0]; // unused variable $first (but before used)
  echo $arrowFunc('hello');
}
