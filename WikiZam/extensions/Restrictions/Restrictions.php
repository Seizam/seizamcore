<?php

/*
 * Restrictions extension, developped by Yann Missler, Seizam SARL
 * www.seizam.com
 */

if (!defined('MEDIAWIKI')) {
	echo "Restrictions extension\n";
    die(-1);
}

# ======================
#    CONFIGURATION VARS

// Which groups are accessible from setpermission action ? (the order in IMPORTANT)
// we assign only one group per couple page/action restriction
// so, if the usser check multiple group in SetRestrictions form, the first group found in
// $wgRestrictionsGroups will be used to assign the restriction
// (ex: 'user' + 'owner' checked == restriction to 'user')
// by default:
//  # group '' (=everyone) is virtual
//  # group 'owner' is virtual and used only by this extension (never stored in db)
// any other group added to this var need to exist (in $wgGroupPermissions)
// NOTE: $wgRestrictionLevels will be updated in order for theses level to be accessed via protect
$wgRestrictionsGroups = array( '', 'owner');

// This value is used in other extensions to link to the restrictions interface
define('RESTRICTIONS_ACTION', 'setrestrictions');


# ==================================== 
#   REGISTERING EXTENSION

$wgExtensionCredits['validextensionclass'][] = array(
   'path' => __FILE__,
   'name' => 'Restrictions',
   'author' => array('Yann Missler', 'Seizam'), 
   'url' => 'http://www.seizam.com', 
   'description' => 'This extension overrides MediaWiki\'s inital restrictions system.',
   'version'  => 'alpha',
   );

/*
$wgResourceModules['ext.Restrictions'] = array(
        'scripts' => 'myExtension.js',
        'styles' => 'myExtension.css',
        'dependencies' => array( 'jquery.cookie', 'jquery.tabIndex' ),
        'localBasePath' => dirname( __FILE__ ),
        'remoteExtPath' => 'MyExtension',
);
 */


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

// clear page cache when restrictions have been changed, in order to remove text 
// from any cache if read is restricted
$wgHooks['ArticleProtectComplete'][] = 'efRestrictionsArticleProtectComplete';

// called when mediawiki updates its search engine cache
// remove page text if it is read restricted
$wgHooks['SearchUpdate'][] = 'efRestrictionsSearchUpdate';

// add a "read restrictions" check when trying to add a page to a watchlist
$wgHooks['WatchArticle'][] = 'efRestrictionsWatchArticle';


// registering our defered setup function
$wgExtensionFunctions[] = 'efRestrictionsSetup';


# internal variables

// tells to our own UserGetRight hook implementation if we need to grant protect right
// (required when updating article restriction when validating SetRestrictions form)
$wgSetRestrictionsDoProtect = false;

// Used for caching usercan result
// store result to avoid checking restrictions again
// [user_id][title_id][action] = true(=allowed) / false(=disallowed)
$wgRestrictionsUserCanCache = array();
// Used for caching isowner result
// [user_id][title_id] = true(=owner) / false(=not owner)
$wgRestrictionsIsOwnerCache = array();



# ==================================== 
#   CONFIGURING MEDIAWIKI


# 1) immediate setup

$wgAvailableRights[] = 'setrestrictions'; 
// registered users can set permissions to pages they own
$wgGroupPermissions['user']['setrestrictions'] = true;

$wgAvailableRights[] = 'bypassrestrictions'; 
// sysops bypass restrictions
$wgGroupPermissions['sysop']['bypassrestrictions'] = true;

// add the read rights to restricted actions list
$wgRestrictionTypes[] = "read";

// disable automatic summary, because api.php can always see comments, even if the page is read restricted
// so, only protection log and user's comment are visible via history
$wgUseAutomaticEditSummaries = false;

// NEW EXTENSION'S HOOK
// this hook is called if an extension wants to set restrictions to a page
$wgHooks['SetRestrictions'][] = 'efRestrictionsSetRestrictions';


# 2) defered setup 

