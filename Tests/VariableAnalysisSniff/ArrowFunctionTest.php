<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class ArrowFunctionTest extends BaseTestCase
{
	public function testArrowFunctions()
	{
		$fixtureFile = $this->getFixture('ArrowFunctionFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUnusedParametersBeforeUsed', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			9,
			14,
			19,
			24,
			30,
			34,
			51,
			57,
			61,
			67,
			71,
			87,
			102,
			112,
			150,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testArrowFunctionsWithoutUnusedBeforeUsed()
	{
		$fixtureFile = $this->getFixture('ArrowFunctionFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUnusedParametersBeforeUsed', 'false');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			9,
			14,
			19,
			24,
			30,
			34,
			39,
			51,
			57,
			61,
			63,
			67,
			71,
			87,
			102,
			112,
			150,
		];
		$this->assertSame($expectedWarnings, $lines);
	}
}
