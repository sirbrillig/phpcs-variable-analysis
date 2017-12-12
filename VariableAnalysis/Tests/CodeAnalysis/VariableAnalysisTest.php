<?php
namespace VariableAnalysis\Tests;

use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class VariableAnalysisTest extends BaseTestCase {
  public function testFunctionWithoutParamsErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithoutParamFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithoutParamsWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithoutParamFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
    $fixtureFile = __DIR__ . '/FunctionWithDefaultParamFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithDefaultParamWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithDefaultParamFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
      14,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithGlobalVarErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithGlobalVarFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithGlobalVarWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithGlobalVarFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      7,
      4,
      22,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithForeachErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithForeachFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithForeachWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithForeachFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testClassWithMembersErrors() {
    $fixtureFile = __DIR__ . '/ClassWithMembersFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testClassWithMembersWarnings() {
    $fixtureFile = __DIR__ . '/ClassWithMembersFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
    $fixtureFile = __DIR__ . '/FunctionsOutsideClassFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      8,
      12,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionsOutsideClassWarnings() {
    $fixtureFile = __DIR__ . '/FunctionsOutsideClassFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithClosureErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithClosureFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [
      50,
    ];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithClosureWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithClosureFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
      36,
      37,
      35,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithReferenceErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithReferenceFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithReferenceWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithReferenceFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      20,
      8,
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
    $fixtureFile = __DIR__ . '/FunctionWithTryCatchFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithTryCatchWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithTryCatchFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
      7,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithInlineAssignErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithInlineAssignFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithInlineAssignWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithInlineAssignFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      3,
      6,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }

  public function testFunctionWithRedeclarationsErrors() {
    $fixtureFile = __DIR__ . '/FunctionWithRedeclarationsFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testFunctionWithRedeclarationsWarnings() {
    $fixtureFile = __DIR__ . '/FunctionWithRedeclarationsFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
    $fixtureFile = __DIR__ . '/HeredocFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testHeredocWarnings() {
    $fixtureFile = __DIR__ . '/HeredocFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
    $fixtureFile = __DIR__ . '/ClassReferenceFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testClassReferenceWarnings() {
    $fixtureFile = __DIR__ . '/ClassReferenceFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
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
    $fixtureFile = __DIR__ . '/CompactFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getErrorLineNumbersFromFile($phpcsFile);
    $expectedErrors = [];
    $this->assertEquals($expectedErrors, $lines);
  }

  public function testCompactWarnings() {
    $fixtureFile = __DIR__ . '/CompactFixture.php';
    $sniffFiles = [__DIR__ . '/../../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
    $phpcsFile = $this->prepareLocalFileForSniffs($sniffFiles, $fixtureFile);
    $phpcsFile->process();
    $lines = $this->getWarningLineNumbersFromFile($phpcsFile);
    $expectedWarnings = [
      7,
      10,
      2,
      23,
      26,
      14,
      19,
    ];
    $this->assertEquals($expectedWarnings, $lines);
  }
}
