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

// this hook is called when mediawiki request the rights of a user, it can give more 
// rights than mediawiki does, in our case, it depends if the user is the owner
// it can also give the protect right while updating restrictons
$wgHooks['UserGetRights'][] = 'efRestrictionsUserGetRights';

// add an item to the action menu
$wgHooks['SkinTemplateNavigation::Universal'][] = 'efRestrictionsMakeContentAction';

// add a non native action to mediawiki
$wgHooks['UnknownAction'][] = 'efRestrictionsForm';

// secure from viewing a transclusion if the template forbidden it
// Available from MW version 1.10.1: "Allows an extension to specify a version of a page to get for inclusion in a template?"
$wgHooks['BeforeParserFetchTemplateAndtitle'][] = 'efRestrictionsBeforeParserFetchTemplateAndtitle';

// registering our defered setup function
$wgExtensionFunctions[] = 'efRestrictionsSetup';


# internal variable

// tells to our own UserGetRight hook implementation if we need to grant protect right
// (required when updating article restriction when validating SetPermissions form)
$wgSetPermissionsDoProtect = false;

// Used for cache
// store result to avoid checking restrictions again
// [user_id][title_id][action] = true(=allowed) / false(=disallowed)
$wgRestrictionsUserCan = array();


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

	global $wgRestrictionsGroups, $wgRestrictionLevels,$wgGroupPermissions;
	//TODO: refactore to use merge
	foreach ($wgRestrictionsGroups as $group) {
		// if not virtual, add the group to "protect"'s accessible levels
		if (!in_array($group, $wgRestrictionLevels)) {
			$wgRestrictionLevels[] = $group;
		}
		// TODO
		// if not in wgGroupPermission > create it
		if ($group!='' && $group!='owner' && !array_key_exists($group,$wgGroupPermissions)) {
			wfDebugLog( 'restrictions', 'Setup: /!\ creating user group "'.$group.'"');
			$wgGroupPermissions[$group] = array();
		}
	}
}



# ==================================== 
#           THE CODE
# ====================================

function efRestrictionsIsUserInGroup($user, $group) {
	return in_array($group, $user->getEffectiveGroups());
}

// when the parser process a page and find a template(=transclusions?), this hook is called
// so we can skip this transclusion or specifiy another revision of it
function efRestrictionsBeforeParserFetchTemplateAndtitle( $parser, Title $title, &$skip, &$id ) {
	
	if ( !( $parser instanceof Parser ) ) {
		wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: NOTHING TO DO (no parser given)');
		return true; // nothing to do
	}
	
	if ( $title->getNamespace() < 0 || $title->getNamespace() == NS_MEDIAWIKI ) {
		wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: NOTHING TO DO (bug 29579 for NS_MEDIAWIKI)');
		return true; // nothing to do (bug 29579 for NS_MEDIAWIKI)
	}
	
	// we have to know if the user can read this template
	$result = true;
	global $wgUser;
	wfRunHooks( 'userCan', array( $title, &$wgUser, 'read', &$result ) );
	$skip = !$result;	// skip  if  she can't
	
	wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: '
			.($skip ? "SKIP (=do not display) THE TEMPLATE" : "CAN FETCH THE TEMPLATE")
			.' (title="'.$title->getPrefixedDBkey()
			.'"('.$title->getArticleId().') $skip="'.var_export($skip, true)
			.'" $id="'.var_export($id, true).'")');
	
	// return true (=continue processing) when do not skip the transclusion 
	// return false  (=stop processing)   when     skip    the transclusion 
	return !$skip;	
	
}

