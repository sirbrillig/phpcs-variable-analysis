<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class ClosingPhpTagsTest extends BaseTestCase
{
	public function testVariableWarningsWhenClosingTagsAreUsed()
	{
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

	public function testVariableWarningsHaveCorrectSniffCodesWhenClosingTagsAreUsed()
	{
		$fixtureFile = $this->getFixture('ClosingPhpTagsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$warnings = $phpcsFile->getWarnings();
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[6][1][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[8][6][0]['source']);
		$this->assertSame(self::UNUSED_ERROR_CODE, $warnings[13][1][0]['source']);
		$this->assertSame(self::UNDEFINED_ERROR_CODE, $warnings[16][6][0]['source']);
	}
}