function efRestrictionsSetup() {

	global $wgRestrictionsGroups, $wgRestrictionLevels,$wgGroupPermissions;
	//TODO: refactore to use merge
	foreach ($wgRestrictionsGroups as $group) {
		// if not virtual, add the group to "protect"'s accessible levels
		if (!in_array($group, $wgRestrictionLevels)) {
			$wgRestrictionLevels[] = $group;
		}

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
		wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: NOTHING TO DO, no parser given');
		return true; // nothing to do
	}
	
	if ( $title->getNamespace() < 0 || $title->getNamespace() == NS_MEDIAWIKI ) {
		wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: NOTHING TO DO, bug 29579 for NS_MEDIAWIKI');
		return true; // nothing to do (bug 29579 for NS_MEDIAWIKI)
	}
	
	global $wgUser;
	
	// disallow translcusion of a read restricted page
	// because of cache, this protection may be bypassed if the first visitor of the page
	// satisfy restriction, but the next vistors don't
	// so, if there is a read restriction, skip this transclusion
	if ($title->isProtected('read')) {
/*		wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: "'
				.$title->getPrefixedDBkey().'"['.$title->getArticleId()
				.'] is read restricted, so it will be skipped');
*/
		$skip = true;
		
	} else {
		// because of cache, the following lines do not work properly
		// the usercan is only called when first caching, then it will never
		// be called again, because mediawiki will fetch the page from cache 
		// but, maybe someone will found this usefull
		/*
		$result = true;
		// we have to know if the user can read this template
		wfRunHooks( 'userCan', array( $title, &$wgUser, 'read', &$result ) );
		$skip = !$result;	// skip  if  she can't
		*/
	}
	
	wfDebugLog( 'restrictions', 'BeforeParserFetchTemplateAndtitle: '
			.($skip ? "SKIP" : "FETCH")
			.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
			.' id="'.var_export($id, true).'"'
			.' user="'.$wgUser->getName().'"['.$wgUser->getID().']'
			);
	// return true (=continue processing) when do not skip the transclusion 
	// return false  (=stop processing)   when     skip    the transclusion 
	return !$skip;	
	
}


/**
 * Grant rights as needed later by $title->checkPagesRestriction(). Hook userCan is always called
 * in function $title->checkPermissionHooks(), before calling checkPagesRestriction(). So, it ensures
 * that correct rights are available when checkPagesRestriction() will be executed.
 * @global array $wgRestrictionsUserCanCache
 * @param Title $title
 * @param User $user
 * @param string $action
 * @param boolean $result
 * @return boolean 
 */