// donner le droit dans le userCan
// 
// meme si le pageRestrictions ne restreint pas alors qu'il devrait
// ,mon code lui sera juste et fera le bon test d'appartenance à un groupe
// 
// avant l'exécution (problématique) de title->checkPagesRestriction, 
// le hook userCan est appelé dans Title->checkPermissionHooks
// donc quoiqu'il arrive, mon bon usercan aura la main
function efRestrictionsUserCan( $title, &$user, $action, &$result ) {
	
	// userCan is called before title->checkPageRestrictions
	
	$act = $action;

	//just in case, but shouldn't occur
	if ( $action == '' || $action == 'view' ) { 
		$act = 'read' ; 
	}
	
	// fetch restriction and user groups from MediaWiki core
	$title_restrictions = $title->getRestrictions( $act );

	// if no restrictions, return "the $user can do $action on $title"
	if ( $title_restrictions === array() ) {
		wfDebugLog( 'restrictions', 'UserCan: action not restricted, resume other hooks ('
				.$user->getName().'['.$user->getID().'] '.$action.' "'
				.$title->getPrefixedDBkey().'"['.$title->getArticleId().']');
		return true;		// continue userCan hooks processing (another hook can still disallow user)
	}
	
	
	// update user right about the current title
	// (not pretty, but needed because this is the way MW core check restrictions...)
	efRestrictionsGrantRestrictionsRights( $title, $user );
	
	//check if cached
	global $wgRestrictionsUserCan;
	if (isset($wgRestrictionsUserCan[$user->getID()][$title->getArticleId()][$act])) {
		$result = $wgRestrictionsUserCan[$user->getID()][$title->getArticleId()][$act];
		wfDebugLog( 'restrictions', 'UserCan: CACHE HIT "'
				.($result?'YES':'NO').'" (title="'
			.$title->getPrefixedDBkey().'"['.$title->getArticleId().'] user="'
			.$user->getName().'"['.$user->getID().'] action="'.$action.'"]');
		
		return false;
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
	
	// store to cache
	$wgRestrictionsUserCan[$user->getID()][$title->getArticleId()][$act] = $result;
	
	wfDebugLog( 'restrictions', 'UserCan: CACHE MISS "'.($result?'YES':'NO'). '" ("'.$title->getPrefixedDBkey()
			.'"('.$title->getArticleId().')" '.$act.' restricted to "'.implode(',',$title_restrictions).'") ');

//	wfDebugLog( 'restrictions', wfGetPrettyBacktrace());

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
	
	wfDebugLog( 'restrictions', 'IsOwner: '.( $result ? 'YES' : 'NO').' (title="'.$title->getPrefixedDBkey()
			.'" user="'.$user->getName().'")');
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
	
	// When updating title's restriction, "protect" and user's group rights are needed
	// so when flushing rights (in Form() code), the next lines grant the rights.
	// Activated by setting $wgSetPermissionsDoProtect to true.
	global $wgSetPermissionsDoProtect, $wgTitle;
	
	if ( $wgSetPermissionsDoProtect ) {
		
		wfDebugLog( 'restrictions', 'UserGetRights: granting "protect" right to '.$user->getName()
				.' (title="'.$wgTitle->getPrefixedDBkey().'")');
		$aRights[] = 'protect';
		$aRights = array_unique( $aRights );
		
	}
	
	efRestrictionsGrantRestrictionsRights( $wgTitle, $user, $aRights );
	
	return true;	// don't stop hook processing
	
}

function efRestrictionsGrantRestrictionsRights( $title, $user, &$aRights = null ) {
	
	if ($aRights==null) {
		$aRights = &$user->mRights;
	} 
	
	if ($aRights==null) {
		wfDebugLog( 'restrictions', 'GrantRestrictionsRights: need to fetch rights by calling getRights()');
		$user->mRights = null;    // clear current user rights (and clear the "protect" right
		$user->getRights();
		$aRights = &$user->mRights;
	}
	
	if ($aRights==null) {
		wfDebugLog( 'restrictions', 'GrantRestrictionsRights: /!\ $aRights is still null, something goes wrong');
	}
	
	# grant group right if the user is in group
	# this is not pretty, but this is how MediaWiki manage restrictions (see Title.php->checkPageRestrictions)

	global $wgRestrictionsGroups;
	$to_grant = array_intersect($wgRestrictionsGroups, $user->getEffectiveGroups());
	foreach ($to_grant as $group) {
		// add the group to user right
		// (not very pretty, but this is how MediaWiki manage restrictions)
		if (!in_array($group, $aRights)) {
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: granting "'.$group.'" right to '
					.$user->getName());
			$aRights[] = $group;
		}
	}

	if ($user->isLoggedIn() && efRestrictionsIsOwner( $title , $user ) ) {

		# user is owner of the current title

		if (!in_array('owner', $aRights)) {
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: granting "owner" right to '
					.$user->getName().' (title="'.$title->getPrefixedDBkey().'")');
			$aRights[] = 'owner';
		}

	} else {

		# user is not owner of the current title

		if(in_array('owner', $aRights)) {
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: removing "owner" right to '
					.$user->getName().' (title="'.$title->getPrefixedDBkey().'")');
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: ..rights before: '.implode(',',$aRights));
			unset($aRights[array_search('owner',$aRights)]); // remove owner
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: ..rights after: '.implode(',',$aRights));
		}

	}

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
			foreach ($current_restrictions as $current_restriction) {
				
				if ( !efRestrictionsCanTheUserSetToThisLevel($wgUser, $title, $current_restriction) )  {
					
					// if the user cannot set this restriction, we keep the previous restrictions
					$new_restrictions[$action] = $current_restrictions;
					
					break; //end foreach 
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
		foreach( $wgRestrictionsGroups as $current_level) {
			
			// convert from BACK-END to FRONT-END: 'everyone' = ''
			$current_level = ($current_level=='' ? 'everyone' : $current_level);
			
			wfDebugLog( 'restrictions', 'Form: get check "'."check-$action-$current_level\""
					.print_r($wgRequest->getCheck( "check-$action-$current_level" ), true) );
			
			// is the checkbox $action/$current_level checked ?
			if ( $wgRequest->getCheck( "check-$action-$current_level" ) ) {
				
				// convert from FRONT-END to BACK-END
				$current_level = ( $current_level=='everyone' ? '' : $current_level);
				
				// can the user set to this level?
				if (efRestrictionsCanTheUserSetToThisLevel($wgUser, $title, $current_level) )  {

					// so we can set the restriction to it			
					$new_restrictions[$action] = $current_level;
					
					// we have found the restriction to apply, so exit foreach
					// (we apply only one restriction, but it chould be possible to apply few ones)
					break;
					
				} else {
					
					# the user wanted to restrict the action to a level, in which she is not
					# what to do? diplay an error message? 
					# if no code in this block, we will resume checkboxes getting values,
					# and set to restriction level 'owner' if no one else checked
					
				}
			}
		}

	} // END foreach $applicableRestrictionTypes

	// don't cascade the owner restriction, because a subpage may not have the same owner
	// so casacing won't make sens, and can be very problematic
	// don't change this unless you know serioulsy what you are doing !!!
	$cascade = false;
		
	# temporary assign protect right, in order to update the restricitons

	global $wgSetPermissionsDoProtect;
	$wgSetPermissionsDoProtect = true;  // tells spSetPermissionsAssignDynamicRights to add the "protect" right
	$wgUser->mRights = null;			// clear current user rights
	$wgUser->getRights();				// force rights reloading
	$wgSetPermissionsDoProtect = false;

	wfDebugLog( 'restrictions', "Form: updating restrictions to\n "
			.var_export( array( 
				'restrictions' => $new_restrictions, 
				'reason' => 'MediaWiki extension Restrictions', 
				'cascade' => $cascade, 
				'expiry' => $expiration ), true ));
	
	// update restrictions
	$success = $article->updateRestrictions(
		$new_restrictions,				// array of restrictions
		'Restrictions extension',	// reason
		$cascade,					// cascading protection disabled, need to pass by reference
		$expiration					// expiration
	);  // note that this article function check that the user has sufficient rights

	# force reloading page's restrictions
	wfDebugLog( 'restrictions', 'Form: purge title\'s restrictions then force reload ');
	$article->getTitle()->mRestrictions = array();
	$article->getTitle()->mRestrictionsLoaded = false;
	$article->getTitle()->loadRestrictions();
	
	# remove temporary assigned protect right
	wfDebugLog( 'restrictions', 'Form: purge user\'s rights then force reload');
	$wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
	$wgUser->getRights();	    // force rights reloading

	# clear userCan cache (needed because of protect right granted few instants)
	wfDebugLog( 'restrictions', 'Form: purge userCan cached values (will be reloaded when hit miss)');
	global $wgRestrictionsUserCan;
	$wgRestrictionsUserCan = array();
	
	// display error/succes message
	if ( $success ) {
		$wgOut->addWikiMsg( 'setpermissions-success' );
	} else {
		$wgOut->addWikiMsg( 'setpermissions-failure' );
	}
	
	// re-display the setpermissions form with the current restrictions (reloaded above)
	$wgOut->addHTML( efRestrictionsMakeForm( $article->getTitle() ) );
	
	// stop hook processing, and doesn't throw an error message
	return false;

}


