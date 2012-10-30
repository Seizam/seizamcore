<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

class XorParameter extends Parameter {

    protected $parameters;
    protected $default_parameter;

    /** Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is empty string</li>
     * <li>parameter is not required</li>
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

    /**
     * Accepts everything.
     * @param mixed $value
     * @return mixed
     */
    protected function parse($value) {
        return $value;
    }

    /**
     * Set the subparameter used:
     * <ul>
     * <li>when a value without subparameter name found,</li>
     * <li>when getOutput() is called and xorparameter has not been set</li>
     * </ul>
     * @param \WidgetsFramework\Parameter $default_parameter
     * @throws \MWException
     */
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
        if (!is_null($parameter) && !$parameter instanceof Parameter) {
            throw new \MWException('Method addParameter() of parameter ' . $this->getName() . ' requires an argument of type Parameter.');
        }
        $this->parameters[] = $parameter;
    }

    /**
     * Returns true if one subparameter has been set or xorparameter has a value.
     * @return boolean
     */
    public function hasBeenSet() {
        return ( !is_null($this->getSetParameter()) );
    }

    /**
     * 
     * @return Parameter
     */
    public function getDefaultParameter() {
        return $this->default_parameter;
    }

    /**
     * Returns the parameter which is set, or null if none set.
     * In most case, you should probably use getParameter() instead.
     * @return Parameter
     */
    public function getSetParameter() {
        foreach ($this->parameters as $parameter) {
            if ($parameter->hasBeenSet()) {
                return $parameter;
            }
        }
        // no parameter set
        return null;
    }
    
    /**
     * <ul>
     * <li>When a parameter has been set, return it</li>
     * <li>else, if a default parameter has been set, return it</li>
     * <li>else, return null</li>
     * </ul>
     * @return Parameter can be null if no parameter set and no default parameter defined.
     */
    public function getParameter() {
        if (!is_null($parameter = $this->getSetParameter())) {
            return $parameter;
        } else {
            return $this->getDefaultParameter();
        }
    }

    /**
     * <ul>
     * <li>If a default parameter has been set, returns its default value.</li>
     * <li>Else, if a default value as been specified using setDefaultValue(), return it.</li>
     * <li>else, return empty string.</li>
     * </ul>
     * @return mixed
     */
    public function getDefaultValue() {

        if (!is_null($default_parameter = $this->getDefaultParameter())) { 
            return $default_parameter->getDefaultValue();
        } else {
            return parent::getDefaultValue();
        }
    }

    /**
     * <ul>
     * <li>when a parameter has been set, return its value</li>
     * <li>else, if a default parameter as been set, return its value</li>
     * <li>else, if a default value as been specified using setDefaultValue(), return it.</li>
     * <li>else, return empty string.</li>
     * </ul>
     * @return mixed The type depends on the final Parameter class
     */
    public function getValue() {

        if (!is_null($parameter = $this->getParameter())) {
            return $parameter->getValue();
        } else {
            return parent::getDefaultValue();
        }
    }

    /**
     * Try to set by name all of the sub parameter with this $argument. 
     * @param string $argument Raw argument, with no spaces at the begin or at the end
     * @return boolean True if set successfull, false is $argument is not a named value for this parameter.
     * @throws UserError If $argument is a named value for this parameter, but the value cannot
     * be parsed or validated.
     */
    protected function trySetParametersByName($argument) {

        // check it $argument is a named value of a sub parameter
        foreach ($this->parameters as $parameter) {
            if ($parameter->trySetByName($argument)) {
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
     * @return boolean True if set successfull
     * @throws UserError 
     * @todo use identifyByName
     */
    public function trySetByName($argument) {

        if ($this->hasBeenSet()) {
            return false; // cannot read a value anymore
        }

        // Identify the XorParameter by its name
        $named_value = $this->identifyByName($argument);

        if ($named_value === false) {
            // $argument is not named for this xorparameter,
            // let's try the subparameters
            return $this->trySetParametersByName($argument);
        } elseif ($named_value === true) {
            // $argument is declared with no value
            Tools::throwUserError(wfMessage('wfmk-req-value', $this->getName()));
        } elseif ($this->trySetParametersByName($named_value)) {
            // $argument is named for this xorparameter and we matched a subparameter by name.
            return true;
        } else {
            // $argument did not match any subparameter by name, try force the default parameter.
            $default = $this->getDefaultParameter();

            if ($default != null && $default->trySet($named_value)) {
                return true;
            } else {
                Tools::throwUserError(wfMessage('wfmk-req-xorparameter-value', $this->getName()));
            }
        }
    }

    /**
     * Call the trySetByOrder() method of the default parameter if set
     */
    public function trySet($argument) {

        if ($this->getDefaultParameter() == null) {
            return false;
        }

        return $this->getDefaultParameter()->trySet($argument);
    }

    /**
     * When a default parameter has been set, set its default value (parsing according to $do_parse value).
     * Else, store the default value without as it is (without any parse).
     * @param mixed $default_value
     * @param boolean $do_parse Default is true, forced to false when no default parameter set.
     */
    public function setDefaultValue($default_value, $do_parse = true) {

        if (!is_null($default_parameter = $this->getDefaultParameter())) {
            $default_parameter->setDefaultValue($default_value, $do_parse);
        } else {
            parent::setDefaultValue($default_value, false); // store as it is
        } 
    }

    /**
     * <ul>
     * <li>When a parameter has been set, return its getOutput()</li>
     * <li>Else, if a default parameter has been set, return its getOutput()</li>
     * <li>Else, if a default value as been specified using setDefaultValue(), return it.</li>
     * <li>Else, return empty string.</li>
     * </ul>
     * Returs the getOutput() of the parameter that has been set.
     * If none set, returns the getOutput() of the default parameter.
     * If no default parameter set, return empty string.
     * @return string
     */
    public function getOutput() {

        if (!is_null($parameter = $this->getParameter())) {
            return $parameter->getOutput();
        } else {
            return parent::getDefaultValue();
        }
    }

    /**
     * <b>This method cannot be use on XorParameter instance</b>
     * @throws \MWException Everytime
     */
    protected function setValue($value) {
        throw new \MWException('You cannot use the method setValue() on the XorParameter "' . $this->getName() . '".');
    }

}