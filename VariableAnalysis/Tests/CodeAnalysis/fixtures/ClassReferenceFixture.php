<?php
class ClassWithSymbolicRefProperty {
    public $my_property;

    function method_with_symbolic_ref_property() {
        $properties = array('my_property');
        foreach ($properties as $property) {
            $this->$property = 'some value';
            $this -> $property = 'some value';
            $this->$undefined_property = 'some value';
            $this -> $undefined_property = 'some value';
        }
    }

    function method_with_symbolic_ref_method() {
        $methods = array('method_with_symbolic_ref_property');
        foreach ($methods as $method) {
            $this->$method();
            $this -> $method();
            $this->$undefined_method();
            $this -> $undefined_method();
        }
    }
}
