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

function assignmentByReference() {
  $a = new A();

  $var = &$a->getProp();
  $var = ['bar'];
  return $a;
}
