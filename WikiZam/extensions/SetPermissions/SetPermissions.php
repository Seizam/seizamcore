<?php
/**
 * SetPermissions extension by Yann Missler
 * http://www.seizam.com
 * 
 * Based on AuthorProtect extension by Ryan Schmidt
 * See http://www.mediawiki.org/wiki/Extension:AuthorProtect for more details
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This file is an extension to MediaWiki and cannot be run externally\n";
	die( 1 );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Set Permissions',
	'author' => 'Ryan Schmidt, Yann Missler',
	'url' => 'http://www.seizam.com',
	'version' => '1.0 alpha',
	'descriptionmsg' => 'setpermissions-desc',
);

$wgAvailableRights[] = 'author';
    // dynamically assigned to the author of a page, but can be set w/ wgGroupPermissions too
$wgAvailableRights[] = 'setpermissions'; 
    // users without this right cannot protect pages they author
$wgExtensionMessagesFiles['SetPermissions'] = dirname( __FILE__ ) . '/SetPermissions.i18n.php';

$wgHooks['SkinTemplateNavigation::Universal'][] = 'efMakeContentAction';
	// add an item to the action menu
$wgHooks['UnknownAction'][] = 'efSetPermissionsForm';
	// add a non native action to mediawiki
$wgHooks['userCan'][] = 'efSetPermissionsDelay';
$wgHooks['UserGetRights'][] = 'efAssignAuthor';

$wgGroupPermissions['sysop']['author'] = true; 
    // sysops can edit every page despite author protection
$wgGroupPermissions['user']['setpermissions'] = true;
    // registered users can protect pages they author
$wgRestrictionLevels[] = 'author';
    // so sysops, etc. using the normal protection interface can protect and unprotect it at the author level

// internal variables, do not modify
$wgSetPermissionsDoProtect = false;
$wgSetPermissionsDelayRun = true;

/**
 * Extensions like ConfirmAccount do some weird stuff to $wgTitle during the UserGetRights hook
 * So this delays the hook's execution to a point where $wgTitle is set
 */
function efSetPermissionsDelay( $title, &$user, $action, $result ) {
	global $wgSetPermissionsDelayRun;
	if ( $wgSetPermissionsDelayRun ) {
		$user->mRights = null;
		$user->getRights(); // delay hook execution for compatibility w/ ConfirmAccount
		$act = ( $action == '' || $action == 'view' ) ? 'edit' : $action;
		$wgSetPermissionsDelayRun = false;
		if ( userIsAuthor( $title ) && isAuthorProtected( $title, $act ) ) {
			$result = true;
			return false;
		}
	}
	$result = null;
	return true;
}

function efAssignAuthor( $user, &$aRights ) {
	global $wgTitle;

	// don't assign author to anons... messes up logging stuff.
	// plus it's all user_id based so it is impossible to differentiate one anon from another
	if ( userIsAuthor( $wgTitle ) && $user->isLoggedIn() ) {
		$aRights[] = 'author';
		$aRights = array_unique( $aRights );
	}
	// assign protect too if we need to
	global $wgSetPermissionsDoProtect;
	if ( $wgSetPermissionsDoProtect ) {
		$aRights[] = 'protect';
		$aRights = array_unique( $aRights );
	}
	return true;
}

function efSetPermissionsAssignProtect() {
	global $wgSetPermissionsDoProtect, $wgUser;
	$wgSetPermissionsDoProtect = true;
	$wgUser->mRights = null;
	$wgUser->getRights(); // re-trigger the above function to assign the protect right
	$wgSetPermissionsDoProtect = false;
}

function efSetPermissionsUnassignProtect() {
	global $wgUser;
	$wgUser->mRights = null;
	$wgUser->getRights();
}

/**
 * Add a custom item to the action list on pages
 * @global type $wgUser
 * @global type $wgRequest
 * @param type $skin
 * @param type $cactions
 * @return type 
 */
function efMakeContentAction( $skin, &$cactions ) {
	global $wgUser, $wgRequest;

	$title = $skin->getTitle();
	
	// if user has 'protect' right, she cannot use 'setpermissions'
	if ( userIsAuthor( $title ) && $wgUser->isAllowed( 'setpermissions' ) && !$wgUser->isAllowed( 'protect' ) ) {
		$action = $wgRequest->getText( 'action' );
		$cactions['actions']['setpermissions'] = array(
			'class' => $action == 'setpermissions' ? 'selected' : false,
			'text' => wfMsg( 'setpermissions' ),
			'href' => $title->getLocalUrl( 'action=setpermissions' ),
		);
	}
	return true;
}

