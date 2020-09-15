<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class IfConditionTest extends BaseTestCase {
  public function testIfConditionWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithIfConditionFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUnusedParametersBeforeUsed',
      'true'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      15,
      27,
      36,
      38,
      47,
      58,
      62,
      70,
      74,
      82,
      87,
      98,
      101,
      159,
      161,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testInlineIfConditionWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithInlineIfConditionFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->ruleset->setSniffProperty(
      'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
      'allowUnusedParametersBeforeUsed',
      'true'
    );
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      14,
      25,
      34,
      35,
      44,
      54,
      56,
      64,
      66,
      74,
      77,
      86,
      88,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }
}
