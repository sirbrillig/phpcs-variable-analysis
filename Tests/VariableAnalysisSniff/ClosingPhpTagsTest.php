<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class ClosingPhpTagsTest extends BaseTestCase {
  public function testVariableWarningsWhenClosingTagsAreUsed() {
    $fixtureFile = $this->getFixture('ClosingPhpTagsFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      6,
      8,
      13,
      16,
    ];
    $this->assertEquals($expectedWarnings, $lines);

    // $warnings = $phpcsFile->getWarnings();
    // $this->assertEquals('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[2][49][0]['source']);
    // $this->assertEquals('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[2][49][0]['source']);
  }
}
