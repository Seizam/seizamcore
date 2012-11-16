<?php

/**
 * WidgetsFramework extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Clément Dietschy <clement@seizam.com>
 * @author Yann Missler <yann@seizam.com>
 * @license GPL v3 or later
 * @version 0.3
 */

namespace WidgetsFramework;

if (!defined('MEDIAWIKI')) {
    exit(1);
}

/* Configuration */

// The max width of widgets.
$wgWFMKMaxWidth = 800;

/* Setup */

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'WidgetsFramework',
    'author' => array('[http://www.seizam.com/User:Yannouk Yann Missler] & [http://www.seizam.com/User:Bedhed Clément Dietschy]'),
    'url' => 'http://www.seizam.com',
    'descriptionmsg' => 'widgetsframework-desc',
    'version' => '0.3',
);

$_dir = dirname(__FILE__);

$wgExtensionMessagesFiles['WidgetsFramework'] = $_dir . '/WidgetsFramework.i18n.php';

$wgAutoloadClasses['WidgetsFramework\\ParserFunction'] = $_dir . '/ParserFunction.php';
$wgAutoloadClasses['WidgetsFramework\\Tools'] = $_dir . '/Tools.php';
$wgAutoloadClasses['WidgetsFramework\\UserError'] = $_dir . '/UserError.php';
$wgAutoloadClasses['WidgetsFramework\\Widget'] = $_dir . '/Widget.php';
$wgAutoloadClasses['WidgetsFramework\\Hooks'] = $_dir . '/Hooks.php';

// Declare resources
$widgetsFrameworkTpl = array(
    'localBasePath' => $_dir . '/Modules',
    'remoteExtPath' => 'WidgetsFramework/Modules',
    'group' => 'ext.widgetsFramework',
);
$wgResourceModules += array(
    'ext.widgetsFramework.css' => $widgetsFrameworkTpl + array(
        'styles' => 'WidgetsFramework.css',
        'position' => 'top'
     )
);

// Load Resources
$wgHooks['BeforePageDisplay'][] = 'WidgetsFramework\\Hooks::beforePageDisplay';

// Automatic load of all parameters classes
foreach (glob($_dir . '/Parameters/*') as $parameter_file) {
    $infos = pathinfo($parameter_file);
    $wgAutoloadClasses['WidgetsFramework\\' . $infos['filename']] = $parameter_file;
}

// Automatic load of all widgets
foreach (glob($_dir . '/Widgets/*', GLOB_ONLYDIR) as $widget_dir) {

    $dir_infos = pathinfo($widget_dir);
    $widget_name = $dir_infos['filename'];

    foreach (glob($widget_dir . '/*.php') as $widget_php_file) {

        $file_infos = pathinfo($widget_php_file);
        $meta_start_pos = strrpos($file_infos['filename'], '.');

        if ($meta_start_pos === false) {
            // the file is a PHP class
            $wgAutoloadClasses['WidgetsFramework\\' . $file_infos['filename']] = $widget_php_file;
            if ($widget_name == $file_infos['filename']) {
                // this is the main widget class, linking to MediaWiki parser
                $wgHooks['ParserFirstCallInit'][] = 'WidgetsFramework\\' . $widget_name . '::Register';
            }
        } else {
            switch (substr($file_infos['filename'], $meta_start_pos + 1)) {
                case 'magic':
                    // *.magic.php 
                    $wgExtensionMessagesFiles['WidgetsFramework/' . $widget_name . 'Magic'] = $widget_php_file;
                    break;
                case 'i18n':
                    // *.i18n.php
                    $wgExtensionMessagesFiles['WidgetsFramework/' . $widget_name] = $widget_php_file;
                    break;
            }
        }
    }
}
