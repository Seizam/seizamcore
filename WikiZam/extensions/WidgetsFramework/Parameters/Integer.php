<?php

namespace WidgetsFramework;

class Integer extends Parameter {
    
    protected $min;
    protected $max;
    
    /**
     * <ul>
     * <li>default value is 0</li>
     * <li>no minimal value</li>
     * <li>no maximal value</li>
     * </ul>
     * @param string $name
     */
    public function __construct($name) {
        
        parent::__construct($name);
        
        $this->default_value = 0;
        
        $this->min = null;
        $this->max = null;
    }
    
    /**
     * Only accepts digits as value.
     * @param string $value
     * @return int
     * @throws UserError
     */
    public function parse($value) {
        
        $spaces_free = str_replace(' ','',trim($value));
        
        if (!ctype_digit($spaces_free)) {
            Tools::throwUserError('Parameter '.$this->getName().' only accepts digits as value.');
        } 
        
        // else
        return intval($px_free);
        
    }
        
    /**
     * Set the minimal value.
     * @param int $min_value The minimal value as int, or null for no limit (default).
     * @throws \MWException If $min_value is not an int value.
     */
    public function setMin($min_value = null) {
        if ( !is_null($min_value) && !is_int($min_value) ) {
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
        if ( !is_null($max_value) && !is_int($max_value) ) {
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
    public function validateDuringSet($value) {
        
        $value = parent::validateDuringSet($value);
        
        $min = $this->getMin();
        if ( !is_null($min) ) {
            if ($value < $min) {
                Tools::throwUserError('Parameter '.$this->getName().' cannot be less than '.$min);
            }
        }
        
        $max = $this->getMax();
        if ( !is_null($max) ) {
            if ($value < $max) {
                Tools::throwUserError('Parameter '.$this->getName().' cannot be more than '.$max);
            }
        }     
        
        return $value;
        
    }

    public function getHtml() {
        return strval($this->getValue());
    }
    
}