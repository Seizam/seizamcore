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
$wgHooks['AbortNewAccount'][] = 'SeizamACLHooks::RejectUsernamesTooExotics';
$wgHooks['UploadCreateFromRequest'][] = 'SeizamACLHooks::GetUploadRequestHandler';


/**
 * Upload handler class that extends the normal UploadFromFile class to modify
 * the desired destination name and add the generic comment
 */
class SeizamACLUploadResourceFromFile extends UploadFromFile {
	/**
	 * Modify the desired destination name.
	 */
	function initializeFromRequest( &$request ) {

		global $wgUser;

		//if not logged in, cannot upload, so initialize nothing
		if ( !$wgUser->isLoggedIn() ) return true;

		$desiredDestName = $request->getText( 'wpDestFile' );
		if( !$desiredDestName ) {
			$desiredDestName = $request->getFileName( 'wpUploadFile' );
		}

		$prefix = $wgUser->getName() . '.';

		// check if the prefix is already present, don't care about the case
		// if not, append: username + '.'
		if ( substr( $desiredDestName, 0, strlen( $prefix ) ) != $prefix )
			$destName = $prefix . $desiredDestName;
		else
			$destName = $desiredDestName;

		$request->setVal( 'wpDestFile', $destName );

		// for debugging purpose, un comment he above
		// normal use -> keep the following commented
		/*
		echo 'SeizamACLUploadResourceFromFile::initializeFromRequest( $request->getFileName( \'wpUploadFile\' ) = ['.
		$request->getFileName( 'wpUploadFile' ).'] )<br/>
		 desiredDestName = ['.$desiredDestName.']<br/>
		 destName = ['.$destName.']<br/>
		'; 
		die(-1);
		*/
		return parent::initializeFromRequest( $request );
	}

}
// Adds the necessary tables to the DB  --> NOT CURRENTLY USED
// $wgHooks['LoadExtensionSchemaUpdates'][] = 'SeizamACLHooks::loadExtensionSchemaUpdates';

