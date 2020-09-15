<?php

function function_with_global_var() {
    global $var, /*comment*/ $var2, $unused; // should warn that `unused` is unused

    echo $var;
    echo $var3; // should warn that var is undefined
    echo $ice_cream; // should warn that var is undefined
    return $var2;
}

function function_with_superglobals() {
    echo print_r($GLOBALS, true);
    echo print_r($_SERVER, true);
    echo print_r($_GET, true);
    echo print_r($_POST, true);
    echo print_r($_FILES, true);
    echo print_r($_COOKIE, true);
    echo print_r($_SESSION, true);
    echo print_r($_REQUEST, true);
    echo print_r($_ENV, true);
    echo "{$GLOBALS['whatever']}";
    echo "{$GLOBALS['whatever']} $var"; // should warn that var is undefined
}

// Variables within the global scope
$cherry = 'topping';
$sunday = $ice_cream . 'and a ' . $cherry; // should warn that ice_cream is undefined
if ( $ice_cream ) { // should warn that var is undefined
    echo 'Two scoops please!';
}

function updateGlobal($newVal) {
  global $myGlobal;
  $myGlobal = $newVal;
}

function unusedGlobal() {
  global $myGlobal; // should warn that var is unused
}

function updateGlobalWithList($newVal) {
  global $myGlobal;
  list( $myGlobal, $otherVar ) = my_function($newVal);
  echo $otherVar;
}

function updateGlobalWithListShorthand($newVal) {
  global $myGlobal;
  [ $myGlobal, $otherVar ] = my_function($newVal);
  echo $otherVar;
}

function globalWithUnusedFunctionArg($user_type, $text, $testvar) { // should warn that testvar is unused
	global $config;
	return $config . $text . $user_type;
}

echo $sunday;

function function_with_reserved_variables() {
  $opts = [];
  $url  = 'https://example.com/';
  $context = stream_context_create($opts);
  $response = @file_get_contents($url, false, $context);
  $response_headers = handle_response_headers($http_response_header);
  return [
    $response,
    $response_headers,
    $php_errormsg,
    $HTTP_RAW_POST_DATA,
  ];
}
