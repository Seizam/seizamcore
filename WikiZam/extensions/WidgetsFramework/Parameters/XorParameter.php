<?php

/**
 * Abstract Parameter which groups N parameters.
 * Only one of these N parameters can be set. The XorParameter takes care of that.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class XorParameter extends Parameter {

    protected $parameters;
    protected $default_parameter;

    /**
     * This parameter is a container for several sub-parameters.
     * Only one of these can be set by user (the XOR condition)
     * 
     * Typical usage is for a "float" parameter, that contains options
     * "right" and "left". See Vimeo widget for example.
     * 
     * <ul>
     * <li>The default value is the empty string</li>
     * <li>No default sub-parameter</li>
     * <li>The parameter is not required</li>
     * </ul>
     * 
     * @param string $name The parameter name, case insensitive
     * @throws \MWException If $name not set
     */
    public function __construct($name) {
        parent::__construct($name);

        $this->parameters = array();
        $this->default_parameter = null;
    }

    /**
     * Keep the value unchanged.
     * 
     * @param string|boolean $value String, or boolean <i>true</i>
     * @return string|boolean The unchanged $value.
     */
    protected function parse($value) {
        return $value;
    }

    /**
     * Set the sub-parameter to use when:
     * <ul>
     * <li>a value without subparameter name is found or</li>
     * <li>getOutput() is called but no sub-parameter has been set.</li>
     * </ul>
     * @param Parameter $default_parameter
     * @throws \MWException
     */
    public function setDefaultParameter($default_parameter) {

        if (!$default_parameter instanceof Parameter) {
            throw new \MWException('An argument of type Parameter is required.');
        }

        $this->default_parameter = $default_parameter;
    }

    /**
     * Add a sub-parameter.
     * 
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
     * Returns true if a sub-parameter has been set.
     * 
     * @return boolean
     */
    public function hasBeenSet() {
        return (!is_null($this->getSetParameter()) );
    }

    /**
     * Returns the default sub-parameter, or null if none.
     * 
     * @return Parameter
     */
    public function getDefaultParameter() {
        return $this->default_parameter;
    }

    /**
     * Returns the sub-parameter which is set, or null if none.
     * In most case, you should probably use getParameter() instead.
     * 
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
     * <li>If a sub-parameter has been set, returns it,</li>
     * <li>else, if a default parameter has been set, returns it,</li>
     * <li>else returns null.</li>
     * </ul>
     * 
     * @return Parameter Can be null
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
     * <li>else, if a default value as been specified using setDefaultValue(), returns it,</li>
     * <li>else return empty string.</li>
     * </ul>
     * 
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
     * <li>If a sub-parameter has been set, returns its value,</li>
     * <li>else, if a default parameter has been set, return its value,</li>
     * <li>else, if a default value as been specified using setDefaultValue(), returns it,</li>
     * <li>else returns empty string.</li>
     * </ul>
     * 
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
     * Tries to set by name all of the sub-parameters with this $argument. 
     * 
     * @param string $argument Raw argument, with no leading or ending spaces.
     * @return boolean <i>true</i> if set successfull
     * @throws UserError 
     */
    protected function trySetParametersByName($argument) {

        // checks if $argument is a named value of a sub-parameter
        foreach ($this->parameters as $parameter) {
            if ($parameter->trySetByName($argument)) {
                // a sub-parameter has been set by name
                return true;
            }
        }

        return false;
    }

    public function trySetByName($argument) {

        if ($this->hasBeenSet()) {
            return false; // cannot read a value anymore
        }

        // Identify the XorParameter by its name
        $named_value = $this->identifyByName($argument);

        if ($named_value === false) {
            // $argument doesn't contain the name if this xor parameter
            // try with the sub-parameters
            return $this->trySetParametersByName($argument);
        } elseif ($named_value === true) {
            // this xor parameter has been used as an option (without value)
            Tools::ThrowUserError(wfMessage('wfmk-value-required', $this->getName()));
        } elseif ($this->trySetParametersByName($named_value)) {
            // $argument contains the name of this xor parameter with a value
            // and the value contains the name of a sub-parameter
            return true;
        } else {
            // $argument contains the name of this xor parameter with a value
            // but the value doesn't contain the name of a sub-parameter
            // try force the default parameter.
            if (($default = $this->getDefaultParameter()) != null && $default->trySet($named_value)) {
                return true;
            } else {
                Tools::ThrowUserError(wfMessage('wfmk-xorparameter-syntax', $this->getName()));
            }
        }
    }

    /**
     * Tries to set the value to $argument.
     * 
     * @param string $argument Raw argument
     * @return boolean <ul>
     * <li><i>true</i> if set successfull,</li>
     * <li><i>false</i> if $argument is empty of no default parameter set</li>
     * </ul>
     * @throws UserError When the parameter has already read a value, or when
     * $argument cannot be parsed.
     */
    public function trySet($argument) {

        if ($this->hasBeenSet()) {
            // cannot read a value anymore
            Tools::ThrowUserError(wfMessage('wfmk-already-assigned', $this->getName(), $this->getOutput(), $argument));
        }

        if (strlen($argument) == 0) {
            return false; // nothing to read
        }

        if ($this->getDefaultParameter() == null) {
            return false;
        }

        return $this->getDefaultParameter()->trySet($argument);
    }

    /**
     * <ul>
     * <li>If a default parameter has been set, set its default value (parsing
     * according to $do_parse value).</li>
     * <li>Else, store the default value without as it is (without any parse).</li>
     * </ul>
     * @param mixed $default_value
     * @param boolean $do_parse Default is <i>true</i>, forced to false when no 
     * default parameter set.
     */
    public function setDefaultValue($default_value, $do_parse = true) {

        if (!is_null($default_parameter = $this->getDefaultParameter())) {
            $default_parameter->setDefaultValue($default_value, $do_parse);
        } else {
            parent::setDefaultValue($default_value, false); // store as it is
        }
    }

    /**
     * If the xor-parameter is required, checks that a sub-parameter
     * has been set.
     * 
     * If a sub-parameter has been set, calls its validate() method.
     * 
     * @return void
     * @throws UserError
     */
    public function validate() {
        parent::validate(); // If the parameter is required, checks that it has been set.
        $parameter = $this->getParameter();
        if (!is_null($parameter)) {
            $parameter->validate();
        }
    }

    /**
     * <ul>
     * <li>If a sub-parameter has been set, returns its getOutput()</li>
     * <li>Else, if a default parameter has been set, returns its getOutput()</li>
     * <li>Else, if a default value as been set using setDefaultValue(), returns it.</li>
     * <li>Else, returns empty string.</li>
     * </ul>
     * 
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