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
	'author' => 'Ryan Schmidt, Jean-Lou Dupont, Yann Missler',
	'url' => 'http://www.seizam.com',
	'version' => '1.0 alpha',
	'descriptionmsg' => 'setpermissions-desc',
);

$wgAvailableRights[] = 'owner';
    // dynamically assigned to the owner of a page, but can be set w/ wgGroupPermissions too
$wgAvailableRights[] = 'setpermissions'; 
    // users without this right cannot access setpermissions page, even if they own the page
$wgExtensionMessagesFiles['SetPermissions'] = dirname( __FILE__ ) . '/SetPermissions.i18n.php';

$wgHooks['SkinTemplateNavigation::Universal'][] = 'spSetPermissionsMakeContentAction';
	// add an item to the action menu
$wgHooks['UnknownAction'][] = 'spSetPermissionsForm';
	// add a non native action to mediawiki
$wgHooks['userCan'][] = 'spSetPermissionsUserCanIfAtOwnerLevel';
	// if action is restricted at owner level, check  that the user is the owner
$wgHooks['UserGetRights'][] = 'spSetPermissionsAssignDynamicRights';
	// this hook is call when mediawiki request the rights of a user, it can give more 
	// rights than mediawiki does, in our case, it depends if the user is the owner
	// >> this hook dynamically give the current user the "owner" right if this
	//    function estimate that she is the owner


// This hook can be reused in another extension, wich can say if someone is owner or not
/*
$wgHooks['IsOwner'][] = 'kikoo';
function kikoo( $title, $user, &$result) {
    $result = false;	//false=not owner , true=owner
    return false;	//false=stop proccessing , true=continue 
			//(call other IsOwner declared functions, then the basic spSetPermissionsIsOwner)
}
*/

$wgRestrictionTypes[] = "read";
    // Configure actions that can be restricted using action=protect, that is, made 
    // unavailable to classes of users via protection (using action=protect)
    // We add a read right, this way sysops can also change read permissions via action=protect


$wgGroupPermissions['user']['setpermissions'] = true;
    // registered users can set permissions to pages they own

//TO BE MOVED IN  LOCALSETTINGS.PHP
$wgGroupPermissions['artist'] = array();			// create artists user group   
$wgSetPermissionsRestrictions = array(
	'user',
	'artist',
	'owner'
);
//END TO BE MOVED

   

// internal variables, do not modify (inherited from AuthorProtect)
$wgSetPermissionsDoProtect = false;
    // tells to the UserGetRight own hook implementation if we need to grant protect right
    // (required when updating article restriction when validating SetPermissions form

spSetPermissionsSetup();


function spSetPermissionsSetup() {
	global $wgRestrictionLevels, $wgGroupPermissions, $wgSetPermissionsRestrictions, $wgRestrictionTypes;
	var_dump($wgSetPermissionsRestrictions);
	foreach ($wgSetPermissionsRestrictions as $rest) {
		$wgRestrictionLevels[] = $rest.'-perm';
			// this add the levels used by SetPermissions to MediaWiki system, so that 
			// the normal protection interface can protect and unprotect at every levels exactly
			// the same way as SetPermissions internally does
		if ($rest!='owner') //because owner-perm is dynamically assign depending of the title
			$wgGroupPermissions[$rest][$rest.'-perm'] = true;
		$wgGroupPermissions['sysop'][$rest.'-perm'] = true;
			// with this, for permissions checks, sysops are virtually owner of everything
			// sysops are in owner level of every page, that is they can always do what they
			// wants to every pages (big boss mode)
	}
}
 

function spSetPermissionsAssignProtect() {
	global $wgSetPermissionsDoProtect, $wgUser;
	$wgSetPermissionsDoProtect = true;  //tells spSetPermissionsAssignDynamicRights to add the "protect" right
	$wgUser->mRights = null;	    // clear current user rights
	$wgUser->getRights();		    // force rights reloading
	$wgSetPermissionsDoProtect = false;
}