function efRestrictionsUserCan( $title, &$user, $action, &$result ) {
	
	if ( ($title->getNamespace()==NS_SPECIAL) || ( ! $title->isKnown()) ) {
		return true; // skip
	}
	
	// userCan is called before title->checkPageRestrictions
	
	$act = $action;

	//just in case, but shouldn't occur
	if ( $action == '' || $action == 'view' ) { 
		$act = 'read' ; 
	}
	
	// update user right about the current title, especially the owner right
	// (not pretty, but needed because this is the way MW core check restrictions...)
	efRestrictionsGrantRestrictionsRights( $title, $user );
	
	// fetch restriction and user groups from MediaWiki core
	$title_restrictions = $title->getRestrictions( $act );

	// if no restrictions, return "the $user can do $action on $title"
	if ( $title_restrictions === array() ) {
		wfDebugLog( 'restrictions', 'UserCan: action not restricted, resume other hooks'
				.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
				.' isKnown()='.($title->isKnown()?'YES':'NO')
				.' user="'.$user->getName().'"['.$user->getID().']'
				.' action="'.$action.'"'
				);
		return true; // continue userCan hooks processing (another hook can still disallow user)
	}
	
	//check if cached
	global $wgRestrictionsUserCanCache;
	if (isset($wgRestrictionsUserCanCache[$user->getID()][$title->getArticleId()][$act])) {
		$result = $wgRestrictionsUserCanCache[$user->getID()][$title->getArticleId()][$act];
/*		wfDebugLog( 'restrictions', 'UserCan: '.($result?'YES':'NO').', CACHE HIT '
				.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
				.' isKnown()='.($title->isKnown()?'YES':'NO')
				.' user="'.$user->getName().'"['.$user->getID().']'
				.' action="'.$action.'"'
				);
*/		
		return false; //stop processing
	}

	// there should only be one restriction level per page/action 
	// (in MediaWiki core, if multiple levels, user has to be in every restricted level... not very logic, but it's like that)
	// if not... well, we handle this the same way as in MediaWiki core, but that's bad!
	if (count($title_restrictions) != 1) {
		wfDebugLog( 'restrictions', 'UserCan: /!\ few restricted level for only a page/action '
				. '("'.$act.'" restricted to "'.implode(',',$title_restrictions).'")');
		wfDebug('Restrictions.extension>>UserCan(): /!\ few restricted level for only a page/action '
				. '("'.$act.'" restricted to "'.implode(',',$title_restrictions).'")');
	}
	
	// please kick this foreach or change Title.php>checkPageRestrictions() code... we should not test intersection !!!
	$result = true;
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
		
	// if the user has a bypass, she is allowed
	if ( $user->isAllowed( 'bypassrestrictions' ) ) {
		wfDebugLog( 'restrictions', 'UserCan: YES, user has "bypassrestrictions" right');
		$result = true;		// allowed
	}
	
	// store to cache
	$wgRestrictionsUserCanCache[$user->getID()][$title->getArticleId()][$act] = $result;
	
	wfDebugLog( 'restrictions', 'UserCan: '.($result?'YES':'NO'). ', CACHE MISS '
			.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
			.' isKnown()='.($title->isKnown()?'YES':'NO')
			.' user="'.$user->getName().'"['.$user->getID().']'
			.' action="'.$action.'"'
			.' restrictions="'.implode(',',$title_restrictions).'"'
			);

	// stop processing
	return false;
	
}

function efRestrictionsIsOwner( $title, $user ) {
	
//		wfDebugLog( 'restrictions', 'IsOwner: '.  wfGetPrettyBacktrace());
    
	if ( (!$title instanceOf Title) || ( $user->getID() === 0 ) || ($title->isSpecialPage()) ) {
	    return false; 
	}
	
	global $wgRestrictionsIsOwnerCache;
	if (isset($wgRestrictionsIsOwnerCache[$user->getID()][$title->getArticleId()])) {
		
		$result = $wgRestrictionsIsOwnerCache[$user->getID()][$title->getArticleId()];
		
/*		wfDebugLog( 'restrictions', 'IsOwner: '.( $result ? 'YES' : 'NO')
				.', CACHE HIT '
				.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
				.' user="'.$user->getName().'"['.$user->getID().']'
				);
*/		
		return $result;
	}
	
	// process custom hook IsOwner, in order for other extensions to fetch 
	// ownership using a different way 
	$result = false;
	if ( wfRunHooks( 'IsOwner', array( $title, $user, &$result ) ) ) {

		/*
		// no hook  or  no hook functions stopped processing, so we have use the default method:
		// looking for the first revisonner
		$id = $title->getArticleId();
		$dbr = wfGetDB( DB_SLAVE ); // grab the slave for reading
		$firstrevionnerid = $dbr->selectField( 'revision', 'rev_user',  array( 'rev_page' => $id ),
			__METHOD__, array( 'ORDER BY' => 'rev_timestamp ASC'  ) );

		$userid = $user->getID();
		$result = ( $userid == $firstrevionnerid );
		*/
		
		// hook has no answer
		$result = false;
	}
	
	// store to cache
	$wgRestrictionsIsOwnerCache[$user->getID()][$title->getArticleId()] = $result;
	
	wfDebugLog( 'restrictions', 'IsOwner: '.( $result ? 'YES' : 'NO')
//			.', CACHE MISS '
			.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
			.' user="'.$user->getName().'"['.$user->getID().']'
			);

	return $result ;

}


