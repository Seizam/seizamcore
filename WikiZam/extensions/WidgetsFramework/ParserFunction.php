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
     * This method returns the name of the parser function, ie the name of the child class.
     * Although it is static, it uses Late Static Binding to return the real name of the child class.
     * @return string
     */
    public static function GetName() {
        return implode('', array_slice(explode('\\', get_called_class()), -1));
    }

    /**
     * Child class write its parameters instanciation in this method.
     * @return void
     */
    abstract protected function declareParameters();

    /**
     * Add a parameter.
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
     * 
     * @return array Array of Parameters
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * Lopp on each argument then loop on each parameter to call their trySetByName() method.
     * @param array $arguments Array of string
     * @return array Array of strings: arguments not used.
     */
    protected function setParametersByName($arguments) {

        $parameters = $this->getParameters(); // get all parameters
        $unused_arguments = array();

        foreach ($arguments as $argument) {

            $set = false;

            foreach ($parameters as $parameter) {
                if ($parameter->trySetByName($argument)) {
                    $set = true;
                    wfDebugLog('WidgetsFramework', static::GetName() . ' : parameter ' . $parameter->getName() . ' used argument "' . $argument . '" by name');
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
     * Return the list of parameters that can still accept values.
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
     * Loop on each not set parameter to call its trySetByOrder() method.
     * @param array $arguments Array of string
     * @return array Array of string: arguments that have not been used.
     */
    protected function setParametersByOrder($arguments) {

        $parameters = $this->getNotSetParameters(); // only get not set parameters

        $unused_arguments = $arguments; // copy
        reset($parameters);
        reset($arguments);
        // loop in $not_set_parameters and $arguments arrays, while possible        
        while ((list(, $parameter) = each($parameters)) &&
        (list($index, $argument) = each($arguments))) {

            if (!$parameter->trySet($argument)) {

                wfDebugLog('WidgetsFramework', static::GetName() . ' : parameter ' . $parameter->getName() . ' DID NOT USED "' . $argument . '" by order');
            } else {

                unset($unused_arguments[$index]);
                wfDebugLog('WidgetsFramework', static::GetName() . ' : parameter ' . $parameter->getName() . ' used argument "' . $argument . '" by order');
            }
        }

        return $unused_arguments;
    }

    protected function validate() {
        $parameters = $this->getParameters();
        foreach ($parameters as $parameter) {
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

    protected function insertNoWikiStripItem($text) {
        $rnd = "{$this->parser->mUniqPrefix}-item-{$this->parser->mMarkerIndex}-" . \Parser::MARKER_SUFFIX;
        $this->parser->mMarkerIndex++;
        $this->parser->mStripState->addNoWiki($rnd, $text);
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
     * @param string $output <i>OPTIONAL</i> By default, use the getOutput() method returned value.
     * If this parameter is specified, its value will be used instead.
     * @return string|array Prepared string $output for parser, or unchanged array $output.
     */
    protected function getOutputForParser( $output = null ) {

        if ( is_null($output) ) {
            $output = $this->getOutput();
        }

        if ( is_null($output) ) {
            // avoid mess
            return '';
        }

        if ( is_array($output) ) {
            // this array contains MediaWiki parser flags
            return $output;
        }

        // else

        if ( $this->is_html ) {
            // do nowiki strip
            $output = $this->insertNoWikiStripItem($output);
        }

        if ( $this->is_block ) {
            $output = '<div class="wfmkwidget">' . $output . "</div>";
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

        wfDebugLog('WidgetsFramework', static::GetName() . '->execute(' . count($arguments) . ' argument(s))');

        // initialize
        $this->declareParameters();

        // first pass : identify by name
        $arguments_without_name = $this->setParametersByName($arguments);

        // second pass : try to set the value by matching the order parameters are declared in widget class and typed in wikitext
        $unused_arguments = $this->setParametersByOrder($arguments_without_name);
        wfDebugLog('WidgetsFramework', static::GetName() . '->execute() : ' . count($unused_arguments) . ' argument(s) left unused');

        // check all parameters (required, ...)
        $this->validate();

        // the last step, format the output
        $output = $this->getOutputForParser();

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

            wfDebugLog('WidgetsFramework', static::GetName() . '->tryExecute() UserError exception catched : "' . $e->getMessage() . '"');

            $this->setHTML();
            $this->setBlock();
            return $this->getOutputForParser(wfMessage('wfmk-error-in-widget', static::GetName(), $e->getText())->parse());
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
        if (count($arguments) == 1 && reset($arguments) == '') {
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

        wfDebugLog('WidgetsFramework', $name . '::Register() : parser function registered with flags=' . $flags);

        return true;
    }

}