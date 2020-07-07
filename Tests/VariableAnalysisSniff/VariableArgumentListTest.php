<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class VariableArgumentListTest extends BaseTestCase {
  public function testVariableArgumentList() {
    $fixtureFile = $this->getFixture('VariableArgumentListFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUnusedParametersBeforeUsed',
      'true'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      6,
      15,
      23,
      33,
      38,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testVariableArgumentListWithoutUnusedBeforeUsed() {
    $fixtureFile = $this->getFixture('VariableArgumentListFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUnusedParametersBeforeUsed',
      'false'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      6,
      15,
      19,
      23,
      33,
      38,
      43,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }
}
