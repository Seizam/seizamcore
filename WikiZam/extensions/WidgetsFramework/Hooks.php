<?php

/**
 * Hooks for WidgetsFramework extension
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

if (!defined('MEDIAWIKI')) {
    die(-1);
}

class Hooks {

    /**
     * BeforePageDisplay hook
     * 
     * Adds the modules to the page
     * 
     * @param OutputPage $out output page
     * @param Skin $skin current skin
     * @return Boolean always true (unless we are very self-centered and want the resource loader to work just for us, what a waste it would be)
     */
    public static function beforePageDisplay($out, $skin) {
        $out->addModules('ext.widgetsFramework.css');
        return true;
    }

}
