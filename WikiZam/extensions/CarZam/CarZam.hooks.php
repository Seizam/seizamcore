<?php

/**
 * Hooks for CarZam extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

class CarZamHooks {

    /**
     * BeforePageDisplay hook
     * 
     * Adds the modules to the page
     * 
     * @param OutputPage $out output page
     * @param Skin $skin current skin
     */
    public static function beforePageDisplay($out, $skin) {
        $out->addModules(array('ext.carzam.carrousel'));
        return true;
    }

    /**
     *
     * @param Parser $parser
     * @return boolean 
     */
    public static function onParserFirstCallInit(&$parser) {
        $parser->setHook('carrousel', array('CarZamHooks', 'renderCarrouselTag'));
        $parser->setHook('slideshow', array('CarZamHooks', 'renderSlideshowTag'));
        return true;
    }

    /**
     * Takes a line and parse it into title, titleLink, alt & caption parameters
     * 
     * @param string $line
     * @param Parser $parser
     * @return array title, titleLink, alt, caption 
     */
    private static function parseLine($line, $parser, $frame) {
        $explosion = explode('|', $line);
        
        $parameters = array();

        $parameters['title'] = null;
        $parameters['titleLink'] = null; #Title to link to
        $parameters['alt'] = ''; #html alternative text
        $parameters['caption'] = ''; #html caption

        #first arg is file title
        $titleText = array_shift($explosion);

        if (is_null($titleText)) {
            # Empty line or something went wrong
            return false;
        }

        if (strpos($titleText, '%') !== false) {
            #fix possible url encoding of title
            $titleText = rawurldecode($titleText);
        }

        $title = Title::newFromText($titleText, NS_FILE);
        if (is_null($title)) {
            # Bogus title. Ignore these so we don't bomb out later.
            return false;
        } else {
           $parameters['title'] = $title; 
        }

        #second arg could be link
        $titleLinkText = count($explosion) > 0 ? self::identifyByName('link', $explosion[0]) : false;

        if ($titleLinkText === false) {
            #title link is not set
        } else {
            if ($titleLinkText === true || $titleLinkText == '') {
                #title link is set to empty (no linking)
            } else {
                #title link is set
                $titleLink = Title::newFromText($titleLinkText);
                if (!is_null($titleLink) && $titleLink->isKnown()) {
                    #isKnown, register to the output for the link table
                    $parser->getOutput()->addLink($titleLink);
                    $parameters['titleLink'] = $titleLink;
                }
            }
            array_shift($explosion);
        }

        #third arg could be alt
        $altText = count($explosion) > 0 ? self::identifyByname('alt', $explosion[0]) : false;

        if ($altText === false) {
            #alt is not set
        } else {
            $parameters['alt'] = htmlspecialchars($altText);
            array_shift($explosion);
        }

        #the rest is the caption
        $captionText = implode('|', $explosion);

        $parameters['caption'] = $parser->recursiveTagParse($captionText, $frame);
        
        return $parameters;
    }

    /**
     * Analyses the argument, and look for this parameter name, case
     * insensitive.
     * 
     * @todo copied from WidgetsFramework\Parameter, move all this logic to Wfmk
     * 
     * @param string $name The parameter name.
     * @param string $argument The raw argument.
     * @return string|boolean <ul>
     * <li>If its name is found followed by equal sign, returns the string 
     * that follow the equal sign (the value).</li>
     * <li>If the name is found, without anything else, returns boolean
     * <i>true</i>.</li>
     * <li>Else, returns boolean <i>false</i>.</li>
     * </ul>
     */
    private static function identifyByName($name, $argument) {

        $name_length = strlen($name);

        if (strlen($argument) < $name_length) {
            return false; // too short, name cannot be found
        }

        // the comparison is case insensitive
        if (0 != substr_compare(
                        $argument, $name, 0, $name_length, true)) {
            return false; // name not found
        }

        // else: name has been found
        // remove the name, and any space just after
        $argument_without_name = ltrim(substr($argument, $name_length));
        if (strlen($argument_without_name) == 0) {
            return true; // no value, only the name
        }

        // the next char must be '='
        if ($argument_without_name[0] != '=') {
            // this is not the name of this parameter
            return false;
        }

        // get the value by removing '=' and any spaces just after
        $value = ltrim(substr($argument_without_name, 1));
        return $value;
    }

    /**
     * @todo better argument parsing within the tag.
     * 
     * @param  $in
     * @param array $param
     * @param Parser $parser
     * @param bool $frame
     * @return string
     */
    public static function renderCarrouselTag($in, $param=array(), $parser=null, $frame=false) {

        $c = new CarZamCarrousel();
        $c->setParser($parser);
        $c->setContextTitle($parser->getTitle());
        $c->setHideBadImages();
        if (isset($param['height'])) {
            $explosion = explode('px', strtolower($param['height']));
            $c->setPhotoHeight($explosion[0]);
        }

        # Reading inside the tag, right now takes arguments by order
        /** @todo make less ugly */
        $lines = StringUtils::explode("\n", $in);
        foreach ($lines as $line) {
            
            $parameters = self::parseLine($line, $parser, $frame);
            
            if ($parameters === false)
                continue;
            else {
                $c->add($parameters['title'], $parameters['caption'], $parameters['alt'], $parameters['titleLink']);
            }
        }

        return $c->toHTML();
    }

    /**
     * @param  $in
     * @param array $param
     * @param Parser $parser
     * @param bool $frame
     * @return string
     */
    public static function renderSlideshowTag($in, $param=array(), $parser=null, $frame=false) {

        $s = new CarZamSlideshow();
        $s->setParser($parser);
        $s->setContextTitle($parser->getTitle());
        $s->setHideBadImages();
        if (isset($param['height'])) {
            $explosion = explode('px', strtolower($param['height']));
            $s->setPhotoHeight($explosion[0]);
        }

        if (isset($param['width'])) {
            $explosion = explode('px', strtolower($param['width']));
            $s->setPhotoWidth($explosion[0]);
        }

        if (isset($param['float']))
            $s->setFloat($param['float']);
        
        # Reading inside the tag, right now takes arguments by order
        /** @todo make less ugly */
        $lines = StringUtils::explode("\n", $in);
        foreach ($lines as $line) {
            
            $parameters = self::parseLine($line, $parser, $frame);
            
            if ($parameters === false)
                continue;
            else {
                $s->add($parameters['title'], $parameters['caption'], $parameters['alt'], $parameters['titleLink']);
            }
        }

        return $s->toHTML();
    }

}
