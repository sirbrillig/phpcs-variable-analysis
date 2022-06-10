<?php

namespace VariableAnalysis\Tests;

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class BaseTestCase extends TestCase
{
	const STANDARD_NAME = 'VariableAnalysis';

	public function prepareLocalFileForSniffs($fixtureFile)
	{
		$sniffFile = __DIR__ . '/../VariableAnalysis/Sniffs/CodeAnalysis/VariableAnalysisSniff.php';

		$config            = new Config();
		$config->cache     = false;
		$config->standards = [self::STANDARD_NAME];
		$config->ignored   = [];

		$sniffFiles = [realpath($sniffFile)];
		$ruleset    = new Ruleset($config);
		$ruleset->registerSniffs($sniffFiles, [], []);
		$ruleset->populateTokenListeners();
		if (! file_exists($fixtureFile)) {
			throw new \Exception('Fixture file does not exist: ' . $fixtureFile);
		}
		return new LocalFile($fixtureFile, $ruleset, $config);
	}

	public function getLineNumbersFromMessages(array $messages)
	{
		$lines = array_keys($messages);
		sort($lines);
		return $lines;
	}

	public function getWarningLineNumbersFromFile(LocalFile $phpcsFile)
	{
		return $this->getLineNumbersFromMessages($phpcsFile->getWarnings());
	}

	public function getErrorLineNumbersFromFile(LocalFile $phpcsFile)
	{
		return $this->getLineNumbersFromMessages($phpcsFile->getErrors());
	}

	public function getFixture($fixtureFilename)
	{
		return realpath(__DIR__ . '/VariableAnalysisSniff/fixtures/' . $fixtureFilename);
	}
}
