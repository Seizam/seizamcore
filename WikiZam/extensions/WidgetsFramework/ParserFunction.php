<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class ParserFunction implements Widget {
    
    protected $parameters;
    
    protected static $NAME = 'ParserFunction';
    protected static $FLAGS = SFH_NO_HASH;
    
    
    public static function Setup( $parser ) {
        $name = static::$NAME;
        $function = get_called_class().'::onParserFunctionHook';
        // ensure adding a frame parameter and passing the arguments as an array
        $flags = static::$FLAGS | SFH_OBJECT_ARGS;
        $parser->setFunctionHook( $name, $function, $flags );   
        
        wfDebugLog('WidgetsFramework', 'ParserFunction::Setup() : name='.$name.' function='.$function.' flags='.$flags);
    }
    
    /**
     * 
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string
     */
    public static function onParserFunctionHook( $parser, $frame, $args ) {
        wfDebugLog('WidgetsFramework', 'ParserFunction::onParserFunctionHook() : name='.static::$NAME);
        
        $child_class = get_called_class();
        $widget = new $child_class();
        
        return $widget->getOutput();
    }

}