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

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Skinzam',
	'author' => array( 'Clément Dietschy', 'Seizam Sàrl.'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'sz-skinzam-desc',
);

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['HTMLFormS'] = $dir . 'HTMLFormS.php';
$wgAutoloadClasses['SkinzamHooks'] = $dir . 'Skinzam.hooks.php';
$wgExtensionMessagesFiles['Skinzam'] = $dir . 'Skinzam.i18n.php';
$wgExtensionAliasesFiles['Skinzam'] = $dir . 'Skinzam.alias.php';

// Load JS Resources
$wgHooks['BeforePageDisplay'][] = 'SkinzamHooks::beforePageDisplay';
// Remove TOC
$wgHooks['ParserClearState'][] = 'SkinzamHooks::parserClearState';


// JS Resources Declaration
$skinzamResourceTemplate = array(
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'Skinzam/modules',
	'group' => 'ext.skinzam',
);
$wgResourceModules += array(
	'ext.skinzam.global' => $skinzamResourceTemplate + array(
		'scripts' => 'ext.skinzam.global.js')
);

# Special Skinzam (UI test class)
$wgAutoloadClasses['SpecialSkinzam'] = $dir . 'Skinzam_body.php';
$wgSpecialPages['Skinzam'] = 'SpecialSkinzam';

$wgSpecialPageGroups['Skinzam'] = 'other';


