<?php

$wgExtensionCredits['validextensionclass'][] = array(
   'path' => __FILE__,
   'name' => 'Restrictions',
   'author' =>'Yann Missler', 
   'url' => 'http://www.seizam.com', 
   'description' => 'This extension override initial MediaWiki\'s inital restrictions system',
   'version'  => 'alpha',
   );

// level '' (=everyone) is virtual
// level 'owner' is virtual and used only by this extension (never stored in db)
// but any other level added to this var need to have its group existing in $wgGroupPermissions
$wgRestrictionsLevelsHierarchy = array( '', 'user', 'owner' );

// load messages file
$wgExtensionMessagesFiles['Restrictions'] = dirname( __FILE__ ) . '/Restrictions.i18n.php';

// add our own userCan hook
$wgHooks['userCan'][] = 'efRestrictionsUserCan';

// this hook is called when mediawiki request the rights of a user, it can give more 
// rights than mediawiki does, in our case, it depends if the user is the owner
// it can also give the protect right while updating restrictons
$wgHooks['UserGetRights'][] = 'efRestrictionsUserGetRights';

// add an item to the action menu
$wgHooks['SkinTemplateNavigation::Universal'][] = 'efRestrictionsMakeContentAction';

// add a non native action to mediawiki
$wgHooks['UnknownAction'][] = 'efRestrictionsForm';

// registered users can set permissions to pages they own
$wgGroupPermissions['user']['setpermissions'] = true;
$wgAvailableRights[] = 'setpermissions'; 

// internal variable
// tells to our own UserGetRight hook implementation if we need to grant protect right
// (required when updating article restriction when validating SetPermissions form)
$wgSetPermissionsDoProtect = false;
    
# for dev
//$wgGroupPermissions['user']['protect'] = true;
//$wgAvailableRights[] = 'owner';



# ==================================== 
#   SETUP

// purge default restrictions levels
$wgRestrictionLevels = array();

// add the read rights to restricted actions list
$wgRestrictionTypes[] = "read";

// create our new restrictions levels
foreach ($wgRestrictionsLevelsHierarchy as $level) {
	
	// add the levels used by this extension to MediaWiki system, so that 
	// the normal protection interface can protect and unprotect at every levels exactly
	// the same way as this extension internally does
	if ($level=='')
		$wgRestrictionLevels[] = '';
	else
		$wgRestrictionLevels[] = $level.'-level';
	
	// if not virtual, assign this restrictions level to corresponding user group
	// (owner and everyone are virtuals, and dynamically assigned)
	if ( $level!='owner' && $level!='' ) 	
		$wgGroupPermissions[$level][$level.'-level'] = true;

	// sysops can always do what they want on every restrictions levels (even owner and everyone)
	if ( $level!='' ) 
		$wgGroupPermissions['sysop'][$level.'-level'] = true;
		
}



# ==================================== 
#           THE CODE
# ====================================

function efRestrictionsUserCan( $title, &$user, $action, $result ) {
	
	wfDebugLog( 'restrictions', 'UserCan: title="'
		.$title->getLocalURL().'"('.$title->getArticleId().') user="'
		.$user->getName().'"('.$user->getID().') action="'.$action.'"');

	$act = $action;

	//just in case, but shouldn't occur
	if ( $action == '' || $action == 'view' ) { 
		$act = 'read' ; 
	}

	// fetch restriction from MediaWiki core
	$title_restrictions = $title->getRestrictions( $action );
        
	// if no restrictions, return "the $user can do $action on $title"
	if ( $title_restrictions === array() ) {
		wfDebugLog( 'restrictions', 'UserCan: action not restricted');
		$result = true;		// allowed
		return true;		// continue userCan hooks processing (another hook can still disallow user)
	}

	// there should be only one restriction per page/action 
	// if not, we don't know how to handle this 
	// (does it meen that the user has to be in every groups, or just one, ... ??)
	if (count($title_restrictions) != 1) {
		wfDebugLog( 'restrictions', 'UserCan: /!\ few restrictions for only a page/action !!!');
		// in this case, we simply state that the user can't
		$result = false;	//not allowed
		return false;		//stop processing 
	}

	$title_restriction = $title_restrictions[0];
	
	if ($title_restriction == 'owner-level') {
		// check if the current user is the owner
		$result = efRestrictionsIsOwner( $title, $user ) ||		// allow if $user is the owner  OR
				in_array('owner-level', $user->getRights());	// allow if $user has 'owner-level' right
	}
		
	else
		// allow if $user is member of the group
		$result = in_array($title_restriction, $user->getRights());	
		
	wfDebugLog( 'restrictions', 'UserCan: '.($result?'YES':'NO'));
	
	// stop processing
	return false;
	
}

