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
    'editwarning' => array('global' => true, 'user' => true),
    'simplesearch' => array('global' => false, 'user' => false)
);

/* Setup */

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'Skinzam',
    'author' => array('Clément Dietschy', 'Seizam'),
    'version' => '0.1.0',
    'url' => 'http://www.seizam.com/',
    'descriptionmsg' => 'sz-skinzam-desc',
);

$dir = dirname(__FILE__) . '/';

require_once($dir . 'Skinzam.GlobalFunctions.php');

$wgAutoloadClasses['HTMLFormS'] = $dir . 'model/HTMLFormS.php';
$wgAutoloadClasses['SkinzamTablePager'] = $dir . 'model/SkinzamTablePager.php';
$wgAutoloadClasses['SkinzamHooks'] = $dir . 'Skinzam.hooks.php';
$wgExtensionMessagesFiles['Skinzam'] = $dir . 'Skinzam.i18n.php';
$wgExtensionAliasesFiles['Skinzam'] = $dir . 'Skinzam.alias.php';

// Load JS Resources
$wgHooks['BeforePageDisplay'][] = 'SkinzamHooks::beforePageDisplay';
// Remove TOC
$wgHooks['ParserClearState'][] = 'SkinzamHooks::parserClearState';

$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'SkinzamHooks::skinTemplateOutputPageBeforeExec';


// JS Resources Declaration
$skinzamResourceTemplate = array(
    'localBasePath' => $dir . 'modules',
    'remoteExtPath' => 'Skinzam/modules',
    'group' => 'ext.skinzam',
);

$wgResourceModules += array(
    'ext.skinzam.global' => $skinzamResourceTemplate + array(
        'scripts' => 'ext.skinzam.global.js',
        ),
    'jquery.scrollto' => array(
         'scripts' => 'extensions/Skinzam/modules/jquery/jquery.scrollto.js'
        ),
    'jquery.backstretch' => array(
         'scripts' => 'extensions/Skinzam/modules/jquery/jquery.backstretch.js',
         'position' => 'top',
        ),
    'ext.skinzam.simpleSearch' => $skinzamResourceTemplate + array(
		'scripts' => 'ext.skinzam.simpleSearch.js',
		'messages' => array(
			'vector-simplesearch-search',
			'vector-simplesearch-containing',
		),
		'dependencies' => array(
			'jquery.client',
			'jquery.suggestions',
			'jquery.autoEllipsis',
			'jquery.placeholder',
		),
	),
    );


# Special Skinzam (UI test class)
/*$wgAutoloadClasses['SpecialSkinzam'] = $dir . 'SpecialSkinzam.php';

$wgSpecialPages['Skinzam'] = 'SpecialSkinzam';

$wgSpecialPageGroups['Skinzam'] = 'other';*/

# Some global settings
# Tune Special:Preferences
$wgHiddenPrefs = array('userid', 'underline', 'stubthreshold', 'showtoc', 'showjumplinks', 'editsection', 'externaldiff', 'externaleditor', 'diffonly', 'norollbackdiff');
# Do not display IP as a username-like. (Careful, breaks SeizamFooter if turned true).
$wgShowIPinHeader = false;

# Do not display license icon in absolutefooter
$wgFooterIcons = $wgFooterIcons = array(
	"poweredby" => array(
		"mediawiki" => array(
			"src" => null, // Defaults to "$wgStylePath/common/images/poweredby_mediawiki_88x31.png"
			"url" => "http://www.mediawiki.org/",
			"alt" => "Powered by MediaWiki",
		)
	),
);

# Try something cool
$wgPageShowWatchingUsers = true;
$wgMaxCredits = 1;

