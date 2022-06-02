<?php
namespace VariableAnalysis\Tests\VariableAnalysisSniff;

use VariableAnalysis\Tests\BaseTestCase;

class VariableAnalysisTest extends BaseTestCase {
	public function testFunctionWithoutParamsErrors() {
		$fixtureFile = $this->getFixture('FunctionWithoutParamFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithoutParamsWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithoutParamFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
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

	public function testFunctionWithUseReferenceWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithUseReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithDefaultParamErrors() {
		$fixtureFile = $this->getFixture('FunctionWithDefaultParamFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithDefaultParamWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithDefaultParamFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			3,
			14,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithGlobalVarErrors() {
		$fixtureFile = $this->getFixture('FunctionWithGlobalVarFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithGlobalVarWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithGlobalVarFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			7,
			8,
			23,
			28,
			29,
			39,
			54,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithForeachErrors() {
		$fixtureFile = $this->getFixture('FunctionWithForeachFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithForeachWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithForeachFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedForeachVariables',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			8,
			12,
			16,
			20,
			22,
			24,
			26,
			48,
			50,
			52,
			54,
			67,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testClassWithMembersErrors() {
		$fixtureFile = $this->getFixture('ClassWithMembersFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testTraitWithMembersWarnings() {
		$fixtureFile = $this->getFixture('TraitWithMembersFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedErrors = [
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
			64,
		];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testClassWithMembersWarnings() {
		$fixtureFile = $this->getFixture('ClassWithMembersFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
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
			66,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionsOutsideClassErrors() {
		$fixtureFile = $this->getFixture('FunctionsOutsideClassFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionsOutsideClassWarnings() {
		$fixtureFile = $this->getFixture('FunctionsOutsideClassFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			3,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithClosureErrors() {
		$fixtureFile = $this->getFixture('FunctionWithClosureFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [
			58,
			70,
		];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithClosureWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithClosureFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			7,
			9,
			10,
			13,
			16,
			18,
			19,
			20,
			25,
			26,
			27,
			28,
			35,
			64,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithReferenceErrors() {
		$fixtureFile = $this->getFixture('FunctionWithReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithReferenceWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			8,
			20,
			32,
			33,
			34,
			36,
			37,
			39,
			40,
			46,
			59,
			60,
			64,
			81,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithReferenceWarningsAllowsCustomFunctions() {
		$fixtureFile = $this->getFixture('FunctionWithReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'sitePassByRefFunctions',
			'my_reference_function:2,3 another_reference_function:2,...'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			8,
			20,
			32,
			33,
			34,
			36,
			37,
			39,
			40,
			46,
			64,
			81,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithReferenceWarningsAllowsWordPressFunctionsIfSet() {
		$fixtureFile = $this->getFixture('FunctionWithReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowWordPressPassByRefFunctions',
			'true'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			8,
			20,
			32,
			33,
			34,
			36,
			37,
			39,
			40,
			46,
			59,
			60,
			81,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithTryCatchErrors() {
		$fixtureFile = $this->getFixture('FunctionWithTryCatchFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithTryCatchWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithTryCatchFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			3,
			7,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithInlineAssignErrors() {
		$fixtureFile = $this->getFixture('FunctionWithInlineAssignFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithInlineAssignWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithInlineAssignFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			3,
			6,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testFunctionWithRedeclarationsErrors() {
		$fixtureFile = $this->getFixture('FunctionWithRedeclarationsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testFunctionWithRedeclarationsWarnings() {
		$fixtureFile = $this->getFixture('FunctionWithRedeclarationsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			7,
			14,
			15,
			16,
			17,
			18,
			23,
			26,
			33,
			34,
			35,
			36,
			37,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testHeredocErrors() {
		$fixtureFile = $this->getFixture('HeredocFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testHeredocWarnings() {
		$fixtureFile = $this->getFixture('HeredocFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			8,
			9,
			10,
			12,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testClassReferenceErrors() {
		$fixtureFile = $this->getFixture('ClassReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testClassReferenceWarnings() {
		$fixtureFile = $this->getFixture('ClassReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			10,
			11,
			12,
			13,
			22,
			23,
			24,
			25
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testCompactErrors() {
		$fixtureFile = $this->getFixture('CompactFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testCompactWarnings() {
		$fixtureFile = $this->getFixture('CompactFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			2,
			7,
			10,
			14,
			19,
			23,
			26,
			36,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testCompactWarningsHaveCorrectSniffCodes() {
		$fixtureFile = $this->getFixture('CompactFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();

		$warnings = $phpcsFile->getWarnings();
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[2][49][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[7][23][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[10][54][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[14][52][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[19][5][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[23][23][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[26][66][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[36][5][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable', $warnings[36][23][0]['source']);
	}

	public function testTraitAllowsThis() {
		$fixtureFile = $this->getFixture('TraitFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [];
		$this->assertSame($expectedWarnings, $lines);
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testAnonymousClassAllowsThis() {
		$fixtureFile = $this->getFixture('AnonymousClassFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [];
		$this->assertSame($expectedWarnings, $lines);
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testVariableFunctionCallsCountAsUsage() {
		$fixtureFile = $this->getFixture('FunctionWithVariableCallFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [18];
		$this->assertSame($expectedWarnings, $lines);
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testVariableVariables() {
		$fixtureFile = $this->getFixture('VariableVariablesFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			10,
			16,
			22,
			29,
			35,
			41,
			47,
			48,
			52,
			53,
		];
		$this->assertSame($expectedWarnings, $lines);
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testTraitsAllowPropertyDefinitions() {
		$fixtureFile = $this->getFixture('TraitWithPropertiesFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [];
		$this->assertSame($expectedWarnings, $lines);
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testAnonymousClassAllowPropertyDefinitions() {
		$fixtureFile = $this->getFixture('AnonymousClassWithPropertiesFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			17,
			26,
			38,
		];
		$this->assertSame($expectedWarnings, $lines);
		$lines = $this->getErrorLineNumbersFromFile($phpcsFile);
		$expectedErrors = [];
		$this->assertSame($expectedErrors, $lines);
	}

	public function testUnusedParamsAreReported() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			16,
			27,
			39,
			72,
			73,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedParamsHaveCorrectSniffCodes() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();

		$warnings = $phpcsFile->getWarnings();
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[4][43][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[16][52][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[27][60][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[39][42][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[39][51][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[72][5][0]['source']);
		$this->assertSame('VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable', $warnings[73][5][0]['source']);
	}

	public function testValidUnusedVariableNamesIgnoresUnusedVariables() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'validUnusedVariableNames',
			'ignored'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			16,
			39,
			72,
			73,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testAllowUnusedFunctionParametersIgnoresUnusedVariables() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedFunctionParameters',
			'true'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testAllowUnusedCaughtExceptionsIgnoresUnusedVariablesIfSet() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedCaughtExceptions',
			'true'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			16,
			27,
			39,
			72,
			73,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testAllowUnusedCaughtExceptionsDoesNotIgnoreUnusedVariablesIfFalse() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedCaughtExceptions',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			16,
			27,
			39,
			66,
			72,
			73,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testIgnoreUnusedRegexpIgnoresUnusedVariables() {
		$fixtureFile = $this->getFixture('FunctionWithUnusedParamsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'ignoreUnusedRegexp',
			'/^unused_/'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			16,
			27,
			39,
			72,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testAllowDestructuringAssignment() {
		$fixtureFile = $this->getFixture('DestructuringFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			12,
			26,
			28,
			43,
			45,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testValidUndefinedVariableNamesIgnoresVarsInGlobalScope() {
		$fixtureFile = $this->getFixture('FunctionWithGlobalVarFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'validUndefinedVariableNames',
			'ice_cream'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			4,
			7,
			23,
			39,
			54,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testValidUndefinedVariableNamesIgnoresUndefinedProperties() {
		$fixtureFile = $this->getFixture('ClassReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'validUndefinedVariableNames',
			'ignored_property'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			10,
			11,
			22,
			23,
			24,
			25
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testValidUndefinedVariableRegexpIgnoresUndefinedProperties() {
		$fixtureFile = $this->getFixture('ClassReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'validUndefinedVariableRegexp',
			'/^undefined_/'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			12,
			13,
			24,
			25
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedArgumentsBeforeUsedArgumentsAreFoundIfFalse() {
		$fixtureFile = $this->getFixture('UnusedAfterUsedFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedParametersBeforeUsed',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			5,
			8,
			16,
			19,
			28,
			34,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedArgumentsBeforeUsedArgumentsAreIgnoredByDefault() {
		$fixtureFile = $this->getFixture('UnusedAfterUsedFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			8,
			19,
			28,
			34,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testPregReplaceIgnoresNumericVariables() {
		$fixtureFile = $this->getFixture('PregReplaceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			15,
			20,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedForeachVariablesAreIgnoredByDefault() {
		$fixtureFile = $this->getFixture('UnusedForeachFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			5,
			7,
			8,
			16,
			17,
			23,
			25,
			26,
			32,
			33,
			34,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedForeachVariablesAreNotIgnoredIfDisabled() {
		$fixtureFile = $this->getFixture('UnusedForeachFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedForeachVariables',
			'false'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			5,
			7,
			8,
			14,
			16,
			17,
			23,
			25,
			26,
			32,
			33,
			34,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedForeachVariablesAreIgnoredIfSet() {
		$fixtureFile = $this->getFixture('UnusedForeachFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'allowUnusedForeachVariables',
			'true'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			5,
			7,
			8,
			16,
			17,
			23,
			25,
			26,
			32,
			33,
			34,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testGetDefinedVarsCountsAsRead() {
		$fixtureFile = $this->getFixture('GetDefinedVarsFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			6,
			18,
			22,
			29,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testThisWithinNestedClosedScope() {
		$fixtureFile = $this->getFixture('ThisWithinNestedClosedScopeFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			5,
			8,
			15,
			20,
			33,
			41,
			47,
			61,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testUnusedVarWithValueChange() {
		$fixtureFile = $this->getFixture('UnusedVarWithValueChangeFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			5,
			6,
			8,
			9,
			11,
			12,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testAssignmentByReference() {
		$fixtureFile = $this->getFixture('AssignmentByReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			26,
			34,
			35,
			43,
			70,
		];
		$this->assertSame($expectedWarnings, $lines);
	}

	public function testAssignmentByReferenceWithIgnoreUnusedMatch() {
		$fixtureFile = $this->getFixture('AssignmentByReferenceFixture.php');
		$phpcsFile = $this->prepareLocalFileForSniffs($fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff',
			'ignoreUnusedRegexp',
			'/^varX$/'
		);
		$phpcsFile->process();
		$lines = $this->getWarningLineNumbersFromFile($phpcsFile);
		$expectedWarnings = [
			26,
			34,
			35,
			70,
		];
		$this->assertSame($expectedWarnings, $lines);
	}
}
