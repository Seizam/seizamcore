<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class Parameter {

    protected $name;
    protected $value;
    protected $default_value;
    
    protected $required;

    /**
     * <ul>
     * <li>default value is empty string,</li>
     * <li>parameter is not required</li>
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
    }

    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * 
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
     * @param string $argument The raw argument
     * @return boolean/string The string value, or false if not identified
     */
    protected function identifyByName($argument) {
        
        $name = $this->getName();
        $name_length = strlen($name);
        
        // strip whitespace before
        $argument = ltrim($argument);
        
        if ( strlen($argument) < $name_length ) {
            return false; // cannot be found
        }
        
        // comparison is case insensitive
        if ( ! substr_compare(
                $argument, $name, 
                0, $name_length,
                true) ) { 
            
            return false; // name not found
        }
        
        // else: name has been found :)
        // 
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
     * If the value cannot be parsed (contains bad caracters, is too long, ...), an Exception is thrown.
     * This method can transform the value, so only the returned value can be considered as parsed.
     * See Boolean or PixelSize classes as good examples.
     * @param string $value The wikitext string (only the value, without "name=")
     * @return mixed $value The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * @throws UserError if $value cannot be parsed
     */
    abstract protected function parse($value) ;
    
    /**
     * Ensure the given value is acceptable. (minimum, maximum, ...)
     * If the value is not acceptable, an Exception is thrown.
     * See Boolean or PixelSize classes as good examples.
     * @param mixed $value The type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * @return mixed The unchanged $value, type depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * For futur use.
     * @throws UserError if $value cannot be validated
     */
    abstract protected function validateDuringSet($value) ;
       
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
        
        $raw = $this->identifyByName($argument);
        if ( $raw === false ) {
            return false;
        }
        
        $parsed = $this->parse($raw); // can throw UserError Exception
        
        $validated = $this->validateDuringSet($parsed); // can throw UserError Exception
        
        $this->setValue($validated);
        
        return true;
        
    }
    
    /**
     * 
     * @param mixed $default_value The type of this argument depends on the final class extending Parameter.
     * In most cases, is should be a string.
     * @param boolean $do_validate Validate before saving the default value (default = true)
     * @return void
     * @throws \MWException if $default_value cannot be parsed or validated
     */
    public function setDefaultValue($default_value, $do_validate = true) {
        
        if ($do_validate) {
            
            try {      
                $parsed = $this->parse($default_value); // can throw UserError Exception
                $default_value = $this->validateDuringSet($parsed); // can throw UserError Exception
                
            } catch (UserError $e) {
                throw new \MWException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->default_value = $default_value;
        
    }
    
    /**
     * Check the parameter requirements.
     * @return void
     */
    public function validateAfterSet() {
        if ($this->isRequired() && !$this->hasBeenSet()) {
            $this->generateUserError('The parameter '.$this->getName().' is required.');
        }
    }
    
    /**
     * @return string The HTML output
     */
    abstract public function getHtml() ;
    
}

