<?php

namespace WidgetsFramework;

class Boolean extends Parameter {

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is boolean false</li>
     * <li>parameter is not required</li>
     * <li>position is not set (for futur use)</li>
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
     * <li>string "true" or empty => returns boolean <b>true</b></li>
     * <li>string "false" => returns boolean <b>false</b></li>
     * <li>anything else => throws UserError exception
     * </ul>
     * @param string $value The string to transform
     * @return boolean
     * @throws UserError
     */
    protected function parse($value) {

        $value = strtolower(trim($value)); // case insensitive normalisation, and remove spaces before and after

        if ($value == 'false') {
            return false;
            
        } elseif ((strlen($value) == 0) || ($value == 'true')) {
            return true;
            
        } else {
            Tools::throwUserError(wfMessage('wfmk-validate',
                    $this->getName(), $value, wfMessage('wfmk-req-boolean-value') )->text() );
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
     * @return string "true" or "false" depending on what the getValue() method returns.
     */
    public function getOutput() {
        if ($this->getValue()) {
            return 'true';
        } else {
            return 'false';
        }
    }

}
