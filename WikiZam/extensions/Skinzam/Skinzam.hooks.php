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
     * @param OutputPage $out output page
     * @param Skin $skin current skin
     */
    public static function beforePageDisplay($out, $skin) {
        if ($skin instanceof SkinSkinzam) {
            $out->addModules(array('jquery.scrollto','jquery.backstretch','ext.skinzam.global'));
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
     * @param SkinSkinzam $skin
     * @param SkinzamTemplate $tpl
     */
    public static function skinTemplateOutputPageBeforeExec(&$skin, &$tpl) {
        // The links on the absolute footer
        
        $AbsoluteFooterUrls = array();
        
        if ($skin->loggedin) {
            $AbsoluteFooterUrls['allpages'] = array(
                'text' => wfMessage('sz-browse'),
                'href' => Skin::makeSpecialUrl('AllPages'),
                'active' => ( $skin->thispage == 'Special:AllPages' )
            );

            $AbsoluteFooterUrls['myseizam'] = array(
                'text' => wfMessage('sz-myseizam'),
                'href' => Skin::makeSpecialUrl('MySeizam'),
                'class' => false,
                'active' => ( $skin->thispage == 'Special:MySeizam' )
            );
            $szPrettyUserName = $skin->username;
            $tpl->set('sz_pretty_username', $szPrettyUserName);
        } else {
            $AbsoluteFooterUrls['myseizam'] = $tpl->data['personal_urls']['login'];
        }

        $tpl->set('absolute_footer_urls', $AbsoluteFooterUrls);
        
        
        // The links on the content footer
        
        $tpl->set( 'footerlinks', array(
			'info' => array(
				'lastmod',
				'viewcount',
				'numberofwatchingusers',
				'credits',
				'copyright',
			)
		) );
        
        
        return true;
    }

    
    /**
     * Hook to cook the personal menu Seizam style.
     * 
     * @param Array $personal_urls
     * @param Title $title
     * @return Boolean True (=continue hook)
     * 
     */
    public static function PersonalUrls(&$personal_urls, &$title) {
        // Remove Preferences
        /*if (isset($personal_urls['preferences']))
            unset ($personal_urls['preferences']);*/
        // Remove Contributions
        if (isset($personal_urls['mycontris']))
            unset ($personal_urls['mycontris']);
        
        $beginning = array();
        if (isset ($personal_urls['userpage'])) {
            $beginning['userpage'] = $personal_urls['userpage'];
            unset ($personal_urls['userpage']);
        }
        if (isset ($personal_urls['mytalk'])) {
            $beginning['mytalk'] = $personal_urls['mytalk'];
            unset ($personal_urls['mytalk']);
        }
        
        if (isset($personal_urls['logout'])) {
            // Add MySeizam
            $href = Title::makeTitle(NS_SPECIAL, 'MySeizam')->getCanonicalURL();
            $middle['myseizam'] = array(
                    'text' => wfMsg( 'sz-myseizam' ),
                    'href' => $href,
                    'active' => ( $title->getText() == 'MySeizam' )
                );
            // Add MyWikiPlaces
            $href = Title::makeTitle(NS_SPECIAL, 'Wikiplaces')->getCanonicalURL();
            $middle['wikiplaces'] = array(
                    'text' => wfMsg( 'wikiplaces' ),
                    'href' => $href,
                    'active' => ( $title->getText() == 'Wikiplaces' )
                );
            $personal_urls = array_merge($middle, $personal_urls);
        }
        
        $personal_urls = array_merge($beginning, $personal_urls);
        
        return true;
    }
    
    
    /**
     * Hook to cook the toolbox menu Seizam style.
     * 
     * @param BaseTemplate $BaseTemplate
     * @param Array $toolbox
     * @return Boolean True (=continue hook)
     * 
     */
    public static function BaseTemplateToolbox( &$BaseTemplate, &$toolbox) {
        // Remove upload
        if (isset($toolbox['upload']))
            unset ($toolbox['upload']);
        // Remove specialpages
        if (isset($toolbox['specialpages']))
            unset ($toolbox['specialpages']);
        
        $href = Title::makeTitle(NS_SPECIAL, 'Random')->getCanonicalURL();
        $toolbox['random'] = array(
            'text' => wfMsg( 'randompage' ),
            'href' => $href
        );
        
        return true;
    }
    
}
