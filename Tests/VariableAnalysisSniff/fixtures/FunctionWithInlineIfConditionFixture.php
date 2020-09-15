<?php

function normalIfCondition($first, $second) {
  $name = 'human';
  if ($first)
    $words = "hello {$name}";
  elseif ($second)
    $words = "bye {$name}";
  echo $words;
}

function undefinedInsideIfCondition($second) {
  $name = 'human';
  if ($first) // undefined variable $first
    $words = "hello {$name}";
  elseif ($second)
    $words = "bye {$name}";
  echo $words;
}

function undefinedInsideElseCondition($first) {
  $name = 'human';
  if ($first)
    $words = "hello {$name}";
  elseif ($second) // undefined variable $second
    $words = "bye {$name}";
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseCondition($first) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $second = true; // unused variable $second
  elseif ($second) // undefined variable $second
    $words = "bye {$name}";
  echo $words;
}

function unusedInsideFirstBlock($first, $second) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $unused = true; // unused variable $unused
  elseif ($second)
    $words = "bye {$name}";
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseIfBlock($first) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $second = true; // unused variable $second
  elseif ($name)
    echo $second; // undefined variable $second
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseBlock($first) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $second = true; // unused variable $second
  else
    echo $second; // undefined variable $second
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseBlockInsideAnotherIf($first) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $second = true; // unused variable $second
  else
    if ($name)
      echo $second; // undefined variable $second
  echo $words;
}

function definedInsideElseIfBlockUndefinedInsideElseBlock($first) {
  $name = 'human';
  if ($first)
    $words = "hello {$name}";
  elseif ($name)
    $second = true; // unused variable $second
  else
    echo $second; // undefined variable $second
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideUnconnectedElseCondition($first) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $second = true;
  elseif ($name)
    $words = "bye {$name}";
  if ($first)
    $second = true;
  elseif ($second)
    $words = "bye {$name}";
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideSecondCondition($first) {
  $name = 'human';
  $words = "hello {$name}";
  if ($first)
    $second = true;
  if ($second)
    $words = "bye {$name}";
  echo $words;
}

function ifConditionWithPossibleDefinition($first) {
  if ($first)
    $name = 'person';
  echo $name;
}

function ifConditionWithPossibleUse($first) {
  $name = 'person';
  if ($first)
    echo $name;
}

function ifConditionWithUndefinedArrayAssignment($first) {
  if ($first)
    $things[] = 'person'; // undefined array variable
  return $things;
}

function loopAndPushWithUndefinedArray($parts) {
  while ($part = array_shift($parts))
    $suggestions[] = $part; // undefined array variable
  return $suggestions;
}
