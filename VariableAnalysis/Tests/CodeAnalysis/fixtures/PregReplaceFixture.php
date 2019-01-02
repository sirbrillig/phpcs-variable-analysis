<?php

function doPregReplaceWithIgnoredVariable($subject) {
    $selector = 'good morning';
    return preg_replace('/(hello \w+)/', "$selector$1", $subject, 1);
}

function doPregReplaceWithZeroIgnoredVariable($subject) {
    $selector = 'good morning';
    return preg_replace('/(hello \w+)/', "$selector$0", $subject, 1);
}

function doPregReplaceWithNonIgnoredVariable($subject) {
    $selector = 'good morning';
    return preg_replace('/(hello \w+)/', "$selector$bad", $subject, 1);
}

function doPregReplaceWithNonIgnoredAndIgnoredVariable($subject) {
    $selector = 'good morning';
    return preg_replace('/(hello \w+)/', "$selector$1$bad", $subject, 1);
}
