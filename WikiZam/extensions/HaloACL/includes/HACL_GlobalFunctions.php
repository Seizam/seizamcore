<?php
/**
 * @file
 * @ingroup HaloACL
 */

/*  Copyright 2009, ontoprise GmbH
 *  This file is part of the HaloACL-Extension.
 *
 *   The HaloACL-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The HaloACL-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This file contains global functions that are called from the Halo-Access-Control-List
 * extension.
 *
 * @author Thomas Schweitzer
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

$haclgDoEnableTitleCheck = haclfDisableTitlePatch();


/**
 * Switch on Halo Access Control Lists. This function must be called in
 * LocalSettings.php after HACL_Initialize.php was included and default values
 * that are defined there have been modified.
 * For readability, this is the only global function that does not adhere to the
 * naming conventions.
 *
 * This function installs the extension, sets up all autoloading, special pages
 * etc.
 */
function enableHaloACL() {
    global $haclgIP, $wgExtensionFunctions, $wgAutoloadClasses, $wgSpecialPages, $wgSpecialPageGroups, $wgHooks, $wgExtensionMessagesFiles, $wgJobClasses, $wgExtensionAliasesFiles;

    require_once("$haclgIP/includes/HACL_ParserFunctions.php");

    $wgExtensionFunctions[] = 'haclfSetupExtension';
    $wgHooks['LanguageGetMagic'][] = 'haclfAddMagicWords'; // setup names for parser functions (needed here)
    $wgExtensionMessagesFiles['HaloACL'] = $haclgIP . '/languages/HACL_Messages.php'; // register messages (requires MW=>1.11)

    // Register special pages aliases file
    $wgExtensionAliasesFiles['HaloACL'] = $haclgIP . '/languages/HACL_Aliases.php';

    ///// Set up autoloading; essentially all classes should be autoloaded!
    $wgAutoloadClasses['HACLEvaluator'] = $haclgIP . '/includes/HACL_Evaluator.php';
    $wgAutoloadClasses['HaloACLSpecial'] = $haclgIP . '/specials/HACL_ACLSpecial.php';
    $wgAutoloadClasses['HACLStorage'] = $haclgIP . '/includes/HACL_Storage.php';
    if (defined('SMW_VERSION')) {
        $wgAutoloadClasses['HACLSMWStore'] = $haclgIP . '/includes/HACL_SMWStore.php';
    }
    $wgAutoloadClasses['HACLGroup'] = $haclgIP . '/includes/HACL_Group.php';
    $wgAutoloadClasses['HACLDynamicMemberCache'] = $haclgIP . '/includes/HACL_DynamicMemberCache.php';
    $wgAutoloadClasses['HACLSecurityDescriptor'] = $haclgIP . '/includes/HACL_SecurityDescriptor.php';
    $wgAutoloadClasses['HACLRight'] = $haclgIP . '/includes/HACL_Right.php';
    $wgAutoloadClasses['HACLWhitelist'] = $haclgIP . '/includes/HACL_Whitelist.php';
    $wgAutoloadClasses['HACLDefaultSD'] = $haclgIP . '/includes/HACL_DefaultSD.php';
    $wgAutoloadClasses['HACLResultFilter'] = $haclgIP . '/includes/HACL_ResultFilter.php';
    $wgAutoloadClasses['HACLQueryRewriter'] = $haclgIP . '/includes/HACL_QueryRewriter.php';
    $wgAutoloadClasses['HACLQuickacl'] = $haclgIP . '/includes/HACL_Quickacl.php';
    $wgAutoloadClasses['HACLLanguageEn'] = $haclgIP . '/languages/HACL_LanguageEn.php';
    $wgAutoloadClasses['HACLGroupPermissions'] = $haclgIP . '/includes/HACL_GroupPermissions.php';
    
    // UI
    $wgAutoloadClasses['HACL_GenericPanel'] = $haclgIP . '/includes/HACL_GenericPanel.php';
    $wgAutoloadClasses['HACL_helpPopup'] = $haclgIP . '/includes/HACL_helpPopup.php';
    $wgAutoloadClasses['HACLUIGroupPermissions'] = $haclgIP . '/includes/UI/HACL_UIGroupPermissions.php';
    
    //--- Autoloading for exception classes ---
    $wgAutoloadClasses['HACLException']        = $haclgIP . '/exceptions/HACL_Exception.php';
    $wgAutoloadClasses['HACLStorageException'] = $haclgIP . '/exceptions/HACL_StorageException.php';
    $wgAutoloadClasses['HACLGroupException']   = $haclgIP . '/exceptions/HACL_GroupException.php';
    $wgAutoloadClasses['HACLSDException']      = $haclgIP . '/exceptions/HACL_SDException.php';
    $wgAutoloadClasses['HACLRightException']   = $haclgIP . '/exceptions/HACL_RightException.php';
    $wgAutoloadClasses['HACLWhitelistException'] = $haclgIP . '/exceptions/HACL_WhitelistException.php';    
    $wgAutoloadClasses['HACLGroupPermissionsException'] = $haclgIP . '/exceptions/HACL_GroupPermissionException.php';    

    return true;
}

