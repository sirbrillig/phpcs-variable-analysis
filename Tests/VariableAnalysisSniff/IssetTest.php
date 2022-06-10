<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class IssetTest extends BaseTestCase
{
	public function testIssetVariableUse()
	{
		$fixtureFile = $this->getFixture('IssetFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			23, // ideally this should not be a warning, but will be because it is difficult to know: https://github.com/sirbrillig/phpcs-variable-analysis/issues/202#issuecomment-688507314
		];
		$this->assertSame($expectedWarnings, $lines);
	}
}
