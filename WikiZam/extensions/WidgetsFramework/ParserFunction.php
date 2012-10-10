<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class ParserFunction implements Widget {
    
    protected static $PARSER_MARKER = 1;
    protected static $NAME = 'ParserFunction';
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
            $position = $parameter->updatePosition($position);
        }
    }

    /**
     * 
     * @param Parameter $new_parameter
     * @return void
     */
    public function addParameter($new_parameter) {

        if (!$new_parameter instanceof Parameter) {
            throw new MWException(__METHOD__ . ' called without argument of type "Parameter".');
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
                    wfDebugLog('WidgetsFramework', 'ParserFunction '.static::$NAME.' -> Parameter ' . $parameter->getName() . ' : consumed by name argument "' . $argument . '"');
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

        $unsassigned = array();
        $unamed_arg_position = 1;

        // construct the list of parameter which can still accpet values
        $not_set_parameters = array();
        foreach ($this->parameters as $parameter) {
            if (!$parameter->hasBeenSet()) {
                $not_set_parameters[] = $parameter;
            }
        }
        
        foreach ($arguments as $call_arg_position => $argument) {

            $assigned = false;

            foreach ($not_set_parameters as $index => $parameter) {

                if ($parameter->trySetByOrder($argument, $unamed_arg_position, $call_arg_position)) {
                    $assigned = true;
                    if ($parameter->hasBeenSet()) {
                        unset($not_set_parameters[$index]);
                    }
                    wfDebugLog('WidgetsFramework', 'ParserFunction '.static::$NAME.' -> Parameter ' . $parameter->getName() . ' : consumed by order argument "' . $argument . '"');
                    break;
                }
            }

            if (!$assigned) {
                $unsassigned[$call_arg_position] = $argument;
            }
            
            $unamed_arg_position++;
        }

        return $unsassigned;
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
        
        wfDebugLog('WidgetsFramework', 'ParserFunction '.static::$NAME.' -> execute('.count($arguments).' arguments)');
        
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
        
        wfDebugLog('WidgetsFramework', 'ParserFunction '.static::$NAME.' -> execute : '.count($unassigned).' argument(s) unassigned');
        
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

            wfDebugLog('WidgetsFramework', 'ParserFunction '.static::$NAME.' -> tryExecute : EXCEPTION "'.$e->getMessage().'"');
            
            return array(
                '<div class="error">' . $e->getHTML() . '</div>',
                'isHTML' => true
            );
        }
        
    }
    
    public static function Setup($parser) {
        $name = static::$NAME;
        $function = get_called_class() . '::onParserFunctionHook';
        // ensure using string arguments (not array of object)
        $flags = static::$FLAGS & ~SFH_OBJECT_ARGS;
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