<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class Parameter {

    protected $name;
    protected $value;
    protected $default_value;
    protected $position;
    
    protected $required;

    /**
     * <ul>
     * <li>default value is empty string,</li>
     * <li>parameter is not required</li>
     * <li>position not set</li>
     * </ul>  
     * @param string $name Required, case insensisitve
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {

        if (!is_string($name)) {
            throw new \MWException('Parameter constructor need a name of type string.');
        }
        $this->name = strtolower($name);

        $this->value = null;
        $this->default_value = '';
        $this->required = false;
        $this->position = -1;
    }

    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Indicate if the parameter can still read a value.
     * @return boolean
     */
    public function hasBeenSet() {
        return !is_null($this->value);
    }
    
    /**
     * Internally called with a parsed and validated value
     * @param mixed $value The type of this argument depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    protected function setValue($value) {
        $this->value = $value;
    }
    
    /**
     * Get the set value. or default value if it has not been set. 
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * This value should be always valid.
     */
    public function getValue() {
        if ($this->hasBeenSet()) {
            return $this->value;
        } else {
            return $this->getDefaultValue();
        }
    }

    /**
     * 
     * @return mixed The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     */
    public function getDefaultValue() {
        return $this->default_value;
    }
    
    /**
     * 
     * @param boolean $required If not specified, considered as true
     */
    public function setRequired($required = true) {
        
        if (!is_bool($required)) {
            throw new \MWException(__METHOD__.' need a boolean argument');
        }
        
        $this->required = $required;
    }
    
    /**
     * 
     * @return boolean
     */
    public function isRequired() {
        return $this->required;
    }
    
    /**
     * 
     * @param int $position The position that the current parameter can take in the list of parameters which can still read a value
     * @return int The position of the next parameter.
     */
    public function updatePosition($position) {
        if ($this->hasBeenSet()) {
            $this->position = -1;
            return $position;
        }
        // else
        $this->position = $position;
        return $position + 1;
    }
    
    /**
     * The position in the list of parameters which can still read a value
     * @return int
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * 
     * @param string $argument The raw argument
     * @return boolean/string The string value, or false if not identified
     */
    protected function identifyByName($argument) {
        
        $name = $this->getName();
        $name_length = strlen($name);
        
        if ( strlen($argument) < $name_length ) {
            return false; // cannot be found
        }
        
        // comparison is case insensitive
        if ( 0 != substr_compare(
                $argument, $name, 
                0, $name_length,
                true) ) { 
            
            return false; // name not found
        }
        
        // else: name has been found :)
 
        // strip the name, any space just after
        $argument_without_name = ltrim(substr($argument, $name_length));
        if (strlen($argument_without_name) == 0) {
            return '';
        }
        
        // strip any '=' juste after, and any space just after
        if ($argument_without_name[0] == '=') {
            $argument_without_name = ltrim(substr($argument_without_name, 1));
        }
        
        return $argument_without_name;

    }
    
    /**
     * Parse the value from wikitext string.
     * If the value cannot be parsed (contains bad caracters, is too long, ...), an Exception is thrown with Tools::throwUserError().
     * This method can transform the value, so only the returned value can be considered as parsed.
     * See Boolean or IntegerInPixel classes as good examples.
     * @param string $value The wikitext string (only the value, without "name=")
     * @return mixed $value The type depends on the final class extending Parameter.
     * @throws UserError if $value cannot be parsed
     */
    abstract protected function parse($value) ;
    
    /**
     * Ensure the given value is acceptable. (minimum, maximum, ...)
     * If the value is not acceptable, an Exception is thrown.
     * See Boolean or Integer classes as good examples.
     * @param mixed $value The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * @return mixed The unchanged $value, type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * For futur use.
     * @throws UserError if $value cannot be validated
     */
    abstract protected function validate($value) ;
       
    /**
     * Used when parsing wikitext.
     * Try to set the value of this parameter if the named argument correspond.
     * Note that the name is not case sensitive.
     * @param string $argument Raw argument
     * @param int $position
     * @return boolean True if set successfull
     * @throws UserError If parse() or validateDuringSet() failed
     */
    public function trySetByName($argument, $position) {
 
        if ($this->hasBeenSet()) {
            return false;
        }
        
        // strip whitespaces before and after
        $argument = trim($argument);
        
        $raw = $this->identifyByName($argument);
        if ( $raw === false ) {
            return false;
        }
        
        $parsed = $this->parse($raw); // can throw UserError Exception
        
        $validated = $this->validate($parsed); // can throw UserError Exception
        
        $this->setValue($validated);
        
        return true;
        
    }
    
    /**
     * Used when parsing wikitext.
     * Try to set the value of this parameter if the position of argument correspond.
     * @param string $argument Raw argument
     * @param int $unamed_arg_position The position of the argument in the unamed list
     * @param int $call_arg_position The orignal position (in wikitext widget call)
     * @return boolean True if set successfull
     * @throws UserError If parse() or validateDuringSet() failed
     */
    public function trySetByOrder($argument, $unamed_arg_position, $call_arg_position ) {
 
        if ($this->hasBeenSet()) {
            return false;
        }
        
        if ($this->getPosition() != $unamed_arg_position) {
            return false;
        }
        
        // strip whitespaces before and after
        $argument = trim($argument);
        
        $parsed = $this->parse($argument); // can throw UserError Exception
        
        $validated = $this->validate($parsed); // can throw UserError Exception
        
        $this->setValue($validated);
        
        return true;
        
    }
    
    /**
     * 
     * @param string|mixed $default_value As string, except if $do_validate is false
     * (in this case, it should be the same type as the final child class handle the value)
     * @param boolean $do_validate Validate before saving the default value (default is true)
     * @return void
     * @throws \MWException if $default_value cannot be parsed or validated
     */
    public function setDefaultValue($default_value, $do_validate = true) {
        
        if ($do_validate) {
            
            try {
                $as_string = strval($default_value);
                $parsed = $this->parse($as_string); // can throw UserError Exception
                $default_value = $this->validate($parsed); // can throw UserError Exception
                
            } catch (UserError $e) {
                throw new \MWException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->default_value = $default_value;
        
    }
    
    /**
     * Check the parameter requirements.
     * @return void
     * @throws UserError
     */
    public function validateAfterSet() {
        if ($this->isRequired() && !$this->hasBeenSet()) {
            Tools::throwUserError('The parameter '.$this->getName().' is required.');
        }
    }
    
    /**
     * @return string The output
     */
    abstract public function getOutput() ;
    
}

