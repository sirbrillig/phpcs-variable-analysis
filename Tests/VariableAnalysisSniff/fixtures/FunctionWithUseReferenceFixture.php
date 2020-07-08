<?php

$ref = 0;

$function_with_use_reference = function () use (& /*comment */ $ref) {
    $ref = 1;
};
echo $function_with_use_reference();
