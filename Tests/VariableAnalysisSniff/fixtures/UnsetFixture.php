<?php

$foo = 'hello';

unset($foo);
unset($bar); // undefined variable $bar

function unset_loop($array) {
  foreach ($array as $value) {
  }
  unset($key, $value); // undefined variable $key
}
