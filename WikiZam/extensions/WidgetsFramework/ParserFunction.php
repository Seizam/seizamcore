<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

abstract class ParserFunction implements Widget {
    
    protected static $PARSER_MARKER = 1;
    protected static $NAME = null;
    protected static $FLAGS = SFH_NO_HASH;

    protected $parameters;
    protected $parser;
    protected $frame;
    
    // output configuration
    protected $is_html; 
    protected $is_block;
    

    public function __construct($parser, $frame) {
        $this->parser = $parser;
        $this->frame = $frame;
        $this->parameters = array();
        
        $this->is_block = true;
        $this->is_html = true;
    }
    
    /**
     * This method return the name of the parser function, ie the name of the child class.
     * Even if it is static, it uses Late Static Binding to returns the real name of each instance.
     * @return string
     */
    public static function GetName() {
        return implode('', array_slice(explode('\\', get_called_class()), -1));
    }
        
    /**
     * Child class write their parameters instanciation in this method.
     * @return void
     */
    abstract protected function declareParameters();
    
    /**
     * For futur use.
     */
    protected function updateParametersPositions() {
        $position = 1;
        foreach ($this->parameters as $parameter) {
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
            throw new \MWException('Method addParameter() of widget '.static::GetName().' requires an argument of type "Parameter".');
        }

        $new_parameter_name = $new_parameter->getName();
        if (array_key_exists($new_parameter_name, $this->parameters)) {
            throw new \MWException('Cannot add parameter "'.$new_parameter_name.'". Each parameter need disctinct name.');
        }
        
        $this->parameters[$new_parameter->getName()] = $new_parameter;
    }

    /**
     * 
     * @param array $arguments Array of string
     * @return array Array of string: arguments that have not been used.
     */
    protected function setParametersByName($arguments) {

        $position = 1;
        $unused_arguments = array();

        foreach ($arguments as $argument) {

            $set = false;

            foreach ($this->parameters as $parameter) {
                if ($parameter->trySetByName($argument, $position)) {
                    $set = true;
                    wfDebugLog('WidgetsFramework', static::GetName().' : parameter ' . $parameter->getName() . ' used argument "' . $argument . '" by name');
                    break;
                }
            }

            if (!$set) {
                $unused_arguments[$position] = $argument;
            }

            $position++;
        }

        return $unused_arguments;
    }

    /**
     * 
     * @param array $arguments Array of string: (int)position => (string)argument
     * @return array Array of string: arguments that have not been used.
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
        
        $unused_arguments = $arguments; // copy
        reset($not_set_parameters);
        reset($arguments);
        // loop in $not_set_parameters and $arguments arrays, while possible        
        while ( 
                (list($index, $parameter) = each($not_set_parameters)) && 
                (list($call_arg_position, $argument) = each($arguments)) ) {
            
            if ( ! $parameter->trySetByOrder($argument, $index, $call_arg_position)) {
                
                wfDebugLog('WidgetsFramework', static::GetName().' : parameter ' . $parameter->getName() . ' DID NOT USED "' . $argument . '" by order');
                
            } else {
                
                unset($unused_arguments[$call_arg_position]);
                wfDebugLog('WidgetsFramework', static::GetName().' : parameter ' . $parameter->getName() . ' used argument "' . $argument . '" by order');
                
            }
            
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
     * <ul>
     * <li>This method can simply returns a string, that will be rendered as defined by a previous call
     * to one of the <i>setOutputAs...()</i> methods. (setOutputAsHTMLBlock() by default)</li>
     * <li>The returned value can also be an array. In this case, the framework will no longer
     * format the output, and this array is directly given to the MediaWiki parser.
     * The first value of the array is the <i>output</i> and is required.
     * Others keys define some parser flag with their value. All are optionals.
     * See Parser.php method braceSubstitution() (starting around line 2930) for more options.
     * The main keys/values are:
     * <ul>
     * <li> First array element => (string) the widget output, <b>REQUIRED</b></li>
     * <li> <i>nowiki</i> => (boolean) parser flag: "wiki markup in <i>output</i> should be escaped", default 
     * is <b>false</b></li>
     * <li> <i>isHTML</i> => (boolean) parser flag: "<i>output</i> is HTML, armour it against wikitext 
     * transformation", default is <b>false</b> 
     * WARNING: is set to true, a preceding blank line will be added, creating an empty paragraphe
     * on the final page (use <i>fixHtmlEmptyP</i> option to remove this preceding line)</li>
     * <li> <i>noparse</i> => (boolean) parser flag, indicate to not parse the <i>output</i>, default is <b>true</b></li>
     * <li> <i>preprocessFlags</i> => (integer) additional flags used if noparse is false, default is <b>0</b></li>
     * <li> <i>found</i> => (boolean) parser flag "<i>output</i> has been filled", default is <b>true</b></li>
     * <li> <i>isChildObj<i> => (boolean) parser flag "$text is a DOM node needing expansion in a child frame", default
     * is <b>false</b>, but is forced to true if <i>noparse</i> is false</li>
     * <li> <i>isLocalObj</i> => (boolean) parser flag: "<i>output</i> is a DOM node needing expansion in the current 
     * frame", default is <b>false</b></li>
     * </ul>
     * </ul>
     * @return string|array The output string, or an array containing output and MediaWiki parser flags.
     */
    abstract protected function getOutput();
    

