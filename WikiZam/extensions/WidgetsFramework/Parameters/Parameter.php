<?php

/**
 * Mother class of all widget parameters.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

abstract class Parameter {

    /** @var string */
    protected $name;

    /** @var mixed */
    protected $value;

    /** @var mixed */
    protected $default_value;

    /** @var boolean */
    protected $required;

    /**
     * <ul>
     * <li>The default value is the empty string</li>
     * <li>The parameter is not required</li>
     * </ul>
     * 
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {

        if (!is_string($name)) {
            throw new \MWException('Parameter constructor need an argument "name" of type string.');
        }
        $this->name = strtolower($name);
        $this->value = null;
        $this->default_value = '';
        $this->required = false;
    }

    /**
     * Returns this parameter name.
     * 
     * @return string This parameter name.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Indicates if this parameter is already set.
     * @return boolean <i>true</i> means already set and cannot read a value
     * anymore
     */
    public function hasBeenSet() {
        return !is_null($this->value);
    }

    /**
     * Internally called to store a value.
     * 
     * @param mixed $value The type of this argument depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    protected function setValue($value) {
        $this->value = $value;
    }

    /**
     * 
     * <ul>
     * <li>If the parameter has been set, returns the value,</li>
     * <li>Else returns the default value</li>
     * </ul>
     * 
     * @return mixed The type depends on the child Parameter class.
     * In most cases, is should be a string.
     */
    public function getValue() {
        if ($this->hasBeenSet()) {
            return $this->value;
        } else {
            return $this->getDefaultValue();
        }
    }

    /**
     * Returns the default value.
     * 
     * @return mixed The type depends on the child Parameter class.
     * In most cases, is should be a string.
     */
    public function getDefaultValue() {
        return $this->default_value;
    }

    /**
     * Indicates whether this parameter needs to be set or not.
     * 
     * @param boolean $required If not specified, considered as true.
     */
    public function setRequired($required = true) {

        if (!is_bool($required)) {
            throw new \MWException('Method setRequired() of parameter ' . $this->getName() . ' need a boolean argument.');
        }

        $this->required = $required;
    }

    /**
     * Is this parameter required ?
     * 
     * @return boolean
     */
    public function isRequired() {
        return $this->required;
    }

    /**
     * Analyses this argument, and look for this parameter name, case
     * insensitive.
     * 
     * @param string $argument The raw argument.
     * @return string|boolean <ul>
     * <li>If its name is found followed by equal sign, returns the string 
     * that follow the equal sign (the value).</li>
     * <li>If the name is found, without anything else, returns boolean
     * <i>true</i>.</li>
     * <li>Else, returns boolean <i>false</i>.</li>
     * </ul>
     */
    protected function identifyByName($argument) {

        $name = $this->getName();
        $name_length = strlen($name);

        if (strlen($argument) < $name_length) {
            return false; // too short, name cannot be found
        }

        // the comparison is case insensitive
        if (0 != substr_compare(
                        $argument, $name, 0, $name_length, true)) {
            return false; // name not found
        }

        // else: name has been found
        // remove the name, and any space just after
        $argument_without_name = ltrim(substr($argument, $name_length));
        if (strlen($argument_without_name) == 0) {
            return true; // no value, only the name
        }

        // the next char must be '='
        if ($argument_without_name[0] != '=') {
            // this is not the name of this parameter
            return false;
        }

        // get the value by removing '=' and any spaces just after
        $value = ltrim(substr($argument_without_name, 1));
        return $value;
    }

    /**
     * Parses the value from wikitext string.
     * 
     * If it fails (bad characters), a UserError exception is thrown.
     * 
     * This method can transform the value. See class Boolean as a good example.
     * 
     * @param string|boolean $value 
     * <ul>
     * <li>The wikitext string value, without "name=" (can be empty string)</li>
     * <li>boolean <i>true</i> if parameter is specified without value</li>
     * </ul>
     * @return mixed $value The type depends on the final class extending 
     * Parameter.
     * @throws UserError When $value cannot be parsed
     */
    abstract protected function parse($value);

    /**
     * Tries to set the value by name from $argument.
     * 
     * @param string $argument Raw argument, without leading or ending spaces.
     * @return boolean <ul>
     * <li>If successfull, returns <i>true</i>,</li>
     * <li>else returns <i>false</i></li>
     * </ul>
     * @throws UserError When $argument is a named value for this parameter and
     * the value cannot be parsed.
     */
    public function trySetByName($argument) {

        if ($this->hasBeenSet()) {
            return false; // cannot read a value anymore
        }

        $named_value = $this->identifyByName($argument);
        if ($named_value === false) {
            return false;
        }

        // $argument is a named value for this parameter, tries to parse
        $parsed = $this->parse($named_value); // can throw UserError Exception      

        $this->setValue($parsed);
        return true;
    }

    /**
     * Tries to set the value to $argument.
     * 
     * @param string $argument Raw argument
     * @return boolean <ul>
     * <li>If successfull, returns <i>true</i>,</li>
     * <li>else returns <i>false</i></li>
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

        // $argument should be the value, try to parse and validate
        $parsed = $this->parse($argument); // can throw UserError Exception
        // value is ok :)
        $this->setValue($parsed);
        return true;
    }

    /**
     * Store the default value.
     * 
     * If $default_value is a string and $do_parse is true (default), the 
     * $default_value will be parsed before storing it. It can raise 
     * MWException exception.
     *  
     * @param string|mixed $default_value The value to set as default
     * @param boolean $do_parse Default is <i>true</i> 
     * @return void
     * @throws \MWException When $default_value cannot be parsed
     */
    public function setDefaultValue($default_value, $do_parse = true) {

        if ($do_parse && is_string($default_value)) {
            try {
                // may throw UserError Exception
                $default_value = $this->parse($default_value);
            } catch (UserError $e) {
                throw new \MWException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->default_value = $default_value;
    }

    /**
     * If the parameter is required, checks that it has been set.
     * 
     * @return void
     * @throws UserError When the parameter is required and not set.
     */
    public function validate() {
        if ($this->isRequired() && !$this->hasBeenSet()) {
            Tools::ThrowUserError(wfMessage('wfmk-parameter-required', $this->getName()));
        }
    }

    /**
     * Returns the string that represents the value of this parameter.
     * 
     * This method is used in widgets getOuput() method.
     * 
     * @return string The string representation of this parameter value. 
     */
    abstract public function getOutput();
}

