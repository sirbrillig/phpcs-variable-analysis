<?php

trait Hello {
    protected $storedHello;
    public $helloOptions = [];
    public function sayHelloWorld() {
        echo "hello world";
    }
    abstract public function getWorld();
}

