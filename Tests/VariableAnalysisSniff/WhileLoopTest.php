<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class WhileLoopTest extends BaseTestCase {
	public function testFunctionWithWhileLoopWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithWhileLoopFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			38,
			46,
		];
		$this->assertSame($expectedWarnings, $lines);
	}
}
