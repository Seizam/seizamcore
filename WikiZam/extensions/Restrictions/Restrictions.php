<?php
// TODO:
//   backward compatible,
//   configuration,
//   user-> check si user appartient groupe
//   change name to SetPermissions



# ======================
#    CONFIGURATION VARS

// Which groups are accessible from setpermission action ? (the order in IMPORTANT)
// we assign only one group per couple page/action restriction
// so, if the usser check multiple group in SetPermissions form, the first group found in
// $wgRestrictionsGroups will be used to assign the restriction
// (ex: 'user' + 'owner' checked == restriction to 'user')
// by default:
//  # group '' (=everyone) is virtual
//  # group 'owner' is virtual and used only by this extension (never stored in db)
// any other group added to this var need to exist (in $wgGroupPermissions)
// NOTE: $wgRestrictionLevels will be updated in order for theses level to be accessed via protect
$wgRestrictionsGroups = array( '', 'owner'); 

// Can sysop users do everything on all restricted pages? default: yes(true)
$wgSysopsCanBypassRestrictions = true; 



# ==================================== 
#   REGISTERING EXTENSION

$wgExtensionCredits['validextensionclass'][] = array(
   'path' => __FILE__,
   'name' => 'Restrictions',
   'author' =>'Yann Missler', 
   'url' => 'http://www.seizam.com', 
   'description' => 'This extension override initial MediaWiki\'s inital restrictions system',
   'version'  => 'alpha',
   );

$wgResourceModules['ext.Restrictions'] = array(
        'scripts' => 'myExtension.js',
        'styles' => 'myExtension.css',
        'dependencies' => array( 'jquery.cookie', 'jquery.tabIndex' ),
        'localBasePath' => dirname( __FILE__ ),
        'remoteExtPath' => 'MyExtension',
);


// load messages file
$wgExtensionMessagesFiles['Restrictions'] = dirname( __FILE__ ) . '/Restrictions.i18n.php';

// add our own userCan hook
$wgHooks['userCan'][] = 'efRestrictionsUserCan';

// this hook is call when mediawiki request the rights of a user, it can give more 
// rights than mediawiki does, in our case, it depends if the user is the owner
// it can also give the protect right while updating restrictons
$wgHooks['UserGetRights'][] = 'efRestrictionsUserGetRights';

// add an item to the action menu
$wgHooks['SkinTemplateNavigation::Universal'][] = 'efRestrictionsMakeContentAction';

// add a non native action to mediawiki
$wgHooks['UnknownAction'][] = 'efRestrictionsForm';

// registering our defered setup function
$wgExtensionFunctions[] = 'efRestrictionsSetup';

// internal variable
// tells to our own UserGetRight hook implementation if we need to grant protect right
// (required when updating article restriction when validating SetPermissions form)
$wgSetPermissionsDoProtect = false;
    


# ==================================== 
#   CONFIGURING MEDIAWIKI


# 1) immediate setup

// registered users can set permissions to pages they own
$wgGroupPermissions['user']['setpermissions'] = true;
$wgAvailableRights[] = 'setpermissions'; 

// add the read rights to restricted actions list
$wgRestrictionTypes[] = "read";


# 2) defered setup 

function efRestrictionsSetup() {

	global $wgRestrictionsGroups, $wgRestrictionLevels;
	//TODO: refactore to use merge
	foreach ($wgRestrictionsGroups as $group) {
		// if not virtual, add the group to "protect"'s accessible levels
		if (!in_array($group, $wgRestrictionLevels)) {
			$wgRestrictionLevels[] = $group;
		}
		// TODO
		// if not in wgGroupPermission > create it
	}
}



# ==================================== 
#   THE CODE

function efRestrictionsIsUserInGroup($user, $group) {
	$user_groups = $user->getEffectiveGroups();
	return in_array($group, $user_groups);
}

