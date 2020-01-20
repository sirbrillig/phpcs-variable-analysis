<?php

function foo() {
    // Using $this will not work.
    if ($this->something) {
        function nestedFunctionDeclaration() {
            // Using $this here will also not work as the nested function is a closed scope in the global namespace.
            if ($this->something) {
                // Do something.
            }
        }
    }
};

$closure = function() {
    // Using $this here is fine.
    if ($this->something) {
        function nestedFunctionDeclaration() {
            // Using $this here is not ok as the nested function is a closed scope in the global namespace.
            if ($this->something) {
                // Do something.
            }
        }
    }
};

class Foo {
    public function bar() {
        // Using $this here is fine.
        if ($this->something) {
            function nestedFunctionDeclaration() {
                // Using $this here is not ok as the nested function is a closed scope in the global namespace.
                if ($this->something) {
                    // Do something.
                }
            }
        }
    }
}

$anonClass = class() {
    public function bar() {
        // Using $this here is fine.
        if ($this->something) {
            function nestedFunctionDeclaration() {
                // Using $this here is not ok as the nested function is a closed scope in the global namespace.
                if ($this->something) {
                    // Do something.
                }
            }
        }
    }
}

trait FooTrait {
    public function bar() {
        // Using $this here is fine.
        if ($this->something) {
            function nestedFunctionDeclaration() {
                // Using $this here is not ok as the nested function is a closed scope in the global namespace.
                if ($this->something) {
                    // Do something.
                }
            }
        }
    }
}
