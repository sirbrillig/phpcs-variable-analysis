<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class GlobalScopeTest extends BaseTestCase {
  public function testGlobalScopeWarnings() {
    $fixtureFile = $this->getFixture('GlobalScopeFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      4,
      7,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }
}
