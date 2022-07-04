<?php

function defineIncrementBeforeLoop($fp, $string) {
  $count = 10;
  for (
    $written = 0;
    $written < strlen($string);
    $written += $count
  ) {
    $fwrite = fwrite($fp, substr($string, $written));
    if ($fwrite === false) {
      return $written;
    }
  }
  return $written;
}

function defineIncrementAfterLoop($fp, $string) {
  for (
    $written = 0;
    $written < strlen($string);
    $written += $count // Undefined variable $count
  ) {
    $fwrite = fwrite($fp, substr($string, $written));
    if ($fwrite === false) {
      return $written;
    }
  }
  $count = 10; // This is an unused variable, but it's hard to tell because it has been read above in the same scope.
  return $written;
}

function defineIncrementInsideLoop($fp, $string) {
  for (
    $written = 0;
    $written < strlen($string);
    $written += $fwrite
  ) {
    $fwrite = fwrite($fp, substr($string, $written));
    if ($fwrite === false) {
      return $written;
    }
  }
  return $written;
}

function defineConditionBeforeLoop($fp, $string) {
  $count = 10;
  for (
    $written = 0;
    $written < $count;
    $written += 1
  ) {
    $fwrite = fwrite($fp, substr($string, $written));
    if ($fwrite === false) {
      return $written;
    }
  }
  return $written;
}

function defineConditionAfterLoop($fp, $string) {
  for (
    $written = 0;
    $written < $count; // Undefined variable $count
    $written += 1
  ) {
    $fwrite = fwrite($fp, substr($string, $written));
    if ($fwrite === false) {
      return $written;
    }
  }
  $count = 10;
  return $written;
}

function defineConditionInsideLoop($fp, $string) {
  for (
    $written = 0;
    $written < $fwrite; // Undefined variable $fwrite
    $written += 1
  ) {
    $fwrite = fwrite($fp, substr($string, $written));
    if ($fwrite === false) {
      return $written;
    }
  }
  return $written;
}

function unusedInitializer($fp, $string) {
  $written = 0;
  for (
    $foo = 0; // Unused variable $foo
    ;
    $written += 1
  ) {
    fwrite($fp, substr($string, $written));
    if ($written > 10) {
      break;
    }
  }
}

function closureInsideLoopInit() {
  for (
    $closure = function(
      $a,
      $b,
      $c // Unused variable $c
    ) {
      $m = 1; // Unused variable $m
      var_dump($i); // Undefined variable $i
      return $a * $b;
    },
    $a = 0,
    $b = 0,
    $c = 0, // Unused variable $c
    $i = 10;
  $closure($a, $b, 4) < $i;
  $i++,
    $a++,
    $t++, // Undefined variable $t
    $b++
  )
  {
    var_dump($a);
    var_dump($b);
    var_dump($m); // Undefined variable $m
  }
}

function veryBoringForLoop() {
  for (
    ;
    ;
  ) {
  }
}
