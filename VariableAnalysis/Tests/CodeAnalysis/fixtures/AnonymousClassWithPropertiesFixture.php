<?php

class ClassWithAnonymousClass {
    public function getAnonymousClass() {
        return new class {
            protected $storedHello;
            private static $storedHello;
            private $storedHello;
            public $helloOptions = [];
            public function sayHelloWorld() {
                echo "hello world";
            }
        };
    }
}
