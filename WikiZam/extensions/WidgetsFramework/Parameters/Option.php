<?php

/**
 * Parameter of type option in pixel for widgets.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class Option extends Parameter {

    protected $output_on;
    protected $output_off;

    /**
     * <ul>
     * <li>By default the option is <i>OFF</i></li>
     * <li>The output when option is <i>ON</i> is this parameter name</li>
     * <li>The output when option is <i>OFF</i> is empty string</li>
     * <li>The parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {

        parent::__construct($name);

        $this->default_value = false;
        $this->output_on = $name;
        $this->output_off = '';
    }

    /**
     * Sets the output when option is <i>ON</i>
     * 
     * @param string $value The output
     */
    public function setONOutput($output) {
        $this->output_on = $output;
    }

    /**
     * Gets the output when option is <i>ON</i>
     * 
     * @return string The output
     */
    public function getONOutput() {
        return $this->output_on;
    }

    /**
     * Sets the output when option is <i>OFF</i>
     * 
     * @param string $value The output
     */
    public function setOFFOutput($output) {
        $this->output_off = $output;
    }

    /**
     * Gets the output when option is <i>OFF</i>
     * 
     * @return string The output
     */
    public function getOFFOutput() {
        return $this->output_off;
    }

    /**
     * Transforms from string to boolean, case insensitive.
     * <ul>
     * <li>string "true" or boolean <i>true</i> (parameter name without value)
     * => returns boolean <i>true</i> that means the option is <i>ON</i></li>
     * <li>anything else (even string "false") => throws UserError exception
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

        // case insensitive normalisation
        $value = strtolower($value);

        if ($value == 'true') {
            return true;
        } else {
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-boolean-syntax')));
        }
    }

    /**
     * 
     * @return string Returns output according the value. See setONOutput() and setOFFOutput();
     */
    public function getOutput() {

        if ($this->getValue()) {
            return $this->getONOutput();
        } else {
            return $this->getOFFOutput();
        }
    }

}