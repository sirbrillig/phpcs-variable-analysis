<?php

namespace VariableAnalysis\Lib;

use PHP_CodeSniffer\Files\File;

class Helpers {
  public static function findContainingBrackets(File $phpcsFile, int $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
      $openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
      return end($openPtrs);
    }
    return false;
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
}
