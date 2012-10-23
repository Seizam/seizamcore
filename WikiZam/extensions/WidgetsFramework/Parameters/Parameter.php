<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class Parameter {

    protected $name;
    protected $value;
    protected $default_value;
    protected $required;

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is empty string</li>
     * <li>parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {

        if (!is_string($name)) {
            throw new \MWException('Parameter object constructor need an argument "name" of type string.');
        }
        $this->name = strtolower($name);
        $this->value = null;
        $this->default_value = '';
        $this->required = false;
    }

    /**
     * Returns this parameter name.
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Indicate if this parameter as already be valuated.
     * @return boolean true=<i>already valuated, cannot read a value anymore</i> , false=<i>value not set yet</i>
     */
    public function hasBeenSet() {
        return !is_null($this->value);
    }

    /**
     * Internally called to store a parsed and validated value.
     * @param mixed $value The type of this argument depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    protected function setValue($value) {
        $this->value = $value;
    }

    /**
     * Returns the set value.
     * If it hasn't been set, returns the default value. 
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * This value should always be valid.
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
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    public function getDefaultValue() {
        return $this->default_value;
    }

    /**
     * Set this parameter as required.
     * When a parameter is set as required, if it is not valuated during argument parsing,
     * it will thrown a UserError exception during validateAfterSet().
     * @param boolean $required If not specified, considered as true
     */
    public function setRequired($required = true) {

        if (!is_bool($required)) {
            throw new \MWException('Method setRequired() of parameter ' . $this->getName() . ' need a boolean argument.');
        }

        $this->required = $required;
    }

    /**
     * Is this parameter required ?
     * @return boolean
     */
    public function isRequired() {
        return $this->required;
    }

    /**
     * Analyse this argument, and look for a named value using this parameter name, case insensitive.
     * @param string $argument The raw argument
     * @return string|true|false If name found followed by assignment sign '=', returns the string value (without "name=")<br />
     * If name found without anything else, return true<br />
     * Else returns false
     */
    protected function identifyByName($argument) {

        $name = $this->getName();
        $name_length = strlen($name);

        if (strlen($argument) < $name_length) {
            return false; // too short, name cannot be found
        }

        // comparison is case insensitive
        if (0 != substr_compare(
                        $argument, $name, 0, $name_length, true)) {

            return false; // name not found
        }

        // else: name has been found :)
        // strip the name, and any space just after
        $argument_without_name = ltrim(substr($argument, $name_length));
        if (strlen($argument_without_name) == 0) {
            return true; // no value, juste the name of this parameter
        }

        // the next char must be '=', and maybe some spaces after
        if ($argument_without_name[0] != '=') {
            // this is not the name of this parameter
            return false;
        }

        // get the value by removing '=' and any spaces just after
        $value = ltrim(substr($argument_without_name, 1));
        return $value;
    }

    /**
     * Parse the value from wikitext string.
     * If the value cannot be parsed (contains bad caracters, is too long, ...), a UserError exception
     * is thrown with Tools::throwUserError().
     * This method can transform the value, so only the returned value can be considered as parsed.
     * See Boolean as a good example.
     * @param string|true $value The wikitext string value, without "name=" (can be empty string)<br />
     * true if parameter specified without value assignment
     * @return mixed $value The type depends on the final class extending Parameter.
     * @throws UserError if $value cannot be parsed
     */
    abstract protected function parse($value);

    /**
     * Ensure the given value is acceptable. (minimum, maximum, ...)
     * If the value is not acceptable, a UserError exception is thrown with Tools::throwUserError().
     * See Integer or String classes as good examples.
     * @param mixed $value The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * @return mixed The unchanged $value, type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * @throws UserError if $value cannot be validated
     */
    abstract protected function validate($value);

    /**
     * Used when parsing wikitext.
     * Try to set the value if $argument is a named value for this parameter.
     * The name is case insensitive.
     * @param string $argument Raw argument, with no spaces at the begin or at the end
     * @return boolean True if set successfull, false is $argument is not a named value for this parameter.
     * @throws UserError If $argument is a named value for this parameter, but the value cannot
     * be parsed or validated.
     */
    public function trySetByName($argument) {

        if ($this->hasBeenSet()) {
            return false; // cannot read a value anymore
        }

        $named_value = $this->identifyByName($argument);
        if ($named_value === false) {
            return false; // $argument is not a named value for this parameter
        }

        // $argument is a named value for this parameter, try to parse and validate

        $parsed = $this->parse($named_value); // can throw UserError Exception      
        $validated = $this->validate($parsed); // can throw UserError Exception
        // value is ok :)
        $this->setValue($validated);
        return true;
    }

    /**
     * Used when parsing wikitext.
     * Try to set the value.
     * @param string $argument Raw argument
     * @return boolean True if set successfull, false if $argument not read
     * @throws UserError If the parameter has already read a value, or if $argument cannot be 
     * parsed and validated.
     */
    public function trySet($argument) {

        if ($this->hasBeenSet()) {
            // cannot read a value anymore
            Tools::throwUserError(wfMessage('wfmk-already-assigned', $this->getName(), $this->getOutput(), $argument));
        }

        if (strlen($argument) == 0) {
            return false; // nothing to read
        }

        // $argument should be the value, try to parse and validate
        $parsed = $this->parse($argument); // can throw UserError Exception
        $validated = $this->validate($parsed); // can throw UserError Exception
        // value is ok :)
        $this->setValue($validated);
        return true;
    }

    /**
     * Store the default value. 
     * @param string|mixed $default_value As string (safer), will be parsed 
     * (except if $do_validate is false)<br/>
     * @param boolean $do_validate do validate the $default_value (default is true, safer) 
     * @return void
     * @throws \MWException if $default_value cannot be parsed or validated
     */
    public function setDefaultValue($default_value, $do_validate = true) {

        if ($do_validate) {

            try {

                if (is_string($default_value)) {
                    $default_value = $this->parse($default_value); // can throw UserError Exception
                }

                $default_value = $this->validate($default_value); // can throw UserError Exception
            } catch (UserError $e) {
                throw new \MWException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->default_value = $default_value;
    }

    /**
     * Check the parameter requirements.
     * @return void
     * @throws UserError If this parameter is required and has not been set.
     */
    public function validateAfterSet() {
        if ($this->isRequired() && !$this->hasBeenSet()) {
            Tools::throwUserError(wfMessage('wfmk-req-parameter', $this->getName()));
        }
    }

    /**
     * Returns the string that represents the value of this parameter.
     * This method is used to output a parameter in widgets getOuput() method.
     * @return string The string representation of this parameter value. 
     */
    abstract public function getOutput();
}

