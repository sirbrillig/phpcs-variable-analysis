<?php
namespace VariableAnalysis\Tests;

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class BaseTestCase extends TestCase {
  public function prepareLocalFileForSniffs($sniffFiles, $fixtureFile) {
    $config = new Config(['--standard=VariableAnalysis']);
    $ruleset = new Ruleset($config);
    if (! is_array($sniffFiles)) {
      $sniffFiles = [$sniffFiles];
    }
    $ruleset->registerSniffs($sniffFiles, [], []);
    $ruleset->populateTokenListeners();
    if (! file_exists($fixtureFile)) {
      throw new \Exception('Fixture file does not exist: ' . $fixtureFile);
    }
    return new LocalFile($fixtureFile, $ruleset, $config);
  }

  public function getLineNumbersFromMessages(array $messages) {
    $lines = array_keys($messages);
    sort($lines);
    return $lines;
  }

  public function getWarningLineNumbersFromFile(LocalFile $phpcsFile) {
    return $this->getLineNumbersFromMessages($phpcsFile->getWarnings());
  }

  public function getErrorLineNumbersFromFile(LocalFile $phpcsFile) {
    return $this->getLineNumbersFromMessages($phpcsFile->getErrors());
  }

  public function getSniffFiles() {
    return [__DIR__ . '/../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
  }

  public function getFixture($fixtureFilename) {
    return __DIR__ . '/CodeAnalysis/fixtures/' . $fixtureFilename;
  }
}
