<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class GlobalScopeTest extends BaseTestCase
{
	public function testGlobalScopeWarnings()
	{
		$fixtureFile = $this->getFixture('GlobalScopeFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUndefinedVariablesInFileScope',
			'false'
		);
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
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUndefinedVariablesInFileScope',
			'true'
		);
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
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedVariablesInFileScope',
			'true'
		);
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
