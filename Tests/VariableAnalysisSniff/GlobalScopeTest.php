<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class GlobalScopeTest extends BaseTestCase
{
	public function testGlobalScopeWarnings()
	{
		$fixtureFile = $this->getFixture('GlobalScopeFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUndefinedVariablesInFileScope', 'false');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedErrors = [
			3,
			5,
			8,
			11,
			14,
		];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testGlobalScopeWarningsWithAllowUndefinedVariablesInFileScope()
	{
		$fixtureFile = $this->getFixture('GlobalScopeFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUndefinedVariablesInFileScope', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedErrors = [
			3,
			5,
			11,
			14,
		];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testGlobalScopeWarningsWithAllowUnusedVariablesInFileScope()
	{
		$fixtureFile = $this->getFixture('GlobalScopeFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUnusedVariablesInFileScope', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedErrors = [
			3,
			8,
			11,
		];
		$this->assertSame($expectedErrors, $lines);
	}
}
