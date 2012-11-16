<?php

/**
 * Parser functions widget.
 * {{WidgetName:Par1|Par2...}}
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

abstract class ParserFunction implements Widget {

    protected $parameters;

    /** @var Parser */
    protected $parser;

    /** @var PPFrame */
    protected $frame;

    /** @var boolean  should output be stripped? */
    protected $is_html;

    /** @var boolean should output be considered a block? */
    protected $is_block;

    /** @var int flags for parserfunction setup */
    protected static $FLAGS = SFH_NO_HASH;

    /**
     * @param Parser $parser
     * @param PPFrame $frame
     */
    public function __construct($parser, $frame) {
        $this->parser = $parser;
        $this->frame = $frame;
        $this->parameters = array();

        $this->is_block = true;
        $this->is_html = true;
    }

    /**
     * Returns the name of the parser function (using Late Static Binding).
     * 
     * @return string The widget's name (ie the name of the child class)
     */
    public static function GetName() {
        return implode('', array_slice(explode('\\', get_called_class()), -1));
    }

    /**
     * Declares the widget's parameters:
     * <ul>
     * <li>instanciates Parameter objects,</li>
     * <li>configures them and</li>
     * <li>calls addParameter() for each of them.</li>
     * </ul>
     * 
     * @return void
     */
    abstract protected function declareParameters();

    /**
     * Adds a parameter to the widget
     * 
     * @param Parameter $new_parameter
     * @return void
     */
    public function addParameter($new_parameter) {

        if (!$new_parameter instanceof Parameter) {
            throw new \MWException('Method addParameter() of widget ' . static::GetName() . ' requires an argument of type "Parameter".');
        }

        $new_parameter_name = $new_parameter->getName();
        if (array_key_exists($new_parameter_name, $this->parameters)) {
            throw new \MWException('Cannot add parameter "' . $new_parameter_name . '". Each parameter needs a disctinct name.');
        }

        $this->parameters[$new_parameter_name] = $new_parameter;
    }

    /**
     * Get all parameters.
     * 
     * @return array Array of Parameter objects
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * Tries to set parameters by name from $arguments.
     * 
     * @param array $arguments Array of strings
     * @return array Array of strings: arguments that have not been used.
     * @throws UserError
     */
    protected function trySetParametersByName($arguments) {

        $parameters = $this->getParameters();
        $unused_arguments = array();

        foreach ($arguments as $argument) {

            $set = false;

            foreach ($parameters as $parameter) {
                if ($parameter->trySetByName($argument)) {
                    $set = true;
                    break;
                }
            }

            if (!$set) {
                $unused_arguments[] = $argument;
            }
        }

        return $unused_arguments;
    }

    /**
     * Get the list of parameters that are not set.
     * 
     * @return array Array of Parameters
     */
    protected function getNotSetParameters() {

        $parameters = $this->getParameters();
        $filtered = array();

        foreach ($parameters as $parameter) {
            if (!$parameter->hasBeenSet()) {
                $filtered[] = $parameter;
            }
        }

        return $filtered;
    }

    /**
     * Tries to set parameters by order from $argument.
     * 
     * @param array $arguments Array of string
     * @return array Array of string: arguments that have not been used.
     */
    protected function trySetParametersByOrder($arguments) {

        $parameters = $this->getNotSetParameters();
        $unused_arguments = $arguments; // copy
        // foreach($parameters as $parameter)  +  foreach($arguments as $index,=> $argument)
        reset($parameters);
        reset($arguments);
        while ((list(, $parameter) = each($parameters)) &&
        (list($index, $argument) = each($arguments))) {

            if ($parameter->trySet($argument)) {
                unset($unused_arguments[$index]);
            }
        }

        return $unused_arguments;
    }

    /**
     * Checks parameters requirements (required, min, max,...).
     * 
     * @throws UserError When a parameter fails its validate.
     */
    protected function validate() {
        $parameters = $this->getParameters();
        foreach ($parameters as $parameter) {
            $parameter->validate();
        }
    }

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output.
     * 
     * <ul>
     * <li>When the returned value is a string: this widgets framework makes 
     * sure that the MediaWiki's parser correclty handles it. By default, it
     * will be considered as raw HTML block, but this behaviour can be changed using
     * '''setBlock()''' and '''setHTML()''' methods.</li>
     * 
     * <li>When the returned value is an array: the framework cares no more
     * about it, and give it directly to the MediaWiki's parser. It must have
     * the text in element 0, and a number of flags in the other elements.
     * The names of the flags are specified in the keys.
     * See Parser.php,method braceSubstitution(), starting around line 2930.
     * Valid official flags are:
     * <ul>
     * <li> <i>found</i> => (boolean) "<i>output</i> has been filled, stop
     * processing the template", this is <b>true</b> by default</li>
     * <li> <i>nowiki</i> => (boolean) "wiki markup in <i>output</i> should be
     * escaped", this is <b>false</b> by default</li>
     * <li> <i>noparse</i> => (boolean) indicates to not parse the 
     * <i>output</i>, this is <b>true</b> by default</li>
     * <li> <i>noargs</i> => Don't replace triple-brace arguments in the return 
     * value</li>
     * <li> <i>isHTML</i> => (boolean) "<i>output</i> is HTML, armour it against
     * wikitext transformation", this is <b>false</b> by default (is set to
     * true, a preceding wikitext blank line will be added, which will be
     * transformed to an empty paragraphe in the final page)</li>
     * </ul>
     * </ul>
     * @return string|array The output string, or an array containing output and MediaWiki parser flags.
     */
    abstract protected function getOutput();

    /**
     * Adds a nowiki strip item to the MediaWiki's parser.
     * Makes sure the parser does not play with the output.
     * 
     * Internal use.
     * 
     * @param string $text The original text to strip
     * @return string The key of the "stripped" item 
     */
    protected function insertNoWikiStripItem($text) {
        $rnd = "{$this->parser->mUniqPrefix}-item-{$this->parser->mMarkerIndex}-" . \Parser::MARKER_SUFFIX;
        $this->parser->mMarkerIndex++;
        $this->parser->mStripState->addNoWiki($rnd, $text);
        return $rnd;
    }

    /**
     * Defines the getOutput() returned value as "<i>ready to display HTML</i>"
     * (default) or as <i>wikitext</i>.
     * 
     * @param boolean $is_html <ul>
     * <li><i>true</i> parser should not interpretate or modify the output
     *  <b>(default behavior)</b></li>
     * <li><i>false</i> parser should interprete the output as wiki markup</li>
     * </ul>
     */
    protected function setHTML($is_html = true) {
        $this->is_html = $is_html;
    }

    /**
     * Defines how the parser will integrate the getOutput() returned value in
     * the page.
     * @param boolean $is_block <ul>
     * <li><i>true</i> the output is a block, and will 
     * not put inside a paragraph (default)</li>
     * <li><i>false</i> the output is inline and will be handled regularly by the parser</li>
     * </ul>
     */
    protected function setBlock($is_block = true) {
        $this->is_block = $is_block;
    }

    /**
     * Prepares the output for the parser
     * 
     * @param string $output <i>OPTIONAL</i> If null (default), uses the
     * getOutput() returned value.
     * @return string|array Ready output for parser
     */
    protected function getOutputForParser($output = null) {

        if (is_null($output)) {
            $output = $this->getOutput();
        }

        if (is_null($output)) {
            // avoid mess
            return '';
        }

        if (is_array($output)) {
            // this array contains MediaWiki parser flags
            return $output;
        }

        // else

        if ($this->is_html) {
            // strip (avoid parser)
            $output = $this->insertNoWikiStripItem($output);
        }

        if ($this->is_block) {
            /** 
             *  the hidden div will ensure the parser won't add unwanted paragraph tags
             *  @todo find something else than this ugly hack (either change the parser or use our own stripper)
             */
            $output = '<div class="hidden"></div>' . $output;
        }

        return $output;
    }

    /**
     * Execute the ParserFunction and throws exceptions if needed
     * 
     * @param array $arguments Array of strings
     * @return string output for parser
     * @throws \MWException Internal errors + user errors
     */
    public function tryExecute($arguments) {

        // if the limit has been exceeded, output is an error message
        // an additional warning message is displayed in page edit mode
        if (!$this->parser->incrementExpensiveFunctionCount()) {
            return 'Expensive function count error.';
        }

        // initializes
        $this->declareParameters();

        // tries to set parameters by name
        $arguments_without_name = $this->trySetParametersByName($arguments);

        // tries to set parameters by order
        $this->trySetParametersByOrder($arguments_without_name);

        // check all parameters (required, ...)
        $this->validate();

        // returns the output
        return $this->getOutputForParser();
    }

    /**
     * Executes the ParserFunction and handles user errors.
     * 
     * @param array $arguments Array of strings
     * @return string output for parser
     * @throws \MWException Internal errors
     */
    public function execute($arguments) { 

        try {
            return $this->tryExecute($arguments);
        } catch (UserError $e) {
            $this->setHTML();
            $this->setBlock();
            return $this->getOutputForParser(wfMessage('wfmk-widget-error', static::GetName(), $e->getText())->parse());
        }
    }

    /**
     * Returns either the text result of the function, or an array with the
     * text in element 0, and a number of flags in the other elements.
     * 
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
        if (count($arguments) == 1 && reset($arguments) == '') {
            array_shift($arguments);
        }

        // instanciate
        $child_class = get_called_class();
        $widget = new $child_class($parser, $frame);
        
        // execute
        return $widget->execute($arguments);
    }

    /**
     * Registers the widget as a parser function to MediaWiki's parser.
     * 
     * @param Parser $parser Parser object
     * @return boolean Always true
     */
    public static function Register($parser) {

        $name = static::GetName();
        $method = get_called_class() . '::onParserFunctionHook';
        $flags = static::$FLAGS | SFH_OBJECT_ARGS; // ensure we will receive a PPFrame object and arguments as objects 

        $parser->setFunctionHook($name, $method, $flags);

        return true;
    }

}