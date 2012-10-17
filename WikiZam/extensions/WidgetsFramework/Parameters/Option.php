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
     * <li>position is not set (for futur use)</li>
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
    
    /**
     * 
     * @param string $value The output string when value is false. 
     */
    public function setOutputOnFalse($output) {
        $this->output_on_false = $output;
    }
    
    /**
     * 
     * @return string Returns output according the value. See setOutputOnTrue() and setOutputOnFalse();
     */
    public function getOutput() {
        
        if ($this->getValue()) {
            return $this->output_on_true;
            
        } else {
            return $this->output_on_false;
        }
    }
    
}