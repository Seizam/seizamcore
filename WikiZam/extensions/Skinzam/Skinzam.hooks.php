<?php

/**
 * Hooks for Skinzam extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

class SkinzamHooks {
    
    /* Protected Static Members */
	
	protected static $features = array(
		'editwarning' => array(
			'preferences' => array(
				// Ideally this would be 'vector-editwarning'
				'useeditwarning' => array(
					'type' => 'toggle',
					'label-message' => 'vector-editwarning-preference',
					'section' => 'editing/advancedediting',
				),
			),
			'requirements' => array(
				'useeditwarning' => true,
			),
			'modules' => array( 'ext.vector.editWarning' ),
		),
		'simplesearch' => array(
			'requirements' => array( 'vector-simplesearch' => true, 'disablesuggest' => false ),
			'modules' => array( 'ext.skinzam.simpleSearch' ),
		)
	);
    
    
    
    protected static function isEnabled( $name ) {
		global $wgSkinzamFeatures, $wgUser;
		
		// Features with global set to true are always enabled
		if ( !isset( $wgSkinzamFeatures[$name] ) || $wgSkinzamFeatures[$name]['global'] ) {
			return true;
		}
		// Features with user preference control can have any number of preferences to be specific values to be enabled
		if ( $wgSkinzamFeatures[$name]['user'] ) {
			if ( isset( self::$features[$name]['requirements'] ) ) {
				foreach ( self::$features[$name]['requirements'] as $requirement => $value ) {
					// Important! We really do want fuzzy evaluation here
					if ( $wgUser->getOption( $requirement ) != $value ) {
						return false;
					}
				}
			}
			return true;
		}
		// Features controlled by $wgSkinzamFeatures with both global and user set to false are awlways disabled 
		return false;
	}

    /**
     * BeforePageDisplay hook
     * 
     * Adds the modules to the page
     * 
     * @param $out OutputPage output page
     * @param $skin Skin current skin
     */
    public static function beforePageDisplay($out, $skin) {
        if ($skin instanceof SkinSkinzam) {
            $out->addModules('ext.skinzam.global');
            $out->addModules('ext.skinzam.jquery.scrollto-min');
			// Add modules for enabled features
			foreach ( self::$features as $name => $feature ) {
				if ( isset( $feature['modules'] ) && self::isEnabled( $name ) ) {
					$out->addModules( $feature['modules'] );
				}
			}
        }
        return true;
    }

    /**
     * parserClearState hook
     * 
     * Removes Table of Content from every pages
     * 
     * @param $parser Parser Object
     */
    public static function parserClearState($parser) {
        $parser->mShowToc = false;
        return true;
    }

    /**
     * skinTemplateOutputPageBeforeExec hook
     * 
     * Cooks the skin template Seizam-Style!
     * 
     * @param $parser Parser Object
     */
    public static function skinTemplateOutputPageBeforeExec(&$skin, &$tpl) {
        $szFooterUrls = array();
        
        if ($skin->loggedin) {
            $szFooterUrls['allpages'] = array(
                'text' => wfMessage('sz-browse'),
                'href' => Skin::makeSpecialUrl('AllPages'),
                'active' => ( $skin->thispage == 'Special:AllPages' )
            );

            $szFooterUrls['myseizam'] = array(
                'text' => wfMessage('sz-myseizam'),
                'href' => Skin::makeSpecialUrl('MySeizam'),
                'class' => false,
                'active' => ( $skin->thispage == 'Special:MySeizam' )
            );
            $szPrettyUserName = $skin->username;
            $tpl->set('sz_pretty_username', $szPrettyUserName);
        } else {
            $szFooterUrls['myseizam'] = $tpl->data['personal_urls']['login'];
        }
        
        $tpl->set( 'footerlinks', array(
			'info' => array(
				'lastmod',
				'viewcount',
				'numberofwatchingusers',
				'credits',
				'copyright',
			)
		) );

        $tpl->set('sz_footer_urls', $szFooterUrls);
        return true;
    }

}