/**
 * Do the actual initialisation of the extension. This is just a delayed init that
 * makes sure MediaWiki is set up properly before we add our stuff.
 *
 * The main things this function does are: register all hooks, set up extension
 * credits, and init some globals that are not for configuration settings.
 */
function haclfSetupExtension() {
    wfProfileIn('haclfSetupExtension');
    global $haclgIP, $wgHooks, $wgParser, $wgExtensionCredits,
    $wgLanguageCode, $wgVersion, $wgRequest, $wgContLang;
    
    // Initialize group permissions
    global $haclgUseFeaturesForGroupPermissions;
    if ($haclgUseFeaturesForGroupPermissions === true) {
	    HACLGroupPermissions::initDefaultPermissions();
	    HACLGroupPermissions::initPermissionsFromDB();
    }
    
   	haclfInitSemanticStores();

    global $haclgDoEnableTitleCheck;
    haclfRestoreTitlePatch($haclgDoEnableTitleCheck);

    //--- Register hooks ---
    global $wgHooks;
    $wgHooks['userCan'][] = 'HACLEvaluator::userCan';

    wfLoadExtensionMessages('HaloACL');
    ///// Register specials pages
    global $wgSpecialPages, $wgSpecialPageGroups;
    $wgSpecialPages['HaloACL']      = array('HaloACLSpecial');
    $wgSpecialPageGroups['HaloACL'] = 'hacl_group';

    $wgHooks['ArticleSaveComplete'][]  = 'HACLParserFunctions::articleSaveComplete';
    $wgHooks['ArticleSaveComplete'][]  = 'HACLDefaultSD::articleSaveComplete';
    $wgHooks['ArticleDelete'][]        = 'HACLParserFunctions::articleDelete';
    $wgHooks['OutputPageBeforeHTML'][] = 'HACLParserFunctions::outputPageBeforeHTML';
    $wgHooks['IsFileCacheable'][]      = 'haclfIsFileCacheable';
    $wgHooks['PageRenderingHash'][]    = 'haclfPageRenderingHash';
    $wgHooks['SpecialMovepageAfterMove'][] = 'HACLParserFunctions::articleMove';
	$wgHooks['SkinTemplateContentActions'][] = 'haclfRemoveProtectTab';
    $wgHooks['UserEffectiveGroups'][]  = 'HACLGroupPermissions::onUserEffectiveGroups';
    $wgHooks['BeforeParserFetchTemplateAndtitle'][] = 'HACLEvaluator::onBeforeParserFetchTemplateAndtitle';
    

    $wgHooks['FilterQueryResults'][] = 'HACLResultFilter::filterResult';
    $wgHooks['SmwhNewBaseStore'][] = 'haclfOnSmwhNewBaseStore';
    
    global $haclgProtectProperties;
    if ($haclgProtectProperties === true) {
        $wgHooks['RewriteQuery'][]       = 'HACLQueryRewriter::rewriteQuery';
        $wgHooks['DiffViewHeader'][]     = 'HACLEvaluator::onDiffViewHeader';
        $wgHooks['EditFilter'][]         = 'HACLEvaluator::onEditFilter';
        $wgHooks['PropertyBeforeOutput'][] = 'HACLEvaluator::onPropertyBeforeOutput';
        $wgHooks['BeforeDerivedPropertyQuery'][] = 'haclfAllowVariableForPredicate';
        $wgHooks['AfterDerivedPropertyQuery'][] = 'haclfDisallowVariableForPredicate';
        
    }

    global $haclgNewUserTemplate, $haclgDefaultQuickAccessRights;
    if (isset($haclgNewUserTemplate) || 
    	isset($haclgDefaultQuickAccessRightMasterTemplates)) {
        $wgHooks['UserLoginComplete'][] = 'HACLDefaultSD::newUser';
    }

    #	$wgHooks['InternalParseBeforeLinks'][] = 'SMWParserExtensions::onInternalParseBeforeLinks'; // parse annotations in [[link syntax]]

	/*
	 if( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
		$wgHooks['ParserFirstCallInit'][] = 'SMWParserExtensions::registerParserFunctions';
		} else {
		if ( class_exists( 'StubObject' ) && !StubObject::isRealObject( $wgParser ) ) {
		$wgParser->_unstub();
		}
		SMWParserExtensions::registerParserFunctions( $wgParser );
		}
		*/

    $spns_text = $wgContLang->getNsText(NS_SPECIAL);
    // register AddHTMLHeader functions for special pages
    // to include javascript and css files (only on special page requests).
    if (stripos($wgRequest->getRequestURL(), $spns_text.":HaloACL") !== false
        || stripos($wgRequest->getRequestURL(), $spns_text."%3AHaloACL") !== false) {
        $wgHooks['BeforePageDisplay'][]='haclAddHTMLHeader';
    }else {
        $wgHooks['BeforePageDisplay'][]='addNonSpecialPageHeader';

    }
    
    //-- Hooks for ACL toolbar--
//	$wgHooks['EditPageBeforeEditButtons'][] = 'haclfAddToolbarForEditPage';
	$wgHooks['EditPage::showEditForm:fields'][] = 'haclfAddToolbarForEditPage';
	$wgHooks['sfHTMLBeforeForm'][]     		= 'haclfAddToolbarForSemanticForms';
	$wgHooks['sfSetTargetName'][]           = 'haclfOnSfSetTargetName';
	$wgHooks['sfUserCanEditPage'][]         = 'HACLEvaluator::onSfUserCanEditPage';
	
    
    //-- includes for Ajax calls --
    global $wgUseAjax, $wgRequest;
    if ($wgUseAjax && $wgRequest->getVal('action') == 'ajax' ) {
		$funcName = isset( $_POST["rs"] ) 
						? $_POST["rs"] 
						: (isset( $_GET["rs"] ) ? $_GET["rs"] : NULL);
    	if (strpos($funcName, 'hacl') === 0) {
			require_once('HACL_Toolbar.php');
			require_once('HACL_AjaxConnector.php');
    	}
    }
    
    //--- credits (see "Special:Version") ---
    $wgExtensionCredits['other'][]= array(
        'name'=>'HaloACL',
        'version'=>HACL_HALOACL_VERSION,
        'author'=>"Thomas Schweitzer. Owned by [http://www.ontoprise.de ontoprise GmbH].",
        'url'=>'http://smwforum.ontoprise.com/smwforum/index.php/Help:Access_Control_List_extension',
        'description' => 'Protect the content of your wiki.');

    // Register autocompletion icon
    $wgHooks['smwhACNamespaceMappings'][] = 'haclfRegisterACIcon';


    // Handle input fields of Semantic Forms
    $wgHooks['sfCreateFormField'][] = 'haclfHandleFormField';
    wfProfileOut('haclfSetupExtension');
    return true;
}

