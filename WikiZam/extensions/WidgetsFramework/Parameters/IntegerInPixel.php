<?php
namespace WidgetsFramework;


class IntegerInPixel extends Integer {
    
    
    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>parameter is not required</li>
     * <li>position is not set (for futur use)</li>
     * <li>default value is integer 0</li>
     * <li>minimal value is 0</li>
     * <li>no maximal value</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {     
        parent::__construct($name);  
        $this->setMin(0);
    }
    
    /**
     * Convert from string to signed integer.
     * The string can contains the "px" unit.
     * The minimum and maximum value depends on the system. 32 bit systems have 
     * a maximum signed integer range of -2147483648 to 2147483647.
     * The maximum signed integer value for 64 bit systems is 9223372036854775807.
     * Empty string is considered as 0.
     * @param string $value 
     * @return int
     * @throws UserError
     */
    public function parse($value) {
            
        try {
            $parsed = parent::parse( str_ireplace(array('px', 'p'),'',$value) );     
            
        } catch (UserError $e) {        

            Tools::throwUserError(wfMessage('wfmk-validate',
                    $this->getName(), $value, wfMessage('wfmk-req-integerinpixel-value') )->text() ); 
        }
        
        return $parsed;
        
    }
    
}