<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class GlobalScopeTest extends BaseTestCase {
  public function testGlobalScopeWarnings() {
    $fixtureFile = $this->getFixture('GlobalScopeFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUndefinedVariablesInFileScope',
      'false'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      // 4, This should be finding the unused variable but we'll need to backport https://github.com/sirbrillig/phpcs-variable-analysis/pull/190 to 2.x
      7,
      10,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testGlobalScopeWarningsWithAllowUndefinedVariablesInFileScope() {
    $fixtureFile = $this->getFixture('GlobalScopeFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUndefinedVariablesInFileScope',
      'true'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      // 4, This should be finding the unused variable but we'll need to backport https://github.com/sirbrillig/phpcs-variable-analysis/pull/190 to 2.x
      10,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }
}
