<?php
function function_with_this_outside_class() {
    return $this->whatever();
}

function function_with_static_members_outside_class() {
    echo SomeOtherClass::$external_static_member_var;
    return self::$whatever;
}

function function_with_late_static_binding_outside_class() {
    echo static::$whatever;
}
