<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

/**
 * Hooks for WidgetsFramework extension
 * 
 * @file
 * @ingroup Extensions
 */
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
     */
    public static function beforePageDisplay($out, $skin) {
        $out->addModules('ext.widgetsFramework.css');
        return true;
    }

}