function efRestrictionsMakeForm( $title ) {
	
	global $wgUser, $wgRestrictionsGroups;
	$applicableRestrictionTypes  = $title->getRestrictionTypes(); // this way, do not display create for exsiting page
		
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

		$form .= '<td>'.Xml::openElement( 'fieldset' );
		$form .= Xml::element( 'legend', null, wfMsg( "setpermissions-whocan-$action") ) ;
		
		// this foreach normally does only one iteration, but MediaWiki core can handle
		// multiple restrictions per one action... but the current code is not optimised
		// for that case
		// please review this if you have mutliple restrictions per action per page
		$stop = false;
		foreach ( $title_action_restrictions as $current_restriction ) {
			
			// if restricted to a level that the user can't set, display only a message
			if (!efRestrictionsCanTheUserSetToThisLevel($wgUser, $title, $current_restriction)) {
				
				$form .=  wfMsg( "restriction-level-$title_action_restrictions[0]" ).' ('.$title_action_restrictions[0].')';
				$form .= Xml::closeElement( 'fieldset' ) .'</td>';
				
				// restrictions are inclusive (see Title.php>checkPageRestrictions()
				// so no more iteration needed
				$stop = true; 
				break;
			}
			
		}
		
		// if the user cannot change a restriction, we prematurely end this iteration
		// because we cannot add any checkboxe for that action
		if ($stop)
			continue; // end foreach iteration
		
		# the next lines display checkboxes and eventually check them

		foreach( $wgRestrictionsGroups as $current_level) {
			
			// if the level is not selectable, do not display the checkboxe
			if (!efRestrictionsCanTheUserSetToThisLevel($wgUser, $title, $current_level)) { 
				continue; // end foreach iteration
			}
			
			$checked = ( ($current_level=='' && $title_action_restrictions===array()) ?
					// everyone is checked if there is currently no restrictions
					true	
					// else, check if there is a restriction to this level
					: in_array( $current_level, $title_action_restrictions ) ); 
			
			// convert from BACK-END to FRONT-END
			$current_level = ( $current_level=='' ? 'everyone' : $current_level);
			
			$form .= Xml::checkLabel( 
					wfMsg( "setpermissions-$current_level" ), 
					"check-$action-$current_level", 
					"check-$action-$current_level", 
					$checked ) . $br;

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


function efRestrictionsCanTheUserSetToThisLevel($user, $title, $level) {
			
	return ( 
			//    user in group
			efRestrictionsIsUserInGroup($user, $level) 
			// OR level everyone
			|| $level == ''	
			// OR ( level is owner AND user is the owner of the title )
			|| ( $level=='owner' && efRestrictionsIsOwner($title, $user) ) ) ;
			
}