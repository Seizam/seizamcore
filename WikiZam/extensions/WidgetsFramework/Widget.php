<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file

interface Widget {
    
    /**
     * 
     * @param Parser $parser
     * @return void
     */
    public static function Setup( $parser ) ;
    
    public static function getOutput() ;
    
}

