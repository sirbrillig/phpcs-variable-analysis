<?php

function normalIfCondition($first, $second) {
  $name = 'human';
  if ($first) {
    $words = "hello {$name}";
  } elseif ($second) {
    $words = "bye {$name}";
  }
  echo $words;
}

function undefinedInsideIfCondition($second) {
  $name = 'human';
  if ($first) { // undefined variable $first
    $words = "hello {$name}";
  } elseif ($second) {
    $words = "bye {$name}";
  }
  echo $words;
}

function undefinedInsideElseCondition($first) {
  $name = 'human';
  if ($first) {
    $words = "hello {$name}";
  } elseif ($second) { // undefined variable $second
    $words = "bye {$name}";
  }
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseCondition($first) {
  $name = 'human';
  if ($first) {
    $second = true; // unused variable $second
    $words = "hello {$name}";
  } elseif ($second) { // undefined variable $second
    $words = "bye {$name}";
  }
  echo $words;
}

function unusedInsideFirstBlock($first, $second) {
  $name = 'human';
  if ($first) {
    $unused = true; // unused variable $unused
    $words = "hello {$name}";
  } elseif ($second) {
    $words = "bye {$name}";
  }
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseIfBlock($first) {
  $name = 'human';
  if ($first) {
    $second = true; // unused variable $second
    $words = "hello {$name}";
  } elseif ($name) {
    $words = "bye {$name}";
    echo $second; // undefined variable $second
  }
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideElseBlock($first) {
  $name = 'human';
  if ($first) {
    $second = true; // unused variable $second
    $words = "hello {$name}";
  } else {
    $words = "bye {$name}";
    echo $second; // undefined variable $second
  }
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideUnconnectedElseCondition($first) {
  $name = 'human';
  if ($first) {
    $second = true;
    $words = "hello {$name}";
  } elseif ($name) {
    $words = "bye {$name}";
  }
  if ($first) {
    $second = true;
    $words = "hello {$name}";
  } elseif ($second) {
    $words = "bye {$name}";
  }
  echo $words;
}

function definedInsideFirstBlockUndefinedInsideSecondCondition($first) {
  $name = 'human';
  if ($first) {
    $second = true;
    $words = "hello {$name}";
  }
  if ($second) {
    $words = "bye {$name}";
  }
  echo $words;
}

function ifConditionWithPossibleDefinition($first) {
  if ($first) {
    $name = 'person';
  }
  echo $name;
}

function ifConditionWithPossibleUse($first) {
  $name = 'person';
  if ($first) {
    echo $name;
  }
}
