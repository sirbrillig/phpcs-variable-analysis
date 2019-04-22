<?php

namespace VariableAnalysis\Lib;

/**
 * Holds details of a variable within a scope.
 */
class VariableInfo {
  public $name;
  /**
   * What scope the variable has: local, param, static, global, bound
   */
  public $scopeType;
  public $typeHint;
  public $passByReference = false;
  public $firstDeclared; // stack pointer of first declaration
  public $firstInitialized; // stack pointer of first initialization
  public $firstRead; // stack pointer of first read
  public $ignoreUnused = false;
  public $ignoreUndefined = false;
  public $isForeachLoopVar = false;

  public static $scopeTypeDescriptions = array(
    'local'  => 'variable',
    'param'  => 'function parameter',
    'static' => 'static variable',
    'global' => 'global variable',
    'bound'  => 'bound variable',
  );

  public function __construct($varName) {
    $this->name = $varName;
  }
}
