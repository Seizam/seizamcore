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
        if (isset ($param['height'])) {
            $explosion = explode('px', strtolower($param['height']));
            $c->setPhotoHeight($explosion[0]);
        }

		$lines = StringUtils::explode( "\n", $in );
		foreach ( $lines as $line ) {
            
            $matches = array();
			preg_match( "/^([^|]+)(.*)?$/", $line, $matches );
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
			if ( isset( $matches[2] ) ) {
				$label = $parser->recursiveTagParse(substr(trim($matches[2]), 1));
			}
            
            $tag = '';
            $titleLink = Title::newFromText('Help:Slideshow');

			$c->add($title, $label, $tag, $titleLink);
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
        if (isset ($param['height'])) {
            $explosion = explode('px', strtolower($param['height']));
            $s->setPhotoHeight($explosion[0]);
        }
        
        if (isset ($param['width'])) {
            $explosion = explode('px', strtolower($param['width']));
            $s->setPhotoWidth($explosion[0]);
        }
        
        if (isset ($param['float']))
            $s->setFloat($param['float']);

		$lines = StringUtils::explode( "\n", $in );
		foreach ( $lines as $line ) {
            
            $matches = array();
			preg_match( "/^([^|]+)(.*)?$/", $line, $matches );
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
			if ( isset( $matches[2] ) ) {
				$label = $parser->recursiveTagParse(substr(trim($matches[2]), 1));
			}

			$s->add($title, $label);
		}
		return $s->toHTML();
    }

}
