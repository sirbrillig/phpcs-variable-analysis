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

  $var = &$a->getProp(); // unused variable $var (because it is actually $a->prop and changes to $bee on the next line)
  $var = &$bee;
  return $var;
}

function doubleiUsedThenUsedAssignmentByReference() {
  $a = new A();
  $bee = 'hello';

  $var = &$a->getProp();
  echo $var;
  $var = &$bee;
  return $var;
}
