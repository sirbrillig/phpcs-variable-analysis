<?php

namespace VariableAnalysis\Lib;

use PHP_CodeSniffer\Files\File;
use VariableAnalysis\Lib\ScopeInfo;
use VariableAnalysis\Lib\Constants;
use VariableAnalysis\Lib\ForLoopInfo;
use VariableAnalysis\Lib\EnumInfo;
use VariableAnalysis\Lib\ScopeType;
use VariableAnalysis\Lib\VariableInfo;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Conditions;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Lists;
use PHPCSUtils\Utils\Parentheses;

class Helpers
{
	/**
	 * @return array<int|string>
	 */
	public static function getPossibleEndOfFileTokens()
	{
		return array_merge(
			array_values(Tokens::$emptyTokens),
			[
				T_INLINE_HTML,
				T_CLOSE_TAG,
			]
		);
	}

	/**
	 * @param int|bool $value
	 *
	 * @return ?int
	 */
	public static function getIntOrNull($value)
	{
		return is_int($value) ? $value : null;
	}

	/**
	 * Find the position of the square bracket containing the token at $stackPtr,
	 * if any.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findContainingOpeningSquareBracket(File $phpcsFile, $stackPtr)
	{
		// Find the previous bracket within this same statement.
		$previousStatementPtr = self::getPreviousStatementPtr($phpcsFile, $stackPtr);
		$openBracketPosition = self::getIntOrNull($phpcsFile->findPrevious([T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET], $stackPtr - 1, $previousStatementPtr));
		if (empty($openBracketPosition)) {
			return null;
		}
		// Make sure we are inside the pair of brackets we found.
		$tokens = $phpcsFile->getTokens();
		$openBracketToken = $tokens[$openBracketPosition];
		if (empty($openBracketToken) || empty($tokens[$openBracketToken['bracket_closer']])) {
			return null;
		}
		$closeBracketPosition = $openBracketToken['bracket_closer'];
		if (empty($closeBracketPosition)) {
			return null;
		}
		if ($stackPtr > $closeBracketPosition) {
			return null;
		}
		return $openBracketPosition;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return int
	 */
	public static function getPreviousStatementPtr(File $phpcsFile, $stackPtr)
	{
		$result = $phpcsFile->findPrevious([T_SEMICOLON, T_CLOSE_CURLY_BRACKET], $stackPtr - 1);
		return is_bool($result) ? 1 : $result;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findContainingOpeningBracket(File $phpcsFile, $stackPtr)
	{
		// Use PHPCSUtils to get the innermost parenthesis opener
		$result = Parentheses::getLastOpener($phpcsFile, $stackPtr);

		// PHPCSUtils returns false on failure, but our code expects null
		return $result !== false ? $result : null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function areAnyConditionsAClass(File $phpcsFile, $stackPtr)
	{
		$classlikeCodes = [T_CLASS, T_ANON_CLASS, T_TRAIT];
		if (defined('T_ENUM')) {
			$classlikeCodes[] = T_ENUM;
		}
		$classlikeCodes[] = 'PHPCS_T_ENUM';
		return Conditions::hasCondition($phpcsFile, $stackPtr, $classlikeCodes);
	}

	/**
	 * Return true if the token conditions are within a function before they are
	 * within a class.
	 *
	 * @param array{conditions: (int|string)[], content: string} $token
	 *
	 * @return bool
	 */
	public static function areConditionsWithinFunctionBeforeClass(array $token)
	{
		$conditions = $token['conditions'];
		$classlikeCodes = [T_CLASS, T_ANON_CLASS, T_TRAIT];
		if (defined('T_ENUM')) {
			$classlikeCodes[] = T_ENUM;
		}
		$classlikeCodes[] = 'PHPCS_T_ENUM';
		foreach (array_reverse($conditions, true) as $scopeCode) {
			if (in_array($scopeCode, $classlikeCodes)) {
				return false;
			}
			if ($scopeCode === T_FUNCTION) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if the token conditions are within an IF/ELSE/ELSEIF block
	 * before they are within a class or function.
	 *
	 * @param (int|string)[] $conditions
	 *
	 * @return int|string|null
	 */
	public static function getClosestConditionPositionIfBeforeOtherConditions(array $conditions)
	{
		$conditionsInsideOut = array_reverse($conditions, true);
		if (empty($conditions)) {
			return null;
		}
		$scopeCode = reset($conditionsInsideOut);
		$conditionalCodes = [
			T_IF,
			T_ELSE,
			T_ELSEIF,
		];
		if (in_array($scopeCode, $conditionalCodes, true)) {
			return key($conditionsInsideOut);
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenFunctionParameter(File $phpcsFile, $stackPtr)
	{
		return is_int(self::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr));
	}

	/**
	 * Return true if the token is inside the arguments of a function call.
	 *
	 * For example, the variable `$foo` in `doSomething($foo)` is inside the
	 * arguments to the call to `doSomething()`.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideFunctionCallArgument(File $phpcsFile, $stackPtr)
	{
		return is_int(self::getFunctionIndexForFunctionCallArgument($phpcsFile, $stackPtr));
	}

	/**
	 * Find the index of the function keyword for a token in a function
	 * definition's parameters.
	 *
	 * Does not work for tokens inside the "use".
	 *
	 * Will also work for the parenthesis that make up the function definition's
	 * parameters list.
	 *
	 * For arguments inside a function call, rather than a definition, use
	 * `getFunctionIndexForFunctionCallArgument`.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getFunctionIndexForFunctionParameter(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		if ($token['code'] === 'PHPCS_T_OPEN_PARENTHESIS') {
			$startOfArguments = $stackPtr;
		} elseif ($token['code'] === 'PHPCS_T_CLOSE_PARENTHESIS') {
			if (empty($token['parenthesis_opener'])) {
				return null;
			}
			$startOfArguments = $token['parenthesis_opener'];
		} else {
			if (empty($token['nested_parenthesis'])) {
				return null;
			}
			$startingParenthesis = array_keys($token['nested_parenthesis']);
			$startOfArguments = end($startingParenthesis);
		}

		if (! is_int($startOfArguments)) {
			return null;
		}

		$nonFunctionTokenTypes = Tokens::$emptyTokens;
		$nonFunctionTokenTypes[] = T_STRING;
		$nonFunctionTokenTypes[] = T_BITWISE_AND;
		$functionPtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $startOfArguments - 1, null, true, null, true));
		if (! is_int($functionPtr)) {
			return null;
		}
		$functionToken = $tokens[$functionPtr];

		$functionTokenTypes = [
			T_FUNCTION,
			T_CLOSURE,
		];
		if (!in_array($functionToken['code'], $functionTokenTypes, true) && ! self::isArrowFunction($phpcsFile, $functionPtr)) {
			return null;
		}
		return $functionPtr;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideFunctionUseImport(File $phpcsFile, $stackPtr)
	{
		return is_int(self::getUseIndexForUseImport($phpcsFile, $stackPtr));
	}

	/**
	 * Find the token index of the "use" for a token inside a function use import
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getUseIndexForUseImport(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$nonUseTokenTypes = Tokens::$emptyTokens;
		$nonUseTokenTypes[] = T_VARIABLE;
		$nonUseTokenTypes[] = T_ELLIPSIS;
		$nonUseTokenTypes[] = T_COMMA;
		$nonUseTokenTypes[] = T_BITWISE_AND;
		$openParenPtr = self::getIntOrNull($phpcsFile->findPrevious($nonUseTokenTypes, $stackPtr - 1, null, true, null, true));
		if (! is_int($openParenPtr) || $tokens[$openParenPtr]['code'] !== T_OPEN_PARENTHESIS) {
			return null;
		}

		$usePtr = self::getIntOrNull($phpcsFile->findPrevious(array_values($nonUseTokenTypes), $openParenPtr - 1, null, true, null, true));
		if (! is_int($usePtr) || $tokens[$usePtr]['code'] !== T_USE) {
			return null;
		}
		return $usePtr;
	}

	/**
	 * Return the index of a function's name token from inside the function.
	 *
	 * $stackPtr must be inside the function body or parameters for this to work.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findFunctionCall(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$openPtr = self::findContainingOpeningBracket($phpcsFile, $stackPtr);
		if (is_int($openPtr)) {
			// First non-whitespace thing and see if it's a T_STRING function name
			$functionPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $openPtr - 1, null, true, null, true);
			if (is_int($functionPtr)) {
				$functionTokenCode = $tokens[$functionPtr]['code'];
				// In PHPCS 4.x, function names can be T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, or T_NAME_RELATIVE
				$validFunctionTokens = [
					T_STRING,
					T_NAME_FULLY_QUALIFIED,
					T_NAME_QUALIFIED,
					T_NAME_RELATIVE,
				];
				if (in_array($functionTokenCode, $validFunctionTokens, true)) {
					return $functionPtr;
				}
			}
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return array<int, array<int>>
	 */
	public static function findFunctionCallArguments(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// Slight hack: also allow this to find args for array constructor.
		if (($tokens[$stackPtr]['code'] !== T_STRING) && ($tokens[$stackPtr]['code'] !== T_ARRAY)) {
			// Assume $stackPtr is something within the brackets, find our function call
			$stackPtr = self::findFunctionCall($phpcsFile, $stackPtr);
			if ($stackPtr === null) {
				return [];
			}
		}

		// $stackPtr is the function name, find our brackets after it
		$openPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);
		if (($openPtr === false) || ($tokens[$openPtr]['code'] !== T_OPEN_PARENTHESIS)) {
			return [];
		}

		if (!isset($tokens[$openPtr]['parenthesis_closer'])) {
			return [];
		}
		$closePtr = $tokens[$openPtr]['parenthesis_closer'];

		$argPtrs = [];
		$lastPtr = $openPtr;
		$lastArgComma = $openPtr;
		$nextPtr = $phpcsFile->findNext([T_COMMA], $lastPtr + 1, $closePtr);
		while (is_int($nextPtr)) {
			if (self::findContainingOpeningBracket($phpcsFile, $nextPtr) === $openPtr) {
				// Comma is at our level of brackets, it's an argument delimiter.
				$range = range($lastArgComma + 1, $nextPtr - 1);
				array_push($argPtrs, $range);
				$lastArgComma = $nextPtr;
			}
			$lastPtr = $nextPtr;
			$nextPtr = $phpcsFile->findNext([T_COMMA], $lastPtr + 1, $closePtr);
		}
		$range = range($lastArgComma + 1, $closePtr - 1);
		$range = array_filter($range, function ($element) {
			return is_int($element);
		});
		array_push($argPtrs, $range);

		return $argPtrs;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getNextAssignPointer(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		// Is the next non-whitespace an assignment?
		$nextPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true, null, true);
		if (
			is_int($nextPtr)
			&& isset(Tokens::$assignmentTokens[$tokens[$nextPtr]['code']])
			// Ignore double arrow to prevent triggering on `foreach ( $array as $k => $v )`.
			&& $tokens[$nextPtr]['code'] !== T_DOUBLE_ARROW
		) {
			return $nextPtr;
		}
		return null;
	}

	/**
	 * @param string $varName
	 *
	 * @return string
	 */
	public static function normalizeVarName($varName)
	{
		$result = preg_replace('/[{}$]/', '', $varName);
		return $result ? $result : $varName;
	}

	/**
	 * @param File   $phpcsFile
	 * @param int    $stackPtr
	 * @param string $varName   (optional) if it differs from the normalized 'content' of the token at $stackPtr
	 *
	 * @return ?int
	 */
	public static function findVariableScope(File $phpcsFile, $stackPtr, $varName = null)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$varName = isset($varName) ? $varName : self::normalizeVarName($token['content']);

		$enclosingScopeIndex = self::findVariableScopeExceptArrowFunctions($phpcsFile, $stackPtr);

		if (!is_null($enclosingScopeIndex)) {
			$arrowFunctionIndex = self::getContainingArrowFunctionIndex($phpcsFile, $stackPtr, $enclosingScopeIndex);
			$isTokenInsideArrowFunctionBody = is_int($arrowFunctionIndex);
			if ($isTokenInsideArrowFunctionBody) {
				// Get the list of variables defined by the arrow function
				// If this matches any of them, the scope is the arrow function,
				// otherwise, it uses the enclosing scope.
				if ($arrowFunctionIndex) {
					$variableNames = self::getVariablesDefinedByArrowFunction($phpcsFile, $arrowFunctionIndex);
					self::debug('findVariableScope: looking for', $varName, 'in arrow function variables', $variableNames);
					if (in_array($varName, $variableNames, true)) {
						return $arrowFunctionIndex;
					}
				}
			}
		}

		return $enclosingScopeIndex;
	}

	/**
	 * Return the variable names and positions of each variable targetted by a `compact()` call.
	 *
	 * @param File                   $phpcsFile
	 * @param int                    $stackPtr
	 * @param array<int, array<int>> $arguments The stack pointers of each argument; see findFunctionCallArguments
	 *
	 * @return array<VariableInfo> each variable's firstRead position and its name; other VariableInfo properties are not set!
	 */
	public static function getVariablesInsideCompact(File $phpcsFile, $stackPtr, $arguments)
	{
		$tokens = $phpcsFile->getTokens();
		$variablePositionsAndNames = [];

		foreach ($arguments as $argumentPtrs) {
			$argumentPtrs = array_values(array_filter($argumentPtrs, function ($argumentPtr) use ($tokens) {
				return isset(Tokens::$emptyTokens[$tokens[$argumentPtr]['code']]) === false;
			}));
			if (empty($argumentPtrs)) {
				continue;
			}
			if (!isset($tokens[$argumentPtrs[0]])) {
				continue;
			}
			$argumentFirstToken = $tokens[$argumentPtrs[0]];
			if ($argumentFirstToken['code'] === T_ARRAY) {
				// It's an array argument, recurse.
				$arrayArguments = self::findFunctionCallArguments($phpcsFile, $argumentPtrs[0]);
				$variablePositionsAndNames = array_merge($variablePositionsAndNames, self::getVariablesInsideCompact($phpcsFile, $stackPtr, $arrayArguments));
				continue;
			}
			if (count($argumentPtrs) > 1) {
				// Complex argument, we can't handle it, ignore.
				continue;
			}
			if ($argumentFirstToken['code'] === T_CONSTANT_ENCAPSED_STRING) {
				// Single-quoted string literal, ie compact('whatever').
				// Substr is to strip the enclosing single-quotes.
				$varName = substr($argumentFirstToken['content'], 1, -1);
				$variable = new VariableInfo($varName);
				$variable->firstRead = $argumentPtrs[0];
				$variablePositionsAndNames[] = $variable;
				continue;
			}
			if ($argumentFirstToken['code'] === T_DOUBLE_QUOTED_STRING) {
				// Double-quoted string literal.
				$regexp = Constants::getDoubleQuotedVarRegexp();
				if (! empty($regexp) && preg_match($regexp, $argumentFirstToken['content'])) {
					// Bail if the string needs variable expansion, that's runtime stuff.
					continue;
				}
				// Substr is to strip the enclosing double-quotes.
				$varName = substr($argumentFirstToken['content'], 1, -1);
				$variable = new VariableInfo($varName);
				$variable->firstRead = $argumentPtrs[0];
				$variablePositionsAndNames[] = $variable;
				continue;
			}
		}
		return $variablePositionsAndNames;
	}

	/**
	 * Return the token index of the scope start for a token
	 *
	 * For a variable within a function body, or a variable within a function
	 * definition argument list, this will return the function keyword's index.
	 *
	 * For a variable within a "use" import list within a function definition,
	 * this will return the enclosing scope, not the function keyword. This is
	 * important to note because the "use" keyword performs double-duty, defining
	 * variables for the function's scope, and consuming the variables in the
	 * enclosing scope. Use `getUseIndexForUseImport` to determine if this
	 * token needs to be treated as a "use".
	 *
	 * For a variable within an arrow function definition argument list,
	 * this will return the arrow function's keyword index.
	 *
	 * For a variable in an arrow function body, this will return the enclosing
	 * function's index, which may be incorrect.
	 *
	 * Since a variable in an arrow function's body may be imported from the
	 * enclosing scope, it's important to test to see if the variable is in an
	 * arrow function and also check its enclosing scope separately.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function findVariableScopeExceptArrowFunctions(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$allowedTypes = [
			T_VARIABLE,
			T_DOUBLE_QUOTED_STRING,
			T_HEREDOC,
			T_STRING,
			T_NAME_FULLY_QUALIFIED,
			T_NAME_QUALIFIED,
			T_NAME_RELATIVE,
		];
		if (! in_array($tokens[$stackPtr]['code'], $allowedTypes, true)) {
			throw new \Exception("Cannot find variable scope for non-variable {$tokens[$stackPtr]['type']}");
		}

		$startOfTokenScope = self::getStartOfTokenScope($phpcsFile, $stackPtr);
		if (is_int($startOfTokenScope) && $startOfTokenScope > 0) {
			return $startOfTokenScope;
		}

		// If there is no "conditions" array, this is a function definition argument.
		if (self::isTokenFunctionParameter($phpcsFile, $stackPtr)) {
			$functionPtr = self::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr);
			if (! is_int($functionPtr)) {
				throw new \Exception("Function index not found for function argument index {$stackPtr}");
			}
			return $functionPtr;
		}

		self::debug('Cannot find function scope for variable at', $stackPtr);
		return $startOfTokenScope;
	}

	/**
	 * Return the token index of the scope start for a variable token
	 *
	 * This will only work for a variable within a function's body. Otherwise,
	 * see `findVariableScope`, which is more complex.
	 *
	 * Note that if used on a variable in an arrow function, it will return the
	 * enclosing function's scope, which may be incorrect.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	private static function getStartOfTokenScope(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];

		$inClass = false;
		$conditions = isset($token['conditions']) ? $token['conditions'] : [];
		$functionTokenTypes = [
			T_FUNCTION,
			T_CLOSURE,
		];
		foreach (array_reverse($conditions, true) as $scopePtr => $scopeCode) {
			if (in_array($scopeCode, $functionTokenTypes, true) || self::isArrowFunction($phpcsFile, $scopePtr)) {
				return $scopePtr;
			}
			if (isset(Tokens::$ooScopeTokens[$scopeCode]) === true) {
				$inClass = true;
			}
		}

		if ($inClass) {
			// If this is inside a class and not inside a function, this is either a
			// class member variable definition, or a function argument. If it is a
			// variable definition, it has no scope on its own (it can only be used
			// with an object reference). If it is a function argument, we need to do
			// more work (see `findVariableScopeExceptArrowFunctions`).
			return null;
		}

		// If we can't find a scope, let's use the first token of the file.
		return 0;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 * @param int  $enclosingScopeIndex
	 *
	 * @return ?int
	 */
	public static function getContainingArrowFunctionIndex(File $phpcsFile, $stackPtr, $enclosingScopeIndex)
	{
		$arrowFunctionIndex = self::getPreviousArrowFunctionIndex($phpcsFile, $stackPtr, $enclosingScopeIndex);
		if (! is_int($arrowFunctionIndex)) {
			return null;
		}
		$arrowFunctionInfo = self::getArrowFunctionOpenClose($phpcsFile, $arrowFunctionIndex);
		if (! $arrowFunctionInfo) {
			return null;
		}

		// We found the closest arrow function before this token. If the token is
		// within the scope of that arrow function, then return it.
		if ($stackPtr >= $arrowFunctionInfo['scope_opener'] && $stackPtr <= $arrowFunctionInfo['scope_closer']) {
			return $arrowFunctionIndex;
		}

		// If the token is after the scope of the closest arrow function, we may
		// still be inside the scope of a nested arrow function, so we need to
		// search further back until we are certain there are no more arrow
		// functions.
		if ($stackPtr > $arrowFunctionInfo['scope_closer']) {
			return self::getContainingArrowFunctionIndex($phpcsFile, $arrowFunctionIndex, $enclosingScopeIndex);
		}

		return null;
	}

	/**
	 * Move back from the stackPtr to the start of the enclosing scope until we
	 * find a 'fn' token that starts an arrow function, returning the index of
	 * that token. Returns null if there are no arrow functions before stackPtr.
	 *
	 * Note that this does not guarantee that stackPtr is inside the arrow
	 * function scope we find!
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 * @param int  $enclosingScopeIndex
	 *
	 * @return ?int
	 */
	private static function getPreviousArrowFunctionIndex(File $phpcsFile, $stackPtr, $enclosingScopeIndex)
	{
		$tokens = $phpcsFile->getTokens();
		for ($index = $stackPtr - 1; $index > $enclosingScopeIndex; $index--) {
			$token = $tokens[$index];
			if ($token['content'] === 'fn' && self::isArrowFunction($phpcsFile, $index)) {
				return $index;
			}
		}
		return null;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isArrowFunction(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		return $tokens[$stackPtr]['code'] === T_FN;
	}

	/**
	 * Find the opening and closing scope positions for an arrow function if the
	 * given position is the start of the arrow function (the `fn` keyword
	 * token).
	 *
	 * Returns null if the passed token is not an arrow function keyword.
	 *
	 * If the token is an arrow function keyword, the scope opener is returned as
	 * the provided position.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?array<string, int>
	 */
	public static function getArrowFunctionOpenClose(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['code'] !== T_FN) {
			return null;
		}

		if (!isset($tokens[$stackPtr]['scope_closer'])) {
			return null;
		}

		return [
			'scope_opener' => $tokens[$stackPtr]['scope_opener'],
			'scope_closer' => $tokens[$stackPtr]['scope_closer'],
		];
	}

	/**
	 * Return a list of indices for variables assigned within a list assignment.
	 *
	 * The index provided can be either the opening square brace of a short list
	 * assignment like the first character of `[$a] = $b;` or the `list` token of
	 * an expression like `list($a) = $b;` or the opening parenthesis of that
	 * expression.
	 *
	 * @param File $phpcsFile
	 * @param int  $listOpenerIndex
	 *
	 * @return ?array<int>
	 */
	public static function getListAssignments(File $phpcsFile, $listOpenerIndex)
	{
		self::debug('getListAssignments', $listOpenerIndex, $phpcsFile->getTokens()[$listOpenerIndex]);

		// Use PHPCSUtils to get detailed assignment information
		try {
			$assignments = \PHPCSUtils\Utils\Lists::getAssignments($phpcsFile, $listOpenerIndex);
		} catch (\PHPCSUtils\Exceptions\UnexpectedTokenType $e) {
			// Not a list token
			return null;
		}

		if (empty($assignments)) {
			return null;
		}

		// Extract just the variable token positions for backward compatibility
		$variablePtrs = [];
		foreach ($assignments as $assignment) {
			// Skip empty list items like in: list($a, , $b)
			if ($assignment['is_empty']) {
				continue;
			}

			// For nested lists, recursively get the assignments
			if ($assignment['is_nested_list'] && $assignment['assignment_token'] !== false) {
				$nestedVars = self::getListAssignments($phpcsFile, $assignment['assignment_token']);
				if (is_array($nestedVars)) {
					$variablePtrs = array_merge($variablePtrs, $nestedVars);
				}
				continue;
			}

			// For regular variables, use the assignment_token which points to the T_VARIABLE
			if ($assignment['assignment_token'] !== false && $assignment['variable'] !== false) {
				$variablePtrs[] = $assignment['assignment_token'];
			}
		}

		return empty($variablePtrs) ? null : $variablePtrs;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return string[]
	 */
	public static function getVariablesDefinedByArrowFunction(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$arrowFunctionToken = $tokens[$stackPtr];
		$variableNames = [];
		self::debug('looking for variables in arrow function token', $arrowFunctionToken);
		for ($index = $arrowFunctionToken['parenthesis_opener']; $index < $arrowFunctionToken['parenthesis_closer']; $index++) {
			$token = $tokens[$index];
			if ($token['code'] === T_VARIABLE) {
				$variableNames[] = self::normalizeVarName($token['content']);
			}
		}
		self::debug('found these variables in arrow function token', $variableNames);
		return $variableNames;
	}

	/**
	 * @return void
	 */
	public static function debug()
	{
		$messages = func_get_args();
		if (! defined('PHP_CODESNIFFER_VERBOSITY')) {
			return;
		}
		if (PHP_CODESNIFFER_VERBOSITY <= 3) {
			return;
		}
		$output = PHP_EOL . 'VariableAnalysisSniff: DEBUG:';
		foreach ($messages as $message) {
			if (is_string($message) || is_numeric($message)) {
				$output .= ' "' . $message . '"';
				continue;
			}
			$output .= PHP_EOL . var_export($message, true) . PHP_EOL;
		}
		$output .= PHP_EOL;
		echo $output;
	}

	/**
	 * @param string $pattern
	 * @param string $value
	 *
	 * @return string[]
	 */
	public static function splitStringToArray($pattern, $value)
	{
		if (empty($pattern)) {
			return [];
		}
		$result = preg_split($pattern, $value);
		return is_array($result) ? $result : [];
	}

	/**
	 * @param string $varName
	 *
	 * @return bool
	 */
	public static function isVariableANumericVariable($varName)
	{
		return is_numeric(substr($varName, 0, 1));
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideElseCondition(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$nonFunctionTokenTypes = Tokens::$emptyTokens;
		$nonFunctionTokenTypes[] = T_OPEN_PARENTHESIS;
		$nonFunctionTokenTypes[] = T_INLINE_HTML;
		$nonFunctionTokenTypes[] = T_CLOSE_TAG;
		$nonFunctionTokenTypes[] = T_VARIABLE;
		$nonFunctionTokenTypes[] = T_ELLIPSIS;
		$nonFunctionTokenTypes[] = T_COMMA;
		$nonFunctionTokenTypes[] = T_STRING;
		$nonFunctionTokenTypes[] = T_BITWISE_AND;
		$elsePtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $stackPtr - 1, null, true, null, true));
		$elseTokenTypes = [
			T_ELSE,
			T_ELSEIF,
		];
		if (is_int($elsePtr) && in_array($tokens[$elsePtr]['code'], $elseTokenTypes, true)) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideElseBody(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$conditions = isset($token['conditions']) ? $token['conditions'] : [];
		$elseTokenTypes = [
			T_ELSE,
			T_ELSEIF,
		];
		foreach (array_reverse($conditions, true) as $scopeCode) {
			if (in_array($scopeCode, $elseTokenTypes, true)) {
				return true;
			}
		}

		// Some else body code will not have conditions because it is inline (no
		// curly braces) so we have to look in other ways.
		$previousSemicolonPtr = $phpcsFile->findPrevious([T_SEMICOLON], $stackPtr - 1);
		if (! is_int($previousSemicolonPtr)) {
			$previousSemicolonPtr = 0;
		}
		$elsePtr = $phpcsFile->findPrevious([T_ELSE, T_ELSEIF], $stackPtr - 1, $previousSemicolonPtr);
		if (is_int($elsePtr)) {
			return true;
		}

		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return int[]
	 */
	public static function getAttachedBlockIndicesForElse(File $phpcsFile, $stackPtr)
	{
		$currentElsePtr = $phpcsFile->findPrevious([T_ELSE, T_ELSEIF], $stackPtr - 1);
		if (! is_int($currentElsePtr)) {
			throw new \Exception("Cannot find expected else at {$stackPtr}");
		}

		$ifPtr = $phpcsFile->findPrevious([T_IF], $currentElsePtr - 1);
		if (! is_int($ifPtr)) {
			throw new \Exception("Cannot find if for else at {$stackPtr}");
		}
		$blockIndices = [$ifPtr];

		$previousElseIfPtr = $currentElsePtr;
		do {
			$elseIfPtr = $phpcsFile->findPrevious([T_ELSEIF], $previousElseIfPtr - 1, $ifPtr);
			if (is_int($elseIfPtr)) {
				$blockIndices[] = $elseIfPtr;
				$previousElseIfPtr = $elseIfPtr;
			}
		} while (is_int($elseIfPtr));

		return $blockIndices;
	}

	/**
	 * @param int $needle
	 * @param int $scopeStart
	 * @param int $scopeEnd
	 *
	 * @return bool
	 */
	public static function isIndexInsideScope($needle, $scopeStart, $scopeEnd)
	{
		return ($needle > $scopeStart && $needle < $scopeEnd);
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $scopeStartIndex
	 *
	 * @return int
	 */
	public static function getScopeCloseForScopeOpen(File $phpcsFile, $scopeStartIndex)
	{
		$tokens = $phpcsFile->getTokens();
		$scopeCloserIndex = isset($tokens[$scopeStartIndex]['scope_closer']) ? $tokens[$scopeStartIndex]['scope_closer'] : 0;
		if ($scopeStartIndex === 0) {
			$scopeCloserIndex = self::getLastNonEmptyTokenIndexInFile($phpcsFile);
		}
		return $scopeCloserIndex;
	}

	/**
	 * @param File $phpcsFile
	 *
	 * @return int
	 */
	public static function getLastNonEmptyTokenIndexInFile(File $phpcsFile)
	{
		$tokens = $phpcsFile->getTokens();
		foreach (array_reverse($tokens, true) as $index => $token) {
			if (! in_array($token['code'], self::getPossibleEndOfFileTokens(), true)) {
				return $index;
			}
		}
		self::debug('no non-empty token found for end of file');
		return 0;
	}

	/**
	 * @param VariableInfo $varInfo
	 * @param ScopeInfo    $scopeInfo
	 *
	 * @return bool
	 */
	public static function areFollowingArgumentsUsed(VariableInfo $varInfo, ScopeInfo $scopeInfo)
	{
		$foundVarPosition = false;
		foreach ($scopeInfo->variables as $variable) {
			if ($variable === $varInfo) {
				$foundVarPosition = true;
				continue;
			}
			if (! $foundVarPosition) {
				continue;
			}
			if ($variable->scopeType !== ScopeType::PARAM) {
				continue;
			}
			if ($variable->firstRead) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param File         $phpcsFile
	 * @param VariableInfo $varInfo
	 * @param ScopeInfo    $scopeInfo
	 *
	 * @return bool
	 */
	public static function isRequireInScopeAfter(File $phpcsFile, VariableInfo $varInfo, ScopeInfo $scopeInfo)
	{
		$requireTokens = [
			T_REQUIRE,
			T_REQUIRE_ONCE,
			T_INCLUDE,
			T_INCLUDE_ONCE,
		];
		$indexToStartSearch = $varInfo->firstDeclared;
		if (! empty($varInfo->firstInitialized)) {
			$indexToStartSearch = $varInfo->firstInitialized;
		}
		$tokens = $phpcsFile->getTokens();
		$indexToStopSearch = isset($tokens[$scopeInfo->scopeStartIndex]['scope_closer']) ? $tokens[$scopeInfo->scopeStartIndex]['scope_closer'] : null;
		if (! is_int($indexToStartSearch) || ! is_int($indexToStopSearch)) {
			return false;
		}
		$requireTokenIndex = $phpcsFile->findNext($requireTokens, $indexToStartSearch + 1, $indexToStopSearch);
		if (is_int($requireTokenIndex)) {
			return true;
		}
		return false;
	}

	/**
	 * Find the index of the function keyword for a token in a function call's arguments
	 *
	 * For the variable `$foo` in the expression `doSomething($foo)`, this will
	 * return the index of the `doSomething` token.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ?int
	 */
	public static function getFunctionIndexForFunctionCallArgument(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		if (empty($token['nested_parenthesis'])) {
			return null;
		}
		/**
		 * @var list<int|string>
		 */
		$startingParenthesis = array_keys($token['nested_parenthesis']);
		$startOfArguments = end($startingParenthesis);
		if (! is_int($startOfArguments)) {
			return null;
		}

		$nonFunctionTokenTypes = Tokens::$emptyTokens;
		$functionPtr = self::getIntOrNull($phpcsFile->findPrevious($nonFunctionTokenTypes, $startOfArguments - 1, null, true, null, true));
		if (! is_int($functionPtr) || ! isset($tokens[$functionPtr]['code'])) {
			return null;
		}
		if (
			$tokens[$functionPtr]['content'] === 'function'
			|| ($tokens[$functionPtr]['content'] === 'fn' && self::isArrowFunction($phpcsFile, $functionPtr))
		) {
			// If there is a function/fn keyword before the beginning of the parens,
			// this is a function definition and not a function call.
			return null;
		}
		if (! empty($tokens[$functionPtr]['scope_opener'])) {
			// If the alleged function name has a scope, this is not a function call.
			return null;
		}

		$functionNameType = $tokens[$functionPtr]['code'];
		if (! in_array($functionNameType, Tokens::$functionNameTokens, true)) {
			// If the alleged function name is not a variable or a string, this is
			// not a function call.
			return null;
		}

		if ($tokens[$functionPtr]['level'] !== $tokens[$stackPtr]['level']) {
			// If the variable is inside a different scope than the function name,
			// the function call doesn't apply to the variable.
			return null;
		}

		return $functionPtr;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideIssetOrEmpty(File $phpcsFile, $stackPtr)
	{
		// Use PHPCSUtils which handles all edge cases across PHP/PHPCS versions
		return Context::inIsset($phpcsFile, $stackPtr) || Context::inEmpty($phpcsFile, $stackPtr);
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableArrayPushShortcut(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$nonFunctionTokenTypes = Tokens::$emptyTokens;

		$arrayPushOperatorIndex1 = self::getIntOrNull($phpcsFile->findNext($nonFunctionTokenTypes, $stackPtr + 1, null, true, null, true));
		if (! is_int($arrayPushOperatorIndex1)) {
			return false;
		}
		if (! isset($tokens[$arrayPushOperatorIndex1]['content']) || $tokens[$arrayPushOperatorIndex1]['content'] !== '[') {
			return false;
		}

		$arrayPushOperatorIndex2 = self::getIntOrNull($phpcsFile->findNext($nonFunctionTokenTypes, $arrayPushOperatorIndex1 + 1, null, true, null, true));
		if (! is_int($arrayPushOperatorIndex2)) {
			return false;
		}
		if (! isset($tokens[$arrayPushOperatorIndex2]['content']) || $tokens[$arrayPushOperatorIndex2]['content'] !== ']') {
			return false;
		}

		$arrayPushOperatorIndex3 = self::getIntOrNull($phpcsFile->findNext($nonFunctionTokenTypes, $arrayPushOperatorIndex2 + 1, null, true, null, true));
		if (! is_int($arrayPushOperatorIndex3)) {
			return false;
		}
		if (! isset($tokens[$arrayPushOperatorIndex3]['content']) || $tokens[$arrayPushOperatorIndex3]['content'] !== '=') {
			return false;
		}

		return true;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isVariableInsideUnset(File $phpcsFile, $stackPtr)
	{
		// Use PHPCSUtils which handles all edge cases across PHP/PHPCS versions
		return Context::inUnset($phpcsFile, $stackPtr);
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideAssignmentRHS(File $phpcsFile, $stackPtr)
	{
		$previousStatementPtr = $phpcsFile->findPrevious([T_SEMICOLON, T_CLOSE_CURLY_BRACKET, T_OPEN_CURLY_BRACKET, T_COMMA], $stackPtr - 1);
		if (! is_int($previousStatementPtr)) {
			$previousStatementPtr = 1;
		}
		$previousTokenPtr = $phpcsFile->findPrevious([T_EQUAL], $stackPtr - 1, $previousStatementPtr);
		if (is_int($previousTokenPtr)) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenInsideAssignmentLHS(File $phpcsFile, $stackPtr)
	{
		// Is the next non-whitespace an assignment?
		$assignPtr = self::getNextAssignPointer($phpcsFile, $stackPtr);
		if (! is_int($assignPtr)) {
			return false;
		}

		// Is this a variable variable? If so, it's not an assignment to the current variable.
		if (self::isTokenVariableVariable($phpcsFile, $stackPtr)) {
			self::debug('found variable variable');
			return false;
		}
		return true;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isTokenVariableVariable(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
		if ($prev === false) {
			return false;
		}
		if ($tokens[$prev]['code'] === T_DOLLAR) {
			return true;
		}
		if ($tokens[$prev]['code'] !== T_OPEN_CURLY_BRACKET) {
			return false;
		}

		$prevPrev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prev - 1), null, true);
		if ($prevPrev !== false && $tokens[$prevPrev]['code'] === T_DOLLAR) {
			return true;
		}
		return false;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return EnumInfo|null
	 */
	public static function makeEnumInfo(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];

		if (isset($token['scope_opener'])) {
			$blockStart = $token['scope_opener'];
			$blockEnd = $token['scope_closer'];
		} else {
			// Enums before phpcs could detect them do not have scopes so we have to
			// find them ourselves.

			$blockStart = $phpcsFile->findNext([T_OPEN_CURLY_BRACKET], $stackPtr + 1);
			if (! is_int($blockStart)) {
				return null;
			}
			$blockEnd = $tokens[$blockStart]['bracket_closer'];
		}

		return new EnumInfo(
			$stackPtr,
			$blockStart,
			$blockEnd
		);
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return ForLoopInfo
	 */
	public static function makeForLoopInfo(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		$forIndex = $stackPtr;
		$blockStart = $token['parenthesis_closer'];
		if (isset($token['scope_opener'])) {
			$blockStart = $token['scope_opener'];
			$blockEnd = $token['scope_closer'];
		} else {
			// Some for loop blocks will not have scope positions because it they are
			// inline (no curly braces) so we have to find the end of their scope by
			// looking for the end of the next statement.
			$nextSemicolonIndex = $phpcsFile->findNext([T_SEMICOLON], $token['parenthesis_closer']);
			if (! is_int($nextSemicolonIndex)) {
				$nextSemicolonIndex = $token['parenthesis_closer'] + 1;
			}
			$blockEnd = $nextSemicolonIndex;
		}
		$initStart = intval($token['parenthesis_opener']) + 1;
		$initEnd = null;
		$conditionStart = null;
		$conditionEnd = null;
		$incrementStart = null;
		$incrementEnd = $token['parenthesis_closer'] - 1;

		$semicolonCount = 0;
		$forLoopLevel = $tokens[$forIndex]['level'];
		$forLoopNestedParensCount = 1;

		if (isset($tokens[$forIndex]['nested_parenthesis'])) {
			$forLoopNestedParensCount = count($tokens[$forIndex]['nested_parenthesis']) + 1;
		}

		for ($i = $initStart; ($i <= $incrementEnd && $semicolonCount < 2); $i++) {
			if ($tokens[$i]['code'] !== T_SEMICOLON) {
				continue;
			}

			if ($tokens[$i]['level'] !== $forLoopLevel) {
				continue;
			}

			if (count($tokens[$i]['nested_parenthesis']) !== $forLoopNestedParensCount) {
				continue;
			}

			switch ($semicolonCount) {
				case 0:
					$initEnd = $i;
					$conditionStart = $initEnd + 1;
					break;
				case 1:
					$conditionEnd = $i;
					$incrementStart = $conditionEnd + 1;
					break;
			}
			$semicolonCount += 1;
		}

		if ($initEnd === null || $conditionStart === null || $conditionEnd === null || $incrementStart === null) {
			throw new \Exception("Cannot parse for loop at position {$forIndex}");
		}

		return new ForLoopInfo(
			$forIndex,
			$blockStart,
			$blockEnd,
			$initStart,
			$initEnd,
			$conditionStart,
			$conditionEnd,
			$incrementStart,
			$incrementEnd
		);
	}

	/**
	 * @param int                     $stackPtr
	 * @param array<int, ForLoopInfo> $forLoops
	 * @return ForLoopInfo|null
	 */
	public static function getForLoopForIncrementVariable($stackPtr, $forLoops)
	{
		foreach ($forLoops as $forLoop) {
			if ($stackPtr > $forLoop->incrementStart && $stackPtr < $forLoop->incrementEnd) {
				return $forLoop;
			}
		}
		return null;
	}

	/**
	 * Return true if the token looks like constructor promotion.
	 *
	 * Call on a parameter variable token only.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isConstructorPromotion(File $phpcsFile, $stackPtr)
	{
		$functionIndex = self::getFunctionIndexForFunctionParameter($phpcsFile, $stackPtr);
		if (! $functionIndex) {
			return false;
		}
		$params = FunctionDeclarations::getParameters($phpcsFile, $functionIndex);
		foreach ($params as $param) {
			if ($param['token'] === $stackPtr) {
				return isset($param['property_visibility']);
			}
		}
		return false;
	}

	/**
	 * If looking at a function call token, return a string for the full function
	 * name including any inline namespace.
	 *
	 * So for example, if the call looks like `\My\Namespace\doSomething($bar)`
	 * and `$stackPtr` refers to `doSomething`, this will return
	 * `\My\Namespace\doSomething`.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return string|null
	 */
	public static function getFunctionNameWithNamespace(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		if (! isset($tokens[$stackPtr])) {
			return null;
		}
		$startOfScope = self::findVariableScope($phpcsFile, $stackPtr);
		$functionName = $tokens[$stackPtr]['content'];

		// In PHPCS 4.x, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, and T_NAME_RELATIVE
		// tokens already contain the full namespaced name, so we can return early.
		if ($tokens[$stackPtr]['code'] === T_NAME_FULLY_QUALIFIED) {
			return $functionName;
		}
		if ($tokens[$stackPtr]['code'] === T_NAME_QUALIFIED) {
			return $functionName;
		}
		if ($tokens[$stackPtr]['code'] === T_NAME_RELATIVE) {
			return $functionName;
		}

		// Move backwards from the token, collecting namespace separators and
		// strings, until we encounter whitespace or something else.
		$partOfNamespace = [
			T_NS_SEPARATOR,
			T_STRING,
			T_NAME_QUALIFIED,
			T_NAME_RELATIVE,
			T_NAME_FULLY_QUALIFIED,
		];
		for ($i = $stackPtr - 1; $i > $startOfScope; $i--) {
			if (! in_array($tokens[$i]['code'], $partOfNamespace, true)) {
				break;
			}
			$functionName = "{$tokens[$i]['content']}{$functionName}";
		}
		return $functionName;
	}

	/**
	 * Return true if the token is inside an abstract class.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return bool
	 */
	public static function isInAbstractClass(File $phpcsFile, $stackPtr)
	{
		$classIndex = $phpcsFile->getCondition($stackPtr, T_CLASS);
		if (! is_int($classIndex)) {
			return false;
		}
		$classProperties = $phpcsFile->getClassProperties($classIndex);
		return $classProperties['is_abstract'];
	}

	/**
	 * Return true if the function body is empty or contains only `return;`
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr  The index of the function keyword.
	 *
	 * @return bool
	 */
	public static function isFunctionBodyEmpty(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		if ($tokens[$stackPtr]['code'] !== T_FUNCTION) {
			return false;
		}
		$functionScopeStart = $tokens[$stackPtr]['scope_opener'];
		$functionScopeEnd = $tokens[$stackPtr]['scope_closer'];
		$tokensToIgnore = array_merge(
			Tokens::$emptyTokens,
			[
				T_RETURN,
				T_SEMICOLON,
				T_OPEN_CURLY_BRACKET,
				T_CLOSE_CURLY_BRACKET,
			]
		);
		for ($i = $functionScopeStart; $i < $functionScopeEnd; $i++) {
			if (! in_array($tokens[$i]['code'], $tokensToIgnore, true)) {
				return false;
			}
		}
		return true;
	}
}
