<?php
/**
 * Seizam extension
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
 * @version 0.3.0
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}
/* Configuration */

// Each module may be configured individually to be globally on/off or user preference based
$wgSeizamFeatures = array(
);

/* Setup */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Seizam',
	'author' => array( 'Clément Dietschy', 'Seizam Sàrl.'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'sz-seizam-desc',
);

$wgAutoloadClasses['SeizamHooks'] = dirname( __FILE__ ) . '/Seizam.hooks.php';
$wgExtensionMessagesFiles['Seizam'] = dirname( __FILE__ ) . '/Seizam.i18n.php';
$wgHooks['BeforePageDisplay'][] = 'SeizamHooks::beforePageDisplay';

$seizamResourceTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'Seizam/modules',
	'group' => 'ext.seizam',
);
$wgResourceModules += array(
	'ext.seizam.global' => $seizamResourceTemplate + array(
		'scripts' => 'ext.seizam.global.js')
);

