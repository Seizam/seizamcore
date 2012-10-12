<?php
namespace WidgetsFramework;


class IntegerInPixel extends Integer {
    
    public function __construct($name) {
        parent::__construct($name);
    }
    
    /**
     * Only accepts digits and "px" as value.
     * Returns an int.
     * @param string $value
     * @return int
     * @throws UserError
     */
    public function parse($value) {
        
        $parsed = null;
        
        try {
            $parsed = parent::parse( str_ireplace('px','',$value) );     
            
        } catch (UserError $e) {        
            Tools::throwUserError('Parameter '.$this->getName().' only accepts digits and "px" as value ('.$value.' given).');        
        }
        
        return $parsed;
        
    }
    
}
