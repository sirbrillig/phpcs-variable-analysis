<?php

class MyClass {
    public function funcUsingSelfCallbackFromAlias($meta, $callback) {
        $get_meta_callback = $callback;
        return self::$get_meta_callback( $meta  );
    }

    public function funcUsingSelfCallbackFromArgument($meta, $callback) {
        return self::$callback( $meta  );
    }

    public function funcUsingStaticCallbackFromArgument($meta, $callback) {
        return static::$callback( $meta  );
    }

    public function funcUsingStaticCallbackWithUndefinedVariable($meta) {
        return static::$badName( $meta  );
    }

    public function funcUsingPropertyReference($meta, $callback) {
        return $this->$callback( $meta  );
    }

    public function funcUsingDirectCallback($meta, $callback) {
        return $callback( $meta  );
    }

    public function funcUsingPropertyReferenceDirectly($meta) {
        return $meta;
    }

    public function funcUsingPropertyReferenceWithSelf($meta) {
        return self::$$meta;
    }

    public function funcUsingPropertyReferenceWithThis($meta) {
        return $this->$meta;
    }

    public function funcUsingPropertyReferenceWithStatic($meta) {
        return static::$$meta;
    }
}
