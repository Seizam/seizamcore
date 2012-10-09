<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

class XorParameter extends Parameter {

    protected $parameters;
    protected $default_parameter;
    
    /**
     * <ul>
     * <li>no default parameter</li>
     * </ul>  
     * @param string $name Required, case insensisitve
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {
        parent::__construct($name);

        $this->parameters = array();
        $this->default_parameter = null;
    }

    public function addParameter($parameter) {
        if ( !is_null($parameter) && !$parameter instanceof Parameter ) {
            throw new \MWException(__METHOD__.' require an argument of type Parameter.');
        }
        $this->parameters[] = $parameter;
    }
    
    /**
     * Returns true if one parameter has been set.
     * @return boolean
     */
    public function hasBeenSet() {
        foreach ($this->parameters as $parameter) {
            if ($parameter->hasBeenSet()) {
                return true;
            }
        }
        return false;
    }
       
    /**
     * 
     * @param \WidgetsFramework\Parameter $parameter The parameter, default: null
     * @throws \MWException If $parameter not of type Parameter
     * @return void
     */
    public function setDefaultParameter($parameter = null) {
        if ( !is_null($parameter) && !$parameter instanceof Parameter ) {
            throw new \MWException(__METHOD__.' require an argument of type Parameter.');
        }
        $this->default_parameter = $parameter;  
    }
    
    /**
     * 
     * @return Parameter
     */
    public function getDefaultParameter() {
        return $this->default_parameter;
    }


    /**
     * Returns the parameter which is set.
     * @return Parameter
     */
    public function getParameter() {
        foreach ($this->parameters as $parameter) {
            if ($parameter->hasBeenSet()) {
                return $parameter;
            }
        }
        // if no parameter set, return the default parameter
        return $this->getDefaultParameter();
    }
        
    /**
     * Get the set value. or default value if it has not been set. 
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * This value should be always valid.
     */
    public function getValue() {
        
        $parameter_which_is_set = $this->getParameter();
        if (!is_null($parameter_which_is_set)) {
            return $parameter_which_is_set->getValue();
        }
        // else
        return '';
    }

    /**
     * 
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    
    /**
     * Returns the default value of the default parameter, empty string if not default parameter set.
     * @return mixed
     */
    public function getDefaultValue() {
        $default_parameter = $this->getDefaultParameter();
        if (is_null($default_parameter)) {
            return '';
        }
        // else
        return $default_parameter->getValue();
    }
    

       
    /**
     * Used when parsing wikitext.
     * @param string $argument Raw argument
     * @param int $position
     * @return boolean True if set successfull
     * @throws UserError 
     */
    public function trySetByName($argument, $position) {
        
        if ($this->hasBeenSet()) {
            return false;
        }
        
        foreach ($this->parameters as $parameter) {           
            if ( $parameter->trySetByName($argument, $position) ) {
                return true;
            }
        }
        
        // not set during foreach
        return false;
        
    }
    
    /**
     * Set a default value to the default parameter
     * @param mixed $default_value 
     * @param boolean $do_validate Validate before saving the default value (default = true)
     * @return void
     * @throws \MWException if default parameter not set
     */
    public function setDefaultValue($default_value, $do_validate = true) {
        
        $default_parameter = $this->getDefaultParameter();
        if (is_null($default_parameter)) {
            throw new \MWException('A default parameter has to be set before calling '.__METHOD__.' method.');
        }
        
        $default_parameter->setDefaultValue($default_value, $do_validate);
        
    }
    
    public function getHtml() {
        $parameter_which_is_set = $this->getParameter();
        if (!is_null($parameter_which_is_set)) {
            return $parameter_which_is_set->getHtml();
        }
        // else
        return '';
    }
    
    protected function setValue($value) {
        throw new \MWException(__METHOD__.' cannot be called for this object.');
    }

    protected function identifyByName($argument) {
        throw new \MWException(__METHOD__.' cannot be called for this object.');
    }

    protected function parse($value) {
        throw new \MWException(__METHOD__.' cannot be called for this object.');
    }

    protected function validateDuringSet($value) {
        throw new \MWException(__METHOD__.' cannot be called for this object.');
    }

}