<?php

namespace VariableAnalysis;

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
  public $firstDeclared;
  public $firstInitialized;
  public $firstRead;
  public $ignoreUnused = false;

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
