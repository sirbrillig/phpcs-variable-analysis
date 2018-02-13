<?php
namespace VariableAnalysis\Tests;

use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class VariableAnalysisTest extends BaseTestCase {
  public function testFunctionWithoutParamsErrors() {
    $fixtureFile = $this->getFixture('FunctionWithoutParamFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithoutParamsWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithoutParamFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithDefaultParamErrors() {
    $fixtureFile = $this->getFixture('FunctionWithDefaultParamFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithDefaultParamWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithDefaultParamFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
      14,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithGlobalVarErrors() {
    $fixtureFile = $this->getFixture('FunctionWithGlobalVarFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithGlobalVarWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithGlobalVarFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      4,
      7,
      22,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithForeachErrors() {
    $fixtureFile = $this->getFixture('FunctionWithForeachFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithForeachWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithForeachFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
      // FIXME: this is an unused variable that needs to be fixed but for now
      // we will ignore it. See
      // https://github.com/sirbrillig/phpcs-variable-analysis/pull/36
      // 67,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testClassWithMembersErrors() {
    $fixtureFile = $this->getFixture('ClassWithMembersFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testClassWithMembersWarnings() {
    $fixtureFile = $this->getFixture('ClassWithMembersFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
      62,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionsOutsideClassErrors() {
    $fixtureFile = $this->getFixture('FunctionsOutsideClassFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      8,
      12,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionsOutsideClassWarnings() {
    $fixtureFile = $this->getFixture('FunctionsOutsideClassFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithClosureErrors() {
    $fixtureFile = $this->getFixture('FunctionWithClosureFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      50,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithClosureWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithClosureFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithReferenceErrors() {
    $fixtureFile = $this->getFixture('FunctionWithReferenceFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithReferenceWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithReferenceFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithTryCatchErrors() {
    $fixtureFile = $this->getFixture('FunctionWithTryCatchFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithTryCatchWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithTryCatchFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
      7,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithInlineAssignErrors() {
    $fixtureFile = $this->getFixture('FunctionWithInlineAssignFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithInlineAssignWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithInlineAssignFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
      6,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithRedeclarationsErrors() {
    $fixtureFile = $this->getFixture('FunctionWithRedeclarationsFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithRedeclarationsWarnings() {
    $fixtureFile = $this->getFixture('FunctionWithRedeclarationsFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testHeredocErrors() {
    $fixtureFile = $this->getFixture('HeredocFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testHeredocWarnings() {
    $fixtureFile = $this->getFixture('HeredocFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      8,
      9,
      10,
      12,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testClassReferenceErrors() {
    $fixtureFile = $this->getFixture('ClassReferenceFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testClassReferenceWarnings() {
    $fixtureFile = $this->getFixture('ClassReferenceFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      10,
      11,
      20,
      21,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testCompactErrors() {
    $fixtureFile = $this->getFixture('CompactFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testCompactWarnings() {
    $fixtureFile = $this->getFixture('CompactFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
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
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testTraitAllowsThis() {
    $fixtureFile = $this->getFixture('TraitFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [];
    $this->assertEquals($expectedWarnings, $lines);
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testVariableFunctionCallsCountAsUsage() {
    $fixtureFile = $this->getFixture('FunctionWithVariableCallFixture.php');
    $phpcsFile = $this->prepareLocalFileForSniffs($this->getSniffFiles(), $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [18];
    $this->assertEquals($expectedWarnings, $lines);
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }
}
