<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

class XorParameter extends Parameter {

    protected $parameters;
    protected $default_parameter;
    

    /** Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is the $default_parameter default value</li>
     * <li>parameter is not required</li>
     * <li>position is not set (for futur use)</li>
     * <li>no default parameter</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified or $default_parameter not a Parameter
     */
    public function __construct($name) {
        parent::__construct($name);

        $this->parameters = array();
        $this->default_parameter = null;
    }
    
    public function setDefaultParameter($default_parameter) {
        
        if (!$default_parameter instanceof Parameter) {
            throw new \MWException('An argument of type Parameter is required.');
        }
        
        $this->default_parameter = $default_parameter;
    }
    
    /**
     * Add a parameter to the XOR list.
     * @param \WidgetsFramework\Parameter $parameter
     * @throws \MWException
     */
    public function addParameter($parameter) {
        if ( !is_null($parameter) && !$parameter instanceof Parameter ) {
            throw new \MWException('Method addParameter() of parameter '.$this->getName().' requires an argument of type Parameter.');
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
     * <b>For futur use.</b><br/>
     * Set this parameter position in the widget parameter list.
     * @param int $position
     */
    public function setPosition($position) {
        parent::setPosition($position);
        foreach ($this->parameters as $parameter) {
            $parameter->setPosition($position);
        }
    }
    
    /**
     * 
     * @return Parameter
     */
    public function getDefaultParameter() {
        return $this->default_parameter;
    }


    /**
     * Returns the parameter which is set, or the default parameter if none set.
     * @return Parameter
     */
    public function getParameter() {
        foreach ($this->parameters as $parameter) {
            if ($parameter->hasBeenSet()) {
                return $parameter;
            }
        }
        // no parameter set, so return the default one
        return $this->getDefaultParameter();
    }
        
    /**
     * Get the parameter which is set value. 
     * If none set yet, return the default parameter value. 
     * @return mixed The type depends on the final Parameter class
     */
    public function getValue() {
        return $this->getParameter()->getValue();
    }

    /**
     * 
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    
    /**
     * Returns the default value of the default parameter
     * @return mixed
     */
    public function getDefaultValue() {
        return $this->getDefaultParameter()->getDefaultValue();
    }
    
    /**
     * Try to set by name all of the sub parameter with this $argument. 
     * @param string $argument Raw argument, with no spaces at the begin or at the end
     * @param int $position (for futur use)
     * @return boolean True if set successfull, false is $argument is not a named value for this parameter.
     * @throws UserError If $argument is a named value for this parameter, but the value cannot
     * be parsed or validated.
     */
    protected function trySetParametersByName($argument, $position) {
        
        // check it $argument is a named value of a sub parameter
        foreach ($this->parameters as $parameter) {           
            if ( $parameter->trySetByName($argument, $position) ) {
                // a parameter as identified itsef, parsed and validated succesfully
                return true;
            }
        }
        // no sub parameter name
        return false;
    }

       
    /**
     * Used when parsing wikitext.
     * @param string $argument Raw argument
     * @param int $position
     * @return boolean True if set successfull
     * @throws UserError 
     * @todo use identifyByName
     */
    public function trySetByName($argument, $position) {
        
        
        if ($this->hasBeenSet()) {
            return false; // cannot read a value anymore
        }

        // Identify the XorParameter by its name
        $named_value = $this->identifyByName($argument);
        
        if ($named_value === false) {
            // $argument is not named for this xorparameter,
            // let's try the subparameters
            return $this->trySetParametersByName($argument, $position);
        } elseif ($named_value === true) {
            // $argument is declared with no value
            Tools::throwUserError(wfMessage('wfmk-req-value', $this->getName()));
        } elseif ($this->trySetParametersByName($named_value, $position)) {          
            // $argument is named for this xorparameter and we matched a subparameter by name.
            return true;
        } else {          
            // $argument did not match any subparameter by name, try force the default parameter.
            $default = $this->getDefaultParameter();
            if ($default != null && $default->trySetByOrder($named_value, $position, $position)) {
                return true;
            } else {
                Tools::throwUserError(wfMessage('wfmk-req-xorparameter-value', $this->getName()));
            }
        }
    }
    
    /**
     * Call the trySetByOrder() method of the default parameter if set
     */
    public function trySetByOrder($argument, $unamed_arg_position, $call_arg_position ) {
        
        if ($this->getDefaultParameter() == null) {
            return false;
        }
        
        return $this->getDefaultParameter()->trySetByOrder($argument, $unamed_arg_position, $call_arg_position);
    }
    
    /**
     * Set a default value to the default parameter
     */
    public function setDefaultValue($default_value, $do_validate = true) {
        $this->getDefaultParameter()->setDefaultValue($default_value, $do_validate);        
    }
    
    /**
     * Returs the getOutput() of the parameter that has been set.
     * If none set, returns the getOutput() of the default parameter.
     * @return string
     */
    public function getOutput() {
        return $this->getParameter()->getOutput();
    }
    
    
    protected function setValue($value) {
        throw new \MWException(__METHOD__.' cannot be called for parameter '.$this->getName().'.');
    }

    protected function parse($value) {
        throw new \MWException(__METHOD__.' cannot be called for parameter '.$this->getName().'.');
    }

    protected function validate($value) {
        throw new \MWException(__METHOD__.' cannot be called for parameter '.$this->getName().'.');
    }

}