<?php

/*
 * Restrictions extension, developped by Yann Missler, Seizam SARL
 * www.seizam.com
 * 
 * /!\ WARNING! THIS IS THE UGLIEST CODE EVER!
 */

if (!defined('MEDIAWIKI')) {
    echo "ProtectOwn extension\n";
    die(-1);
}

# ======================
#    CONFIGURATION VARS
// Which groups are accessible from protect actions ? (the order in IMPORTANT)
// we assign only one group per couple page/action restriction
// so, if the usser check multiple group in form, the first group found in
// $wgProtectOwnGroups will be used to assign the restriction
// (ex: 'user' + 'owner' checked == restriction to 'user')
// by default:
//  # group '' (=everyone) is virtual
//  # group 'member' is virtual and used only by this extension (never stored in db)
//  # group 'owner' is virtual and used only by this extension (never stored in db)
// any other group added to this var need to exist (in $wgGroupPermissions)
// NOTE: $wgRestrictionLevels will be updated in order for theses level to be accessed via protect
$wgProtectOwnGroups = array('', 'member', 'owner');

// This value is used in other extensions to link to the ProtectOwn interface
// Describe the action AND the right
define('PROTECTOWN_ACTION', 'setprotection');

// The right to bypass restrictions set here 
define('PROTECTOWN_BYPASS', 'bypassprotectown');


# ==================================== 
#   REGISTERING EXTENSION

$wgExtensionCredits['validextensionclass'][] = array(
    'path' => __FILE__,
    'name' => 'ProtectOwn',
    'author' => array('Yann Missler', 'Seizam'),
    'url' => 'http://www.seizam.com',
    'descriptionmsg' => 'po-desc',
    'version' => '0.1',
);


// load messages file
$wgExtensionMessagesFiles['ProtectOwn'] = dirname(__FILE__) . '/ProtectOwn.i18n.php';

// add our own userCan hook
$wgHooks['userCan'][] = 'poUserCan';

// this hook is called when mediawiki request the rights of a user, it can give more 
// rights than mediawiki does, in our case, it depends if the user is the owner or a member
// it can also give the protect right while updating restrictons
$wgHooks['UserGetRights'][] = 'poUserGetRights';

// add an item to the action menu
$wgHooks['SkinTemplateNavigation::Universal'][] = 'poMakeContentAction';

// add a non native action to mediawiki
$wgHooks['UnknownAction'][] = 'poForm';

// secure from viewing a transclusion if the template forbidden it
// Available from MW version 1.10.1: "Allows an extension to specify a version of a page to get for inclusion in a template?"
$wgHooks['BeforeParserFetchTemplateAndtitle'][] = 'poBeforeParserFetchTemplateAndtitle';

// clear page cache when protections have been changed, in order to remove text 
// from any cache if read is restricted
$wgHooks['ArticleProtectComplete'][] = 'poArticleProtectComplete';

// called when mediawiki updates its search engine cache
// remove page text if it is read restricted
$wgHooks['SearchUpdate'][] = 'poSearchUpdate';

// add a "read restriction" check when trying to add a page to a watchlist
$wgHooks['WatchArticle'][] = 'poWatchArticle';


// registering our defered setup function
$wgExtensionFunctions[] = 'poSetup';


# internal variables
// tells to our own UserGetRight hook implementation if we need to grant protect right
// (required when updating article restriction when validating ProtectOwn form)
$wgProtectOwnDoProtect = false;

// Used for caching usercan result
// store result to avoid checking rights again
// [user_id][title_id][action] = true(=allowed) / false(=disallowed)
$wgProtectOwnCacheUserCan = array();
// Used for caching isowner result
// [user_id][title_id] = true(=owner) / false(=not owner)
$wgProtectOwnCacheIsOwner = array();
// Used for caching isMember result
// [user_id][title_id] = true(=member) / false(=not member)
$wgProtectOwnCacheIsMember = array();



# ==================================== 
#   CONFIGURING MEDIAWIKI
# 1) immediate setup

$wgAvailableRights[] = PROTECTOWN_ACTION;
// registered users can set permissions to pages they own
$wgGroupPermissions['user'][PROTECTOWN_ACTION] = true;

$wgAvailableRights[] = PROTECTOWN_BYPASS;
// sysops bypass
$wgGroupPermissions['sysop'][PROTECTOWN_BYPASS] = true;

// add the read rights to restricted actions list
$wgRestrictionTypes = array_merge(array("read"), $wgRestrictionTypes);


