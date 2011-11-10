<?php
/**
 * Hooks for SeizamACL extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}
class SeizamACLHooks {


            /* Static Methods */

	/**
	 * Reject edit action if user attempts to edit another user's image
	 * Usage: $wgHooks['userCan'][] = 'SeizamACL::CanEditImage';
	 * @param Title $title Title of the article. (passed by reference)
	 * @param User $user User attempting action on article - presumably $wgUser. (passed by reference)
	 * @param String $action Action being taken on article. (passed by value)
	 * @param Mixed $result The result of processing. (passed by reference)
	 * @return true Always true so other extensions have a chance to process 'userCan'
	 */
	public static function CanEditImage($title, $user, $action, $result) {

		# Check for Namespace, edit action, and sysopship
		# we only continue if  ns_image AND edit AND !sysop
		$ns = $title->getNamespace();
		if (
			$ns!=NS_IMAGE || 
			$action!='edit' ||
			in_array('sysop', $user->getGroups())
		) return true;
    
		# Check that the image contains at least 2 points:
		#   * one after the username,
		#   * one after the filename just before extension
		# if yes, we continue, to test if the current user own the file
		# If not, we are uploading a first revission and the username will be added later
		#  by SeizamACLUploadResourceFromFile
		$text = $title->getText();
		$needed = $user->getName().'.';
		if (
			$ns==NS_IMAGE &&
			strpos($text, '.')==strrpos($text, '.')
		) return true; 


		# Check if the image name starts with the username and appropriate separator (.)
		if ( substr( $text, 0, strlen($needed) ) == $needed ) 
			return true;

		# If we got this far, then it's a user trying to edit another user's page or image
		$result = false;
		return false;

	}




	/**
	* Reject any user account creation attempt if the username contains dot(s).
	* Usage: $wgHooks['AbortNewAccount'][] = 'SeizamACL::RejectUsernamesWithDot';
	* @param User $user Attempted user to create (passed by value)
	* @param Mixed $abortError Error string
	* @param Mixed $result The result of processing. (passed by reference)
	* @return Boolean false if username was rejected, true otherwise
	*/
	public static function RejectUsernamesTooExotics($user, &$abortError) {

		if (strstr($user->getName(),'.')!==false) {
			$abortError = wfMsgForContent('seizamacl-nodots');
			return false;
		}
		return true;
	}




	public static function GetUploadRequestHandler( $type, $className ) {

		switch ( $type ) {
			case "File":
				$className = 'SeizamACLUploadResourceFromFile';
				break;
			default:
				print( "GetUploadRequestHandler( type=[$type] , * ) : Unknown type error<br />" );
				die( -1 );
		}
		return true;
	}



	
	/**
	 * LoadExtensionSchemaUpdates hook
	 * 
	 * Adds the necessary tables to the DB
	 * 
	 */
	
	/* CURRENTLY NOT USED

	public static function loadExtensionSchemaUpdates( $updater ) {
        $updater->addExtensionUpdate( array( 'addTable', 'szacl_owner',
                dirname( __FILE__ ) . '/schema/mysql/szacl_owner.sql', true ) );
        return true;
	*/
}
