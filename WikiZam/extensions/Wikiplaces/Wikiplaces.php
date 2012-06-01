<?php

/*
 * Wikiplace extension, developped by Yann Missler, Seizam SARL
 * www.seizam.com
 */

if (!defined('MEDIAWIKI')) {
	echo "Wikiplace extension\n";
    die(-1);
}

$wgExtensionCredits['other'][] = array(
   'path' => __FILE__,
   'name' => 'Wikiplace',
   'author' => array('Yann Missler', 'Seizam'), 
   'url' => 'http://www.seizam.com', 
   'descriptionmsg' => 'wp-desc',
   'version'  => '0.1.0',
   );


define('WP_ACCESS_RIGHT', 'wpaccess');
$wgAvailableRights[] = WP_ACCESS_RIGHT; 
$wgGroupPermissions['user'][WP_ACCESS_RIGHT] = true;

// who can admin wikiplaces ? (accessing wp admin page, bypass move/delete limitations)
define('WP_ADMIN_RIGHT', 'wpadmin');
$wgAvailableRights[] = WP_ADMIN_RIGHT; 
$wgGroupPermissions['sysop'][WP_ADMIN_RIGHT] = true;

$_dir = dirname( __FILE__ ).'/';

# Load extension's classes
$wgAutoloadClasses['WikiplacesHooks'] = $_dir . 'Wikiplaces.hooks.php';
$wgAutoloadClasses['SpecialWikiplaces'] = $_dir . 'SpecialWikiplaces.php';
$wgAutoloadClasses['SpecialSubscriptions'] = $_dir . 'SpecialSubscriptions.php';
$wgAutoloadClasses['SpecialOffers'] = $_dir . 'SpecialOffers.php';
$wgAutoloadClasses['SpecialWikiplacesAdmin'] = $_dir . 'SpecialWikiplacesAdmin.php';
$wgAutoloadClasses['WpWikiplace'] = $_dir . 'model/WpWikiplace.php';
$wgAutoloadClasses['WpWikiplacesTablePager'] = $_dir . 'model/WpWikiplacesTablePager.php';
$wgAutoloadClasses['WpPage'] = $_dir . 'model/WpPage.php';
$wgAutoloadClasses['WpPagesTablePager'] = $_dir . 'model/WpPagesTablePager.php';
$wgAutoloadClasses['WpPlan'] = $_dir . 'model/WpPlan.php';
$wgAutoloadClasses['WpSubscription'] = $_dir . 'model/WpSubscription.php';
$wgAutoloadClasses['WpSubscriptionsTablePager'] = $_dir . 'model/WpSubscriptionsTablePager.php';
$wgAutoloadClasses['WikiplaceUpload'] = $_dir . 'WikiplaceUpload.php';

# i18n
$wgExtensionMessagesFiles['Wikiplaces'] = $_dir.'Wikiplaces.i18n.php';
$wgExtensionMessagesFiles['Wikiplaces.mail'] = $_dir.'Wikiplaces.mail.i18n.php';

# Name aliases
$wgExtensionAliasesFiles['Wikiplaces'] = $_dir . 'Wikiplaces.alias.php';

# Add SpecialPages
$wgSpecialPages['Wikiplaces'] = 'SpecialWikiplaces';
$wgSpecialPageGroups['Wikiplaces'] = 'wikiplace';
$wgSpecialPages['Subscriptions'] = 'SpecialSubscriptions';
$wgSpecialPageGroups['Subscriptions'] = 'wikiplace';
$wgSpecialPages['WikiplacesAdmin'] = 'SpecialWikiplacesAdmin';
$wgSpecialPageGroups['WikiplacesAdmin'] = 'wikiplace';
$wgSpecialPages['Offers'] = 'SpecialOffers';
$wgSpecialPageGroups['Offers'] = 'wikiplace';

// define the default renewal plan
define('WP_FALLBACK_PLAN_ID', 1);

// Define the virtual plan id indicating "do not renew" in wps_renew_wpp_id field
define('WPS_RENEW_WPP_ID__DO_NOT_RENEW', 0);

// Tmr type used for subscriptions in tm
define('WP_SUBSCRIPTION_TMR_TYPE','subscrip');

// define the group in which to add the user in when she makes her first subscription
// (she will not be removed, even if she has no more active subscription)
define('WP_SUBSCRIBERS_USER_GROUP', 'artist');

