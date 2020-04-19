<?php

function reassignInIf($user) {
  $name = 'unknown';
  if ($user === 'admin') {
    $name = 'administrator';
    $flavor = 'grape';
    $flavor = 'candy'; // Should be a warning
  }
  echo $name;
  echo $flavor;
}

function reassignInIfWithoutBrackets($user) {
  $name = 'unknown';
  if ($user === 'admin')
    $name = 'administrator';
  echo $name;
}

function reassignInForEach($people, $data) {
  $name = 'unknown';
  foreach ($people as $person) {
    if ($person->id === $data->id) {
      $name = $person->name;
    }
    $flavor = 'grape';
    $flavor = 'candy'; // Should be a warning
  }
  echo "The name is {$name}";
  echo $flavor;
}

function reassignPlain($id) {
  $hello = 'world';
  $hello = 'abc'; // Should be a warning
  if ($id === 1) {
    $hello = 'admin';
  }
  echo $hello;
}

function reassignInIfElse($user) {
  $name = 'unknown';
  if ($user === 'admin') {
    $name = 'administrator';
    $flavor = 'grape';
    $flavor = 'candy'; // Should be a warning
  } else {
    $name = 'user';
    $tea = 'green';
    $tea = 'oolong'; // Should be a warning
  }
  echo $name;
  echo $tea;
  echo $flavor;
}

function reassignInIfElseWithoutBrackets($user) {
  $name = 'unknown';
  if ($user === 'admin')
    $name = 'administrator';
  else
    $name = 'user';
  echo $name;
}

function reassignInIfElseIf($user) {
  $name = 'unknown';
  if ($user === 'admin') {
    $name = 'administrator';
  } elseif ($user === 'bob') {
    $name = 'user';
    $tea = 'green';
    $tea = 'oolong'; // Should be a warning
  }
  echo $name;
  echo $tea;
}

function reassignInWhile($user) {
  $name = 'unknown';
  while ($user->isValid()) {
    $name = 'someone';
    $tea = 'green';
    $tea = 'oolong'; // Should be a warning
  }
  echo $name;
  echo $tea;
}

function reassignInDoWhile($user) {
  $name = 'unknown';
  do {
    $name = 'someone';
    $tea = 'green';
    $tea = 'oolong'; // Should be a warning
  } while ($user->isValid());
  echo $name;
  echo $tea;
}

function reassignInForInit() {
  $name = 'unknown';
  $tea = 'green';
  for ($name = 1; $name++; $name < 5) { // Should be a warning
    $tea = 'black';
    $flavor = 'grape';
    $flavor = 'candy'; // Should be a warning
    echo 'hello';
  }
  echo $tea;
  echo $flavor;
}

function reassignInSwitch($user) {
  $name = 'unknown';
  switch ($user) {
    case 'admin':
      $name = 'administrator';
      $flavor = 'sweet';
      break;
    case 'other':
      $name = 'someone';
      $flavor = 'salty';
      $tea = 'oolong';
      $tea = 'puer'; // Should be a warning
      break;
  }
  echo $name;
  echo $tea;
  echo $flavor;
}
