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

  $var = &$a->getProp();
  $var = ['bar'];
  return $a;
}

function unusedAssignmentByReference() {
  $a = new A();

  $var = &$a->getProp();
  return $a;
}

function doubleUnusedAssignmentByReference() {
  $a = new A();

  $var = &$a->getProp();
  $var = &$a->getProp();
  return $a;
}

function doubleUnusedThenUsedAssignmentByReference() {
  $a = new A();

  // @todo the first one should be marked as unused.
  $var = &$a->getProp();
  $var = &$a->getProp();
  return $var;
}
