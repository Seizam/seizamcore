<?php
/**
 * SeizamACL extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Yann Missler
 * 
 * Based on the original KeepYourHandsToYourself extension from Jim R. Wilson
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
	'author' => array( 'Clément Dietschy', 'Yann Missler', 'Seizam Sàrl.', 'Jim R. Wilson'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'seizamacl-desc',
);

$wgAutoloadClasses['SeizamACLHooks'] = dirname( __FILE__ ) . '/SeizamACL.hooks.php';
$wgExtensionMessagesFiles['SeizamACL'] = dirname( __FILE__ ) . '/SeizamACL.i18n.php';

# Attach Hooks
$wgHooks['userCan'][] = 'SeizamACLHooks::CanEditImage';
$wgHooks['AbortNewAccount'][] = 'SeizamACLHooks::RejectUsernamesWithDot';
$wgHooks['UploadForm:BeforeProcessing'][] = 'SeizamACLHooks::PrependUsernameToFilename';

// Adds the necessary tables to the DB  --> NOT CURRENTLY USED
// $wgHooks['LoadExtensionSchemaUpdates'][] = 'SeizamACLHooks::loadExtensionSchemaUpdates';