/**
 *  adding headers for non-special-pages
 *  atm only used for toolbar-realted stuff
 *
 * @global <type> $haclgHaloScriptPath
 * @param <type> $out
 * @return <type>
 */
function addNonSpecialPageHeader(&$out) {
	global $wgRequest, $wgContLang;
	// scripts are needed at Special:FormEdit
    $spns_text = $wgContLang->getNsText(NS_SPECIAL);
	if ( ($wgRequest->getText('action', 'view') == 'view') 
		&& stripos($wgRequest->getRequestURL(), $spns_text.":FormEdit") == false
        && stripos($wgRequest->getRequestURL(), $spns_text."%3AFormEdit") == false ) {
		return true;
	}
    global $haclgHaloScriptPath, $smwgDeployVersion;
    haclAddJSLanguageScripts($out);
    if (!isset($smwgDeployVersion) || $smwgDeployVersion === false) {
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/yahoo-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/event-min.js"></script>');
        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/toolbar.js\"></script>");

        $out->addStyle($haclgHaloScriptPath . '/skins/haloacl.css', 'screen, projection');
        $out->addStyle($haclgHaloScriptPath . '/skins/haloacl_toolbar.css', 'screen, projection');
        $out->addStyle($haclgHaloScriptPath . '/yui/container.css', 'screen, projection');

        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/yuiloader-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/event-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/dom-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/treeview-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/element-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/button-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/connection-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/json-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/yahoo-dom-event.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/animation-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/tabview-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datasource-min.js"></script>');
        #$out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datasource-debug.js"></script>');

        #$out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datatable-debug.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datatable-min.js"></script>');

        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/paginator-min.js"></script>');

        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/container-min.js"></script>');
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/dragdrop-min.js"></script>');
        #$out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/autocomplete-min.js"></script>');

        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/haloacl.js\"></script>");

        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/groupuserTree.js\"></script>");
        #$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/rightsTree.js\"></script>");
        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/userTable.js\"></script>");
        #$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/pageTable.js\"></script>");
        #$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/manageUserTree.js\"></script>");
        #$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/whitelistTable.js\"></script>");
        #$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/autoCompleter.js\"></script>");
        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/notification.js\"></script>");
        #$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/quickaclTable.js\"></script>");

    } else {
        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/scripts/hacl-packed.js"></script>');
        $out->addStyle($haclgHaloScriptPath . '/skins/haloacl.css', 'screen, projection');
        $out->addStyle($haclgHaloScriptPath . '/skins/haloacl_toolbar.css', 'screen, projection');
        $out->addStyle($haclgHaloScriptPath . '/yui/container.css', 'screen, projection');

    }
    // -------------------

    return true;
}

