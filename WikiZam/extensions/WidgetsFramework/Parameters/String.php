<?php

namespace WidgetsFramework;

/**
 * plop
 */
class String extends Parameter {

    protected $escape_mode;

    protected $min_length;
    protected $max_length;
    
    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is empty string</li>
     * <li>parameter is not required</li>
     * <li>position is not set (for futur use)</li>
     * <li>escape mode: <i>html</i></li>
     * <li>minimal length: <i>1 character</i></li>
     * <li>maximal length: <i>unlimited</i></li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->escape_mode = 'html';
        $this->min_length = 1; // non-empty string
        $this->max_length = 0; // unlimited length
    }
    
    /**
     * Set the output escape mode, default is "html".
     * @param html|htmlall|url|urlpathinfo|quotes|hex|hexentity|decentity|javascript|mail|nonstd $escape_mode string
     * @throws \MWException
     */
    public function setEscapeMode($escape_mode) {
        if (!is_string($escape_mode)) {
            throw new \MWException( 'Method setEscapeMode() requires an argument "$escape_mode" of type string.' );
        }
        $this->escape_mode = $escape_mode;
    }
    
    public function getEscapeMode() {
        return $this->escape_mode;
    }
    
    
    /**
     * Defines a minimal length for this string value.
     * @param int $min Default is 1.
     * @throws \MWException
     */
    public function setMinimalLength($min = 1) {
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
        if ( $value === true ) {
            // parameter specified without value
            Tools::throwUserError(wfMessage('wfmk-req-value', $this->getName()));
        }
        return $value;
    }

    /**
     * Throws UserError if the string is too short or too long, according setMinimalLength()
     * and setMinimalLength() previous calls.
     * @param string $value
     * @throws UserError
     */
    public function validate($value) {
        
        $length = strlen($value);
        
        if ($length < $this->getMinimalLength()) {
            Tools::throwUserError(wfMessage('wfmk-validate',
                    $this->getName(), $value, wfMessage('wfmk-req-string-min-length', $this->getMinimalLength()) ) );
                        
        } elseif ( ($this->getMaximalLength() != 0) && ($length > $this->getMaximalLength()) ) {
            Tools::throwUserError(wfMessage('wfmk-validate',
                    $this->getName(), $value, wfMessage('wfmk-req-string-max-length', $this->getMaximalLength()) ) );         
        }
        
        // else
        return $value;
    }

    /** 
     * @return String The escaped value. Escaping mode can be defined using setEscapeMode().
     */
    public function getOutput() {
        return Tools::Escape($this->getValue(), $this->getEscapeMode());
    }

}