function spSetPermissionsUnassignProtect() {
	global $wgUser;
	$wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
	$wgUser->getRights();	    // force rights reloading
}

/**
 * Add a custom item to the action list on pages
 * @global type $wgUser
 * @global type $wgRequest
 * @param type $skin
 * @param type $cactions
 * @return type 
 */
function spSetPermissionsMakeContentAction( $skin, &$cactions ) {
	global $wgUser, $wgRequest;

	$title = $skin->getTitle();
	
	// if user has 'protect' right, she cannot use 'setpermissions'
	if ( spSetPermissionsIsOwner( $title , $wgUser ) && $wgUser->isAllowed( 'setpermissions' ) && !$wgUser->isAllowed( 'protect' ) ) {
		$action = $wgRequest->getText( 'action' );
		$cactions['actions']['setpermissions'] = array(
			'class' => $action == 'setpermissions' ? 'selected' : false,
			'text' => wfMsg( 'setpermissions' ),
			'href' => $title->getLocalUrl( 'action=setpermissions' ),
		);
	}
	return true;
}

function spSetPermissionsForm( $action, $article ) {

    wfDebugLog( 'setpermissions', 'Form() enter');
    
	if ( $action != 'setpermissions' ) { // unknown action, so state that the action doesn't exist
		return true; //stop processing
	}
	
	global $wgOut, $wgUser, $wgRequest;

	if ( !$wgUser->isAllowed( 'setpermissions' ) ) {
		// user is not allowed to use setpermissions
		$wgOut->permissionRequired( 'setpermissions' );
		
	} else {
		// user is allowed to use setpermissions
		if ( !spSetPermissionsIsOwner( $article->getTitle() , $wgUser ) ) {
			// user is not the owner of the page
			$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
			$wgOut->addWikiMsg( 'setpermissions-notowner' );
			
		} else {
			// user is the owner of the page, so let's do what we have to
			$wgOut->setPageTitle( wfMsg( 'setpermissions' ) );

			if ( !$wgRequest->wasPosted() ) {
				// no data submitted, so construct the form
				$wgOut->addHTML( spSetPermissionsMakeForm( $article->getTitle() ) );

			} else {
				// ensure that the form was submitted from the user's own login session
				if ( !$wgUser->matchEditToken( $wgRequest->getText( 'wpToken' ) ) ) {
					// hummm.... how did this case happen?
					$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
					$wgOut->addWikiMsg( 'sessionfailure' );
					return false; // stop processing
				}

				// ok, so let's go!
				
				$restrictions = array();
				$expiration = array();
				$expiry = Block::infinity(); // the restriction will never expire

				// we load the title specific available restrictions
				$applicableRestrictionTypes  = $article->getTitle()->getRestrictionTypes();
				wfDebugLog( 'setpermissions'
						, 'Form(): applying permissions, available title restrictions = '
						.implode(',', $applicableRestrictionTypes));
						
				// for each of theses available restrictions
				foreach ( $applicableRestrictionTypes as $type ) {  // $type = 'read' or 'edit' or 'upload' or ....

					$rest = $article->getTitle()->getRestrictions( $type ); 
						// $rest = 'sysop' or 'autoconfirmed' or 'owner' or ...
					wfDebugLog( 'setpermissions', 'Form(): current restriction "'
							.$type.'" value = '.implode(',', $rest));

					if ( $rest !== array() ) { // the title has already a restriction
						if ( !$wgUser->isAllowed( $rest[0] ) ) {
							// this check rights inherited from groups and also our
							// dynamic rights assigned by spSetPermissionsAssignDynamicRights
							$restrictions[$type] = $rest[0]; // don't let them lower the protection level
							continue; // exit foreach
						}
					}
					
					// we arrive here is user can change the permissions
					// checkboxes'name = check-$type-$rest (ex: check-read-artist)
					
					if ( $wgRequest->getCheck( "check-{$type}-everyone" ) ) {
						$restrictions[$type] = ''; // no restriction
			
					} elseif ( $wgRequest->getCheck( "check-{$type}-user" ) ) {
						$restrictions[$type] = 'user'; // set to owner level
						
					} elseif ( $wgRequest->getCheck( "check-{$type}-artist" ) ) {
						$restrictions[$type] = 'artist'; // set to owner level
						
					} else {
							$restrictions[$type] = 'owner'; // unset ownerlevel
					}
					
					$expiration[$type] = $expiry;
					
				}
				
				$cascade = false;
					// don't cascade the owner restriction, because a subpage may not have the same owner
					// so casacing won't make sens, and can be very problematic
					// don't change this unless you know serioulsy what you are doing !!!

				spSetPermissionsAssignProtect();
				//temporary assign protect right, in order to update the restricitons

				$str = var_export( array( 
					'restrictions' => $restrictions, 
					'reason' => $wgRequest->getText( 'wpReason' ), 
					'cascade' => $cascade, 
					'expiry' => $expiration ), true );
				wfDebugLog( 'setpermissions', "Form(): updating to\n $str" );

				$success = $article->updateRestrictions(
					$restrictions, // array of restrictions
					$wgRequest->getText( 'wpReason' ), // reason
					$cascade, // cascading protection disabled, need to pass by reference
					$expiration // expiration
				);  // this article function check that the user has sufficient rights

				spSetPermissionsUnassignProtect();
				// remove temporary assigned protect right

				if ( $success ) {
					$wgOut->addWikiMsg( 'setpermissions-success' );
				} else {
					$wgOut->addWikiMsg( 'setpermissions-failure' );
				}
			}
		} 
	} 
	
	return false; // still continues hook processing, and doesn't throw an error message

}