function efRestrictionsUserCan( $title, &$user, $action, &$result ) {
	
	wfDebugLog( 'restrictions', 'UserCan: title="'
		.$title->getLocalURL().'"('.$title->getArticleId().') user="'
		.$user->getName().'"('.$user->getID().') action="'.$action.'"');
	
	$act = $action;

	//just in case, but shouldn't occur
	if ( $action == '' || $action == 'view' ) { 
		$act = 'read' ; 
	}

	// fetch restriction and user groups from MediaWiki core
	$title_restrictions = $title->getRestrictions( $act );

	// if no restrictions, return "the $user can do $action on $title"
	if ( $title_restrictions === array() ) {
		wfDebugLog( 'restrictions', 'UserCan: action not restricted');
		//$result = true;		// allowed
		return true;		// continue userCan hooks processing (another hook can still disallow user)
	}

	// there should only be one restriction level per page/action 
	// (in MediaWiki core, if multiple levels, user has to be in every restricted level... not very logic, but it's like that)
	// if not... well, we handle this the same way as in MediaWiki core, but that's bad!
	if (count($title_restrictions) != 1) {
		wfDebugLog( 'restrictions', 'UserCan: /!\ few restricted level for only a page/action '
				. '("'.$act.'" restricted to "'.implode(',',$title_restrictions).'")');
		wfDebug('"Restrictions extension">>UserCan(): /!\ few restricted level for only a page/action '
				. '("'.$act.'" restricted to "'.implode(',',$title_restrictions).'")');
	}
	
	// please kick this foreach or change Title.php>checkPageRestrictions() code... we should not test intersection !!!
	$result = true;
	global $wgSysopsCanBypassRestrictions; // if user is a sysop, and config says that they bypass restrictions, will always allow
	foreach ($title_restrictions as $title_restriction) {
		
		// intersection test = if we know that user is not allowed for one, do not test other restrictions
		if (!$result)
			break; // exit foreach
				
		if ($title_restriction == 'owner') {
			// check if the current user is the owner
			$result = efRestrictionsIsOwner( $title, $user);

		} else {
			// allow if $user is member of the group
			$result = efRestrictionsIsUserInGroup($user, $title_restriction);	
		}
	
	}
		
	// TODO: change from "sysop group" to "protect right" or "override_restrictions right" ...
	if ( efRestrictionsIsUserInGroup($user, "sysop") && $wgSysopsCanBypassRestrictions) {
		wfDebugLog( 'restrictions', 'UserCan: YES (user is in group sysop, and configuration says that sysops bypass restrictions)');
		$result = true;		// allowed
	}
	
	wfDebugLog( 'restrictions', 'UserCan: '.($result?'YES':'NO')
		. ' ("'.$act.'" restricted to "'.implode(',',$title_restrictions).'")');
	
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
	    // no hook  or  no hook functions stopped processing, so we have use the default method:
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
 * Dynamically assign "protect" right when needed 
 * @global type $wgTitle
 * @global type $wgSetPermissionsDoProtect
 * @param type $user
 * @param type $aRights
 * @return type 
 */
function efRestrictionsUserGetRights( $user, &$aRights ) {
	
	global $wgTitle;

	// When updating title's restriction, "protect" and user's group rights are needed
	// so when flushing rights (in Form() code), the next lines grant the rights.
	// Activated by setting $wgSetPermissionsDoProtect to true.
	global $wgSetPermissionsDoProtect, $wgRestrictionsGroups;
	if ( $wgSetPermissionsDoProtect ) {
		
		wfDebugLog( 'restrictions', 'UserGetRights: granting "protect" right');
		$aRights[] = 'protect';
		
		$to_grant = array_intersect($wgRestrictionsGroups, $user->getEffectiveGroups());
		
		foreach ($to_grant as $group) {
			// add the group to user right
			// (not very clean, but this is how MediaWiki manage restrictions)
				wfDebugLog( 'restrictions', 'UserGetRights: granting "'.$group.'" right');
				$aRights[] = $group;
		}
		
		if ($user->isLoggedIn() && efRestrictionsIsOwner( $wgTitle , $user ) ) {
				wfDebugLog( 'restrictions', 'UserGetRights: granting "owner" right');
				$aRights[] = 'owner';
		}
		
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

	// is the user allowed to use setpermissions
	if ( !$wgUser->isAllowed( 'setpermissions' ) ) {
		$wgOut->permissionRequired( 'setpermissions' );
		return false; //stop processing	
	} 
	
	# user is allowed to use setpermissions
	
	// is the user the owner?
	if ( !efRestrictionsIsOwner( $article->getTitle() , $wgUser ) ) {
		// user is not the owner of the page
		$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
		$wgOut->addWikiMsg( 'setpermissions-notowner' );
		return false; //stop processing	
	} 
	
	# user is the owner
	
	// start displaying page
	$wgOut->setPageTitle( wfMsg( 'setpermissions' ) );

	// do we have a request to handle?
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

	# ok, so let's change restrictions!
	
	$new_restrictions = array();
	$expiration = array();
	$expiry = Block::infinity(); // the restriction will never expire

	// we load the title specific available restrictions
	$applicableRestrictionTypes  = $article->getTitle()->getRestrictionTypes();
	wfDebugLog( 'restrictions'
			, 'Form: applying permissions, available title restrictions = '
			.implode(',', $applicableRestrictionTypes));

	// for each of theses available restrictions
	foreach ( $applicableRestrictionTypes as $action ) {  // 'read', 'upload', ...
		
		$title = $article->getTitle();
		$current_restrictions = $title->getRestrictions( $action ); //'sysop', 'owner', ...
		
		wfDebugLog( 'restrictions', 'Form: current title restriction "'
				.$action.'" restricted to level(s) "'.implode(',', $current_restrictions).'"');

		// does the title have already a restriction ?
		if ( $current_restrictions !== array() ) {
			
			// check that the user can change the current restriction(s)
			// so, if there is multiple restrictions (for one action), user need to
			// satisfy all current restrictions in order to change at least on of them
			// (maybe, this behviour can be improved)
			// (the mediawiki check that the user satisfy all to allow an action... that's it)
			$stop = false;
			foreach ($current_restrictions as $current_restriction) {
				
				// if we found that the user is not in one of the restrictions, no more need to check others
				if ($stop)
					continue; //end prematurely this foreach iteration
				
				if ( !efRestrictionsIsUserInGroup($wgUser, $current_restriction) &&
						( $current_restriction!='owner' || !efRestrictionsIsOwner($title, $wgUser)) )  {
					// if the user is not in one of the restrictions, we keep the previous restrictions
					$new_restrictions[$action] = $current_restrictions;
					// need no more to test other restrictions for this action
					$stop = true;
					continue; //end this foreach iteration
				}
		
			}
		}

		// set expiry 
		$expiration[$action] = $expiry;
		
		# we arrive here if user can change the restrictions

		// by default, restricted to 'owner'
		$new_restrictions[$action] = 'owner'; 
		
		// check what's checked, taking account $wgRestrictionsGroups order 
		global $wgRestrictionsGroups;
		$stop_foreach = false;
		foreach( $wgRestrictionsGroups as $current_level) {
			
			// end the iteration if requested
			// TO DO: change this exit to something prettier if possible
			if ($stop_foreach)
				continue;
			
			// 'everyone' = ''
			$current_level = ($current_level=='' ? 'everyone' : $current_level);
			
			wfDebugLog( 'restrictions', 'Form: get check "'."check-$action-$current_level\""
					.print_r($wgRequest->getCheck( "check-$action-$current_level" ), true) );
			
			if ( $wgRequest->getCheck( "check-$action-$current_level" ) ) {
				$new_restrictions[$action] = 
					($current_level=='everyone' ? 
						'' :
						$current_level); 
				// we have found the restriction to apply, so exit foreach
				$stop_foreach = true; 
			}
		}

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
				'restrictions' => $new_restrictions, 
				'reason' => 'MediaWiki extension Restrictions', 
				'cascade' => $cascade, 
				'expiry' => $expiration ), true ));

	$success = $article->updateRestrictions(
		$new_restrictions,				// array of restrictions
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
	
	global $wgUser, $wgRestrictionsGroups;
	$applicableRestrictionTypes  = $title->getRestrictionTypes(); // this way, do not display create for exsiting page
	
	wfDebugLog( 'setpermissions', 'MakeForm(): $title->getRestrictionTypes() = '. implode(',',$applicableRestrictionTypes));
	
	$token = $wgUser->editToken();
	$br = Html::element( 'br' );
	
	$form  = Html::rawElement( 'p', array(), htmlspecialchars( wfMsg( 'setpermissions-intro' ) ) );
	$form .= Html::openElement('form', array( 
					'method' => 'post',
					'action' => $title->getLocalUrl( 'action=setpermissions' ) ) );
	$form .=	Xml::openElement( 'table') .
				Xml::openElement( 'tbody' ) . '<tr>';

	// for each of theses available restrictions
	foreach ( $applicableRestrictionTypes as $action ) {  // 'read', 'upload', ...

		$title_action_restrictions = $title->getRestrictions( $action ); //'sysop', 'owner', ...

		wfDebugLog( 'restrictions', 'Form: current title restriction "'
				.$action.'" restricted to level(s) "'.implode(',', $title_action_restrictions).'"');

		$form .= '<td>'.Xml::openElement( 'fieldset' );
		$form .= Xml::element( 'legend', null, wfMsg( "setpermissions-whocan-$action") ) ;
		
		// this foreach normally does only one iteration, but MediaWiki core can handle
		// multiple restrictions per one action... but the current code is not optimised
		// for that case
		// please review this if you have mutliple restrictions per action per page
		$stop = false;
		foreach ( $title_action_restrictions as $one_restriction ) {
			
			wfDebugLog( 'restrictions', 'Form: handling "'.$one_restriction.'"');
			
			if ($stop)
				continue; // end foreach iteration
		
			$result = true;
			
			// if restricted to a level that the user can't set, display only a message
			if (!efRestrictionsIsUserInGroup($wgUser, $one_restriction) &&
					( $one_restriction!='owner' || !efRestrictionsIsOwner($title, $wgUser))) {
				
				$form .=  wfMsg( "restriction-level-$title_action_restrictions[0]" ).' ('.$title_action_restrictions[0].')';
				$form .= Xml::closeElement( 'fieldset' ) .'</td>';
				
				// restrictions are inclusive (see Title.php>checkPageRestrictions()
				// so no more iteration needed
				$stop = true; 
			}
			
		}
		
		// if the user cannot change a restriction, we prematurely this iteration
		// because we cannot add any checkboxe for that action
		if ($stop)
			continue; // end foreach iteration
		
		# the next lines display checkboxes and eventually check them

		foreach( $wgRestrictionsGroups as $current_level) {
			$checked = ( ($current_level=='' && $title_action_restrictions===array()) ?
							true	// everyone is checked by default
							: in_array( $current_level, $title_action_restrictions ) ); 
			$current_level = $current_level=='' ? 'everyone' : $current_level;
			$form .= Xml::checkLabel( 
					wfMsg( "setpermissions-$current_level" ), 
					"check-$action-$current_level", 
					"check-$action-$current_level", 
					$checked ) . $br;
			$check_next = $checked;
		}
		
		// we arrive here if user can change the permissions
	
		$form .= Xml::closeElement( 'fieldset' ) .'</td>';

	}
	
	$form .= '</tr>' . Xml::closeElement( 'tbody' ) . Xml::closeElement( 'table' );
	
	$form .= Html::hidden( 'wpToken', $token );
	$form .= $br . Xml::submitButton( wfMessage( 'setpermissions-confirm' ) );
	$form .= Xml::closeElement( 'form' );
	return $form;
}