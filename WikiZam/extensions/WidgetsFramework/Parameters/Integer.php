<?php

/**
 * Parameter of type integer for widgets.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class Integer extends Parameter {

    protected $min;
    protected $max;

    /**
     * <ul>
     * <li>The default value is 0</li>
     * <li>No minimal value</li>
     * <li>No maximal value</li>
     * <li>The parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {

        parent::__construct($name);

        $this->default_value = 0;

        $this->min = null;
        $this->max = null;
    }

    /**
     * Converts from string to signed integer.
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

        if ($value === true) {
            // parameter specified without value
            Tools::ThrowUserError(wfMessage('wfmk-value-required', $this->getName()));
        }

        // value is a string

        $space_free = str_replace(' ', '', $value); // remove any space inside the number

        if (strlen($value) == 0) {
            // empty string
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-integer-syntax')->text()));
        }

        // if minus sign present, remove it to not break ctype_digit()
        $ctype_test = $space_free[0] == '-' ? substr($space_free, 1) : $space_free;

        if (!ctype_digit($ctype_test)) {
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-integer-syntax')->text()));
        }

        return intval($space_free);
    }

    /**
     * Sets the minimal value.
     * 
     * @param int $min_value The minimal value as int, or null for no limit (default).
     * @throws \MWException When $min_value is not an integer.
     */
    public function setMin($min_value = null) {
        if (!is_null($min_value) && !is_int($min_value)) {
            throw new \MWException('Integer type required as minimal value.');
        }
        $this->min = $min_value;
    }

    /**
     * Gets the minimal value.
     * @return int Returns null if no limit set.
     */
    public function getMin() {
        return $this->min;
    }

    /**
     * Sets the maximal value.
     * @param int $max_value The maximal value as int, or null for no limit (default).
     * @throws \MWException When $max_value is not an integer.
     */
    public function setMax($max_value = null) {
        if (!is_null($max_value) && !is_int($max_value)) {
            throw new \MWException('Integer type required as maximal value.');
        }
        $this->max = $max_value;
    }

    /**
     * Gets the maximal value.
     * @return int Returns null if no limit set.
     */
    public function getMax() {
        return $this->max;
    }

    /**
     * If the parameter is required, checks that it has been set.
     * 
     * Check that the value complies with the minimum and maximum values.
     * 
     * @return void
     *When @throws UserError <ul>
     * <li>When the parameter is required and not set.</li>
     * <li>When value doesn't complies with the minimum and the maximum.</li>
     * </ul>
     */
    public function validate() {

        parent::validate(); // If the parameter is required, checks that it has been set.

        $value = $this->getValue();

        $min = $this->getMin();
        if (!is_null($min)) {
            if ($value < $min) {
                Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-min-value', $min)));
            }
        }

        $max = $this->getMax();
        if (!is_null($max)) {
            if ($value > $max) {
                Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-max-value', $max)));
            }
        }
    }

    public function getOutput() {
        return strval($this->getValue());
    }

}