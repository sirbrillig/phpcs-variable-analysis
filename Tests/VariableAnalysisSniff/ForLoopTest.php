<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class ForLoopTest extends BaseTestCase
{
	public function testFunctionWithForLoopWarningsReportsCorrectLines()
	{
		$fixtureFile = $this->getFixture('FunctionWithForLoopFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			22,
			65,
			80,
			94,
			110,
			112,
			113,
			118,
			123,
			129,
			143,
			145,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithForLoopWarningsHasCorrectSniffCodes()
	{
		$fixtureFile = $this->getFixture('FunctionWithForLoopFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$warnings = $phpcsFile->getWarnings();
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[22][17][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[65][16][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[80][16][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[94][5][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[110][7][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[112][7][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[113][16][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[118][5][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[123][5][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[129][14][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[143][5][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[145][3][0]['source']);
	}
}
