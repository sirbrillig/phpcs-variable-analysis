<?php

namespace VariableAnalysis\Lib;

/**
 * Holds details of a scope.
 */
class ScopeInfo {
  public $owner;
  public $opener;
  public $closer;
  public $variables = [];

  public function __construct($currScope) {
    $this->owner = $currScope;
    // TODO: extract opener/closer
  }
}
