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

class ClassWithAnonymousClassAndTypeHints
{
		readonly int $main_id;
		public int $id = 1;
		public \My\Data|bool $data;

		public function test_1(): object
		{
				return new class
				{
						readonly int $main_id;
						public int $id = 123456;
						public \My\Data|bool $data;
				};
		}

		public function test_2(): object
		{
				return new class
				{
						public $id = 123456;
				};
		}
}
