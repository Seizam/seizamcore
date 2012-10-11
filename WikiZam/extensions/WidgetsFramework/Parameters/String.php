<?php

namespace WidgetsFramework;

class String extends Parameter {

    protected $escape_mode;

    public function __construct($name) {
        parent::__construct($name);
        $this->escape_mode = 'html';
    }
    
    public function setEscapeMode($escape_mode) {
        if (!is_string($escape_mode)) {
            throw new \MWException('Method '.__METHOD__.' of parameter '.$this->getName().' require an argument of type string.');
        }
        $this->escape_mode = $escape_mode;
    }
    
    public function getEscapeMode() {
        return $this->escape_mode;
    }

    /**
     * Accept everything, change nothing
     * @param string $value
     * @return string
     */
    public function parse($value) {
        return $value;
    }

    /**
     * Validate everything
     * @param string $value
     */
    public function validate($value) {
        return $value;
    }

    /** 
     * @return String The escaped value. Escaping mode can be defined using setEscapeMode().
     */
    public function getOutput() {
        return Tools::Escape($this->getValue(), $this->getEscapeMode());
    }

}
