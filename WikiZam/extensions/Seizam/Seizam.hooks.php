<?php
/**
 * Hooks for Seizam extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}
class SeizamHooks {
	
	/* Protected Static Members */
	
	protected static $features = array(
		'global' => array(
			'modules' => array( 'ext.seizam.global' ),
		)
	);
	
	/* Static Methods */
	
	/**
	 * BeforePageDisplay hook
	 * 
	 * Adds the modules to the page
	 * 
	 * @param $out OutputPage output page
	 * @param $skin Skin current skin
	 */
	public static function beforePageDisplay( $out, $skin ) {
		if ($skin instanceof SkinSeizam) {
			$out->addModules( 'ext.seizam.global' );
		}
		return true;
	}
        
         
public static function parserClearState($parser) {
    $parser->mShowToc = false;
    return true;
}
	
}
