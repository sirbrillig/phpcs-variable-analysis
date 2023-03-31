<?php

function arrowFunctionAsVariableWithNoWarnings($subject) {
    $arrowFunc = fn($foo) => $foo . $subject;
    echo $arrowFunc('hello');
}

function arrowFunctionAsVariableWithUndefinedInside($subject) {
    $arrowFunc = fn($foo) => $foo . $bar . $subject; // undefined variable $bar
    echo $arrowFunc('hello');
}

function arrowFunctionAsVariableWithUndefinedInClosure() {
    $arrowFunc = fn($foo) => $foo . $subject; // undefined variable $subject
    echo $arrowFunc('hello');
}

function arrowFunctionAsVariableWithUnusedInside($subject) {
    $arrowFunc = fn($foo) => $subject; // unused variable $foo
    echo $arrowFunc('hello');
}

function unusedArrowFunctionVariable($subject) {
    $arrowFunc = fn($foo) => $foo . $subject; // unused variable $arrowFunc
}

function arrowFunctionAsVariableUsingOutsideArrow($subject) {
    $arrowFunc = fn($foo) => $foo . $subject;
    echo $arrowFunc('hello');
    echo $foo; // undefined variable $foo
}

function arrowFunctionAsVariableWithUnusedInsideAfterUsed($subject) {
    $arrowFunc = fn($foo, $bar) => $foo . $subject; // unused variable $bar
    echo $arrowFunc('hello');
}

function arrowFunctionAsVariableWithUsedInsideAfterUnused($subject) {
    $arrowFunc = fn($foo, $bar) => $bar . $subject; // unused variable $foo (but before used)
    echo $arrowFunc('hello');
}

function arrowFunctionAsExpressionWithNoWarnings() {
    $posts = [];
    $ids = array_map(fn($post) => $post->id, $posts);
    echo $ids;
}

function arrowFunctionAsExpressionWithUndefinedVariableInside() {
    $posts = [];
    $ids = array_map(fn($post) => $post->id . $foo, $posts); // undefined variable $foo
    echo $ids;
}

function arrowFunctionAsExpressionWithUnusedVariableInside($subject) {
    $posts = [];
    $ids = array_map(fn($post) => $subject, $posts); // unused variable $post
    echo $ids;
}

function arrowFunctionAsExpressionWithUsedAfterUnused($subject) { // unused variable $subject
    $posts = [];
    $ids = array_map(fn($foo, $post) => $post->id, $posts); // unused variable $foo (but before used)
    echo $ids;
}

function arrowFunctionAsExpressionWithUnusedVariableOutsideArrow($subject) { //unused variable $subject
    $posts = [];
    $ids = array_map(fn($post) => $post->id, $posts);
    echo $ids;
    echo $post; // undefined variable $post;
}

function arrowFunctionWithVariableUsedInsideQuotes($allowed_extensions) {
    $data = array_map( fn($extension) => '.' . $extension, $allowed_extensions );
    $data = array_map( fn($extension) => ".$extension", $allowed_extensions );
    $data = array_map( fn($extension) => ".{$extension}", $allowed_extensions );
    return $data;
}

function staticArrowFunctionAsVariableWithUsedInside($subject) {
    $arrowFunc = static fn($foo) => $foo . $subject;
    echo $arrowFunc('hello');
}

function staticArrowFunctionAsVariableWithUnusedInside($subject) {
    $arrowFunc = static fn($foo) => $subject; // unused variable $foo
    echo $arrowFunc('hello');
}

function arrowFunctionAsExpressionInArgumentWithArray() {
    $type = do_something(fn($array, $needle) => $array[2] === $needle);
    echo $type;
}

function arrowFunctionAsExpressionInArgumentWithInnerCall() {
    $type = do_something(fn(Thing $func) => $func->call() ? $func : null);
    echo $type;
}

function arrowFunctionAsExpressionInArgumentWithInnerCallAndUndefinedAfterTernary() {
    $type = do_something(fn(Thing $func) => $func->call() ? $func : $foo); // undefined variable $foo
    echo $type;
}

function arrowFunctionAsExpressionInArgumentWithInnerCallAndArgs() {
    $type = do_something(fn(Thing $func) => $func->call(1,2) ? $func : null);
    echo $type;
}

function arrowFunctionAsExpressionWithUndefinedAfterComma() {
    $type = do_something(fn(Thing $func, $bar) => $func->call(1,2) ? $bar : null, $bar); // undefined variable $bar
    echo $type;
}

function arrowFunctionAsExpressionInArgumentWithInnerArrayAndArgs() {
    $type = do_something(fn(Thing $func) => $func->call([1,2]) ? $func : null);
    echo $type;
}

function arrowFunctionAsExpressionInArgumentWithSimpleTernary() {
    $type = do_something(fn(Thing $func) => $func ? $func : null);
    echo $type;
}

function arrowFunctionWithReturnType() {
    $type = do_something(fn(string $func): string => $func ? $func : '');
    echo $type;
}