/**
 * Adds Javascript and CSS files
 *
 * @param OutputPage $out
 * @return true
 */
function haclAddHTMLHeader(&$out) {

    global $wgTitle,$wgUser;

    global $haclgHaloScriptPath, $smwgDeployVersion;

    if ($wgTitle->getNamespace() != NS_SPECIAL) {
        return true;
    } else {
    	// Add global JS variables
    	global $haclgAllowLDAPGroupMembers;
    	$globalJSVar = "var haclgAllowLDAPGroupMembers = "
    				   . (($haclgAllowLDAPGroupMembers == true) ? 'true' : 'false')
    				   .';';
    	
    	$out->addScript('<script type="text/javascript">'.$globalJSVar.'</script>');
    	
    	
    	// Add language files
        haclAddJSLanguageScripts($out);

		if (!isset($smwgDeployVersion) || $smwgDeployVersion === false) {
			// ---- SPECIAL-PAGE related stuff ---
	
	
	        // -------------------
	        // YAHOO Part
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/yahoo-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/yuiloader-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/event-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/dom-min.js"></script>');
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/treeview-min.js"></script>');
	        #$out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/treeview-debug.js"></script>');
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/logger-min.js"></script>');
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/element-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/button-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/connection-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/json-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/yahoo-dom-event.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/animation-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/tabview-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datasource-min.js"></script>');
	        #$out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datasource-debug.js"></script>');
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datatable-min.js"></script>');
	        #$out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/datatable-debug.js"></script>');
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/paginator-min.js"></script>');
	
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/container-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/dragdrop-min.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/yui/autocomplete-min.js"></script>');
	
	        // -------------------
	        // -------------------
	
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/haloacl.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/groupuserTree.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/rightsTree.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/userTable.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/pageTable.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/manageUserTree.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/whitelistTable.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/autoCompleter.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/notification.js\"></script>");
	        $out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/quickaclTable.js\"></script>");
	        
	        //--- jQuery part ---
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/scripts/jsTree.v.0.9.9a/jquery.tree.min.js"></script>');
	        
	        //--- HACL ---
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/scripts/HACL_GroupTree.js"></script>');
	        $out->addScript('<script type="text/javascript" src="'. $haclgHaloScriptPath .  '/scripts/HACL_GroupPermission.js"></script>');
	        
	        
		} else {
			$out->addScript("<script type=\"text/javascript\" src=\"". $haclgHaloScriptPath .  "/scripts/specialhacl-packed.js\"></script>");
		}

        $out->addLink(array(
            'rel'   => 'stylesheet',
            'type'  => 'text/css',
            'media' => 'screen, projection',
            'href'  => $haclgHaloScriptPath. '/yui/container.css'
        ));

        $out->addLink(array(
            'rel'   => 'stylesheet',
            'type'  => 'text/css',
            'media' => 'screen, projection',
            'href'  => $haclgHaloScriptPath.'/yui/autocomplete.css'
        ));


        $out->addLink(array(
            'rel'   => 'stylesheet',
            'type'  => 'text/css',
            'media' => 'screen, projection',
            'href'  => $haclgHaloScriptPath . '/skins/haloacl.css'
        ));
        
        $out->addLink(array(
            'rel'   => 'stylesheet',
            'type'  => 'text/css',
            'media' => 'screen, projection',
            'href'  => $haclgHaloScriptPath . '/skins/haloacl_group_permissions.css'
        ));
        
        $out->addLink(array(
            'rel'   => 'stylesheet',
            'type'  => 'text/css',
            'media' => 'screen, projection',
            'href'  => $haclgHaloScriptPath . '/scripts/jsTree.v.0.9.9a/themes/haloacl/style.css'
        ));
        
        if(get_class($wgUser->getSkin()) == "SkinMonoBook") {
            $out->addLink(array(
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'screen, projection',
                'href'  => $haclgHaloScriptPath . '/skins/mono-fix.css'
            ));
        }


        //<!-- Sam Skin CSS for TabView -->

        return true;
    }
}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

