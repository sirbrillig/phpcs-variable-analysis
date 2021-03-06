<?php

class ClassWithAnonymousClass {
    public function getAnonymousClass() {
        return new class {
            protected $storedHello;
            private static $storedHello2;
            private $storedHello3;
            public $helloOptions = [];
            static $aStaticOne;
            var $aVarOne;
            public function sayHelloWorld() {
                echo "hello world";
            }

            public function methodWithStaticVar() {
                static $myStaticVar; // should trigger unused warning

                echo self::$storedHello;
                echo static::$storedHello;
            }
        };
    }
}

$anonClass = new class() { // should trigger unused warning
    protected $storedHello;
    private static $storedHello2;
    private $storedHello3;
    public $helloOptions = [];
    static $aStaticOne;
    var $aVarOne;
    public function sayHelloWorld() {
        echo "hello world";
    }

    public function methodWithStaticVar() {
        static $myStaticVar; // should trigger unused warning

        echo self::$storedHello;
        echo static::$storedHello;
    }
};
