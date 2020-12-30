<?php

namespace VariableAnalysis\Lib;

use VariableAnalysis\Lib\ScopeType;

/**
 * Holds details of a variable within a scope.
 */
class VariableInfo {
  /**
   * @var string
   */
  public $name;

  /**
   * What scope the variable has: local, param, static, global, bound
   *
   * @var string
   */
  public $scopeType;

  /**
   * @var string
   */
  public $typeHint;

  /**
   * @var int | null
   */
  public $referencedVariableScope;

  /**
   * True if the variable is a reference but one created at runtime
   *
   * @var bool
   */
  public $isDynamicReference = false;

  /**
   * Stack pointer of first declaration
   *
   * Declaration is when a variable is created but has no value assigned.
   *
   * Assignment by reference is also a declaration and not an initialization.
   *
   * @var int
   */
  public $firstDeclared;

  /**
   * Stack pointer of first initialization
   *
   * @var int
   */
  public $firstInitialized;

  /**
   * Stack pointer of first read
   *
   * @var int
   */
  public $firstRead;

  /**
   * Stack pointers of all assignments
   *
   * This includes both declarations and initializations and may contain
   * duplicates!
   *
   * @var int[]
   */
  public $allAssignments = [];

  /**
   * @var bool
   */
  public $ignoreUnused = false;

  /**
   * @var bool
   */
  public $ignoreUndefined = false;

  /**
   * @var bool
   */
  public $isForeachLoopAssociativeValue = false;

  /**
   * @var string[]
   */
  public static $scopeTypeDescriptions = array(
    ScopeType::LOCAL  => 'variable',
    ScopeType::PARAM  => 'function parameter',
    ScopeType::STATICSCOPE => 'static variable',
    ScopeType::GLOBALSCOPE => 'global variable',
    ScopeType::BOUND  => 'bound variable',
  );

  public function __construct($varName) {
    $this->name = $varName;
  }
}
