<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class ParserFunction implements Widget {
    
    protected static $PARSER_MARKER = 1;
    protected static $NAME = null;
    protected static $FLAGS = SFH_NO_HASH;

    protected $parameters;
    protected $parser;

    public function __construct($parser) {
        $this->parser = $parser;
        $this->parameters = array();
    }
    
    /**
     * Child class write their parameters instaciation in this method.
     * @return void
     */
    abstract protected function declareParameters();
    
    protected function updateParametersPositions() {
        $position = 1;
        foreach ($this->parameters as $parameter) {
            // $position = $parameter->updatePosition($position);
            $parameter->setPosition($position);
            $position++;
        }
    }

    /**
     * 
     * @param Parameter $new_parameter
     * @return void
     */
    public function addParameter($new_parameter) {

        if (!$new_parameter instanceof Parameter) {
            throw new \MWException(__METHOD__ . ' called without argument of type "Parameter".');
        }

        $this->parameters[$new_parameter->getName()] = $new_parameter;
    }

    /**
     * 
     * @param array $arguments Array of string
     * @return array Array of string: arguments that have not been assigned.
     */
    protected function setParametersByName($arguments) {

        $position = 1;
        $unset_arguments = array();

        foreach ($arguments as $argument) {

            $set = false;

            foreach ($this->parameters as $parameter) {
                if ($parameter->trySetByName($argument, $position)) {
                    $set = true;
                    wfDebugLog('WidgetsFramework', 'ParserFunction '.static::GetName().' -> Parameter ' . $parameter->getName() . ' : consumed by name argument "' . $argument . '"');
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
    protected function setParametersByOrder($arguments) {

        // construct the list of parameter that can still accept values
        $not_set_parameters = array();
        $index = 1;
        foreach ($this->parameters as $parameter) {
            if (!$parameter->hasBeenSet()) {
                $not_set_parameters[$index] = $parameter;
                $index++;
            }
        }
        
        $index = 1;
        $unused_arguments = array();
        
        foreach ($arguments as $call_arg_position => $argument) {
            
            if ( ! $not_set_parameters[$index]->trySetByOrder($argument, $index, $call_arg_position)) {              
                $unused_arguments[$call_arg_position] = $argument;            
            }
            
            $index++;        
        }

        return $unused_arguments;
        
    }

    protected function validateParametersAfterSet() {
        foreach ($this->parameters as $parameter) {
            $parameter->validateAfterSet();
        }
    }

    /**
     * 
     * @param Parser $parser
     * @return string the HTML output
     */
    abstract protected function getOutput();
    

    /**
     * @todo implement configuration
     * @param array $arguments Array of strings
     * @return string HTML output
     * @throws \MWException
     */
    public function execute($arguments) {
        
        wfDebugLog('WidgetsFramework', 'ParserFunction '.static::GetName().' -> execute('.count($arguments).' arguments)');
        
        // configuration
        $isHTML = false; // parser default = false
        $trim_newlines = true;
        $display_source = false; // require $isHTML=false
        $bypass_parser_mod = true;
 
        // initialize
        $this->declareParameters();
        $this->updateParametersPositions();
        
        //first pass : identify by name
        $not_named = $this->setParametersByName($arguments);
        
        // reset unset parameters positions
        $this->updateParametersPositions();

        // second pass : identify arguments by position
        $unassigned = $this->setParametersByOrder($not_named);
        
        wfDebugLog('WidgetsFramework', 'ParserFunction '.static::GetName().' -> execute : '.count($unassigned).' argument(s) unassigned');
        
        // check all parameters (required, ...)
        $this->validateParametersAfterSet();
        
        $rendered = $this->getOutput();

        // display it as requested
        if ($trim_newlines) {
            $rendered = preg_replace('/\s\s+/', ' ', $rendered);
        }
        if ($display_source) {
            $rendered = '<pre>' . $rendered . '</pre>';
        }
        
        $rendered = WidgetStripper::StripItem($rendered);
        
        return array(
            $rendered,
            'isHTML' => $isHTML
        );

    }

        /**
     * @todo implements $bypass_parser_mod option
     * @param array $arguments Array of strings
     * @return string HTML output
     * @throws \MWException
     */
    public function tryExecute($arguments) {
        
       try {

            return $this->execute($arguments) ;
            
        } catch (UserError $e) {

            wfDebugLog('WidgetsFramework', 'ParserFunction '.static::GetName().' -> tryExecute : EXCEPTION "'.$e->getMessage().'"');
            
            return array(
                '<div class="error"><b>Error in widget '.static::GetName().'</b><br />' . $e->getHTML() . '</div>',
                'isHTML' => true
            );
        }
        
    }
    
    public static function GetName() {

        return implode('', array_slice(explode('\\', get_called_class()), -1));

    }
    
    public static function Setup($parser) {
        
        $name = static::GetName();
        $function = get_called_class() . '::onParserFunctionHook';      
        $flags = static::$FLAGS & ~SFH_OBJECT_ARGS; // ensure using string arguments (not array of object)
        
        $parser->setFunctionHook($name, $function, $flags);

        wfDebugLog('WidgetsFramework', 'ParserFunction::Setup() : name=' . $name . ' function=' . $function . ' flags=' . $flags);
    }

    /**
     * 
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string Html output
     */
    public static function onParserFunctionHook($parser) {
        
        $arguments = func_get_args();
        array_shift($arguments); // shift off the parser
        //
        // when no arguments given (in wikitext), receive an array with 1 empty string : remove this string
        if (count($arguments)==1 && reset($arguments)=='') {
            array_shift($arguments);
        }
        
        $child_class = get_called_class();

        $widget = new $child_class($parser);
        
        return $widget->tryExecute($arguments);
        
    }

}