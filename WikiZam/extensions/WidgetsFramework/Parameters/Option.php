<?php

namespace WidgetsFramework;

class Option extends Boolean {

    protected $output_on_true;
    protected $output_on_false;

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is boolean false</li>
     * <li>parameter is not required</li>
     * <li>output on true is this parameter name</li>
     * <li>output on false is empty string</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {

        parent::__construct($name);

        $this->output_on_true = $name;
        $this->output_on_false = '';
    }

    /**
     * 
     * @param string $value The output string when value is true.
     */
    public function setOutputOnTrue($output) {
        $this->output_on_true = $output;
    }

    public function getOutputOnTrue() {
        return $this->output_on_true;
    }

    /**
     * 
     * @param string $value The output string when value is false. 
     */
    public function setOutputOnFalse($output) {
        $this->output_on_false = $output;
    }

    public function getOutputOnFalse() {
        return $this->output_on_false;
    }

    /**
     * Transforms from string to boolean.
     * Analyse is case insensitive.
     * <ul>
     * <li>string "true" or boolean true (parameter declared without value) => returns boolean <b>true</b></li>
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

        if ($value == 'true') {
            return true;
        } else {
            Tools::throwUserError(wfMessage('wfmk-validate', $this->getName(), $value, wfMessage('wfmk-req-boolean-value')));
        }
    }

    /**
     * 
     * @return string Returns output according the value. See setOutputOnTrue() and setOutputOnFalse();
     */
    public function getOutput() {

        if ($this->getValue()) {
            return $this->getOutputOnTrue();
        } else {
            return $this->getOutputOnFalse();
        }
    }

}