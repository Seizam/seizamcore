<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class ParserFunction implements Widget {
    
    protected $parameters;
    
    protected static $NAME = 'ParserFunction';
    protected static $FLAGS = SFH_NO_HASH;
    
    protected $parser;


    public function __construct() {
        $this->parameters = array();
        $this->parser = null;
    }
    
    /**
     * 
     * @param Parser $parser
     */
    public function setParser($parser) {
        $this->parser = $parser;
    }
    
    /**
     * 
     * @param Parameter $new_parameter
     * @return void
     */
    public function addParameter($new_parameter) {
                
        if (! $new_parameter instanceof Parameter) {
            throw new MWException( __METHOD__ . ' called without argument of type "Parameter".' );
        }
        
        $this->parameters[$new_parameter->getName()] = $new_parameter;
        
        wfDebugLog('WidgetsFramework', 'ParserFunction->addParameter('.$new_parameter->getName().')');
    }
    
    /**
     * 
     * @param array $arguments Array of string
     * @return array Array of string: arguments that have not been assigned.
     */
    protected function setParametersByName($arguments) {
        
        $position = 1;
        $unset_arguments = array();
        
        foreach ( $arguments as $argument ) {
            
            $set = false;
            
            foreach ( $this->parameters as $parameter ) {
                if ( $parameter->trySetByName($argument, $position) ) {
                    $set = true;
                    break;                   
                }
            }
            
            if (!$set) {
                $unset_arguments[$position] = $argument;
            }
            
            $position++;
            
        }
        
        return $unset_arguments;
        
    }
    
    /**
     * 
     * @param array $arguments Array of string: (int)position => (string)argument
     * @return array Array of string: arguments that have not been assigned.
     */
    protected function setParametersByPosition($arguments) {
        
        $unset_arguments = array();
        
        foreach ( $arguments as $position => $argument ) {
            
            $set = false;
            
            foreach ( $this->parameters as $parameter ) {
                if ( $parameter->trySetByPosition($argument, $position) ) {
                    $set = true;
                    break;                   
                }
            }
            
            if (!$set) {
                $unset_arguments[$position] = $argument;
            }
            
        }
        
        return $unset_arguments;
        
    }
    
    /**
     * 
     * @param array $arguments Array of string
     */
    public function setParameters( $arguments ) {
        
        // first pass : identifying named arguments
        $name_not_known = $this->setParametersByName($arguments);
        
        // second pass : identify arguments by position
        $unassigned_arguments = $this->setParametersByPosition($name_not_known);
        
    }
    
    public function validateParametersAfterSet() {
        foreach ( $this->parameters as $parameter ) {
            $parameter->validateAfterSet() ;
        }
    }
    
    
    abstract public function declareParameters() ;
    /**
     * 
     * @param Parser $parser
     * @return string the HTML output
     */
    abstract public function render() ;
    
    /**
     * 
     * @param array $arguments Array of strings
     * @return string HTML output
     */
    public function execute( $arguments ) {
        
        try {
            
            $this->setParameters($arguments);

            $this->validateParametersAfterSet();

            $rendered = preg_replace('/\s\s+/', ' ',$this->render());
            return array(
                $rendered,
                'isHTML' => true
            );
            
            
        } catch (UserError $e) {
            return '<div class=\"error\">Exception !</div>';
        }
    }

    
    
    public static function Setup( $parser ) {
        $name = static::$NAME;
        $function = get_called_class().'::onParserFunctionHook';
        // ensure using string arguments (not array of object)
        $flags = static::$FLAGS & ~SFH_OBJECT_ARGS;
        $parser->setFunctionHook( $name, $function, $flags );   
        
        wfDebugLog('WidgetsFramework', 'ParserFunction::Setup() : name='.$name.' function='.$function.' flags='.$flags);
    }
    
    /**
     * 
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string Html output
     */
    public static function onParserFunctionHook( $parser ) {
        wfDebugLog('WidgetsFramework', 'ParserFunction::onParserFunctionHook() : name='.static::$NAME);
        
        $child_class = get_called_class();
        $widget = new $child_class();
        
        $widget->setParser($parser);
        $widget->declareParameters();
        
        $arguments = func_get_args();              
        array_shift($arguments); // shift off the parser
        
        return $widget->execute( $arguments );
    }
    
}