/**
 * Init the additional namespaces used by HaloACL. The
 * parameter denotes the least unused even namespace ID that is
 * greater or equal to 100.
 */
function haclfInitNamespaces() {
    global $haclgNamespaceIndex, $wgExtraNamespaces, $wgNamespaceAliases,
    $wgNamespacesWithSubpages, $wgLanguageCode, $haclgContLang;

    if (!isset($haclgNamespaceIndex)) {
        $haclgNamespaceIndex = 300;
    }

    define('HACL_NS_ACL',       $haclgNamespaceIndex);
    define('HACL_NS_ACL_TALK',  $haclgNamespaceIndex+1);

    haclfInitContentLanguage($wgLanguageCode);

    // Register namespace identifiers
    if (!is_array($wgExtraNamespaces)) {
        $wgExtraNamespaces=array();
    }
    $namespaces = $haclgContLang->getNamespaces();
    $namespacealiases = $haclgContLang->getNamespaceAliases();
    $wgExtraNamespaces = $wgExtraNamespaces + $namespaces;
    $wgNamespaceAliases = $wgNamespaceAliases + $namespacealiases;

    // Support subpages for the namespace ACL
    $wgNamespacesWithSubpages = $wgNamespacesWithSubpages + array(
        HACL_NS_ACL => true,
        HACL_NS_ACL_TALK => true
    );
}


/**********************************************/
/***** language settings                  *****/
/**********************************************/

/**
 * Set up (possibly localised) names for HaloACL
 */
function haclfAddMagicWords(&$magicWords, $langCode) {
//	$magicWords['ask']     = array( 0, 'ask' );
    return true;
}

/**
 * Initialise a global language object for content language. This
 * must happen early on, even before user language is known, to
 * determine labels for additional namespaces. In contrast, messages
 * can be initialised much later when they are actually needed.
 */
