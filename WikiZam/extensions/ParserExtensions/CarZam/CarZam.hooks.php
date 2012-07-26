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
            
            $matches = array();
			preg_match( "/^([^|]+)(\\|(.*))?$/", $line, $matches );
			# Skip empty lines
			if ( count( $matches ) == 0 ) {
				continue;
			}

			if ( strpos( $matches[0], '%' ) !== false ) {
				$matches[1] = rawurldecode( $matches[1] );
			}
            
			$title = Title::newFromText( $matches[1], NS_FILE );
			if ( is_null( $title ) ) {
				# Bogus title. Ignore these so we don't bomb out later.
				continue;
			}

			$label = '';
			if ( isset( $matches[3] ) ) {

				$label = $parser->recursiveTagParse( trim( $matches[3] ) );
			}

			$c->add( $title, $label);
		}
		return $c->toHTML();
    }

}
