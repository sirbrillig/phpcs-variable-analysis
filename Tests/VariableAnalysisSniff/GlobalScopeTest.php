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
      4,
      7,
      10,
      13,
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
      4,
      10,
      13,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testGlobalScopeWarningsWithAllowUnusedVariablesInFileScope() {
    $fixtureFile = $this->getFixture('GlobalScopeFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUnusedVariablesInFileScope',
      'true'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      7,
      10,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }
}
