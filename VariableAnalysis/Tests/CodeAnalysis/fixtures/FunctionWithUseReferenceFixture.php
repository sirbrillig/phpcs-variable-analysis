<?php

$ref = 0;

$function_with_use_reference = function () use (&$ref) {
    $ref = 1;
};
