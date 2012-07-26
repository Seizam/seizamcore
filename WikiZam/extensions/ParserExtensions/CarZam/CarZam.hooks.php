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
     *
     * @param Parser $parser
     * @return boolean 
     */
    public static function onParserFirstCallInit(&$parser) {
        $parser->setHook('carrousel', array('CarZamHooks', 'renderCarrouselTag'));
        return true;
    }

    /**
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

		$lines = StringUtils::explode( "\n", $in );
		foreach ( $lines as $line ) {
            $elements = StringUtils::explode("|", $line);
            $elementsCount = count($elements);
            $title = Title::newFromText( $elements[0], NS_FILE );
            if (!$title instanceof Title)
                continue;
            $html = $elementsCount > 1 ? $parser->recursiveTagParse($elements[1], $frame) : '';
            $alt = $elementsCount > 2 ? htmlspecialchars($elements[2]) : '';
            $c->add($title, $html, $alt);
		}
		return $c->toHTML();
    }

}
