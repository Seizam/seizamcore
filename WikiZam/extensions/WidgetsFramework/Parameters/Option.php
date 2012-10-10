<?php

namespace WidgetsFramework;

class Option extends Boolean {
    
    /**
     * Returns the name of the boolean if the value is true, or empty string if false.
     * @return string
     */
    public function getOutput() {
        if ($this->getValue()) {
            return $this->getName();
        } else {
            return '';
        }
    }
    
}
