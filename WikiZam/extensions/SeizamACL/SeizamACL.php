<?php
/**
 * SeizamACL extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Clément Dietschy <clement@seizam.com>
 * 
 * Based on the original HaloACL extension from: Ontoprise Gmbh.
 * 
 * @license GPL v2 or later
 * @version 0.1.0
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

/* Setup */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'SeizamACL',
	'author' => array( 'Clément Dietschy', 'Seizam Sàrl.'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'szacl-seizamacl-desc',
);

$wgAutoloadClasses['SeizamACLHooks'] = dirname( __FILE__ ) . '/SeizamACL.hooks.php';
$wgExtensionMessagesFiles['SeizamACL'] = dirname( __FILE__ ) . '/SeizamACL.i18n.php';

// Adds the necessary tables to the DB
$wgHooks['LoadExtensionSchemaUpdates'][] = 'SeizamACLHooks::loadExtensionSchemaUpdates';



