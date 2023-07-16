<?php

namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class IfConditionTest extends BaseTestCase
{
	public function testIfConditionWarnings()
	{
		$fixtureFile = $this->getFixture('FunctionWithIfConditionFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUnusedParametersBeforeUsed', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			15,
			27,
			36,
			38,
			47,
			58,
			62,
			70,
			74,
			82,
			87,
			98,
			101,
			159,
			166,
			176,
			179,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testIfConditionWarningsWithValidUndefinedVariableNames()
	{
		$fixtureFile = $this->getFixture('FunctionWithIfConditionFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'validUndefinedVariableNames', 'second');
		$this->setSniffProperty($phpcsFile, 'allowUnusedParametersBeforeUsed', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			15,
			36,
			47,
			58,
			70,
			82,
			98,
			159,
			166,
			176,
			179,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testInlineIfConditionWarnings()
	{
		$fixtureFile = $this->getFixture('FunctionWithInlineIfConditionFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'allowUnusedParametersBeforeUsed', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			14,
			25,
			34,
			35,
			44,
			54,
			56,
			64,
			66,
			74,
			77,
			86,
			88,
			130,
			136,
			152,
			154,
			165,
			175,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testInlineIfConditionWarningsWithValidUndefinedVariableNames()
	{
		$fixtureFile = $this->getFixture('FunctionWithInlineIfConditionFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$this->setSniffProperty($phpcsFile, 'validUndefinedVariableNames', 'second');
		$this->setSniffProperty($phpcsFile, 'allowUnusedParametersBeforeUsed', 'true');
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			14,
			34,
			44,
			54,
			64,
			74,
			86,
			130,
			136,
			152,
			154,
			165,
			175,
		];
		$this->assertSame($expectedWarnings, $lines);
	}
}