function haclfInitContentLanguage($langcode) {
    global $haclgIP, $haclgContLang;
    if (!empty($haclgContLang)) {
        return;
    }
    wfProfileIn('haclfInitContentLanguage');

    $haclContLangFile = 'HACL_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
    $haclContLangClass = 'HACLLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );
    if (file_exists($haclgIP . '/languages/'. $haclContLangFile . '.php')) {
        include_once( $haclgIP . '/languages/'. $haclContLangFile . '.php' );
    }

    // fallback if language not supported
    if ( !class_exists($haclContLangClass)) {
        include_once($haclgIP . '/languages/HACL_LanguageEn.php');
        $haclContLangClass = 'HACLLanguageEn';
    }
    $haclgContLang = new $haclContLangClass();

    wfProfileOut('haclfInitContentLanguage');
}

/**
 * Returns the ID and name of the given user.
 *
 * @param User/string/int $user
 * 		User-object, name of a user or ID of a user. If <null> (which is the
 *      default), the currently logged in user is assumed.
 *      There are two special user names:
 * 			'*' - anonymous user (ID:0)
 *			'#' - all registered users (ID: -1)
 * @return array(int,string)
 * 		(Database-)ID of the given user and his name. For the sake of
 *      performance the name is not retrieved, if the ID of the user is
 * 		passed in parameter $user.
 * @throws
 * 		HACLException(HACLException::UNKOWN_USER)
 * 			...if the user does not exist.
 */
function haclfGetUserID($user = null) {
    $userID = false;
    $userName = '';
    if ($user === null) {
    // no user given
    // => the current user's ID is requested
        global $wgUser;
        $userID = $wgUser->getId();
        $userName = $wgUser->getName();
    } else if (is_int($user) || is_numeric($user)) {
        // user-id given
            $userID = (int) $user;
        } else if (is_string($user)) {
                if ($user == '#') {
                // Special name for all registered users
                    $userID = -1;
                } else if ($user == '*') {
                    // Anonymous user
                        $userID = 0;
                    } else {
                    // name of user given
                        $etc = haclfDisableTitlePatch();
                        $userID = (int) User::idFromName($user);
                        haclfRestoreTitlePatch($etc);
                        if (!$userID) {
                            $userID = false;
                        }
                        $userName = $user;
                    }
            } else if (is_a($user, 'User')) {
                // User-object given
                    $userID = $user->getId();
                    $userName = $user->getName();
                }

    if ($userID === 0) {
    //Anonymous user
        $userName = '*';
    } else if ($userID === -1) {
        // all registered users
            $userName = '#';
        }

    if ($userID === false) {
    // invalid user
        throw new HACLException(HACLException::UNKOWN_USER,'"'.$user.'"');
    }

    return array($userID, $userName);

}

/**
 * Pages in the namespace ACL are not cacheable
 *
 * @param Article $article
 * 		Check, if this article can be cached
 *
 * @return bool
 * 		<true>, for articles that are not in the namespace ACL
 * 		<false>, otherwise
 */
function haclfIsFileCacheable($article) {
    return $article->getTitle()->getNamespace() != HACL_NS_ACL;
}

/**
 * The hash for the page cache depends on the user.
 *
 * @param string $hash
 * 		A reference to the hash. This the ID of the current user is appended
 * 		to this hash.
 *
 *
 */
function haclfPageRenderingHash($hash) {

    global $wgUser, $wgTitle;
    if (is_object($wgUser)) {
        $hash .= '!'.$wgUser->getId();
    }
    if (is_object($wgTitle)) {
        if ($wgTitle->getNamespace() == HACL_NS_ACL) {
        // How often do we have to say that articles in the namespace ACL
        // can not be cached ?
            $hash .= '!'.wfTimestampNow();
        }

    }
    return true;
}

/**
 * A patch in the Title-object checks for each creation of a title, if access
 * to this title is granted. While the rights for a title are evaluated, this
 * may lead to a recursion. So the patch can be switched off. After the critical
 * operation (typically Title::new... ), the patch should be switched on again with
 * haclfRestoreTitlePatch().
 *
 * @return bool
 * 		The current state of the Title-patch. This value has to be passed to
 * 		haclfRestoreTitlePatch().
 */
function haclfDisableTitlePatch() {
    global $haclgEnableTitleCheck;
    $etc = $haclgEnableTitleCheck;
    $haclgEnableTitleCheck = false;
    return $etc;
}

/**
 * See documentation of haclfDisableTitlePatch
 *
 * @param bool $etc
 * 		The former state of the title patch.
 */
