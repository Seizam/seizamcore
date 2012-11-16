<?php

/**
 * Parameter of type boolean for widgets.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class Boolean extends Parameter {

    /**
     * <ul>
     * <li>The default value is boolean <i>false</i></li>
     * <li>The parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {

        parent::__construct($name);

        $this->default_value = false;
    }

    /**
     * Transforms from string to boolean, case insensitive.
     * <ul>
     * <li>string "true" or boolean <i>true</i> (parameter name without value) => returns boolean <i>true</i></li>
     * <li>string "false" => returns boolean <i>false</i></li>
     * <li>anything else => throws UserError exception
     * </ul>
     * @param string|boolean $value A string or boolean <i>true</i>
     * @return boolean
     * @throws UserError
     */
    protected function parse($value) {

        if ($value === true) {
            // parameter declared without value
            return true;
        }

        // value is a string
        $value = strtolower($value); // case insensitive normalisation, and remove spaces before and after

        if ($value == 'false') {
            return false;
        } elseif ($value == 'true') {
            return true;
        } else {
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-boolean-syntax')));
        }
    }

    /**
     * @return string "true" or "false" depending on the getValue() return.
     */
    public function getOutput() {
        if ($this->getValue()) {
            return 'true';
        } else {
            return 'false';
        }
    }

}