// disable automatic summary, because api.php can always see comments, even if the page is read restricted
// so, only protection log and user's comment are visible via history
$wgUseAutomaticEditSummaries = false;

// NEW EXTENSION'S HOOK
// this hook is called if an extension wants to set protection to a page
$wgHooks['POSetProtection'][] = 'poSetProtection';


# 2) defered setup 

function poSetup() {

    global $wgProtectOwnGroups, $wgRestrictionLevels, $wgGroupPermissions;
    //TODO: refactore to use merge
    foreach ($wgProtectOwnGroups as $group) {
        // add the group to "protect"'s accessible levels
        if (!in_array($group, $wgRestrictionLevels)) {
            $wgRestrictionLevels[] = $group;
        }

        // if group not virtual but missing in wgGroupPermission, creates it
        if ($group != '' && $group != 'owner' && $group != 'member' && !array_key_exists($group, $wgGroupPermissions)) {
            wfDebugLog('ProtectOwn', 'Setup: /!\ creating user group "' . $group . '"');
            $wgGroupPermissions[$group] = array();
        }
    }
}

# ==================================== 
#           THE CODE
# ====================================

function poIsUserInGroup($user, $group) {
    return in_array($group, $user->getEffectiveGroups());
}

// when the parser process a page and find a template(=transclusions?), this hook is called
// so we can skip this transclusion or specifiy another revision of it
function poBeforeParserFetchTemplateAndtitle($parser, Title $title, &$skip, &$id) {

    if (!( $parser instanceof Parser )) {
        wfDebugLog('ProtectOwn', 'BeforeParserFetchTemplateAndtitle: NOTHING TO DO, no parser given');
        return true; // nothing to do
    }

    if ($title->getNamespace() < 0 || $title->getNamespace() == NS_MEDIAWIKI) {
        wfDebugLog('ProtectOwn', 'BeforeParserFetchTemplateAndtitle: NOTHING TO DO, bug 29579 for NS_MEDIAWIKI');
        return true; // nothing to do (bug 29579 for NS_MEDIAWIKI)
    }

    global $wgUser;

    // disallow translcusion of a read restricted page
    // because of cache, this protection may be bypassed if the first visitor of the page
    // satisfy restriction, but the next vistors don't
    // so, if there is a read restriction, skip this transclusion
    if ($title->isProtected('read')) {
        /* 		wfDebugLog( 'ProtectOwn', 'BeforeParserFetchTemplateAndtitle: "'
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

    wfDebugLog('ProtectOwn', 'BeforeParserFetchTemplateAndtitle: '
            . ($skip ? "SKIP" : "FETCH")
            . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            . ' id="' . var_export($id, true) . '"'
            . ' user="' . $wgUser->getName() . '"[' . $wgUser->getID() . ']'
    );
    // return true (=continue processing) when do not skip the transclusion 
    // return false  (=stop processing)   when     skip    the transclusion 
    return!$skip;
}

/**
 * Grant rights as needed later by $title->checkPagesRestriction(). Hook userCan is always called
 * in function $title->checkPermissionHooks(), before calling checkPagesRestriction(). So, it ensures
 * that correct rights are available when checkPagesRestriction() will be executed.
 * @global array $wgProtectOwnCacheUserCan
 * @param Title $title
 * @param User $user
 * @param string $action
 * @param boolean $result
 * @return boolean 
 */
function poUserCan($title, &$user, $action, &$result) {


    if (($title->getNamespace() == NS_SPECIAL) || (!$title->isKnown())) {
        return true; // skip
    }

    // userCan is called before title->checkPageRestrictions

    $act = $action;

    //just in case, but shouldn't occur
    if ($action == '' || $action == 'view') {
        $act = 'read';
    }

    // update user right about the current title, especially the owner and member rights
    // (not pretty, but needed because this is the way MW core checks protection...)
    poGrantProtectionRights($title, $user);

    // fetch restriction and user groups from MediaWiki core
    $title_restrictions = $title->getRestrictions($act);

    // if no restrictions, return "the $user can do $action on $title"
    if ($title_restrictions === array()) {
        wfDebugLog('ProtectOwn', 'UserCan: action not restricted, resume other hooks'
                . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
                . ' isKnown()=' . ($title->isKnown() ? 'YES' : 'NO')
                . ' user="' . $user->getName() . '"[' . $user->getID() . ']'
                . ' action="' . $action . '"'
        );
        return true; // continue userCan hooks processing (another hook can still disallow user)
    }

    //check if cached
    global $wgProtectOwnCacheUserCan;
    if (isset($wgProtectOwnCacheUserCan[$user->getID()][$title->getArticleId()][$act])) {
        $result = $wgProtectOwnCacheUserCan[$user->getID()][$title->getArticleId()][$act];
        /* 		wfDebugLog( 'ProtectOwn', 'UserCan: '.($result?'YES':'NO').', CACHE HIT '
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
        wfDebugLog('ProtectOwn', 'UserCan: /!\ few restricted level for only a page/action '
                . '("' . $act . '" restricted to "' . implode(',', $title_restrictions) . '")');
        wfDebug('Restrictions.extension>>UserCan(): /!\ few restricted level for only a page/action '
                . '("' . $act . '" restricted to "' . implode(',', $title_restrictions) . '")');
    }

    // please kick this foreach or change Title.php>checkPageRestrictions() code... we should not test intersection !!!
    $result = true;
    foreach ($title_restrictions as $title_restriction) {

        // intersection test = if we know that user is not allowed for one, do not test other restrictions
        if (!$result)
            break; // exit foreach

        if ($title_restriction == 'owner') {
            // check if the current user is the owner
            $result = poIsOwner($title, $user);
        } elseif ($title_restriction == 'member') {
            // check if the current user is a member
            $result = ( poIsMember($title, $user) || poIsOwner($title, $user) );
        } else {
            // allow if $user is member of the group
            $result = poIsUserInGroup($user, $title_restriction);
        }
    }

    // if the user has a bypass, she is allowed
    if ($user->isAllowed(PROTECTOWN_BYPASS)) {
        wfDebugLog('ProtectOwn', 'UserCan: YES, user has "' . PROTECTOWN_BYPASS . '" right');
        $result = true;  // allowed
    }

    // store to cache
    $wgProtectOwnCacheUserCan[$user->getID()][$title->getArticleId()][$act] = $result;

    wfDebugLog('ProtectOwn', 'UserCan: ' . ($result ? 'YES' : 'NO') . ', CACHE MISS '
            . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            . ' isKnown()=' . ($title->isKnown() ? 'YES' : 'NO')
            . ' user="' . $user->getName() . '"[' . $user->getID() . ']'
            . ' action="' . $action . '"'
            . ' restrictions="' . implode(',', $title_restrictions) . '"'
    );

    // stop processing
    return false;
}

function poIsOwner($title, $user) {

//		wfDebugLog( 'ProtectOwn', 'IsOwner: '.  wfGetPrettyBacktrace());

    if ((!$title instanceOf Title) || ( $user->getID() === 0 ) || ($title->isSpecialPage())) {
        return false;
    }

    global $wgProtectOwnCacheIsOwner;
    if (isset($wgProtectOwnCacheIsOwner[$user->getID()][$title->getArticleId()])) {

        $result = $wgProtectOwnCacheIsOwner[$user->getID()][$title->getArticleId()];

        /* 		wfDebugLog( 'ProtectOwn', 'IsOwner: '.( $result ? 'YES' : 'NO')
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
    wfRunHooks('IsOwner', array($title, $user, &$result));

    // store to cache
    $wgProtectOwnCacheIsOwner[$user->getID()][$title->getArticleId()] = $result;

    wfDebugLog('ProtectOwn', 'IsOwner: ' . ( $result ? 'YES' : 'NO')
//			.', CACHE MISS '
            . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            . ' user="' . $user->getName() . '"[' . $user->getID() . ']'
    );

    return $result;
}


function poIsMember($title, $user) {

	$userId = $user->getID();
	
//	wfDebugLog('ProtectOwn', 'IsMember(title='.$pageDBkey.'['.$pageId.'], user='.$userId.'): ' . wfGetPrettyBacktrace());
//	wfDebugLog('ProtectOwn', 'IsMember(title='.$pageDBkey.'['.$pageId.'], user='.$userId.'): ' . wfBacktrace());

	if ((!$title instanceOf Title) || ( $userId === 0 ) || ($title->isSpecialPage())) {
		return false;
	}
	
	$pageId = $title->getArticleId();
	$pageDBkey = $title->getPrefixedDBkey();

	global $wgProtectOwnCacheIsMember;
	if (isset($wgProtectOwnCacheIsMember[$userId][$pageId])) {
		$result = $wgProtectOwnCacheIsMember[$userId][$pageId];
		wfDebugLog( 'ProtectOwn', 'IsMember(title='.$pageDBkey.'['.$pageId.'], user='.$userId.'): CACHE IT = '.( $result ? 'YES' : 'NO') );
		return $result;
	}
	
	// fetch membership from other extensions
	$result = false;
	wfRunHooks('IsMember', array($title, $user, &$result));
	$wgProtectOwnCacheIsMember [$userId][$pageId] = $result; // store to cache
	wfDebugLog( 'ProtectOwn', 'IsMember((title='.$pageDBkey.'['.$pageId.'], user='.$userId.'): CACHE MISS = '.( $result ? 'YES' : 'NO') );

	return $result;
}

/**
 * Dynamically assign "protect" right when needed 
 * @global type $wgTitle
 * @global type $wgProtectOwnDoProtect
 * @param type $user
 * @param type $aRights
 * @return type 
 */
function poUserGetRights($user, &$aRights) {

    // When updating title's restriction, "protect" and user's group rights are needed
    // so when flushing rights (in Form() code), the next lines grant the rights.
    // Activated by setting $wgProtectOwnDoProtect to true.
    global $wgProtectOwnDoProtect, $wgTitle;

    if ($wgProtectOwnDoProtect) {

        wfDebugLog('ProtectOwn', 'UserGetRights: GRANT "protect"'
                . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
        );
        $aRights[] = 'protect';
        $aRights = array_unique($aRights);
    }

    poGrantProtectionRights($wgTitle, $user, $aRights);

    return true; // don't stop hook processing
}

function poGrantProtectionRights($title, $user, &$aRights = null) {

    if ($aRights == null) {
        $aRights = &$user->mRights;
    }

    if ($aRights == null) {
        wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: need to fetch rights by calling getRights()');
        $user->mRights = null;    // clear current user rights (and clear the "protect" right
        $user->getRights();
        $aRights = &$user->mRights;
    }

    if ($aRights == null) {
        wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: /!\ $aRights is still null, something goes wrong');
    }

    # grant group right if the user is in group
    # this is not pretty, but this is how MediaWiki manage restrictions (see Title.php->checkPageRestrictions)

    global $wgProtectOwnGroups;

    // if the user has a bypass, she will have all rights
    $bypass = $user->isAllowed(PROTECTOWN_BYPASS);
    $to_grant;
    if ($bypass) {
        $to_grant = $wgProtectOwnGroups; // give all groups, even 'owner', 'member' and ''
        unset($to_grant[array_search('', $to_grant)]); // remove '' (=everyone) right
        wfDebugLog('ProtectOwn'
                , 'GrantRestrictionsRights: user has "' . PROTECTOWN_BYPASS . '" right, give rights "' .
                implode(',', $to_grant) . '" if needed');
    } else {
        $to_grant = array_intersect($wgProtectOwnGroups, $user->getEffectiveGroups());
    }

    foreach ($to_grant as $group) {
        // add the group to user right
        // (not very pretty, but this is how MediaWiki manage restrictions)
        if (!in_array($group, $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: GRANT "' . $group . '"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
            );
            $aRights[] = $group;
        }
    }

    // if the user bypasses, no need to check if we have to give her the 'owner' right, 
    // because she previoulsy get it with $to_grant array
    if ($bypass)
        return;

    # grant owner and member right if the user is the owner of the current title

    if ($user->isLoggedIn() && poIsOwner($title, $user)) {

        # user is owner of the current title : add 'owner' and add 'member' rights

        if (!in_array('owner', $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: GRANT "owner"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
                    . ' for title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            );
            $aRights[] = 'owner';
        }
		
		if (!in_array('member', $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: GRANT "member"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
                    . ' for title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            );
            $aRights[] = 'member';
        }
		
    } elseif ($user->isLoggedIn() && poIsMember($title, $user)) {
		
		# user is a member of the current title : remove 'owner' and add 'member' rights
		
		if (in_array('owner', $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: REMOVE "owner"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
                    . ' for title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            );
            unset($aRights[array_search('owner', $aRights)]); // remove owner
        }
		
		if (!in_array('member', $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: GRANT "member"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
                    . ' for title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            );
            $aRights[] = 'member';
        }
		
	} else {

        # user is not owner of the current title : remove 'owner' and remove 'member' rights

        if (in_array('owner', $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: REMOVE "owner"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
                    . ' for title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            );
            unset($aRights[array_search('owner', $aRights)]); // remove owner
        }
		
		if (in_array('member', $aRights)) {
            wfDebugLog('ProtectOwn', 'GrantRestrictionsRights: REMOVE "member"'
                    . ' to ' . $user->getName() . '"[' . $user->getID() . ']'
                    . ' for title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            );
            unset($aRights[array_search('member', $aRights)]); // remove member
        }
    }
}

function poMakeContentAction($skin, &$cactions) {
    global $wgUser, $wgRequest;

    $title = $skin->getTitle();

    // if user has 'protect' right, she cannot use PROTECTOWN_ACTION, but 'protect' instead'
    if (poIsOwner($title, $wgUser)
            && $wgUser->isAllowed(PROTECTOWN_ACTION)
            && !$wgUser->isAllowed('protect')) {
        $action = $wgRequest->getText('action');
        $cactions['actions'][PROTECTOWN_ACTION] = array(
            'class' => $action == PROTECTOWN_ACTION ? 'selected' : false,
            'text' => wfMsg('protect'),
            'href' => $title->getLocalUrl('action=' . PROTECTOWN_ACTION),
        );
    }
    return true;
}

/**
 *
 * @global Output $wgOut
 * @global User $wgUser
 * @global Request $wgRequest
 * @global Boolean $wgProtectOwnDoProtect
 * @global Array $wgProtectOwnGroups
 * @param String $action
 * @param Wikipage $article
 * @return Boolean 
 */
function poForm($action, $article) {

    if ($action != PROTECTOWN_ACTION) { // not our extension
        return true; //don't stop processing
    }

    global $wgOut, $wgUser, $wgRequest, $wgProtectOwnDoProtect;

    // is the user allowed to use ProtectOwn
    if (!$wgUser->isAllowed(PROTECTOWN_ACTION)) {
        $wgOut->permissionRequired(PROTECTOWN_ACTION);
        return false; //stop processing	
    }

    # user is allowed to use ProtectOwn

    $title = $article->getTitle();

    // is the user the owner?
    if (!poIsOwner($title, $wgUser)) {
        // user is not the owner of the page
        $wgOut->setPageTitle(wfMsg('errorpagetitle'));
        $wgOut->addHTML(wfMessage('po-notowner')->parse());
        return false; //stop processing	
    }

    # user is the owner
    // start displaying page
    $wgOut->setPageTitle(wfMsg('protect-title', $title->getPrefixedText()));

    // as defined in Title.php, around lines 1550 (mw1.18.1), 
    // being authorized to 'protect' require being authorized to 'edit'
    /* Title.php >>
     *  private function checkActionPermissions( $action, $user, $errors, $doExpensiveQueries, $short ) {
     * 	if ( $action == 'protect' ) {
     * 		if ( $this->getUserPermissionsErrors( 'edit', $user ) != array() ) {
     *  ...
     */

    # temporary assign protect right, in order to update the restricitons

    $wgProtectOwnDoProtect = true;  // tells spSetProtectionAssignDynamicRights to add the "protect" right
//	wfDebugLog( 'ProtectOwn', 'Form: purge user\'s rights then force reload');
    $wgUser->mRights = null;   // clear current user rights
    $wgUser->getRights();    // force rights reloading
    $wgProtectOwnDoProtect = false;

    # check that the user can protect (check also write right)

    $readonly = $title->getUserPermissionsErrors('protect', $wgUser);
    $readonly = !empty($readonly);

    # remove temporary assigned protect right by reloading rights with $wgProtectOwnDoProtect = false
//	wfDebugLog( 'ProtectOwn', 'Form: purge user\'s rights then force reload');
    $wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
    $wgUser->getRights();     // force rights reloading

    wfDebugLog('ProtectOwn', 'Form: ' . ($readonly ? 'READ-ONLY' : 'READ/WRITE'));

    // can we AND do we have a request to handle?
    if ($readonly || !$wgRequest->wasPosted()) {
        // readonly OR no data submitted, so construct the form (maybe readonly)
        // display the header.
        if (!$readonly) {
            $wgOut->addHTML(Html::rawElement('div', array('class' => 'form_header informations'), wfMessage('po-header', $title->getPrefixedText(), WpWikiplace::extractWikiplaceRoot($title->getDBkey(), $title->getNamespace()))->parse()));
        } else {
            $wgOut->addHTML(Html::rawElement('div', array('class' => 'form_header informations'), wfMsg('po-locked')));
        }

        $wgOut->addHTML(poMakeForm($title, $readonly));
        return false; //stop processing	
    }

    // ensure that the form was submitted from the user's own login session
    if (!$wgUser->matchEditToken($wgRequest->getText('wpToken'))) {
        // hummm.... how did this case happen?
        $wgOut->setPageTitle(wfMsg('errorpagetitle'));
        $wgOut->addWikiMsg('sessionfailure');
        return false; // stop processing
    }

    # ok, so let's change restrictions!

    $new_restrictions = array();
    $expiration = array();
    $expiry = Block::infinity(); // the restriction will never expire
    // we load the title specific available restrictions
    $applicableRestrictionTypes = $title->getRestrictionTypes();

    // for each of theses available restrictions
    foreach ($applicableRestrictionTypes as $action) {  // 'read', 'upload', ...
        $current_restrictions = $title->getRestrictions($action); //'sysop', 'owner', ...

        wfDebugLog('ProtectOwn', 'Form: current title, action "'
                . $action . '" restricted to level(s) "' . implode(',', $current_restrictions) . '"');

        // ensure that we have not to keep the previous restrictions
        $keep_old_restriction_for_this_action = false;

        // does the title have already a restriction ?
        if ($current_restrictions !== array()) {

            // check that the user can change the current restriction(s)
            // so, if there is multiple restrictions (for one action), user need to
            // satisfy all current restrictions in order to change at least on of them
            // (maybe, this behviour can be improved)
            // (the mediawiki check that the user satisfy all to allow an action... that's it)
            foreach ($current_restrictions as $current_restriction) {

                if (!poCanTheUserSetToThisLevel($wgUser, $title, $current_restriction)) {

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

        // check what's checked, taking account $wgProtectOwnGroups order 
        global $wgProtectOwnGroups;
        foreach ($wgProtectOwnGroups as $current_level) {

            // convert from BACK-END to FRONT-END: 'everyone' = ''
            $current_level = ($current_level == '' ? 'everyone' : $current_level);

            // is the checkbox $action/$current_level checked ?
            if ($wgRequest->getText("radio-$action") == $current_level /* $wgRequest->getCheck( "check-$action-$current_level" ) */) {



                // convert from FRONT-END to BACK-END
                $current_level = ( $current_level == 'everyone' ? '' : $current_level);

                // can the user set to this level?
                if (poCanTheUserSetToThisLevel($wgUser, $title, $current_level)) {

                    wfDebugLog('ProtectOwn', 'Form: restricting ' . $action . ' to ' . $current_level);

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
    // display the header.
    
    // display error/succes message
    if (poUpdateRestrictions($article, $new_restrictions)) {
        $wgOut->addHTML(Html::rawElement('div', array('class' => 'informations success'), wfMessage('po-success')->text()));
    } else {
        $wgOut->addHTML(Html::rawElement('div', array('class' => 'informations error'), wfMessage('po-failure')->text()));
    }
    
    if (!$readonly) {
        $wgOut->addHTML(Html::rawElement('div', array('class' => 'form_header informations'), wfMessage('po-header', $title->getPrefixedText(), WpWikiplace::extractWikiplaceRoot($title->getDBkey(), $title->getNamespace()))->parse()));
    } else {
        $wgOut->addHTML(Html::rawElement('div', array('class' => 'form_header informations'), wfMsg('po-locked')));
    }

    // re-display the ProtectOwn form with the current restrictions (reloaded above)
    $wgOut->addHTML(poMakeForm($article->getTitle()));

    // stop hook processing, and doesn't throw an error message
    return false;
}

function poUpdateRestrictions($article, $restrictions, $cascade = false, $expiration = null) {

    global $wgProtectOwnDoProtect, $wgUser, $wgProtectOwnCacheUserCan, $wgProtectOwnCacheIsOwner;

    if ($expiration === null) {
        $expiration = array();
        $infinity = Block::infinity();
        foreach ($restrictions as $action => $level) {
            $expiration[$action] = $infinity;
        }
    }

    # temporary assign protect right, in order to update the restricitons

    $wgProtectOwnDoProtect = true;  // tells spSetRestrictionsAssignDynamicRights to add the "protect" right
//	wfDebugLog( 'ProtectOwn', 'Form: purge user\'s rights then force reload');
    $wgUser->mRights = null;   // clear current user rights
    $wgUser->getRights();    // force rights reloading
    $wgProtectOwnDoProtect = false;

    wfDebugLog('ProtectOwn', "UpdateRestrictions: restrictions =\n "
            . var_export($restrictions, true)
            . "\nexpiration=\n" . var_export($expiration, true));

    // update restrictions
    $success = $article->updateRestrictions(
            $restrictions, // array of restrictions
            'ProtectOwn', // reason
            $cascade, // cascading protection disabled, need to pass by reference
            $expiration				// expiration
    );  // note that this article function check that the user has sufficient rights
    # clear userCan and isOwner caches
    # because of protect right granted few instants
    # IsOwner cache clearing should not be necessary, but IsOwner hook may be affected by restrictions update
//	wfDebugLog( 'ProtectOwn', 'Form: purge userCan and isOwner caches');
    $wgProtectOwnCacheUserCan = array();
    $wgProtectOwnCacheIsOwner = array();

    # remove temporary assigned protect right by reloading rights with $wgSetRestrictionsDoProtect = false
//	wfDebugLog( 'ProtectOwn', 'Form: purge user\'s rights then force reload');
    $wgUser->mRights = null;    // clear current user rights (and clear the "protect" right
    $wgUser->getRights();     // force rights reloading

    return $success;
}

function poMakeForm($title, $readonly = false) {

    global $wgUser, $wgProtectOwnGroups;
    $applicableRestrictionTypes = $title->getRestrictionTypes(); // this way, do not display create for exsiting page

    $token = $wgUser->editToken();

    if (!$readonly) {
        $form = Html::openElement('form', array(
                    'method' => 'post',
                    'class' => 'visualClear',
                    'action' => $title->getLocalUrl('action=' . PROTECTOWN_ACTION)));
    } else {
        $form = Html::openElement('form', array(
                    'method' => 'post',
                    'class' => 'visualClear',
                    'action' => '#'));
    }

    $form .= Xml::openElement('div', array('class' => 'edit_col_1'));

    $form .= Xml::openElement('fieldset');

    $form .= Xml::element('legend', null, wfMsg("po-legend"));

    $form .= Xml::openElement('div', array('class' => 'content_block'));

    // for each of theses available restrictions
    foreach ($applicableRestrictionTypes as $action) {  // 'read', 'upload', ...
        $title_action_restrictions = $title->getRestrictions($action); //'sysop', 'owner', ...
        // this foreach normally does only one iteration, but MediaWiki core can handle
        // multiple restrictions per one action... but the current code is not optimised
        // for that case
        // please review this if you have mutliple restrictions per action per page
        $stop = false;
        foreach ($title_action_restrictions as $current_restriction) {

            // if restricted to a level that the user can't set, or readonly, display only 
            // a message about the restriction then end
            if ($readonly || !poCanTheUserSetToThisLevel($wgUser, $title, $current_restriction)) {

                $form .= wfMsg("restriction-level-$title_action_restrictions[0]") . ' (' . $title_action_restrictions[0] . ')';
                $form .= Xml::closeElement('div'); //content_block
                $form .= Xml::closeElement('fieldset');

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
            $form .= wfMsg("restriction-level-all");
            $form .= Xml::closeElement('div'); //content_block
            $form .= Xml::closeElement('fieldset');
            continue; // end foreach current iteration
        }

        $form .= Xml::openElement('p', array('class' => 'mw-htmlform-field-HTMLRadioField'));
        $form .= Xml::element('label', null, wfMsg("po-whocan-$action"));
        $form .= Xml::openElement('span', array('class' => 'input_like'));

        # the next lines display checkboxes and eventually check them


        foreach ($wgProtectOwnGroups as $current_level) {

            // if the level is not selectable, do not display the checkboxe
            if (!poCanTheUserSetToThisLevel($wgUser, $title, $current_level)) {
                continue; // end foreach iteration
            }

            if ($current_level == '' && $action == 'upload') {
                continue;
            }
            
            $checked = false;
            if ($current_level == '' && $title_action_restrictions === array())
                $checked = true;  // everyone is checked if there is currently no restrictions
            else 
                $checked = in_array($current_level, $title_action_restrictions);  // else, check if there is a restriction to this level

            // convert from BACK-END to FRONT-END
            $current_level = ( $current_level == '' ? 'everyone' : $current_level);

            $form .= Xml::openElement('span', array('class' => 'mw-htmlform-monoselect-item'));

            /* $form .= Xml::checkLabel(wfMessage("po-$current_level")->text(), "check-$action-$current_level", "check-$action-$current_level", $checked); */

            $form .= Xml::radioLabel(wfMessage("po-$current_level")->text(), "radio-$action", $current_level, "radio-$action-$current_level", $checked);

            $form .= Xml::closeElement('span'); //mw-htmlform-monoselect-item
        }

        $form .= Xml::closeElement('span'); //input_like

        $form .= HTML::rawElement('span', array('class' => 'sread help htmlform-tip'), wfMessage("po-help-$action")->parse()); //input_like

        $form .= Xml::closeElement('p'); //mw-htmlform-field-HTMLRadioField
    }

    $form .= Xml::closeElement('div'); //content_block

    $form .= Xml::closeElement('fieldset');

    $form .= Html::hidden('wpToken', $token);

    if (!$readonly) {
        $form .= Xml::openElement('p', array('class' => 'submit'));

        $form .= Xml::submitButton(wfMessage('po-submit'), array('class' => 'mw-htmlform-submit'));

        $form .= Xml::closeElement('p');
    }

    $form .= Xml::closeElement('div'); //edit_col_1

    $form .= Xml::openElement('div', array('class' => 'edit_col_2'));
    $form .= Xml::openElement('div', array('class' => 'content_block', 'id' => 'help_zone'));
    $form .= Xml::element('h4', null, wfMessage('sz-htmlform-helpzonetitle')->text());
    $form .= HTML::rawElement('p', null, wfMessage('sz-htmlform-helpzonedefault')->parse());
    $form .= Xml::closeElement('div');
    $form .= Xml::closeElement('div');

    $form .= Xml::closeElement('form');

    return $form;
}

function poCanTheUserSetToThisLevel($user, $title, $level) {

    return (
            //    user in group
            poIsUserInGroup($user, $level)
            // OR level everyone
            || $level == ''
            // OR ( user is the owner of the title  AND  level is owner OR member )
            || ( poIsOwner($title, $user) && ( $level == 'owner' || $level == 'member' ) )
			// OR ( level is member AND user is the member of the title )
            || ( $level == 'member' && poIsMember($title, $user) ) );
}

/*
 *     
 * $article: the article object that was protected
 * $user: the user object who did the protection
 * $protect*: boolean whether it was a protect or an unprotect
 * $reason: Reason for protect
 */

function poArticleProtectComplete(&$article, &$user, $protect, $reason) {
    // MediaWiki documentation indicates a fifth argument $moveonly (boolean whether it was 
    // for move only or not), but there are only four args 

    $title = $article->getTitle();

    wfDebugLog('ProtectOwn', 'ArticleProtectComplete: purging title cache'
            . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
    );

    # purge page's restrictions
    $article->getTitle()->mRestrictions = array();
    $article->getTitle()->mRestrictionsLoaded = false;
    //$article->getTitle()->loadRestrictions();
    // Purge caches on page update etc
    WikiPage::onArticleEdit($title); // this put update in $wgDeferredUpdateList
    wfDoUpdates(); // this execute all updates in $wgDeferredUpdateList
    // Update page_touched, this is usually implicit in the page update
    $title->invalidateCache();

    // ask mediawiki to reload search engine cache
    $u = new SearchUpdate($title->getArticleId(), $title->getPrefixedDBkey(), Revision::newFromTitle($title)->getText());
    $u->doUpdate(); // will call wfRunHooks( 'SearchUpdate', array( $this->mId, $this->mNamespace, $this->mTitle, &$text ) );
    // continue hook processing 
    return true;
}

function poSearchUpdate($id, $namespace, $title_text, &$text) {

    $title = Title::newFromID($id);

    wfDebugLog('ProtectOwn', 'SearchUpdate:'
            . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
    );

    if ($title && $title->isProtected('read')) {

        wfDebugLog('ProtectOwn', 'SearchUpdate: CLEAR SEARCH CACHE'
                . ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
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

/**
 * check that the page is not read protected
 * it may be better to check if the "user can read" the page instead... because 
 * maybe she can read even if it read restricted (= read restricted to user)
 * @global Output $wgOut
 * @param User $user
 * @param Article $article
 * @return boolean 
 */
function poWatchArticle(&$user, &$article) {

    $title = $article->getTitle();

    if (!$title->userCan('read')) {

        wfDebugLog('ProtectOwn', 'WatchArticle: FORBIDDEN'
                . ' the title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
                . ' has a read restriction, so no one can watch it');

        throw new ErrorPageError('sorry', 'sz-invalid-request');

        return false;
    }

    wfDebugLog('ProtectOwn', 'WatchArticle: ALLOWED'
            . ' the title "' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . ']'
            . ' has no read restriction');

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
function poSetProtection($wikipage, $restrictions, &$ok) {

    $ok = poUpdateRestrictions($wikipage, $restrictions);

    if ($ok) {
        wfDebugLog('ProtectOwn', 'OnWikiplacePageCreated: restrictions set page "'
                . $wikipage->getTitle()->getPrefixedDBkey() . '"[' . $wikipage->getTitle()->getArticleId() . ']');
    } else {
        wfDebugLog('ProtectOwn', 'OnWikiplacePageCreated: ERROR while setting restrictions to page "'
                . $wikipage->getTitle()->getPrefixedDBkey() . '"[' . $wikipage->getTitle()->getArticleId() . ']');
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
