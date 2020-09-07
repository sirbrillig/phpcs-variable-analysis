<?php

function normalFunctionShouldNotIgnoreUndefinedVariable() {
  if (isActive($undefinedVar)) { // should report undefined variable $undefinedVar
    doSomething();
  }
}

function issetShouldIgnoreUndefinedVariable() {
  if (isset($undefinedVar)) {
    doSomething();
  }
}

function emptyShouldIgnoreUndefinedVariable() {
  if (! empty($undefinedVar)) {
    doSomething();
  }
}

function shouldIgnoreUndefinedVariableUseAfterIsset() {
  if (isset($undefinedVar)) {
    doSomething($undefinedVar); // ideally this should not be a warning, but will be because it is difficult to know: https://github.com/sirbrillig/phpcs-variable-analysis/issues/202#issuecomment-688507314
  }
}

function shouldCountVariableUseInsideIssetAsRead($definedVar) {
  if (isset($definedVar)) {
    doSomething();
  }
}

function shouldCountVariableUseInsideEmptyAsRead($definedVar) {
  if (empty($definedVar)) {
    doSomething();
  }
}
