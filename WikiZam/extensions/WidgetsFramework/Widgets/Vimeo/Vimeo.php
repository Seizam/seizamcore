<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file

class Vimeo extends ParserFunction {
    
    // call it using wikitext {{#vimeo:}}
    protected static $NAME = 'vimeo';
    protected static $FLAGS = 0; 

    public function __construct() {
        wfDebugLog('WidgetsFramework', "Vimeo::__construct()");
    }

    public static function getOutput() {
        wfDebugLog('WidgetsFramework', "Vimeo->getOutput()");
        
        return '<strong class="error"> This is my first widget :) </strong>';
    }

}