function efSetPermissionsForm( $action, $article ) {

    wfDebugLog( 'setpermissions', 'SetPermissions.php>efSetPermissionsForm() enter');
    
	if ( $action == 'setpermissions' ) {
		global $wgOut, $wgUser, $wgRequest, $wgRestrictionTypes;
		if ( $wgUser->isAllowed( 'setpermissions' ) ) {
			if ( userIsAuthor( $article->getTitle() ) ) {
				$wgOut->setPageTitle( wfMsg( 'setpermissions' ) );
				if ( !$wgRequest->wasPosted() ) {
					$wgOut->addHTML( efSetPermissionsMakeProtectForm( $article->getTitle() ) );
				} else {
					if ( !$wgUser->matchEditToken( $wgRequest->getText( 'wpToken' ) ) ) {
						$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
						$wgOut->addWikiMsg( 'sessionfailure' );
						return false;
					}
					$restrictions = array();
					$expiration = array();
					$expiry = efSetPermissionsExpiry( $wgRequest->getText( 'wpExpiryTime' ) );
					foreach ( $wgRestrictionTypes as $type ) {
						$rest = $article->getTitle()->getRestrictions( $type );
						if ( $rest !== array() ) {
							if ( !$wgUser->isAllowed( $rest[0] ) && !in_array( 'author', $rest ) ) {
								$restrictions[$type] = $rest[0]; // don't let them lower the protection level
								continue;
							}
						}
						if ( $wgRequest->getCheck( "check-{$type}" ) ) {
							$restrictions[$type] = 'author';
							$expiration[$type] = $expiry;
						} else {
							if ( in_array( 'author', $rest ) ) {
								$restrictions[$type] = '';
								$expiration[$type] = $expiry;
							} else {
								$restrictions[$type] = ( $rest !== array() ) ? $rest[0] : ''; // we're not setting it
								$expiration[$type] = $expiry;
							}
						}
					}
					$cascade = false;
					efSetPermissionsAssignProtect();
					$str = var_export( array( 'restrictions' => $restrictions, 'reason' => $wgRequest->getText( 'wpReason' ), 'cascade' => $cascade, 'expiry' => $expiration ), true );
					wfDebugLog( 'setpermissions', "SetPermissions.php>efSetPermissionsForm(): asked=\n$str" );
					$success = $article->updateRestrictions(
						$restrictions, // array of restrictions
						$wgRequest->getText( 'wpReason' ), // reason
						$cascade, // cascading protection disabled, need to pass by reference
						$expiration // expiration
					);
					efSetPermissionsUnassignProtect();
					if ( $success ) {
						$wgOut->addWikiMsg( 'setpermissions-success' );
					} else {
						$wgOut->addWikiMsg( 'setpermissions-failure' );
					}
				}
			} else {
				$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
				$wgOut->addWikiMsg( 'setpermissions-notauthor' );
			}
		} else {
			$wgOut->permissionRequired( 'setpermissions' );
		}
		return false; // still continues hook processing, but doesn't throw an error message
	}
	return true; // unknown action, so state that the action doesn't exist
}

function efSetPermissionsMakeProtectForm( $title ) {
    
    wfDebugLog( 'setpermissions', 'SetPermissions.php>efSetPermissionsMakeProtectForm() enter');
	
	global $wgRestrictionTypes, $wgUser;
	    //This array contains the actions that can be restricted, that is, 
	    //made unavailable to classes of users via protection (using action=protect). 
	    //By default, it contains the strings edit and move. 
	    //For this extension, we added "read" string in LocalSettings.php
	$token = $wgUser->editToken();
	// FIXME: raw html messages
	$form = Xml::openElement( 'p' ) . wfMsg( 'setpermissions-intro' ) . Xml::closeElement( 'p' );
	$form .= Xml::openElement( 'form', array( 'method' => 'post', 'action' => $title->getLocalUrl( 'action=setpermissions' ) ) );

	$br = Html::element( 'br' );

	foreach ( $wgRestrictionTypes as $type ) {
		$rest = $title->getRestrictions( $type );
		
		 wfDebugLog( 'setpermissions',
			 'SetPermissions.php>efSetPermissionsMakeProtectForm() $title->getRestrictions('.
			 $type . ') = ' . print_r($rest, true) );
		 
		    //$type = action that permission needs to be checked for
		    //$rest = array of Strings of groups allowed to do action to this article
		if ( $rest !== array() ) {
			if ( !$wgUser->isAllowed( $rest[0] ) && !in_array( 'author', $rest ) )
				continue; // it's protected at a level higher than them, so don't let them change it so they can now mess with stuff
		}

		$checked =  in_array( 'author', $rest );
		$form .= Xml::checkLabel( wfMsg( "setpermissions-$type" ), "check-$type", "check-$type", $checked ) . $br;
	}

	// FIXME: use Xml::inputLabel
	$form .= $br . Xml::element( 'label', array( 'for' => 'wpExpiryTime' ), wfMsg( 'setpermissions-expiry' ) ) . ' ';
	$form .= Xml::element( 'input', array( 'type' => 'text', 'name' => 'wpExpiryTime' ) ) . $br;
	$form .= $br . Xml::element( 'label', array( 'for' => 'wpReason' ), wfMsg( 'setpermissions-reason' ) ) . ' ';
	$form .= Xml::element( 'input', array( 'type' => 'text', 'name' => 'wpReason' ) );
	$form .= $br . Html::hidden( 'wpToken', $token );
	$form .= $br . Xml::element( 'input', array( 'type' => 'submit', 'name' => 'wpConfirm', 'value' => wfMsg( 'setpermissions-confirm' ) ) );
	$form .= Xml::closeElement( 'form' );
	return $form;
}

function userIsAuthor( $title ) {
	global $wgUser;

	if ( !$title instanceOf Title ) {
		return false; // quick hack to prevent the API from messing up.
	}

	$id = $title->getArticleId();
	$dbr = wfGetDB( DB_SLAVE ); // grab the slave for reading
	$aid = $dbr->selectField( 'revision', 'rev_user',  array( 'rev_page' => $id ), __METHOD__ );
	// FIXME: weak comparison
	return $wgUser->getID() == $aid;
}

function isAuthorProtected( $title, $action ) {
	$rest = $title->getRestrictions( $action );
	return in_array( 'author', $rest );
}

// forked from ProtectionForm::getExpiry and modified to rewrite '' to infinity
function efSetPermissionsExpiry( $value ) {
	if ( $value == 'infinite' || $value == 'indefinite' || $value == 'infinity' || $value == '' ) {
		$time = Block::infinity();
	} else {
		$unix = strtotime( $value );

		if ( !$unix || $unix === -1 ) {
			return false;
		}

		// Fixme: non-qualified absolute times are not in users specified timezone
		// and there isn't notice about it in the ui
		$time = wfTimestamp( TS_MW, $unix );
	}
	return $time;
}
