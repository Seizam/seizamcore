<?php
/**
 * Skinzam extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Clément Dietschy <clement@seizam.com>
 * 
 * Based on the original work from:
 * 
 * @author Trevor Parscal <trevor@wikimedia.org>
 * @author Roan Kattouw <roan.kattouw@gmail.com>
 * @author Nimish Gautam <nimish@wikimedia.org>
 * @author Adam Miller <amiller@wikimedia.org>
 * 
 * @license GPL v2 or later
 * @version 0.1.0
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}
/* Configuration */

// Each module may be configured individually to be globally on/off or user preference based
$wgSkinzamFeatures = array(
);

/* Setup */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Skinzam',
	'author' => array( 'Clément Dietschy', 'Seizam Sàrl.'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'sz-skinzam-desc',
);

$wgAutoloadClasses['SkinzamHooks'] = dirname( __FILE__ ) . '/Skinzam.hooks.php';
$wgExtensionMessagesFiles['Skinzam'] = dirname( __FILE__ ) . '/Skinzam.i18n.php';

// Load JS Resources
$wgHooks['BeforePageDisplay'][] = 'SkinzamHooks::beforePageDisplay';
// Remove TOC
$wgHooks['ParserClearState'][] = 'SkinzamHooks::parserClearState';


// JS Resources Declaration
$skinzamResourceTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'Skinzam/modules',
	'group' => 'ext.skinzam',
);
$wgResourceModules += array(
	'ext.skinzam.global' => $skinzamResourceTemplate + array(
		'scripts' => 'ext.skinzam.global.js')
);


