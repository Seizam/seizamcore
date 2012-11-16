<?php

/**
 * Parameter of type string for widgets.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class String extends Parameter {

    protected $escape_mode;
    protected $validate_type;
    protected $min_length;
    protected $max_length;

    /**
     * <ul>
     * <li>The default value is the empty string</li>
     * <li>HTML content is escaped for output.</li>
     * <li>No validation of the content of the value</li>
     * <li>Minimal length is 0 (accepts empty string)</li>
     * <li>Maximal length is 1024</li>
     * <li>The parameter is not required</li>
     * </ul>
     * 
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->escape_mode = 'html';
        $this->validate_type = 'all'; // validates everything
        $this->min_length = 0; // accepts empty string
        $this->max_length = 1024;
    }

    /**
     * Sets the escape mode for output. default is "html".
     * @param none|html|htmlall|url|urlpathinfo|quotes|hex|hexentity|decentity|javascript|mail|nonstd $escape_mode string
     * @throws \MWException
     */
    public function setEscapeMode($escape_mode = 'html') {
        if (!is_string($escape_mode)) {
            throw new \MWException('Method setEscapeMode() requires an argument "$escape_mode" of type string.');
        }
        $this->escape_mode = $escape_mode;
    }

    public function getEscapeMode() {
        return $this->escape_mode;
    }

    /**
     * Sets the validating rule. Default is "all".
     * 
     * See Tools::Escape().
     * @param all|url|int|boolean|float|email|ip $validate_type A string
     * @throws \MWException
     */
    public function setValidateType($validate_type) {
        if (!is_string($validate_type)) {
            throw new \MWException('Method setValidateType() requires an argument "$validate_type" of type string.');
        }
        $this->validate_type = $validate_type;
    }

    public function getValidateType() {
        return $this->validate_type;
    }

    /**
     * Defines a minimal length for the value.
     * 
     * @param int $min Default is 0.
     * @throws \MWException
     */
    public function setMinimalLength($min = 0) {
        if (!is_int($min) || $min < 0) {
            throw new \MWException('Method setMinimalLength() requires an argument "$min" of type int, greater or equal than 0.');
        }
        // else
        $this->min_length = $min;
    }

    public function getMinimalLength() {
        return $this->min_length;
    }

    /**
     * Defines a maximal length for the value.
     * 
     * @param int $max 0 means unlimited, default is 1024.
     * @throws \MWException
     */
    public function setMaximalLength($max = 1024) {
        if (!is_int($max) || $max < 0) {
            throw new \MWException('Method setMaximalLength() requires an argument "$max" of type int, greater or equal than 0.');
        }
        // else
        $this->max_length = $max;
    }

    public function getMaximalLength() {
        return $this->max_length;
    }

    /**
     * Accepts every string, except parameter name without value.
     * 
     * @param string|boolean $value The value
     * @return string
     */
    public function parse($value) {
        if ($value === true) {
            // parameter specified without value
            Tools::ThrowUserError(wfMessage('wfmk-value-required', $this->getName()));
        }
        return $value;
    }

    /**
     * If the parameter is required, checks that it has been set.
     * Checks the value complies with minimal and maximal length.
     * Checks the value validates the rule sets with setValidateType().
     * 
     * @return void
     * @throws UserError When the value doesn't comply with the requirements.
     */
    public function validate() {

        parent::validate(); // When the parameter is required, checks that it has been set.

        $value = $this->getValue();

        $length = strlen($value);

        if ($length < $this->getMinimalLength()) {
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-min-length', $this->getMinimalLength())));
        } elseif (($this->getMaximalLength() != 0) && ($length > $this->getMaximalLength())) {
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-max-length', $this->getMaximalLength())));
        } elseif (!Tools::Validate($value, $this->validate_type)) {
            Tools::ThrowUserError(wfMessage('wfmk-validation-error', $this->getName(), $value, wfMessage('wfmk-string-validate-type', $this->validate_type)));
        }
    }

    /**
     * @return String The escaped value. See setEscapeMode().
     */
    public function getOutput() {
        return Tools::Escape($this->getValue(), $this->getEscapeMode());
    }

}
