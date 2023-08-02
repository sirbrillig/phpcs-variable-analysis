<?php

class A {
    protected $prop = [];

    public function __construct() {
      $this->prop[] = 'foo';
    }

    public function &getProp() {
      return $this->prop;
    }
}

function usedAssignmentByReference() {
  $a = new A();

  $var = /*comment*/ &$a->getProp();
  $var = ['bar'];
  return $a;
}

function unusedAssignmentByReference() {
  $a = new A();

  $var = &$a->getProp(); // unused variable $var
  return $a;
}

function doubleUnusedAssignmentByReference() {
  $a = new A();
  $bee = 'hello';

  $var = &$a->getProp(); // unused variable $var
  $var = &$bee; // unused variable $var
  return $a;
}

function doubleUnusedThenUsedAssignmentByReference() {
  $a = new A();
  $bee = 'hello';

  $varX = &$a->getProp(); // unused variable $varX (because it is actually $a->prop and changes to $bee on the next line)
  $varX = &$bee;
  return $varX;
}

function doubleUsedThenUsedAssignmentByReference() {
  $a = new A();
  $bee = 'hello';

  $var = &$a->getProp();
  echo $var;
  $var = &$bee;
  return $var;
}

function somefunc1($choice, &$arr1, &$arr_default) {
  $var = &$arr_default;

  if ($choice) {
    $var = &$arr1;
  }

  echo $var;
}

function somefunc2($choice, &$arr1, &$arr_default) {
  if ($choice) {
    $var = &$arr_default; // unused variable $var
    $var = &$arr1;
    echo $var;
  }
}

function assignByRef2($field_id, $form) {
  $wrapper_id = $field_id . '_wrapper';
  if (!isset($form[$field_id]) && isset($form[$wrapper_id])) {
    $element = &$form[$wrapper_id][$field_id];
  } else {
    $element = &$form[$field_id];
  }
  $element['test'] = 'test';
}
