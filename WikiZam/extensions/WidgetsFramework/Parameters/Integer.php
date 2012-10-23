<?php

namespace WidgetsFramework;

class Integer extends Parameter {

    protected $min;
    protected $max;

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>parameter is not required</li>
     * <li>default value is integer 0</li>
     * <li>no minimal value</li>
     * <li>no maximal value</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {

        parent::__construct($name);

        $this->default_value = 0;

        $this->min = null;
        $this->max = null;
    }

    /**
     * Convert from string to signed integer.
     * The minimum and maximum value depends on the system. 32 bit systems have 
     * a maximum signed integer range of -2147483648 to 2147483647.
     * The maximum signed integer value for 64 bit systems is 9223372036854775807.
     * Empty string is considered as 0.
     * @param string|true $value The string value to transform, or true if parameter specified without value
     * @return int
     * @throws UserError If parameter named without value or value is not a signed integer
     */
    public function parse($value) {

        if ($value === true) {
            // parameter specified without value
            Tools::throwUserError(wfMessage('wfmk-req-value', $this->getName()));
        }

        // value is a string

        $space_free = str_replace(' ', '', $value); // remove any space inside the number

        if (strlen($value) == 0) {
            // empty string
            Tools::throwUserError(wfMessage('wfmk-validate', $this->getName(), $value, wfMessage('wfmk-req-integer-value')->text()));
        }

        // remove the minus sign if present to not break ctype_digit()
        $ctype_test = $space_free[0] == '-' ? substr($space_free, 1) : $space_free;

        if (!ctype_digit($ctype_test)) {
            Tools::throwUserError(wfMessage('wfmk-validate', $this->getName(), $value, wfMessage('wfmk-req-integer-value')->text()));
        }

        return intval($space_free);
    }

    /**
     * Set the minimal value.
     * @param int $min_value The minimal value as int, or null for no limit (default).
     * @throws \MWException If $min_value is not an int value.
     */
    public function setMin($min_value = null) {
        if (!is_null($min_value) && !is_int($min_value)) {
            throw new \MWException('Int type required as minimal value.');
        }
        $this->min = $min_value;
    }

    /**
     * Get the minimal value.
     * @return int Returns null if no limit set.
     */
    public function getMin() {
        return $this->min;
    }

    /**
     * Set the maximal value.
     * @param int $max_value The maximal value as int, or null for no limit (default).
     * @throws \MWException If $max_value is not an int value.
     */
    public function setMax($max_value = null) {
        if (!is_null($max_value) && !is_int($max_value)) {
            throw new \MWException('Int type required as maximal value.');
        }
        $this->max = $max_value;
    }

    /**
     * Get the maximal value.
     * @return int Returns null if no limit set.
     */
    public function getMax() {
        return $this->max;
    }

    /**
     * 
     * @param int $value
     * @return int The unchanged $value, for futur use
     * @throws UserError If minimal/maximal value exceeded.
     */
    public function validate($value) {

        $min = $this->getMin();
        if (!is_null($min)) {
            if ($value < $min) {
                Tools::throwUserError(wfMessage('wfmk-req-integer-min', $min));
            }
        }

        $max = $this->getMax();
        if (!is_null($max)) {
            if ($value > $max) {
                Tools::throwUserError(wfMessage('wfmk-req-integer-max', $min));
            }
        }

        return $value;
    }

    public function getOutput() {
        return strval($this->getValue());
    }

}