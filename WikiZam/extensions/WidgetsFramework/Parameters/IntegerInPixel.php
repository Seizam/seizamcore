<?php

/**
 * Parameter of type integer in pixel for widgets.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class IntegerInPixel extends Integer {

    /**
     * <ul>
     * <li>The default value is 0</li>
     * <li>The minimal value is 0</li>
     * <li>No maximal value</li>
     * <li>The parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->setMin(0);
    }

    /**
     * Converts from string to signed integer. The string can contains the "px" unit.
     * 
     * The minimum and maximum value depends on the system:
     * <ul>
     * <li>32 bit systems have a range of -2147483648 to 2147483647 and</li>
     * <li>32 bit systems have a range of -9223372036854775807 to 9223372036854775807.</li>
     * </ul>
     * 
     * Empty string is considered as 0.
     * @param string|boolean $value A string or boolean <i>true</i>
     * @return int
     * @throws UserError When value is not a signed integer.
     */
    public function parse($value) {

        // remove the px unit
        if (is_string($value)) {
            $value = str_ireplace(array('px', 'p', 'pt', 'pixel'), '', $value);
        }

        return parent::parse($value);
    }

}
