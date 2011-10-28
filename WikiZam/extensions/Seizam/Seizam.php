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
$wgExtensionMessagesFiles['Seizam'] = dirname( __FILE__ ) . '/Seizam.i18n.php';

$seizamResourceTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'Seizam/modules',
	'group' => 'ext.seizam',
);
$wgResourceModules += array(
	'ext.vector.global' => $vectorResourceTemplate + array(
		'scripts' => 'ext.seizam.global.js')
);