$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['move']	= true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['move-subpages'] = true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['movefile'] = true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['suppressredirect'] = true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['delete'] = true;

// all applicable actions except 'read' will be set to this level when creating a page/file in wikiplace namespaces
define('WP_DEFAULT_RESTRICTION_LEVEL', 'owner');

# Extra namespace for Wikiplace configuration (bg, nav...) settings
define("NS_WIKIPLACE", 70);
define("NS_WIKIPLACE_TALK", 71);
$wgExtraNamespaces[NS_WIKIPLACE] = "Wikiplace";
$wgExtraNamespaces[NS_WIKIPLACE_TALK] = "Wikiplace_talk";   # underscore required

$wgNamespacesWithSubpages[NS_WIKIPLACE] = true; 
$wgNamespacesWithSubpages[NS_WIKIPLACE_TALK] = true;

define('WPBACKGROUNDKEY', 'background'); // Background configuration is at seizam.com/WpName/WPBACKGROUNDKEY
define('WPNAVIGATIONKEY', 'navigation'); // Navigation configuration is at seizam.com/WpName/WPNAVIGATIONKEY

# Array of namespaces influenced by this extension
$wgWikiplaceNamespaces = array(NS_MAIN, NS_TALK, NS_FILE, NS_FILE_TALK, NS_WIKIPLACE, NS_WIKIPLACE_TALK);

define('WP_PUBLIC_FILE_PREFIX', 'Public'); // case sensitive, public files (same behaviour as MW default)
define('WP_ADMIN_FILE_PREFIX', 'Seizam'); // case sensitive, admin file, public read, only wp-admin can edit

// blacklist not case sensitive when cheking, but all items as to be defined in lower case
$wgWikiplaceNameBlacklist = array(
    strtolower(WP_PUBLIC_FILE_PREFIX),
    strtolower(WP_ADMIN_FILE_PREFIX),
    'wikiplace',
    'seizam',
    'admin',
    'user',
    'file',
    'project' );
    
// deferred setup, to not break Hook execution ordering with PreventDuplicate extension
$wgExtensionFunctions[] = 'setupWikiplaces';

function setupWikiplaces() {
	global $wgHooks;
	# Attach our own functions to hooks
	$wgHooks['LoadExtensionSchemaUpdates'][] = 'WikiplacesHooks::onLoadExtensionSchemaUpdates'; // Schema updates for update.php
	$wgHooks['ArticleInsertComplete'][] = 'WikiplacesHooks::onArticleInsertComplete';
	$wgHooks['TitleMoveComplete'][] = 'WikiplacesHooks::onTitleMoveComplete';
	$wgHooks['ArticleDeleteComplete'][] = 'WikiplacesHooks::onArticleDeleteComplete';
	$wgHooks['ArticleUndelete'][] = 'WikiplacesHooks::onArticleUndelete';
	$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'WikiplacesHooks::skinTemplateOutputPageBeforeExec'; //Amend template for WP related front-end element (eg. background)
	$wgHooks['getUserPermissionsErrors'][] = 'WikiplacesHooks::getUserPermissionsErrors'; //former usercan
	$wgHooks['TransactionUpdated'][] = 'WikiplacesHooks::onTransactionUpdated';
	$wgHooks['IsOwner'][] = 'WikiplacesHooks::isOwner';
	$wgHooks['ImgAuthFullyStreamedFile'][] = 'WikiplacesHooks::onImgAuthFullyStreamedFile';
	$wgHooks['SkinTemplateNavigation'][] = 'WikiplacesHooks::SkinTemplateNavigation'; // Remove delete from action menu if necessary
	$wgHooks['EditPageCopyrightWarning'][] = 'WikiplacesHooks::EditPageCopyrightWarning'; // Cooks the edit page warning

	// upload form and handler hooks
	$wgHooks['UploadCreateFromRequest'][] = 'WikiplaceUpload::installWikiplaceUploadHandler';
	$wgHooks['UploadForm:initial'][] = 'WikiplaceUpload::fetchRequestInformations';
	$wgHooks['UploadForm:BeforeProcessing'][] = 'WikiplaceUpload::fetchRequestInformations';
	$wgHooks['UploadFormInitDescriptor'][] = 'WikiplaceUpload::installWikiplaceUploadFrontend';
}