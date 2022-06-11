<?php
namespace VariableAnalysis\Tests;

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class BaseTestCase extends TestCase
{
	const STANDARD_NAME = 'VariableAnalysis';

	const REDECLARATION_ERROR_CODE = 'VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration';
	const SELF_OUTSIDE_CLASS_ERROR_CODE = 'VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass';
	const STATIC_OUSIDE_CLASS_ERROR_CODE = 'VariableAnalysis.CodeAnalysis.VariableAnalysis.StaticOutsideClass';
	const UNDEFINED_ERROR_CODE = 'VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable';
	const UNSET_ERROR_CODE = 'VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedUnsetVariable';
	const UNUSED_ERROR_CODE = 'VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable';

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