    private function insertNoWikiStripItem( $text ) {
		$rnd = "{$this->parser->mUniqPrefix}-item-{$this->parser->mMarkerIndex}-" . \Parser::MARKER_SUFFIX;
		$this->parser->mMarkerIndex++;
		$this->parser->mStripState->addNoWiki( $rnd, $text );
//		$this->parser->mStripState->addGeneral( $rnd, $text );        
		return $rnd;
	}

    /**
     * Defines the output as to not be modified by the parser.
     * @param boolean $is_html True=<i>the output will be rendered as it is, without any parser interpretation
     * or modification</i> <b>(this is the default behavior)</b>
     */
    protected function setHTML($is_html = true) {
        $this->is_html = $is_html;
    }
    
    /**
     * 
     * @param boolean $is_block True=<i>will be rendered inside a DIV element</i> <b>(this is the default behavior)</b><br />
     * False=<i>will be rendered as the parser wants to, most often inside a P element</i>
     */
    protected function setBlock($is_block = true) {
        $this->is_block = $is_block;
    }
    
    /**
     * 
     * @param string|array $output A string, or an array containing output text and parser flag that 
     * will stay unchanged by this method
     * @return string|array Prepared string $output for parser, or unchanged array $output.
     */
    protected function getOutputForParser($output) {
        
        if ( is_array( $output ) ) {         
            // this array should contains MediaWiki parser flags
            return $output;
            
        }
        
        // else
        
        if ($this->is_html) {           
            // do nowiki strip format
            $output = $this->insertNoWikiStripItem($output);
        } 
        
        if ($this->is_block) {
            $output = '<div>'.$output.'</div>';
        }
        
        return $output;
    }
    
    /**
     * @todo implement configuration
     * @param array $arguments Array of strings
     * @return string HTML output
     * @throws \MWException
     */
    public function tryExecute($arguments) {
        
        wfDebugLog('WidgetsFramework', static::GetName().'->execute('.count($arguments).' argument(s))');
        
        // initialize
        $this->declareParameters();
        $this->updateParametersPositions(); // for futur use
        
        // first pass : identify by name
        $not_named = $this->setParametersByName($arguments);
        
        // reset unset parameters positions, for futur use
        $this->updateParametersPositions();

        // second pass : identify arguments by position
        $unused = $this->setParametersByOrder($not_named);
        wfDebugLog('WidgetsFramework', static::GetName().'->execute() : '.count($unused).' argument(s) left unused');
        
        // check all parameters (required, ...)
        $this->validateParametersAfterSet();
        
        // the last step, format the output
        $output = $this->getOutputForParser($this->getOutput());
        
        return $output;

    }

    /**
     * @todo implements $bypass_parser_mod option
     * @param array $arguments Array of strings
     * @return string HTML output
     * @throws \MWException
     */
    public function execute($arguments) {
        
        try {

            return $this->tryExecute($arguments);
            
        } catch (UserError $e) {

            wfDebugLog('WidgetsFramework', static::GetName().'->tryExecute() UserError exception catched : "'.$e->getMessage().'"');
            
            $this->setHTML();
            $this->setBlock();
            return $this->getOutputForParser( wfMessage('wfmk-error-in-widget', static::GetName(), $e->getHTML())->text() );

        }
    
    }
        
    /**
     * Called by the parser when this parser function is used
     * @param Parser $parser
     * @param PPFrame $frame
     * @param array $args
     * @return string Html output
     */
    public static function onParserFunctionHook($parser, $frame, $args) {
        
        $arguments = array();
        
        // transform objects to strings
        foreach ($args as $arg) {
            $arguments[] = trim($frame->expand($arg));
        }
        
        // if no argument given, we receive an array with one empty string, remove it
        if ( count($arguments)==1 && reset($arguments)=='') {
            array_shift($arguments);
        }

        // instanciate
        $child_class = get_called_class();
        $widget = new $child_class($parser, $frame);
        
        // try to execute (catch UserError exceptions to display nice error message)
        return $widget->execute($arguments);
        
    }

    public static function Register($parser) {
        
        $name = static::GetName();
        $method = get_called_class() . '::onParserFunctionHook';      
        $flags = static::$FLAGS | SFH_OBJECT_ARGS; // ensure we will receive a PPFrame object and arguments as objects 
        
        $parser->setFunctionHook($name, $method, $flags);

        wfDebugLog('WidgetsFramework', $name.'::Register() : parser function registered with flags=' . $flags);
        
        return true;
    }
    
}