<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class Parameter {

    protected $name;
    protected $value;
    protected $default_value; 
    protected $required;
    
    protected $position; // for futur use

    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is empty string</li>
     * <li>parameter is not required</li>
     * <li>position is not set (for futur use)</li>
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
        
        $this->position = -1; // for futur use
    }

    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Indicate if this parameter can still read a value.
     * @return boolean true=<i>cannot read a value anymore</i> , false=<i>value not yet set</i>
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
     * 
     * @param boolean $required If not specified, considered as true
     */
    public function setRequired($required = true) {
        
        if (!is_bool($required)) {
            throw new \MWException('Method setRequired() of parameter '.$this->getName().' need a boolean argument.');
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
     * <b>For futur use.</b><br/>
     * Set this parameter position in the widget parameter list.
     * @param int $position
     */
    public function setPosition($position) {
        $this->position = $position;
    }
    
    /**
     * <b>For futur use.</b><br/>
     * The position in the list of parameters which can still read a value
     * @return int
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * Analyse this argument, and look for a named value using this parameter name, case insensitive.
     * @param string $argument The raw argument
     * @return string|null The string value (without "name=") if $argument is a 
     * named value for this parameter, or null if not.
     */
    protected function identifyByName($argument) {
        
        $name = $this->getName();
        $name_length = strlen($name);
        
        if ( strlen($argument) < $name_length ) {
            return null; // too short, name cannot be found
        }
        
        // comparison is case insensitive
        if ( 0 != substr_compare(
                $argument, $name, 
                0, $name_length,
                true) ) { 
            
            return null; // name not found
        }
        
        // else: name has been found :)

        // strip the name, and any space just after
        $argument_without_name = ltrim(substr($argument, $name_length));
        if (strlen($argument_without_name) == 0) {
            return ''; // no value, juste the name of the parameter, this can be acceptable for some parameter
        }
        
        // the next char must be '=', and maybe some spaces after
        if ($argument_without_name[0] != '=') {
            // this is not a named value
            return null;
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
     * @param string $value The wikitext string value, without "name=", can be empty string
     * @return mixed $value The type depends on the final class extending Parameter.
     * @throws UserError if $value cannot be parsed
     */
    abstract protected function parse($value) ;
    
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
    abstract protected function validate($value) ;
       
    /**
     * Used when parsing wikitext.
     * Try to set the value if $argument is a named value for this parameter.
     * The name is case insensitive.
     * @param string $argument Raw argument, with no spaces at the begin or at the end
     * @param int $position (for futur use)
     * @return boolean True if set successfull, false is $argument is not a named value for this parameter.
     * @throws UserError If $argument is a named value for this parameter, but the value cannot
     * be parsed or validated.
     */
    public function trySetByName($argument, $position) {
 
        if ($this->hasBeenSet()) {
            return false; // cannot read a value anymore
        }
        
        if (strlen($argument) == 0) {
            // no name can be found
            return false;
        }
        
        $named_value = $this->identifyByName($argument);
        if ( is_null($named_value) ) {
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
     * Try to set the value of this parameter if the position of argument correspond.
     * @param string $argument Raw argument
     * @param int $unamed_arg_position The position of the argument in the unamed list (for futur use)
     * @param int $call_arg_position The orignal position in wikitext widget call (for futur use)
     * @return boolean True if set successfull, false if $argument not read
     * @throws UserError If the parameter has already read a value, or if $argument cannot be 
     * parsed and validated.
     */
    public function trySetByOrder($argument, $unamed_arg_position, $call_arg_position ) {
 
        if ($this->hasBeenSet()) {
            // cannot read a value anymore
            Tools::throwUserError(wfMessage('wfmk-already-assigned',
                    $this->getName(), $this->getOutput(), $argument)); 
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
            Tools::throwUserError( wfMessage('wfmk-req-parameter', $this->getName())->text() );
        }
    }
    
    /**
     * Returns the string that represents the value of this parameter.
     * This method is used to output a parameter in widgets getOuput() method.
     * @return string The string representation of this parameter value. 
     */
    abstract public function getOutput() ;
    
}
