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
	 * The outer array of scopes is keyed by a string containing the filename.
	 * The inner array of scopes in keyed by the scope start token index.
	 *
	 * @var array<string, array<int, ScopeInfo>>
	 */
	private $scopes = [];

	/**
	 * Add a scope's start and end index to our record for the file.
	 *
	 * @param File $phpcsFile
	 * @param int  $scopeStartIndex
	 *
	 * @return ScopeInfo
	 */
	public function recordScopeStartAndEnd($phpcsFile, $scopeStartIndex)
	{
		$scopeEndIndex = Helpers::getScopeCloseForScopeOpen($phpcsFile, $scopeStartIndex);
		$filename = $phpcsFile->getFilename();
		if (! isset($this->scopes[$filename])) {
			$this->scopes[$filename] = [];
		}
		Helpers::debug('recording scope for file', $filename, 'start/end', $scopeStartIndex, $scopeEndIndex);
		$scope = new ScopeInfo($scopeStartIndex, $scopeEndIndex);
		$this->scopes[$filename][$scopeStartIndex] = $scope;
		return $scope;
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
		if (empty($this->scopes[$filename])) {
			return [];
		}
		return array_values($this->scopes[$filename]);
	}

	/**
	 * Return the scope for a scope start index.
	 *
	 * @param string  $filename
	 * @param int  $scopeStartIndex
	 *
	 * @return ScopeInfo|null
	 */
	public function getScopeForScopeStart($filename, $scopeStartIndex)
	{
		if (empty($this->scopes[$filename][$scopeStartIndex])) {
			return null;
		}
		return $this->scopes[$filename][$scopeStartIndex];
	}
}
