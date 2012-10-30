<?php

namespace WidgetsFramework;

/**
 * plop
 */
class String extends Parameter {

    protected $escape_mode;
    protected $validate_type;
    protected $min_length;
    protected $max_length;

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is empty string</li>
     * <li>parameter is not required</li>
     * <li>escape mode: <i>html</i></li>
     * <li><i>accept empty string</i> (minimal length is 0 character)</li>
     * <li>maximal length: <i>1024 characters</i></li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->escape_mode = 'html';
        $this->validate_type = 'all';
        $this->min_length = 0; // accept empty string
        $this->max_length = 1024; 
    }

    /**
     * Set the output escape mode, default is "html".
     * @param html|htmlall|url|urlpathinfo|quotes|hex|hexentity|decentity|javascript|mail|nonstd $escape_mode string
     * @throws \MWException
     */
    public function setEscapeMode($escape_mode) {
        if (!is_string($escape_mode)) {
            throw new \MWException('Method setEscapeMode() requires an argument "$escape_mode" of type string.');
        }
        $this->escape_mode = $escape_mode;
    }

    public function getEscapeMode() {
        return $this->escape_mode;
    }

    /**
     * Set the validating rule. Default is "all"
     * @param all|url|int|boolean|float|email|ip $validate_type
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
     * Defines a minimal length for this string value.
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
     * Defines a maximal length for the string value.
     * @param int $max 0 means unlimited, this is the default.
     * @throws \MWException
     */
    public function setMaximalLength($max = 0) {
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
     * Accept every string, reject parameter specified without value
     * @param string|true $value The string value to transform, or true if parameter specified without value
     * @return string
     */
    public function parse($value) {
        if ($value === true) {
            // parameter specified without value
            Tools::throwUserError(wfMessage('wfmk-req-value', $this->getName()));
        }
        return $value;
    }

    /**
     * Throws UserError if the string is too short or too long, according setMinimalLength()
     * and setMinimalLength() previous calls.
     * @throws UserError
     */
    public function validate() {

        parent::validate();
        
        $value = $this->getValue();
        
        $length = strlen($value);

        if ($length < $this->getMinimalLength()) {
            Tools::throwUserError(wfMessage('wfmk-validate', $this->getName(), $value, wfMessage('wfmk-req-string-min-length', $this->getMinimalLength())));
        } elseif (($this->getMaximalLength() != 0) && ($length > $this->getMaximalLength())) {
            Tools::throwUserError(wfMessage('wfmk-validate', $this->getName(), $value, wfMessage('wfmk-req-string-max-length', $this->getMaximalLength())));
        } elseif (!Tools::Validate($value, $this->validate_type)) {
            Tools::throwUserError(wfMessage('wfmk-validate', $this->getName(), $value, wfMessage('wfmk-req-string-validate', $this->validate_type)));
        }
    }

    /**
     * @return String The escaped value. Escaping mode can be defined using setEscapeMode().
     */
    public function getOutput() {
        return Tools::Escape($this->getValue(), $this->getEscapeMode());
    }

}
