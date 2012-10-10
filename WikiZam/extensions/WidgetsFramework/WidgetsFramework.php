<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file
//require_once( "$IP/includes/GlobalFunctions.php" );
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

if ( !defined( 'MEDIAWIKI' ) ) {
    exit( 1 );
}

# Credits

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'WidgetsFramework',
    'author' => array('[http://www.seizam.com/User:Yannouk Yann Missler] & [http://www.seizam.com/User:Bedhed Clément Dietschy]'),
    'url' => 'http://www.seizam.com',
    'descriptionmsg' => 'widgetsframework-desc',
    'version'  => '0.1 alpha',
);


# Load files

$_dir = dirname( __FILE__ );

$wgExtensionMessagesFiles['WidgetsFramework'] =  $_dir . '/WidgetsFramework.i18n.php';

$wgAutoloadClasses['WidgetsFramework\\Boolean'] = $_dir . '/Parameters/Boolean.php';
$wgAutoloadClasses['WidgetsFramework\\Integer'] = $_dir . '/Parameters/Integer.php';
$wgAutoloadClasses['WidgetsFramework\\Option'] = $_dir . '/Parameters/Option.php';
$wgAutoloadClasses['WidgetsFramework\\Parameter'] = $_dir . '/Parameters/Parameter.php';
$wgAutoloadClasses['WidgetsFramework\\PixelSize'] = $_dir . '/Parameters/PixelSize.php';
$wgAutoloadClasses['WidgetsFramework\\String'] = $_dir . '/Parameters/String.php';
$wgAutoloadClasses['WidgetsFramework\\XorParameter'] = $_dir . '/Parameters/XorParameter.php';

$wgAutoloadClasses['WidgetsFramework\\DeveloperError'] = $_dir . '/DeveloperError.php';
$wgAutoloadClasses['WidgetsFramework\\ParserFunction'] = $_dir . '/ParserFunction.php';
$wgAutoloadClasses['WidgetsFramework\\WidgetStripper'] = $_dir . '/WidgetStripper.php';
$wgAutoloadClasses['WidgetsFramework\\Tools'] = $_dir . '/Tools.php';
$wgAutoloadClasses['WidgetsFramework\\UserError'] = $_dir . '/UserError.php';
$wgAutoloadClasses['WidgetsFramework\\Widget'] = $_dir . '/Widget.php';

$wgWFmkEnabledWidgets = array();

foreach ( glob( $_dir . '/Widgets/*', GLOB_ONLYDIR ) as $widget_dir ) {
    
    $infos = pathinfo($widget_dir);
    $widget_name = $infos['filename'];
    
    foreach ( glob( $widget_dir . '/*.php' ) as $widget_php_file ) {

        // cannot use autoload, because each widget have to modify $wgWFEnabledWidgets
        // now to install itself
        // ( starting PHP 5.3.7, is_subclass_of supports for class_name to work with intrefaces )
        //require( $file );
        //$wgAutoloadClasses['WidgetsFramework\\'.$name] =  $file;

        $infos = pathinfo($widget_php_file);
        $meta_start = strrpos($infos['filename'], '.');

        if ($meta_start === false) {
            // this is a PHP class
            
            $wgAutoloadClasses['WidgetsFramework\\'.$infos['filename']] = $widget_php_file;
            //wfDebugLog('WidgetsFramework', 'initilization: added autoload '.$widget_name.'/'.$infos['basename']);
            
            if ( $widget_name == $infos['filename']) {
                // this is the main widget class
                $wgWFmkEnabledWidgets[] = $infos['filename'];
            }
            
        } else {

            switch ( substr($infos['filename'], $meta_start + 1 ) ) {
                case 'magic': // MyWidget.magic.php or MyWidget.i18n.magic.php
                    $wgExtensionMessagesFiles['WidgetsFramework/'.$widget_name.'Magic'] = $widget_php_file;
                    //wfDebugLog('WidgetsFramework', 'initilization: added i18n magic '.$infos['basename']);
                    break;
                case 'i18n':
                    /** @todo */
                    break;
            }

        }    
    }
}
   


# Register widgets

$wgHooks['ParserFirstCallInit'][] = 'WidgetsFramework\\efWidgetsFrameworkRegisterWidgets';

/**
 * @param $parser Parser
 * @return bool
 */

function efWidgetsFrameworkRegisterWidgets( $parser ) {
    
    global $wgWFmkEnabledWidgets;
    if ( ! is_array($wgWFmkEnabledWidgets) ) {
        return true;
    }
    
    foreach ( $wgWFmkEnabledWidgets as $name ) {
        call_user_func_array('WidgetsFramework\\'.$name.'::Setup', array ($parser) );
        //wfDebugLog('WidgetsFramework', 'initilization: registered '.$name.' to parser');
    }

    return true; // true = do not break other extensions using this hook
}
 
# Register ParserStripper for unstripping
$wgHooks['ParserAfterTidy'][] = 'WidgetsFramework\\WidgetStripper::UnstripItems';
