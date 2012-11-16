<?php

namespace WidgetsFramework;

class Boolean extends Parameter {

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is boolean false</li>
     * <li>parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {

        parent::__construct($name);

        $this->default_value = false;
    }

    /**
     * Transforms from string to boolean.
     * Analyse is case insensitive.
     * <ul>
     * <li>string "true" or boolean true (parameter declared without value) => returns boolean <b>true</b></li>
     * <li>string "false" => returns boolean <b>false</b></li>
     * <li>anything else => throws UserError exception
     * </ul>
     * @param string|true $value The string value to transform, or true if parameter declared without value
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
     * 
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
