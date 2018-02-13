<?php

namespace VariableAnalysis\Lib;

use PHP_CodeSniffer\Files\File;

class Helpers {
  public static function findContainingOpeningBracket(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
      $openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
      return end($openPtrs);
    }
    return false;
  }

  public static function findParenthesisOwner(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    return $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
  }

  public static function areAnyConditionsAClosure(File $phpcsFile, array $conditions) {
    // self within a closure is invalid
    $tokens = $phpcsFile->getTokens();
    foreach (array_reverse($conditions, true) as $scopePtr => $scopeCode) {
      //  Note: have to fetch code from $tokens, T_CLOSURE isn't set for conditions codes.
      if ($tokens[$scopePtr]['code'] === T_CLOSURE) {
        return true;
      }
    }
    return false;
  }

  public static function areAnyConditionsAClass(array $conditions) {
    foreach (array_reverse($conditions, true) as $scopePtr => $scopeCode) {
      if ($scopeCode === T_CLASS) {
        return true;
      }
    }
    return false;
  }

  public static function findPreviousFunctionPtr(File $phpcsFile, int $openPtr) {
    // Function names are T_STRING, and return-by-reference is T_BITWISE_AND,
    // so we look backwards from the opening bracket for the first thing that
    // isn't a function name, reference sigil or whitespace and check if it's a
    // function keyword.
    $functionPtrTypes = [T_STRING, T_WHITESPACE, T_BITWISE_AND];
    return $phpcsFile->findPrevious($functionPtrTypes, $openPtr - 1, null, true, null, true);
  }

  public static function findFunctionCall(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr) {
      // First non-whitespace thing and see if it's a T_STRING function name
      $functionPtr = $phpcsFile->findPrevious(T_WHITESPACE, $openPtr - 1, null, true, null, true);
      if ($tokens[$functionPtr]['code'] === T_STRING) {
        return $functionPtr;
      }
    }
    return false;
  }

  public static function findFunctionCallArguments(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    // Slight hack: also allow this to find args for array constructor.
    // TODO: probably should refactor into three functions: arg-finding and bracket-finding
    if (($tokens[$stackPtr]['code'] !== T_STRING) && ($tokens[$stackPtr]['code'] !== T_ARRAY)) {
      // Assume $stackPtr is something within the brackets, find our function call
      $stackPtr = Helpers::findFunctionCall($phpcsFile, $stackPtr);
      if ($stackPtr === false) {
        return false;
      }
    }

    // $stackPtr is the function name, find our brackets after it
    $openPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true, null, true);
    if (($openPtr === false) || ($tokens[$openPtr]['code'] !== T_OPEN_PARENTHESIS)) {
      return false;
    }

    if (!isset($tokens[$openPtr]['parenthesis_closer'])) {
      return false;
    }
    $closePtr = $tokens[$openPtr]['parenthesis_closer'];

    $argPtrs = [];
    $lastPtr = $openPtr;
    $lastArgComma = $openPtr;
    while (($nextPtr = $phpcsFile->findNext(T_COMMA, $lastPtr + 1, $closePtr)) !== false) {
      if (Helpers::findContainingOpeningBracket($phpcsFile, $nextPtr) == $openPtr) {
        // Comma is at our level of brackets, it's an argument delimiter.
        array_push($argPtrs, range($lastArgComma + 1, $nextPtr - 1));
        $lastArgComma = $nextPtr;
      }
      $lastPtr = $nextPtr;
    }
    array_push($argPtrs, range($lastArgComma + 1, $closePtr - 1));

    return $argPtrs;
  }

  public static function findWhereAssignExecuted(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    //  Write should be recorded at the next statement to ensure we treat the
    //  assign as happening after the RHS execution.
    //  eg: $var = $var + 1; -> RHS could still be undef.
    //  However, if we're within a bracketed expression, we take place at the
    //  closing bracket, if that's first.
    //  eg: echo (($var = 12) && ($var == 12));
    $semicolonPtr = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1, null, false, null, true);
    $commaPtr = $phpcsFile->findNext(T_COMMA, $stackPtr + 1, null, false, null, true);
    $closePtr = false;
    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr !== false) {
      if (isset($tokens[$openPtr]['parenthesis_closer'])) {
        $closePtr = $tokens[$openPtr]['parenthesis_closer'];
      }
    }

    // Return the first thing: comma, semicolon, close-bracket, or stackPtr if nothing else
    $assignEndTokens = [$commaPtr, $semicolonPtr, $closePtr];
    $assignEndTokens = array_filter($assignEndTokens); // remove false values
    sort($assignEndTokens);
    if (empty($assignEndTokens)) {
      return $stackPtr;
    }
    return $assignEndTokens[0];
  }

  public static function isNextThingAnAssign(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();

    // Is the next non-whitespace an assignment?
    $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true, null, true);
    if ($nextPtr !== false) {
      if ($tokens[$nextPtr]['code'] === T_EQUAL) {
        return $nextPtr;
      }
    }
    return false;
  }

  public static function normalizeVarName($varName) {
    return preg_replace('/[{}$]/', '', $varName);
  }

  public static function findFunctionPrototype(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $openPtr = Helpers::findContainingOpeningBracket($phpcsFile, $stackPtr);
    if ($openPtr === false) {
      return false;
    }
    $functionPtr = Helpers::findPreviousFunctionPtr($phpcsFile, $openPtr);
    if (($functionPtr !== false) && ($tokens[$functionPtr]['code'] === T_FUNCTION)) {
      return $functionPtr;
    }
    return false;
  }

  public static function findVariableScope(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    $in_class = false;
    if (!empty($token['conditions'])) {
      foreach (array_reverse($token['conditions'], true) as $scopePtr => $scopeCode) {
        if (($scopeCode === T_FUNCTION) || ($scopeCode === T_CLOSURE)) {
          return $scopePtr;
        }
        if (($scopeCode === T_CLASS) || ($scopeCode === T_INTERFACE)) {
          $in_class = true;
        }
      }
    }

    $scopePtr = Helpers::findFunctionPrototype($phpcsFile, $stackPtr);
    if ($scopePtr !== false) {
      return $scopePtr;
    }

    if ($in_class) {
      // Member var of a class, we don't care.
      return false;
    }

    // File scope, hmm, lets use first token of file?
    return 0;
  }

  public static function getStackPtrIfVariableIsUnused(VariableInfo $varInfo) {
    if (isset($varInfo->firstDeclared)) {
      return $varInfo->firstDeclared;
    }
    if (isset($varInfo->firstInitialized)) {
      return $varInfo->firstInitialized;
    }
    return null;
  }
}
