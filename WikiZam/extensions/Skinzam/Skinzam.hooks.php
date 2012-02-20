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
        'global' => array(
            'modules' => array('ext.seizam.global'),
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
    public static function beforePageDisplay($out, $skin) {
        if ($skin instanceof SkinSkinzam) {
            $out->addModules('ext.skinzam.global');
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
        // Second Link "My Seizam" (logged in) or "Sign in/Login" (not logged in)
        
        if ($skin->loggedin) {
        // First Link "Browse Seizam"
        $szFooterUrls['allpages'] = array(
				'text' => wfMessage('sz-browse'),
				'href' => Skin::makeSpecialUrl( 'AllPages' ),
				'active' => ( $skin->thispage == 'Special:AllPages' )
			);
            $szFooterUrls['myseizam'] = array(
				'text' => wfMessage('sz-myseizam'),
				'href' => Skin::makeSpecialUrl( 'Preferences' ),
				'class' => false,
				'active' => ( $skin->thispage == 'Special:Preferences' )
			);
         $szPrettyUserName = $skin->username;
        $tpl->set('sz_pretty_username', $szPrettyUserName);
        } else {
            $szFooterUrls['myseizam'] = $tpl->data['personal_urls']['login'];
        }
        
        $tpl->set('sz_footer_urls', $szFooterUrls);
        return true;
    }
    
    

}