/**
 * Dynamically assign "protect" right when needed 
 * @global type $wgTitle
 * @global type $wgSetRestrictionsDoProtect
 * @param type $user
 * @param type $aRights
 * @return type 
 */
function efRestrictionsUserGetRights( $user, &$aRights ) {
	
	// When updating title's restriction, "protect" and user's group rights are needed
	// so when flushing rights (in Form() code), the next lines grant the rights.
	// Activated by setting $wgSetRestrictionsDoProtect to true.
	global $wgSetRestrictionsDoProtect, $wgTitle;
	
	if ( $wgSetRestrictionsDoProtect ) {
		
		wfDebugLog( 'restrictions', 'UserGetRights: GRANT "protect"'
				.' to '.$user->getName().'"['.$user->getID().']'
				);
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
	
	// if the user has a bypass, she will have all rights
	$bypass = $user->isAllowed( 'bypassrestrictions' );
	$to_grant;
	if ($bypass) {
		$to_grant = $wgRestrictionsGroups; // give all groups, even 'owner' and ''
		unset($to_grant[array_search('', $to_grant)]); // remove '' (=everyone) right
		wfDebugLog( 'restrictions'
				, 'GrantRestrictionsRights: user has "bypassrestrictions" right, give rights "'.
				implode(',',$to_grant).'" if needed');
	} else {
		$to_grant = array_intersect($wgRestrictionsGroups, $user->getEffectiveGroups());
	}
	
	foreach ($to_grant as $group) {
		// add the group to user right
		// (not very pretty, but this is how MediaWiki manage restrictions)
		if (!in_array($group, $aRights)) {
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: GRANT "'.$group.'"'
					.' to '.$user->getName().'"['.$user->getID().']'
					);
			$aRights[] = $group;
		}
	}
	
	// if the user bypasses, no need to check if we have to give her the 'owner' right, 
	// because she previoulsy get it with $to_grant array
	if ($bypass)
		return ;
	
	# grant owner right if the user is the owner of the current title

	if ($user->isLoggedIn() && efRestrictionsIsOwner( $title , $user ) ) {

		# user is owner of the current title

		if (!in_array('owner', $aRights)) {
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: GRANT "owner"'
					.' to '.$user->getName().'"['.$user->getID().']'
					.' for title "'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
					);
			$aRights[] = 'owner';
		}

	} else {

		# user is not owner of the current title

		if(in_array('owner', $aRights)) {
			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: REMOVE "owner"'
					.' to '.$user->getName().'"['.$user->getID().']'
					.' for title "'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
					);
//			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: ..rights before: '.implode(',',$aRights));
			unset($aRights[array_search('owner',$aRights)]); // remove owner
//			wfDebugLog( 'restrictions', 'GrantRestrictionsRights: ..rights after: '.implode(',',$aRights));
		}

	}

}


function efRestrictionsMakeContentAction( $skin, &$cactions ) {
	global $wgUser, $wgRequest;

	$title = $skin->getTitle();
	
	// if user has 'protect' right, she cannot use 'setrestrictions', but 'protect' instead'
	if ( efRestrictionsIsOwner( $title , $wgUser ) 
			&& $wgUser->isAllowed( 'setrestrictions' )
			&& !$wgUser->isAllowed( 'protect' ) ) {
		$action = $wgRequest->getText( 'action' );
		$cactions['actions']['setrestrictions'] = array(		
			'class' => $action == 'setrestrictions' ? 'selected' : false,
			'text' => wfMsg( 'setrestrictions' ),
			'href' => $title->getLocalUrl( 'action=setrestrictions' ),
		);
	}
	return true;
}


