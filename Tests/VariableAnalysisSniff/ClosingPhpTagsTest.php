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
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testVariableWarningsHaveCorrectSniffCodesWhenClosingTagsAreUsed() {
		$fixtureFile = $this->getFixture('ClosingPhpTagsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$warnings = $phpcsFile->getWarnings();
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[6][1][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[8][6][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[13][1][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[16][6][0]['source']);
	}
}
