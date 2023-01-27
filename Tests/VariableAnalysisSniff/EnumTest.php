<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class EnumTest extends BaseTestCase
{
	public function testEnum()
	{
		$fixtureFile = $this->getFixture('EnumFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			33,
		];
		$this->assertEquals($expectedWarnings, $lines);
	}
}
