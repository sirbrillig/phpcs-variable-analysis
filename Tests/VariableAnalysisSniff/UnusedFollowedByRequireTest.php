<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class UnusedFollowedByRequire extends BaseTestCase {
  public function testUnusedFollowedByRequireDefault() {
    $fixtureFile = $this->getFixture('UnusedFollowedByRequireFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      2,
      3,
      4,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testUnusedFollowedByRequireWhenSet() {
    $fixtureFile = $this->getFixture('UnusedFollowedByRequireFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUnusedVariablesBeforeRequire',
      'true'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      4,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }
}
