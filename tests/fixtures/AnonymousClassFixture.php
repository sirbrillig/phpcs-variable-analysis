<?php

class ClassWithAnonymousClass {
    public function getMyClass() {
        return new class {
            public function sayHelloWorld() {
                echo 'Hello'.$this->getWorld();
            }

            protected function getWorld() {
                return ' World!';
            }
        };
    }
}