function efRestrictionsIsOwner( $title, $user ) {
    
	if ( !$title instanceOf Title ) {
	    return false; // quick hack to prevent the API from messing up.
	}
	
	if ( $user->getID() === 0 ) { // if anonymous
	    return false; // don't allow anons, they shouldn't even get this far but just in case...
	}
	
	// process custom hook IsOwner, in order for other extensions to fetch 
	// ownership using a different way 
	$result = false;
	if ( wfRunHooks( 'IsOwner', array( $title, $user, &$result ) ) ) {
	    // no hook functions stopped processing, so we have use the default method:
		// looking for the first revisonner
		$id = $title->getArticleId();
		$dbr = wfGetDB( DB_SLAVE ); // grab the slave for reading
		$firstrevionnerid = $dbr->selectField( 'revision', 'rev_user',  array( 'rev_page' => $id ),
			__METHOD__, array( 'ORDER BY' => 'rev_timestamp ASC'  ) );

		$userid = $user->getID();
		$result = ( $userid == $firstrevionnerid );;

	}
	
	wfDebugLog( 'restrictions', 'IsOwner: title="'.$title->getLocalURL()
			.'" user="'.$user->getName().'") = '.( $result ? 'YES' : 'NO'));
	return $result ;
}


/**
 * Dynamically assign "owner-level" right when needed 
 * @global type $wgTitle
 * @global type $wgSetPermissionsDoProtect
 * @param type $user
 * @param type $aRights
 * @return type 
 */
function efRestrictionsUserGetRights( $user, &$aRights ) {
	
	global $wgTitle;

	// if the user is the owner of the currently viewed title, puting her in the restricted level
	if ( $user->isLoggedIn() && efRestrictionsIsOwner( $wgTitle , $user ) ) {
		$aRights[] = 'owner-level';
		$aRights = array_unique( $aRights );
	}
	
	// when updating title's restriction, protect right is needed
	// so when flushing rights (in SetPermissions'form related code), the code above
	// is activated by setting $wgSetPermissionsDoProtect to true 
	global $wgSetPermissionsDoProtect;
	if ( $wgSetPermissionsDoProtect ) {
		$aRights[] = 'protect';
		$aRights = array_unique( $aRights );
	}

	return true;	// don't stop hook processing
	
}


function efRestrictionsMakeContentAction( $skin, &$cactions ) {
	global $wgUser, $wgRequest;

	$title = $skin->getTitle();
	
	// if user has 'protect' right, she cannot use 'setpermissions', but 'protect' instead'
	if ( efRestrictionsIsOwner( $title , $wgUser ) 
			&& $wgUser->isAllowed( 'setpermissions' )
			&& !$wgUser->isAllowed( 'protect' ) ) {
		$action = $wgRequest->getText( 'action' );
		$cactions['actions']['setpermissions'] = array(
			'class' => $action == 'setpermissions' ? 'selected' : false,
			'text' => wfMsg( 'setpermissions' ),
			'href' => $title->getLocalUrl( 'action=setpermissions' ),
		);
	}
	return true;
}


