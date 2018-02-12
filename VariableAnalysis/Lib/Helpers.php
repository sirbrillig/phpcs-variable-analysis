<?php

namespace VariableAnalysis\Lib;

use PHP_CodeSniffer\Files\File;

class Helpers {
  public static function findContainingBrackets(File $phpcsFile, $stackPtr) {
    $tokens = $phpcsFile->getTokens();
    if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
      $openPtrs = array_keys($tokens[$stackPtr]['nested_parenthesis']);
      return end($openPtrs);
    }
    return false;
  }

  public static function areAnyConditionsAClosure($phpcsFile, $conditions) {
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

  public static function areAnyConditionsAClass($conditions) {
    foreach (array_reverse($conditions, true) as $scopePtr => $scopeCode) {
      if ($scopeCode === T_CLASS) {
        return true;
      }
    }
    return false;
  }
}
