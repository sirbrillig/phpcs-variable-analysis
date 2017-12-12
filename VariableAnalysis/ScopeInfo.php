<?php

namespace VariableAnalysis;

/**
 * Holds details of a scope.
 */
class ScopeInfo {
  public $owner;
  public $opener;
  public $closer;
  public $variables = array();

  public function __construct($currScope) {
    $this->owner = $currScope;
    // TODO: extract opener/closer
  }
}