function efRestrictionsForm( $action, $article ) {
    
	if ( $action != 'setpermissions' ) { // not our extension
		return true; //don't stop processing
	}
	
	global $wgOut, $wgUser, $wgRequest;

	// is user is not allowed to use setpermissions
	if ( !$wgUser->isAllowed( 'setpermissions' ) ) {
		$wgOut->permissionRequired( 'setpermissions' );
		return false; //stop processing	
	} 
	
	# user is allowed to use setpermissions
	if ( !efRestrictionsIsOwner( $article->getTitle() , $wgUser ) ) {
		// user is not the owner of the page
		$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
		$wgOut->addWikiMsg( 'setpermissions-notowner' );
		return false; //stop processing	
	} 
	
	// user is the owner of the page, so let's do what we have to
	$wgOut->setPageTitle( wfMsg( 'setpermissions' ) );

	if ( !$wgRequest->wasPosted() ) {
		// no data submitted, so construct the form
		$wgOut->addHTML( efRestrictionsMakeForm( $article->getTitle() ) );
		return false; //stop processing	
	}

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
	wfDebugLog( 'restrictions'
			, 'Form: applying permissions, available title restrictions = '
			.implode(',', $applicableRestrictionTypes));

	// for each of theses available restrictions
	foreach ( $applicableRestrictionTypes as $type ) {  // 'read', 'upload', ...

		$rest = $article->getTitle()->getRestrictions( $type ); //'sysop', 'owner-level', ...

		wfDebugLog( 'restrictions', 'Form: current title restriction "'
				.$type.'" restricted to level(s) "'.implode(',', $rest).'"');

		// does the title have already a restriction ?
		if ( $rest !== array() ) {
			// check rights inherited from groups and also ours dynamic assigned rights 
			if ( !$wgUser->isAllowed( $rest[0] ) ) {
				//so, the user is not allowed to change this
				$restrictions[$type] = $rest[0]; // don't let them lower the protection level
				continue; // exit foreach
			}
		}

		// we arrive here if user can change the permissions

		// by default, restricted to 'owner-level'
		$restrictions[$type] = 'owner-level'; 
		
		// check what's checked, taking account $wgRestrictionsLevelsHierarchy order 
		global $wgRestrictionsLevelsHierarchy;
		$stop_foreach = false;
		foreach( $wgRestrictionsLevelsHierarchy as $current_level) {
			
			// end the iteration if requested
			// TO DO: change this to something better
			if ($stop_foreach)
				continue;
			
			// 'everyone' = level ''
			$current_level = ($current_level=='' ? 'everyone' : $current_level);
			
			wfDebugLog( 'restrictions', 'Form: get check "'."check-$type-$current_level\""
					.print_r($wgRequest->getCheck( "check-$type-$current_level" ), true) );
			
			if ( $wgRequest->getCheck( "check-$type-$current_level" ) ) {
				$restrictions[$type] = 
					($current_level=='everyone' ? 
						'' :
						$current_level.'-level'); 
				// we have found the restriction to apply, so exit foreach
				$stop_foreach = true; 
			}
		}

		$expiration[$type] = $expiry;

	}

	// don't cascade the owner restriction, because a subpage may not have the same owner
	// so casacing won't make sens, and can be very problematic
	// don't change this unless you know serioulsy what you are doing !!!
	$cascade = false;
		
	//temporary assign protect right, in order to update the restricitons

	global $wgSetPermissionsDoProtect;
	$wgSetPermissionsDoProtect = true;  //tells spSetPermissionsAssignDynamicRights to add the "protect" right
	$wgUser->mRights = null;	    // clear current user rights
	$wgUser->getRights();		    // force rights reloading
	$wgSetPermissionsDoProtect = false;

	wfDebugLog( 'restrictions', "Form: updating restrictions to\n "
			.var_export( array( 
				'restrictions' => $restrictions, 
				'reason' => $wgRequest->getText( 'wpReason' ), 
				'cascade' => $cascade, 
				'expiry' => $expiration ), true ));

	$success = $article->updateRestrictions(
		$restrictions,				// array of restrictions
		'Restrictions extension',	// reason
		$cascade,					// cascading protection disabled, need to pass by reference
		$expiration					// expiration
	);  // note that this article function check that the user has sufficient rights

	// remove temporary assigned protect right
	$wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
	$wgUser->getRights();	    // force rights reloading
	
	// force reloading restrictions
	$article->getTitle()->mRestrictions = array();
	$article->getTitle()->mRestrictionsLoaded = false;
	$article->getTitle()->loadRestrictions();

	if ( $success ) {
		$wgOut->addWikiMsg( 'setpermissions-success' );
	} else {
		$wgOut->addWikiMsg( 'setpermissions-failure' );
	}
	
	$wgOut->addHTML( efRestrictionsMakeForm( $article->getTitle() ) );
	
	return false; // stop hook processing, and doesn't throw an error message

}


