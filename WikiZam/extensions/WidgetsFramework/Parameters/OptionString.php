<?php

namespace WidgetsFramework;

class OptionString extends String {
    
    protected $option_value;
    
    /**
     * Default behavior:
     * <ul>
     * <li>default (OFF) value is empty string</li>
     * <li>ON value is empty string</li>
     * <li>parameter is not required</li>
     * <li>escape mode: <i>html</i></li>
     * <li>minimal length: <i>0 character</i></li>
     * <li>maximal length: <i>1024 chars</i></li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->option_value = '';
    }
    
    /**
     * Set the value when option is on (without value)
     * @param string $option_value Will be parsed, except if $do_parse is false<br/>
     * @param boolean $do_parse do parse the $option_value (default is true, safer) 
     * @return void
     * @throws \MWException if $default_value cannot be parsed
     */
    public function setONValue($option_value, $do_parse = true) {

        if ($do_parse && is_string($option_value)) {
            try {

                $option_value = $this->parse($option_value); // can throw UserError Exception
                
            } catch (UserError $e) {
                throw new \MWException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->option_value = $option_value;
    }
    
    /**
     * Parse the value from wikitext string. An OptionString parameter can be set without value, or have any value.
     * @param string|true $value The wikitext string value, without "name=" (can be empty string)<br />
     * true if parameter specified without value assignment
     * @return string The unchanged $value when parameter set with a string value, or the option_value 
     * (defined earlier using setOptionValue) if parameter set as an option (without value)
     */
    public function parse($value) {
        
        if ($value === true) {
            $value = $this->option_value;
        }
        
        return $value;
        
    }
    
}