function haclfRestoreTitlePatch($etc) {
    global $haclgEnableTitleCheck;
    $haclgEnableTitleCheck = $etc;
}

/**
 * Returns the article ID for a given article name. This function has a special
 * handling for Special pages, which do not have an article ID. HaloACL stores
 * special IDs for these pages. Their IDs are always negative while the IDs of
 * normal pages are positive.
 *
 * @param string $articleName
 * 		Name of the article
 * @param int $defaultNS
 * 		The default namespace if no namespace is given in the name
 *
 * @return int
 * 		ID of the article:
 * 		>0: ID of an article in a normal namespace
 * 		=0: Name of the article is invalid
 * 		<0: ID of a Special Page
 *
 */
function haclfArticleID($articleName, $defaultNS = NS_MAIN) {
    $etc = haclfDisableTitlePatch();
    $t = Title::newFromText($articleName, $defaultNS);
    haclfRestoreTitlePatch($etc);
    if (is_null($t)) {
        return 0;
    }
    $id = $t->getArticleID();
    if ($id === 0) {
        $id = $t->getArticleID(GAID_FOR_UPDATE);
    }
    if ($id == 0 && $t->getNamespace() == NS_SPECIAL) {
        $id = HACLStorage::getDatabase()->idForSpecial($articleName);
    }
    return $id;

}

/**
 * If SMW is present, its semantic store is wrapped so that access to
 * properties and protected pages can be restricted.
 * The stores of SMW and the Halo extension are wrapped.
 */
function haclfInitSemanticStores() {
	if (!defined('SMW_VERSION')) {
		return;
	}
	
	// Wrap the semantic store of SMW
	global $smwgMasterStore;
	$smwStore = smwfGetStore();
	$wrapper = new HACLSMWStore($smwStore);
	$smwgMasterStore = $wrapper;
	
}

/**
 * This function is called when a new base store is created in SMWHalo. The
 * given store is wrapped with a HACLSMWStore.
 * @param SMWStore $store
 * 		This is an instance of a SMWStore. It is wrapped by a HACLSMWStore.
 */
function haclfOnSmwhNewBaseStore(&$store) {
	$store = new HACLSMWStore($store);
	return true;
}

/**
 * Add appropriate JS language script
 */
function haclAddJSLanguageScripts(& $jsm, $mode = "all", $namespace = -1, $pages = array()) {
    global $haclgIP, $haclgHaloScriptPath, $wgUser;

    // content language file
    $jsm->addScript('<script type="text/javascript" src="'.$haclgHaloScriptPath . '/scripts/Language/HaloACL_Language.js'.  '"></script>', $mode, $namespace, $pages);
    $lng = '/scripts/Language/HaloACL_Language';
    if (isset($wgUser)) {
        $lng .= ucfirst($wgUser->getOption('language')).'.js';
        if (file_exists($haclgIP . $lng)) {
            $jsm->addScript('<script type="text/javascript" src="'.$haclgHaloScriptPath . $lng .  '"></script>', $mode, $namespace, $pages);
        } else {
            $jsm->addScript('<script type="text/javascript" src="'.$haclgHaloScriptPath . '/scripts/Language/HaloACL_LanguageEn.js'.  '"></script>', $mode, $namespace, $pages);
        }
    } else {
        $jsm->addScript('<script type="text/javascript" src="'.$haclgHaloScriptPath . '/scripts/Language/HaloACL_LanguageEn.js'.  '"></script>', $mode, $namespace, $pages);
    }

}

/**
* This function is called from the hook 'EditPageBeforeEditButtons'. It adds the
* ACL toolbar to edited pages.
*  
*/
function haclfAddToolbarForEditPage ($content_actions) {
    if ($content_actions->mArticle->mTitle->mNamespace == HACL_NS_ACL) {
        return $content_actions;
    }
    global $haclgIP;
    $html = <<<HTML
        <script>
            YAHOO.haloacl.toolbar.actualTitle = '{$content_actions->mTitle}';
            YAHOO.haloacl.toolbar.loadContentToDiv('content','haclGetHACLToolbar',{title:'{$content_actions->mTitle}'});
        </script>
HTML;
    $content_actions->editFormTextBeforeContent .= $html;

    return true;
}

