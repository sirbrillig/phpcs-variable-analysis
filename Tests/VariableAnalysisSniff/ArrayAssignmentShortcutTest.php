<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class ArrayAssignmentShortcutTest extends BaseTestCase
{
	public function testArrayAssignmentReportsCorrectLines()
	{
		$fixtureFile = $this->getFixture('ArrayAssignmentShortcutFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();

		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			21,
			27,
			28,
			29,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testArrayAssignmentHasCorrectSniffCodes()
	{
		$fixtureFile = $this->getFixture('ArrayAssignmentShortcutFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();

		$warnings = $phpcsFile->getWarnings();
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[21][5][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[27][5][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[28][5][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[29][10][0]['source']);
	}
}