function spSetPermissionsMakeForm( $title ) {
	
	global $wgUser, $wgRestrictionLevels;
	$applicableRestrictionTypes  = $title->getRestrictionTypes(); // this way, do not display create for exsiting page
	
	wfDebugLog( 'setpermissions', 'MakeForm(): $title->getRestrictionTypes() = '. implode(',',$applicableRestrictionTypes));
	
	$token = $wgUser->editToken();
	
	$form  = Html::rawElement( 'p', array(), htmlspecialchars( wfMsg( 'setpermissions-intro' ) ) );
	$form .= Html::openElement( 'form', array( 'method' => 'post', 'action' => $title->getLocalUrl( 'action=setpermissions' ) ) );

	$br = Html::element( 'br' );
	
	$form .=	Xml::openElement( 'table') .
				Xml::openElement( 'tbody' ) ;

	foreach ( $applicableRestrictionTypes as $type ) {	// read/edit for a page, upload for a file, ....
		$rest = $title->getRestrictions( $type ); // = who is allowed to do $type on $title
		wfDebugLog( 'setpermissions',
			 'MakeForm(): $title->getRestrictions('.
			 $type . ') = ' . implode(',',$rest) );
		
		$form .= '<tr><td>'.Xml::openElement( 'fieldset' ) . Xml::element( 'legend', null, wfMsg( "setpermissions-whocan-$type") ) ;
		//the next lines display checkboxes and eventually check them
		
		if (  $rest !== array() && !$wgUser->isAllowed($rest[0]) ) {		
			// IF  it is '$rest'ricted to a group in which the user is not 
			// it's protected at a level higher than them, so don't 
			// let them change it so they can now mess with stuff
			$form .=  wfMsg( "restriction-level-$rest[0]" ).' ('.$rest[0].')';
			continue;
		}
						
		$checked =  $rest === array();		// empty = no restriction = everyone can do
		$form .= Xml::checkLabel( wfMsg( "setpermissions-everyone" ), "check-$type-everyone", "check-$type-everyone", $checked ) . $br;
			
		$checked =  $checked || in_array( 'user', $rest ) ; 
		$form .= Xml::checkLabel( wfMsg( "setpermissions-user" ), "check-$type-user", "check-$type-user", $checked ) . $br;
		
		$checked =  $checked || in_array( 'artist', $rest ) ; 
		$form .= Xml::checkLabel( wfMsg( "setpermissions-artist" ), "check-$type-artist", "check-$type-artist", $checked ) . $br;
		
		$checked =  $checked || in_array( 'owner', $rest ) ; // checked if restricted to owner level
		$form .= Xml::checkLabel( wfMsg( "setpermissions-owner-me" ), "check-$type-owner", "check-$type-owner", true, array( 'disabled' => 'disabled' ) ) ;
		
		$form .= Xml::closeElement( 'fieldset' ) .'</td></tr>';

	}

	$form .= $br . Html::hidden( 'wpToken', $token );
	
	
	// Dev In Progress
	
	$form .= Xml::closeElement( 'tbody' ) . Xml::closeElement( 'table' );
	// end DIP section
	
	$form .= $br . Xml::submitButton( wfMessage( 'setpermissions-confirm' ) );
	$form .= Xml::closeElement( 'form' );
	return $form;
}

