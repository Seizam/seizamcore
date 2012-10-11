<?php

namespace WidgetsFramework;

class Boolean extends Parameter {

    public function __construct($name) {

        parent::__construct($name);

        $this->default_value = false;
        
    }

    /**
     * 
     * @param string $value
     * @return boolean
     */
    protected function parse($value) {

        $value = strtolower(trim($value));

        if ($value == 'false') {
            return false;
        } elseif ((strlen($value) == 0) || ($value == 'true')) {
            return true;
        } else {
            Tools::throwUserError('The boolean parameter ' . $this->getName() . ' can only be true or false');
        }
    }

    protected function validate($value) {
        return $value;
    }

    public function getOutput() {
        if ($this->getValue()) {
            return 'true';
        } else {
            return 'false';
        }
    }

}
