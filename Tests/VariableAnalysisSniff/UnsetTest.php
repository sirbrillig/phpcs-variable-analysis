<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class UnsetTest extends BaseTestCase
{
	public function testUnsetReportsUndefinedVariables()
	{
		$fixtureFile = $this->getFixture('UnsetFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		// Technically, these are not illegal, but they may be typos. See https://github.com/sirbrillig/phpcs-variable-analysis/issues/174
		$expectedWarnings = [
			6,
			11,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnsetHasCorrectSniffCodes()
	{
		$fixtureFile = $this->getFixture('UnsetFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();

		$warnings = $phpcsFile->getWarnings();
		$this->assertSame(self::UNSET_ERROR_CODE, $warnings[6][7][0]['source']);
		$this->assertSame(self::UNSET_ERROR_CODE, $warnings[11][9][0]['source']);
	}
}