/**
 * The most important code!
 * This says if the current user is the owner of $title.
 * @global User $user
 * @param Title $title
 * @return boolean 
 */
function spSetPermissionsIsOwner( $title, $user ) {
    
	if ( !$title instanceOf Title ) {
	    wfDebugLog( 'setpermissions', 'IsOwner() = NO (not a valid insance of title)');
	    return false; // quick hack to prevent the API from messing up.
	}
	
	if ( $user->getID() === 0 ) { // if anonymous
	    wfDebugLog( 'setpermissions', 'IsOwner() = NO (anonymous user)');
	    return false; // don't allow anons, they shouldn't even get this far but just in case...
	}
	
	// process custom hook IsOwner, in order for other extensions to fetch 
	// ownership using a different way 
	$result = false;
	if ( wfRunHooks( 'IsOwner', array( $title, $user, &$result ) ) ) {
	    // no hook functions stopped processing, so we have use the default method
	    wfDebugLog( 'setpermissions', 'IsOwner(): hook IsOwner did not answer, so evaluating first revisionner'); 
	    $result = spSetPermissionsIsFirstRevisonner( $title, $user );
	}
	
	wfDebugLog( 'setpermissions', 'IsOwner() = '.( $result ? 'YES' : 'NO'));
	return $result ;
}


function spSetPermissionsIsFirstRevisonner( $title, $user ) {
    
    // looking for the first revisonner
    $id = $title->getArticleId();
    $dbr = wfGetDB( DB_SLAVE ); // grab the slave for reading
    $firstrevionnerid = $dbr->selectField( 'revision', 'rev_user',  array( 'rev_page' => $id ),
	    __METHOD__, array( 'ORDER BY' => 'rev_timestamp ASC'  ) );

    $userid = $user->getID();
    $result = ( $userid == $firstrevionnerid );
    
    wfDebugLog( 'setpermissions', 'IsFirstRevisonner() = '
	    .( $result ? 'YES' : 'NO')
	    ." (page_id=$id first_revisionner_id=$firstrevionnerid current_user_id=$userid)");
    
    return $result;
}


function spSetPermissionsIsRestrictedAtOwnerLevel( $title, $action ) {
	$rest = $title->getRestrictions( $action ); // array()  or array( 'owner' )
	$back = in_array( 'owner', $rest );
	wfDebugLog( 'setpermissions', 'IsRestrictedAtOwnerLevel() = '
		.( $back ? 'YES' : 'NO'). ' (title: "'.$title->getLocalURL().'" , action: '.$action.')');
	return $back;
}


/**
 * registered as hook UserGetRights (executed everytime MediaWiki request the available permissions
 * of a user)
 * >> we give the user the special "owner" right if we estimate she is the owner of the ressource
 * @global type $wgTitle
 * @global boolean $wgSetPermissionsDoProtect
 * @param type $user
 * @param type $aRights
 * @return type 
 */
