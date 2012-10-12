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

    /**
     * Boolean can only have too values, and we accepts both.
     * @param boolean $value
     * @return boolean
     */
    protected function validate($value) {
        return $value;
    }

    /**
     * 
     * @return string "true" or "false" depending on what the parameter getValue() method returns.
     */
    public function getOutput() {
        if ($this->getValue()) {
            return 'true';
        } else {
            return 'false';
        }
    }

}
