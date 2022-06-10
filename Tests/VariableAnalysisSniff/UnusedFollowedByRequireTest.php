<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class UnusedFollowedByRequire extends BaseTestCase
{
	public function testUnusedFollowedByRequireWarnsByDefault()
	{
		$fixtureFile = $this->getFixture('UnusedFollowedByRequireFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			2,
			3,
			4,
			8,
			9,
			10,
			14,
			15,
			16,
			20,
			21,
			22,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedFollowedByRequireDoesNotWarnWhenSet()
	{
		$fixtureFile = $this->getFixture('UnusedFollowedByRequireFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedVariablesBeforeRequire',
			'true'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			10,
			16,
			22,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedFollowedByRequireDoesNotBreakOtherThingsWhenSet()
	{
		$fixtureFile = $this->getFixture('FunctionWithoutParamFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedVariablesBeforeRequire',
			'true'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			5,
			6,
			7,
			8,
			9,
			10,
			11,
			12,
			13,
			18,
			19,
		];
		$this->assertSame($expectedWarnings, $lines);
	}
}