function efRestrictionsForm( $action, $article ) {
    
	if ( $action != 'setrestrictions' ) { // not our extension
		return true; //don't stop processing
	}
	
	global $wgOut, $wgUser, $wgRequest, $wgSetRestrictionsDoProtect;

	// is the user allowed to use setrestrictions
	if ( !$wgUser->isAllowed( 'setrestrictions' ) ) {
		$wgOut->permissionRequired( 'setrestrictions' );
		return false; //stop processing	
	} 
	
	# user is allowed to use setrestrictions
	
	$title = $article->getTitle();
	
	// is the user the owner?
	if ( !efRestrictionsIsOwner( $title , $wgUser ) ) {
		// user is not the owner of the page
		$wgOut->setPageTitle( wfMsg( 'errorpagetitle' ) );
		$wgOut->addWikiMsg( 'setrestrictions-notowner' );
		return false; //stop processing	
	} 
	
	# user is the owner
	
	// start displaying page
	$wgOut->setPageTitle( wfMsg( 'setrestrictions' ) );
	
	// as defined in Title.php, around lines 1550 (mw1.18.1), 
	// being authorized to 'protect' require being authorized to 'edit'
	/* Title.php >>
	 *  private function checkActionPermissions( $action, $user, $errors, $doExpensiveQueries, $short ) {
	 *	if ( $action == 'protect' ) {
	 *		if ( $this->getUserPermissionsErrors( 'edit', $user ) != array() ) {
	 *  ...
	 */

	# temporary assign protect right, in order to update the restricitons

	$wgSetRestrictionsDoProtect = true;  // tells spSetRestrictionsAssignDynamicRights to add the "protect" right
//	wfDebugLog( 'restrictions', 'Form: purge user\'s rights then force reload');
	$wgUser->mRights = null;			// clear current user rights
	$wgUser->getRights();				// force rights reloading
	$wgSetRestrictionsDoProtect = false;
	
	# check that the user can protect (check also write right)
	
	$readonly = $title->getUserPermissionsErrors('protect',$wgUser);
	$readonly = ( $readonly != array() );
	
	# remove temporary assigned protect right by reloading rights with $wgSetRestrictionsDoProtect = false
	
//	wfDebugLog( 'restrictions', 'Form: purge user\'s rights then force reload');
	$wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
	$wgUser->getRights();	    // force rights reloading
	
	wfDebugLog( 'restrictions', 'Form: '.($readonly?'READ-ONLY':'READ/WRITE'));

	// can we AND do we have a request to handle?
	if ( $readonly || !$wgRequest->wasPosted() ) {
		// readonly OR no data submitted, so construct the form (maybe readonly)
		$wgOut->addHTML( efRestrictionsMakeForm( $title, $readonly ) );
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
	$applicableRestrictionTypes  = $title->getRestrictionTypes();

	// for each of theses available restrictions
	foreach ( $applicableRestrictionTypes as $action ) {  // 'read', 'upload', ...
		
		$current_restrictions = $title->getRestrictions( $action ); //'sysop', 'owner', ...
		
		wfDebugLog( 'restrictions', 'Form: current title, action "'
				.$action.'" restricted to level(s) "'.implode(',', $current_restrictions).'"');

		// ensure that we have not to keep the previous restrictions
		$keep_old_restriction_for_this_action = false;
		
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
					// if giving few restrictions, MW core raises a warning:
					//   mysql_real_escape_string() expects parameter 1 to be string, 
					//   array given in /var/seizam/seizamcore/WikiZam/includes/db/DatabaseMysql.php on line 331
					// so, only one restriction per action
					$new_restrictions[$action] = $current_restriction;
					
					$keep_old_restriction_for_this_action = true;
					break; // end $current_restrictions foreach 
				}
			}
		}
		
		if ($keep_old_restriction_for_this_action)
			continue; // end $applicableRestrictionTypes current iteration foreach 

		// set expiry 
		$expiration[$action] = $expiry;
		
		# we arrive here if user can change the restrictions

		// by default, restricted to owner
		$new_restrictions[$action] = 'owner'; 
		
		// check what's checked, taking account $wgRestrictionsGroups order 
		global $wgRestrictionsGroups;
		foreach( $wgRestrictionsGroups as $current_level) {
			
			// convert from BACK-END to FRONT-END: 'everyone' = ''
			$current_level = ($current_level=='' ? 'everyone' : $current_level);
			
			// is the checkbox $action/$current_level checked ?
			if ( $wgRequest->getCheck( "check-$action-$current_level" ) ) {
				
				// convert from FRONT-END to BACK-END
				$current_level = ( $current_level=='everyone' ? '' : $current_level);
				
				// can the user set to this level?
				if (efRestrictionsCanTheUserSetToThisLevel($wgUser, $title, $current_level) )  {
					
					wfDebugLog( 'restrictions', 'Form: restricting '.$action.' to '.$current_level );

					// so we can set the restriction to it			
					$new_restrictions[$action] = $current_level;
					
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

	
	// display error/succes message
	if ( efRestrictionsUpdateRestrictions($article, $new_restrictions) ) {
		$wgOut->addWikiMsg( 'setrestrictions-success' );
	} else {
		$wgOut->addWikiMsg( 'setrestrictions-failure' );
	}
	
	// re-display the setrestrictions form with the current restrictions (reloaded above)
	$wgOut->addHTML( efRestrictionsMakeForm( $article->getTitle() ) );
	
	// stop hook processing, and doesn't throw an error message
	return false;

}


function efRestrictionsUpdateRestrictions( $article , $restrictions , $cascade = false , $expiration = null ) {
	
	global $wgSetRestrictionsDoProtect, $wgUser, $wgRestrictionsUserCanCache, $wgRestrictionsIsOwnerCache;
	
	if ($expiration === null) {
		$expiration = array();
		$infinity =  Block::infinity();
		foreach ($restrictions as $action => $level) {
			$expiration[$action] = $infinity;
		}
	}
	
	# temporary assign protect right, in order to update the restricitons

	$wgSetRestrictionsDoProtect = true;  // tells spSetRestrictionsAssignDynamicRights to add the "protect" right
//	wfDebugLog( 'restrictions', 'Form: purge user\'s rights then force reload');
	$wgUser->mRights = null;			// clear current user rights
	$wgUser->getRights();				// force rights reloading
	$wgSetRestrictionsDoProtect = false;

	wfDebugLog( 'restrictions', "UpdateRestrictions: restrictions =\n "
			.var_export( $restrictions, true )
			."\nexpiration=\n".var_export( $expiration, true ));
	
	// update restrictions
	$success = $article->updateRestrictions(
		$restrictions,		// array of restrictions
		'Restrictions',			// reason
		$cascade,				// cascading protection disabled, need to pass by reference
		$expiration				// expiration
	);  // note that this article function check that the user has sufficient rights
		
	# clear userCan and isOwner caches
	# because of protect right granted few instants
	# IsOwner cache clearing should not be necessary, but IsOwner hook may be affected by restrictions update
//	wfDebugLog( 'restrictions', 'Form: purge userCan and isOwner caches');
	$wgRestrictionsUserCanCache = array();
	$wgRestrictionsIsOwnerCache = array();
	
	# remove temporary assigned protect right by reloading rights with $wgSetRestrictionsDoProtect = false
//	wfDebugLog( 'restrictions', 'Form: purge user\'s rights then force reload');
	$wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
	$wgUser->getRights();	    // force rights reloading
	
	return $success;
}


function efRestrictionsMakeForm( $title, $readonly = false ) {
	
	global $wgUser, $wgRestrictionsGroups;
	$applicableRestrictionTypes  = $title->getRestrictionTypes(); // this way, do not display create for exsiting page
		
	$token = $wgUser->editToken();
	$br = Html::element( 'br' );
	
	if (!$readonly) {
		$form  = Html::rawElement( 'p', array(), htmlspecialchars( wfMsg( 'setrestrictions-intro' ) ) );
		$form .= Html::openElement('form', array( 
						'method' => 'post',
						'action' => $title->getLocalUrl( 'action=setrestrictions' ) ) );
	} else {
		$form  = Html::rawElement( 'p', array(), htmlspecialchars( wfMsg( 'setrestrictions-locked' ) ) );	
	}
	
	$form .= Xml::openElement( 'table') . Xml::openElement( 'tbody' ) . '<tr>';
	
	// for each of theses available restrictions
	foreach ( $applicableRestrictionTypes as $action ) {  // 'read', 'upload', ...

		$title_action_restrictions = $title->getRestrictions( $action ); //'sysop', 'owner', ...

		$form .= '<td>'.Xml::openElement( 'fieldset' );
		$form .= Xml::element( 'legend', null, wfMsg( "setrestrictions-whocan-$action") ) ;
		
		// this foreach normally does only one iteration, but MediaWiki core can handle
		// multiple restrictions per one action... but the current code is not optimised
		// for that case
		// please review this if you have mutliple restrictions per action per page
		$stop = false;
		foreach ( $title_action_restrictions as $current_restriction ) {
			
			// if restricted to a level that the user can't set, or readonly, display only 
			// a message about the restriction then end
			if ( $readonly || !efRestrictionsCanTheUserSetToThisLevel($wgUser, $title, $current_restriction) ) {
				
				$form .=  wfMsg( "restriction-level-$title_action_restrictions[0]" ).' ('.$title_action_restrictions[0].')';
				$form .= Xml::closeElement( 'fieldset' ) .'</td>';
				
				// restrictions are inclusive (see Title.php>checkPageRestrictions()
				// so no more iteration needed
				$stop = true; 
				break;
			}
			
		}
		
		// $stop = the user cannot change a restriction and a message is already displayed
		// we prematurely end this iteration because we don't add any checkboxe for that action
		if ($stop)
			continue; // end foreach current iteration
		
		if ($readonly) {
			// if we arrive here, form is readonly, but no restrictions set
			$form .=  wfMsg( "restriction-level-all" );
			$form .= Xml::closeElement( 'fieldset' ) .'</td>';
			continue; // end foreach current iteration
		}
		
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
					wfMsg( "setrestrictions-$current_level" ), 
					"check-$action-$current_level", 
					"check-$action-$current_level", 
					$checked ) . $br;
			
/*			$form .= Xml::radioLabel( 
					wfMsg( "setrestrictions-$current_level" ),
					$action, 
					$current_level, 
					'',
					$checked ) ;
*/
		}
		
		// we arrive here if user can change the permissions
	
		$form .= Xml::closeElement( 'fieldset' ) .'</td>';

	}
	
	$form .= '</tr>' . Xml::closeElement( 'tbody' ) . Xml::closeElement( 'table' );
	
	$form .= Html::hidden( 'wpToken', $token );
	
	if (!$readonly) {
		$form .= $br . Xml::submitButton( wfMessage( 'setrestrictions-confirm' ) );
		$form .= Xml::closeElement( 'form' );
	}
	
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


/*
 *     
 * $article: the article object that was protected
 * $user: the user object who did the protection
 * $protect*: boolean whether it was a protect or an unprotect
 * $reason: Reason for protect
 */
function efRestrictionsArticleProtectComplete( &$article, &$user, $protect, $reason ) {
	// MediaWiki documentation indicates a fifth argument $moveonly (boolean whether it was 
	// for move only or not), but there are only four args 

	$title = $article->getTitle();

	wfDebugLog( 'restrictions', 'ArticleProtectComplete: purging title cache'
		.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']'
		);
			
	# purge page's restrictions
	$article->getTitle()->mRestrictions = array();
	$article->getTitle()->mRestrictionsLoaded = false;
	//$article->getTitle()->loadRestrictions();

	// Purge caches on page update etc
	WikiPage::onArticleEdit( $title ) ; // this put update in $wgDeferredUpdateList
	wfDoUpdates(); // this execute all updates in $wgDeferredUpdateList
	
	// Update page_touched, this is usually implicit in the page update
	$title->invalidateCache();
	
	// ask mediawiki to reload search engine cache
	$u = new SearchUpdate( $title->getArticleId(), $title->getPrefixedDBkey(), Revision::newFromTitle($title)->getText() );
	$u->doUpdate(); // will call wfRunHooks( 'SearchUpdate', array( $this->mId, $this->mNamespace, $this->mTitle, &$text ) );
	
	// continue hook processing 
	return true; 
}


function efRestrictionsSearchUpdate( $id, $namespace, $title_text, &$text ) {
	
	$title = Title::newFromID($id);
		
	wfDebugLog( 'restrictions', 'SearchUpdate:'
				.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']' 
				);
	
	if ( $title && $title->isProtected('read')) {
			
		wfDebugLog( 'restrictions', 'SearchUpdate: CLEAR SEARCH CACHE'
				.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']' 
				);

		$text = '';

	}
	
	// after this hook, MW core will run
	/*  $dbw = wfGetDB( DB_MASTER );
		$dbw->replace( 'searchindex',
			array( 'si_page' ),
			array(
				'si_page' => $id,
				'si_title' => $this->normalizeText( $title ),
				'si_text' => $this->normalizeText( $text )
			), __METHOD__ );
	 */
	
	// continue hook processing 
	return true;
}
	
// check that the page is not read protected
// it may be better to check if the "user can read" the page instead... because 
// maybe she can read even if it read restricted (= read restricted to user)
function efRestrictionsWatchArticle( &$user, &$article ) {
	
	$title = $article->getTitle();
	
	if ($title->isProtected('read')) {
		
		wfDebugLog( 'restrictions', 'WatchArticle: FORBIDDEN'
				.' the title "'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']' 
				.' has a read restriction, so no one can watch it');
		
		$wgOut->addWikiText( wfMsg( 'badaccess' ) );
		return false;
		
	}
	
	wfDebugLog( 'restrictions', 'WatchArticle: ALLOWED'
		.' the title "'.$title->getPrefixedDBkey().'"['.$title->getArticleId().']' 
		.' has no read restriction');
	
	return true;
}



/**
 *
 * @param WikiPage $wikipage
 * @param array $restrictions examle:<br/>
 * array(<br/>
 * 'edit' => 'owner',<br/>
 * 'move' => 'owner',<br/>
 * 'upload' => 'owner' )
 * @param boolean $ok Restrictions successfully applied (false = error)
 * @return boolean true = continue hook processing
 */
function efRestrictionsSetRestrictions( $wikipage , $restrictions , &$ok ) {
	
	$ok = efRestrictionsUpdateRestrictions( $wikipage , $restrictions ); 
	
	if ( $ok ) {
		wfDebugLog( 'restrictions', 'OnWikiplacePageCreated: restrictions set page "'
				.$wikipage->getTitle()->getPrefixedDBkey().'"['.$wikipage->getTitle()->getArticleId().']');
	} else {
		wfDebugLog( 'restrictions', 'OnWikiplacePageCreated: ERROR while setting restrictions to page "'
				.$wikipage->getTitle()->getPrefixedDBkey().'"['.$wikipage->getTitle()->getArticleId().']');
	}
	
	return true; //continue hook processing
	
}


/* watchlist content may be changed using hook SpecialWatchlistFilters or SpecialWatchlistQuery, maybe....
 
	public static function modifyNewPagesQuery( $specialPage, $opts, &$conds, &$tables, &$fields, &$join_conds ) {
		return self::modifyChangesListQuery( $conds, $tables, $join_conds, $fields );
	}

	public static function modifyChangesListQuery(array &$conds, array &$tables, array &$join_conds, array &$fields) {
		global $wgRequest;
		$tables[] = 'flaggedpages';
		$fields[] = 'fp_stable';
		$fields[] = 'fp_pending_since';
		$join_conds['flaggedpages'] = array( 'LEFT JOIN', 'fp_page_id = rc_cur_id' );
 
		if ( $wgRequest->getBool( 'hideReviewed' ) && !FlaggedRevs::useOnlyIfProtected() ) {
			$conds[] = 'rc_timestamp >= fp_pending_since OR fp_stable IS NULL';
		}
 
		return true;
	}
 */
