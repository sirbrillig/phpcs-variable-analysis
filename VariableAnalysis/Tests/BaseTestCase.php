<?php
namespace VariableAnalysis\Tests;

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class BaseTestCase extends TestCase {
  public function prepareLocalFileForSniffs($sniffFiles, string $fixtureFile): LocalFile {
    $config = new Config();
    $ruleset = new Ruleset($config);
    if (! is_array($sniffFiles)) {
      $sniffFiles = [$sniffFiles];
    }
    $ruleset->registerSniffs($sniffFiles, [], []);
    $ruleset->populateTokenListeners();
    return new LocalFile($fixtureFile, $ruleset, $config);
  }

  public function getLineNumbersFromMessages(array $messages): array {
    return array_keys($messages);
  }

  public function getWarningLineNumbersFromFile(LocalFile $phpcsFile): array {
    return $this->getLineNumbersFromMessages($phpcsFile->getWarnings());
  }

  public function getErrorLineNumbersFromFile(LocalFile $phpcsFile): array {
    return $this->getLineNumbersFromMessages($phpcsFile->getErrors());
  }

  public function getSniffFiles(): array {
    return [__DIR__ . '/../../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php'];
  }

  public function getFixture(string $fixtureFilename): string {
    return __DIR__ . '/CodeAnalysis/fixtures/' . $fixtureFilename;
  }
}
