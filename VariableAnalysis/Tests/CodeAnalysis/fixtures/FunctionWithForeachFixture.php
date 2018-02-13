<?php

function function_with_undefined_foreach() {
    foreach ($array as $element1) {
        echo $element1;
    }
    echo $element1;
    foreach ($array as &$element2) {
        echo $element2;
    }
    echo $element2;
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    echo "$key1 => $value1\n";
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
    echo "$key2 => $value2\n";
    foreach ($array as $element3) {
    }
    foreach ($array as &$element4) {
    }
    foreach ($array as $key3 => $value3) {
    }
    foreach ($array as $key4 => &$value4) {
    }
}

function function_with_defined_foreach() {
    $array = array();
    foreach ($array as $element1) {
        echo $element1;
    }
    echo $element1;
    foreach ($array as &$element2) {
        echo $element2;
    }
    echo $element2;
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    echo "$key1 => $value1\n";
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
    echo "$key2 => $value2\n";
    foreach ($array as $element3) {
    }
    foreach ($array as &$element4) {
    }
    foreach ($array as $key3 => $value3) {
    }
    foreach ($array as $key4 => &$value4) {
    }
}

$data = [
    ['foo', 'Foo'],
    ['bar', 'Bar'],

];
foreach ($data as $val) {
    echo json_encode($val);
}
foreach ($data as $val) {
    foreach( $val as $el ) {
        echo "hi";
    }
}
foreach ($data as list($name, $label)) {
    printf('<div id="%s">%s</div>', $name, $label);
}
foreach ($data as [$first, $second]) {
    printf('<div id="%s">%s</div>', $first, $second);
}
function doSomethingLoopy($receipts) {
    foreach ( $receipts as &$receipt ) {
        $items = $receipt->items;
        foreach ( $items AS $item ) {
            $receipt->receipt_items[] = array_filter( $item );
        }
    }
}