function efRestrictionsMakeForm( $title ) {
	
	global $wgUser, $wgRestrictionsLevelsHierarchy;
	$applicableRestrictionTypes  = $title->getRestrictionTypes(); // this way, do not display create for exsiting page
	
	wfDebugLog( 'setpermissions', 'MakeForm(): $title->getRestrictionTypes() = '. implode(',',$applicableRestrictionTypes));
	
	$token = $wgUser->editToken();
	$br = Html::element( 'br' );
	
	$form  = Html::rawElement( 'p', array(), htmlspecialchars( wfMsg( 'setpermissions-intro' ) ) );
	$form .= Html::openElement('form', array( 
					'method' => 'post',
					'action' => $title->getLocalUrl( 'action=setpermissions' ) ) );
	$form .=	Xml::openElement( 'table') .
				Xml::openElement( 'tbody' );

	// for each of theses available restrictions
	foreach ( $applicableRestrictionTypes as $type ) {  // 'read', 'upload', ...

		$rest = $title->getRestrictions( $type ); //'sysop', 'owner-level', ...

		wfDebugLog( 'restrictions', 'Form: current title restriction "'
				.$type.'" restricted to level(s) "'.implode(',', $rest).'"');

		$form .= '<tr><td>'.Xml::openElement( 'fieldset' );
		$form .= Xml::element( 'legend', null, wfMsg( "setpermissions-whocan-$type") ) ;
		
		// if restricted to a level that the user can't set, display only a message
		if (  $rest !== array() && !$wgUser->isAllowed($rest[0]) ) {		
			$form .=  wfMsg( "restriction-level-$rest[0]" ).' ('.$rest[0].')';
			$form .= Xml::closeElement( 'fieldset' ) .'</td></tr>';
			continue; // exit foreach
		}
		
		# the next lines display checkboxes and eventually check them

		$check_next = false;
		$prev = '';
		foreach( $wgRestrictionsLevelsHierarchy as $current_level) {
			$checked = $check_next || ( ($current_level=='' && $rest===array()) ?
							true	// everyone is checked by default
							: in_array( $current_level.'-level', $rest ) ); 
			$current_level = $current_level=='' ? 'everyone' : $current_level;
			$form .= $prev;
			$form .= Xml::checkLabel( 
					wfMsg( "setpermissions-$current_level" ), 
					"check-$type-$current_level", 
					"check-$type-$current_level", 
					$checked ) . $br;
			$prev = ($prev==''?'&nbsp;&nbsp;+--':'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') . $prev;
			$check_next = $checked;
		}
		
		// we arrive here if user can change the permissions
	
		$form .= Xml::closeElement( 'fieldset' ) .'</td></tr>';

	}
	
	$form .= Xml::closeElement( 'tbody' ) . Xml::closeElement( 'table' );
	
	$form .= Html::hidden( 'wpToken', $token );
	$form .= $br . Xml::submitButton( wfMessage( 'setpermissions-confirm' ) );
	$form .= Xml::closeElement( 'form' );
	return $form;
}