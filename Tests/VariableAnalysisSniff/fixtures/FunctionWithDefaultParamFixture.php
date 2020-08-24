<?php

function function_with_default_defined_param($unused, $param = 12) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function function_with_default_null_param($unused, $param = null) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}

function fetch_product($product_id, $meta = '', $cost = null, $currency = null, $volume = 1, $free_trial = false, $extra = array()) {
    return get_product($product_id, $meta, $currency, $cost, $volume, $free_trial, $extra);
}