/**
* This function is called from the hook 'EditPageBeforeEditButtons'. It adds the
* ACL toolbar to a semantic form.
*  
*/
function haclfAddToolbarForSemanticForms($pageTitle, $html) {
    global $haclgIP;
    if (empty($pageTitle)) return true;
    $html = <<<HTML
    		<script>
	            YAHOO.haloacl.toolbar.actualTitle = '$pageTitle';
	            YAHOO.haloacl.toolbar.loadContentToDiv('content','haclGetHACLToolbar',{title:'$pageTitle'});
	        </script>
HTML;

    return true;
}

/**
* This function is called from the hook 'sfSetTargetName' in SemanticForms. It adds a
* JavaScript line that initializes a variable with the namespace number of the
* current title.
* 
* @param string $titleName
* 	Name of the article that is edited with Semantic Forms
*  
*/
function haclfOnSfSetTargetName($titleName) {
	global $wgOut, $wgJsMimeType;
	if (!empty($titleName)) {
		$t = Title::newFromText($titleName);
		$namespace = $t->getNamespace();
		$script = "<script type= \"$wgJsMimeType\">/*<![CDATA[*/\n";
		$script .= "sfgTargetNamespaceNumber = $namespace;";
		$script .= "\n/*]]>*/</script>\n";
			
		$wgOut->addScript($script);
	}
	return true;
}

/**
 * Normally the query rewriter does not allow queries with a variable for a 
 * predicate. This function turns this protection off.
 */
function haclfAllowVariableForPredicate() {
	HACLQueryRewriter::allowVariableForPredicate(true);
	return true;
}

/**
 * Normally the query rewriter does not allow queries with a variable for a 
 * predicate. This function turns this protection on.
 */
function haclfDisallowVariableForPredicate() {
	HACLQueryRewriter::allowVariableForPredicate(false);
	return true;
}

/**
 * Registers the icon for Auto Completion.
 * 
 * @param $namespaceMappings
 */
function haclfRegisterACIcon(& $namespaceMappings) {
    global $haclgIP;
    $namespaceMappings[HACL_NS_ACL]="/extensions/HaloACL/skins/images/ACL_AutoCompletion.gif";
    return true;
}

/**
 * Removes the tab "Protect"
 * 
 * @param $content_actions
 */
function haclfRemoveProtectTab( &$content_actions ) {
    if (array_key_exists('protect', $content_actions))
		unset($content_actions['protect']);
    return true;
}

// encrypt() and decrypt() functions copied from
// http://us2.php.net/manual/en/ref.mcrypt.php#52384
function haclfEncrypt($string) {
    global $haclgEncryptionKey;
    $result = '';
    for($i=0; $i<strlen($string); $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($haclgEncryptionKey, ($i % strlen($haclgEncryptionKey))-1, 1);
        $char = chr(ord($char)+ord($keychar));
        $result .= $char;
    }
    return base64_encode($result);
}

function haclfDecrypt($string) {
    global $haclgEncryptionKey;
    $result = '';
    $string = base64_decode($string);
    for($i=0; $i<strlen($string); $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($haclgEncryptionKey, ($i % strlen($haclgEncryptionKey))-1, 1);
        $char = chr(ord($char)-ord($keychar));
        $result .= $char;
    }
    return $result;
}

function haclfHandleFormField($form_field, $cur_value, $form_submitted) {
    $property_name = $form_field->template_field->semantic_property;
    if (! empty($property_name)) {
        $property_title = Title::makeTitleSafe(SMW_NS_PROPERTY, $property_name);
        if (!isset($property_title)) {
            return true;
        }
        if ($property_title->exists()) {
            $form_field->is_disabled = false;
            if (! $property_title->userCan('propertyread')) {
                if ($form_submitted) {
                    $cur_value = haclfDecrypt($cur_value);
                } else {
                    $form_field->is_hidden = true;
                    $cur_value = haclfEncrypt($cur_value);
                }
            } elseif ((! $property_title->userCan('propertyedit')) && (! $property_title->userCan('propertyformedit'))) {
                $form_field->is_disabled = true;
            }
        }
    }
    return true;
}

