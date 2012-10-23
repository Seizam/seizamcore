<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

/**
 * This extension is a simple framework for Widgets.
 * 
 * To install:
 * 
 * # copy the Widgets you want into the "Widgets" directory.
 * 
 * # then put this in LocalSettings.php:
 * 
 *     require_once( "$IP/extensions/WidgetsFramework/WidgetsFramework.php" );
 *
 * # That's it :)
 * 
 */
if (!defined('MEDIAWIKI')) {
    exit(1);
}

// Credits

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'WidgetsFramework',
    'author' => array('[http://www.seizam.com/User:Yannouk Yann Missler] & [http://www.seizam.com/User:Bedhed Clément Dietschy]'),
    'url' => 'http://www.seizam.com',
    'descriptionmsg' => 'widgetsframework-desc',
    'version' => '0.2',
);



// Load framework core files

$_dir = dirname(__FILE__);

$wgExtensionMessagesFiles['WidgetsFramework'] = $_dir . '/WidgetsFramework.i18n.php';

$wgAutoloadClasses['WidgetsFramework\\ParserFunction'] = $_dir . '/ParserFunction.php';
$wgAutoloadClasses['WidgetsFramework\\Tools'] = $_dir . '/Tools.php';
$wgAutoloadClasses['WidgetsFramework\\UserError'] = $_dir . '/UserError.php';
$wgAutoloadClasses['WidgetsFramework\\Widget'] = $_dir . '/Widget.php';



// Automatic load of all parameters classes

foreach (glob($_dir . '/Parameters/*') as $parameter_file) {

    $infos = pathinfo($parameter_file);
    $wgAutoloadClasses['WidgetsFramework\\' . $infos['filename']] = $parameter_file;
}



// Automatic load of all widgets

// This var is an array, telling wich widgets need automatic magic words.
$wgWidgetsFrameworkNeedMagicWords = array();

foreach (glob($_dir . '/Widgets/*', GLOB_ONLYDIR) as $widget_dir) {

    $infos = pathinfo($widget_dir);
    $widget_name = $infos['filename'];

    $registered = false;
    $has_magic_file = false;

    foreach (glob($widget_dir . '/*.php') as $widget_php_file) {

        $infos = pathinfo($widget_php_file);
        $meta_start_pos = strrpos($infos['filename'], '.');

        if ($meta_start_pos === false) { // this is a PHP class
            
            $wgAutoloadClasses['WidgetsFramework\\' . $infos['filename']] = $widget_php_file;

            if ($widget_name == $infos['filename']) {
                // this is the main widget class, register its Register() method to ParserFirstCallInit hook
                $wgHooks['ParserFirstCallInit'][] = 'WidgetsFramework\\' . $widget_name . '::Register';
                $registered = true;
            }
        } else {

            switch (substr($infos['filename'], $meta_start_pos + 1)) {
                case 'magic': // MyWidget.magic.php or MyWidget.i18n.magic.php
                    $wgExtensionMessagesFiles['WidgetsFramework/' . $widget_name . 'Magic'] = $widget_php_file;
                    $has_magic_file = true;
                    break;
                case 'i18n':
                    $wgExtensionMessagesFiles['WidgetsFramework/' . $widget_name] = $widget_php_file;
                    break;
            }
        }
    }

    if ($registered && !$has_magic_file) {
        $wgWidgetsFrameworkNeedMagicWords[] = $widget_name;
    }
}



// Magic words can be generated automatically
//  /!\ THIS FUNCTIONALITY IS EXPERIMENTAL 
// You may need to use   maintenance/rebuildLocalisationCache.php --force   just after installing a widgets
// that doesn't have its own i18n.magic.php file (cache doesn't refresh when adding automatic magic words) 
$wgExtensionMessagesFiles['WidgetsFrameworkMagic'] = $_dir . '/WidgetsFramework.i18n.magic.php';