function spSetPermissionsAssignDynamicRights( $user, &$aRights ) {
	global $wgTitle;
    
/*	wfDebugLog( 'setpermissions', 'SetPermissions.php>spSetPermissionsAssignDynamicRights( title='
		. ( $wgTitle instanceOf Title ? 
		    '"'.$wgTitle->getLocalURL().'"('.$wgTitle->getArticleId().')'
		    : '???' )
		.' user="'.$user->getName().'"('.$user->getID().') ): enter');
*/	
	// don't assign "owner" to anons... messes up logging stuff.
	// plus it's all user_id based so it is impossible to differentiate one anon from another
	if ( $user->isLoggedIn() && spSetPermissionsIsOwner( $wgTitle , $user ) ) {
		wfDebugLog( 'setpermissions', 'AssignDynamicRights(): '
			.'assigning current user "'.$user->getName().'"('.$user->getID()
			.') the "owner" right');
		$aRights[] = 'ownage';
		$aRights = array_unique( $aRights );
	}
	// assign protect too if we need to (required when the user submit a permission change)
	global $wgSetPermissionsDoProtect;
	if ( $wgSetPermissionsDoProtect ) {
		wfDebugLog( 'setpermissions', 'AssignDynamicRights(): '
			.'assigning current user "'.$user->getName().'"('.$user->getID()
			.') the "protect" right');
		$aRights[] = 'protect';
		$aRights = array_unique( $aRights );
	}
	
	return true;
}

/**
 * Stop the proccessing and answer NO only if:
 * * the action is restricted at "owner" level
 * * the user is not the owner OR member of special "owner" group
 * @param type $title
 * @param type $user
 * @param type $action
 * @param boolean $result
 * @return type 
 */
function spSetPermissionsUserCanIfAtOwnerLevel( $title, &$user, $action, $result )
//function spSetPermissionsUserCanRead( &$article )
{

/*        wfDebugLog( 'setpermissions', 'SetPermissions.php>spSetPermissionsUserCanIfAtOwnerLevel("'
		.$title->getLocalURL().'"('.$title->getArticleId().') current user="'
		.$user->getName().'"('.$user->getID().') action="'.$action.'") enter');
*/		    
  # if the action is not related to a 'view' (i.e. 'read') request, get out.
//  if ($action != 'read')
//   return true;  #don't stop processing the hook chain
  /*
  wfDebugLog( 'setpermissions', 'SetPermissions.php>spSetPermissionsUserCanIfAtOwnerLevel('.$title->getLocalURL().')'
	  // . " stacktrace=\n ".print_r(wfGetPrettyBacktrace(),true)
	  . "\n the curr user is   explicit   member of groups: " . implode(",", $user->getGroups())
	  . "\n the curr user is   implicit   member of groups: " . implode(",", $user->getAutomaticGroups())
	  . "\n the curr user has  rights:      " . implode(",", $user->getRights())
	  . "\n the curr user has  permissions: " . implode(",", $user->getAllRights())		  
		  
	  );
  */
    
    if ( $action != 'read' && $action != 'edit' )
	wfDebugLog( 'setpermissions-notice', 'UserCanIfAtOwnerLevel(): '
		.'( title=['.$title->getLocalURL().'] action=['.$action.']  user=['.$user->getName()
		.'('.$user->getID().')] )');
    
  $act = $action;
  if ( $action == '' || $action == 'view' ) { //just in case, but shouldn't occur
      $act = 'read' ; 
  }
    
  // If 'owner' restriction is active, then check for 'owner' dynamic assigned right
  if ( spSetPermissionsIsRestrictedAtOwnerLevel( $title, $action) === true ) 
  {
    // if the user is not in the owner group (!sysop), and she is not the owner of the page => not allowed
    if ( !in_array('owner', $user->getRights()) && !spSetPermissionsIsOwner( $title , $user )  )
    {
	wfDebugLog( 'setpermissions', 'UserCanIfAtOwnerLevel(): NO '
	    .'($title="'.$title->getLocalURL().'" $action="'.$action.'" restricted to owner only)');
	$result = false; //not allowed
	return false; //stop processing 
    } 
  }
  return true; # don't stop processing hook chain.
}