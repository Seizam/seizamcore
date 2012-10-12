<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class ParserFunction implements Widget {
    
    protected static $PARSER_MARKER = 1;
    protected static $NAME = null;
    protected static $FLAGS = SFH_NO_HASH;

    protected $parameters;
    protected $parser;
    protected $frame;

    public function __construct($parser, $frame) {
        $this->parser = $parser;
        $this->frame = $frame;
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
     * Called after all parameters have tried to parse available arguments
     * (some of this arguments may not have been used) and after validateParametersAfterSet().
     * This method can simply returns a string, or an array in order to change MediaWiki parser output configuration.
     * The key <i>output</i> is required, others are optionals.
     * See Parser.php method braceSubstitution() (starting around line 2930) for more options.
     * The main keys/values are:
     * <ul>
     * <li> <i>output</i> => (string) the widget output, <b>REQUIRED</b></li>
     * <li> <i>nowiki</i> => (boolean) parser flag: "wiki markup in <i>output</i> should be escaped", default 
     * is <b>false</b></li>
     * <li> <i>isHTML</i> => (boolean) parser flag: "<i>output</i> is HTML, armour it against wikitext 
     * transformation", default is <b>false</b> 
     * WARNING: is set to true, a preceding blank line will be added, creating an empty paragraphe
     * on the final page (use <i>fixHtmlEmptyP</i> option to remove this preceding line)</li>
     * <li> <i>fixHtmlEmptyP</i> => (boolean) WidgetsFramework flag: "remove preceding blank line added by parser when 
     * <i>isHTML</i> is true", default is <b>false</b></li>
     * <li> <i>noparse</i> => (boolean) parser flag, indicate to not parse the <i>output</i>, default is <b>true</b></li>
     * <li> <i>preprocessFlags</i> => (integer) additional flags used if noparse is false, default is <b>0</b></li>
     * <li> <i>found</i> => (boolean) parser flag "<i>output</i> has been filled", default is <b>true</b></li>
     * <li> <i>isChildObj<i> => (boolean) parser flag "$text is a DOM node needing expansion in a child frame", default
     * is <b>false</b>, but is forced to true if <i>noparse</i> is false</li>
     * <li> <i>isLocalObj</i> => (boolean) parser flag: "<i>output</i> is a DOM node needing expansion in the current 
     * frame", default is <b>false</b></li>
     * </ul>
     * @return string|array The output string, or an array to specify special output configuration.
     */
    abstract protected function getOutput();
    

    private function insertNoWikiStripItem( $text ) {
		$rnd = "{$this->parser->mUniqPrefix}-item-{$this->parser->mMarkerIndex}-" . \Parser::MARKER_SUFFIX;
		$this->parser->mMarkerIndex++;
//		$this->parser->mStripState->addNoWiki( $rnd, $text );
		$this->parser->mStripState->addGeneral( $rnd, $text );        
		return $rnd;
	}
    
    /**
     * @todo implement configuration
     * @param array $arguments Array of strings
     * @return string HTML output
     * @throws \MWException
     */
    public function execute($arguments) {
        
        wfDebugLog('WidgetsFramework', 'ParserFunction '.static::GetName().' -> execute('.count($arguments).' arguments)');
        
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
        
        $output = $this->getOutput();
        //$output = preg_replace('/\s\s+/', ' ', $output);
        $output = $this->frame->expand( $output, \PPFrame::RECOVER_ORIG );
            
        // avoid a hardcoded "\n\n" that is prepended to the HTML output of parser functions
        // see http://www.mediawiki.org/wiki/Manual:Parser_functions#Controlling_the_parsing_of_output
        return $this->insertNoWikiStripItem( $output );
        

        
        
        // configuration
        $isHTML = false; // parser default = false
        $trim_newlines = true;
        $display_source = false; // require $isHTML=false
        $bypass_parser_mod = true;
        
        // display it as requested
        if ($trimNewLines) {
            $text = preg_replace('/\s\s+/', ' ', $text);
        }
        if ($displayAsSource) {
            $text = '<pre>' . $text . '</pre>';
        }

        $result = WidgetStripper::StripItem($result);
        
        return array(
            $result,
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

            return $this->execute($arguments);
            
        } catch (UserError $e) {

            wfDebugLog('WidgetsFramework', 'ParserFunction '.static::GetName().' -> tryExecute : UserError exception "'.$e->getMessage().'"');
            // avoid a hardcoded "\n\n" that is prepended to the HTML output of parser functions
            // see http://www.mediawiki.org/wiki/Manual:Parser_functions#Controlling_the_parsing_of_output
            return $this->parser->insertStripItem( '<div class="error"><b>Error in widget '.static::GetName().'</b><br />' . $e->getHTML() . '</div>' );
        }
        
        
        
    }
    
    public static function GetName() {

        return implode('', array_slice(explode('\\', get_called_class()), -1));

    }
    
    
    public static function Setup() {
        
    }
    
    public static function Register($parser) {
        
        $name = static::GetName();
        $function = get_called_class() . '::onParserFunctionHook';      
//        $flags = static::$FLAGS & ~SFH_OBJECT_ARGS; // ensure using string arguments (not array of object)
        $flags = static::$FLAGS | SFH_OBJECT_ARGS; // ensure using string arguments (not array of object)
        
        $parser->setFunctionHook($name, $function, $flags);

        wfDebugLog('WidgetsFramework', 'ParserFunction::Setup() : name=' . $name . ' function=' . $function . ' flags=' . $flags);
        
        return true;
    }

    /**
     * 
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string Html output
     */
 /*   public static function onParserFunctionHook($parser) {
        
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
  */
    
    /**
     * 
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string Html output
     */
    public static function onParserFunctionHook($parser, $frame, $args) {
        
        $arguments = array();
        
        foreach ($args as $arg) {
            $arguments[] = trim($frame->expand($arg));
        }

        $child_class = get_called_class();

        $widget = new $child_class($parser, $frame);
        
        return $widget->tryExecute($arguments);
        
    }

}