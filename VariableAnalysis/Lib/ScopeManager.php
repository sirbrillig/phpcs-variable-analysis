<?php

namespace VariableAnalysis\Lib;

use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\ScopeType;
use VariableAnalysis\Lib\VariableInfo;
use VariableAnalysis\Lib\Constants;
use VariableAnalysis\Lib\Helpers;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

class ScopeManager {

	/**
	 * An associative array of a list of token index pairs which start and end
	 * scopes and will be used to check for unused variables.
	 *
	 * Each array of scopes is keyed by a string containing the filename (see
	 * `getFilename`).
	 *
	 * @var array<string, ScopeInfo[]>
	 */
	private $scopeStartEndPairs = [];

	/**
	 * Add a scope's start and end index to our record for the file.
	 *
	 * @param File $phpcsFile
	 * @param int  $scopeStartIndex
	 *
	 * @return void
	 */
	public function recordScopeStartAndEnd($phpcsFile, $scopeStartIndex)
	{
		$scopeEndIndex = Helpers::getScopeCloseForScopeOpen($phpcsFile, $scopeStartIndex);
		$filename = $phpcsFile->getFilename();
		if (! isset($this->scopeStartEndPairs[$filename])) {
			$this->scopeStartEndPairs[$filename] = [];
		}
		Helpers::debug('recording scope for file', $filename, 'start/end', $scopeStartIndex, $scopeEndIndex);
		$this->scopeStartEndPairs[$filename][] = new ScopeInfo($scopeStartIndex, $scopeEndIndex);
	}

	/**
	 * Return the scopes for a file.
	 *
	 * @param string  $filename
	 *
	 * @return ScopeInfo[]
	 */
	public function getScopesForFilename($filename)
	{
		if (empty($this->scopeStartEndPairs[$filename])) {
			return [];
		}
		return $this->scopeStartEndPairs[$filename];
	}
}
