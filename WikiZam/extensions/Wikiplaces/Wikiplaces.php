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
   'version'  => '1.2',
   );

// defines who can access to Wikiplaces related Special pages: Special:Wikiplaces and Special:Subscriptions
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
$wgAutoloadClasses['SpecialPlans'] = $_dir . 'SpecialPlans.php';
$wgAutoloadClasses['SpecialWikiplacesAdmin'] = $_dir . 'SpecialWikiplacesAdmin.php';
$wgAutoloadClasses['WpWikiplace'] = $_dir . 'model/WpWikiplace.php';
$wgAutoloadClasses['WpWikiplacesTablePager'] = $_dir . 'model/WpWikiplacesTablePager.php';
$wgAutoloadClasses['WpPage'] = $_dir . 'model/WpPage.php';
$wgAutoloadClasses['WpPagesTablePager'] = $_dir . 'model/WpPagesTablePager.php';
$wgAutoloadClasses['WpPlan'] = $_dir . 'model/WpPlan.php';
$wgAutoloadClasses['WpPlansTablePager'] = $_dir . 'model/WpPlansTablePager.php';
$wgAutoloadClasses['WpSubscription'] = $_dir . 'model/WpSubscription.php';
$wgAutoloadClasses['WpSubscriptionsTablePager'] = $_dir . 'model/WpSubscriptionsTablePager.php';
$wgAutoloadClasses['WikiplaceUpload'] = $_dir . 'WikiplaceUpload.php';
$wgAutoloadClasses['WpHomepageTemplate'] = $_dir . 'model/HtmlTemplateField.php';
$wgAutoloadClasses['WpSubpageTemplate'] = $_dir . 'model/HtmlTemplateField.php';
$wgAutoloadClasses['WpInvitation'] = $_dir . 'model/WpInvitation.php';
$wgAutoloadClasses['WpInvitationCategory'] = $_dir . 'model/WpInvitationCategory.php';
$wgAutoloadClasses['WpInvitationsTablePager'] = $_dir . 'model/WpInvitationsTablePager.php';
$wgAutoloadClasses['WpInvitationsTablePagerAdmin'] = $_dir . 'model/WpInvitationsTablePager.php';
$wgAutoloadClasses['SpecialInvitation'] = $_dir . 'SpecialInvitation.php';
$wgAutoloadClasses['SpecialInvitations'] = $_dir . 'SpecialInvitations.php';
$wgAutoloadClasses['WpMembersTablePager'] = $_dir . 'model/WpMembersTablePager.php';
$wgAutoloadClasses['WpMember'] = $_dir . 'model/WpMember.php';

# i18n
$_i18n_dir = $_dir.'i18n/';
$wgExtensionMessagesFiles['Wikiplaces'] = $_i18n_dir.'Main.i18n.php';
$wgExtensionMessagesFiles['Wikiplaces.invitations'] = $_i18n_dir.'Invitations.i18n.php';
$wgExtensionMessagesFiles['Wikiplaces.plans'] = $_i18n_dir.'Plans.i18n.php';
$wgExtensionMessagesFiles['Wikiplaces.mail'] = $_i18n_dir.'Mail.i18n.php';
$wgExtensionMessagesFiles['Wikiplaces.members'] = $_i18n_dir.'Members.i18n.php';

# Name aliases
$wgExtensionAliasesFiles['Wikiplaces'] = $_dir . 'Wikiplaces.alias.php';

# Add SpecialPages
$wgSpecialPages['Wikiplaces'] = 'SpecialWikiplaces';
$wgSpecialPageGroups['Wikiplaces'] = 'wikiplace';
$wgSpecialPages['Subscriptions'] = 'SpecialSubscriptions';
$wgSpecialPageGroups['Subscriptions'] = 'wikiplace';
$wgSpecialPages['WikiplacesAdmin'] = 'SpecialWikiplacesAdmin';
$wgSpecialPageGroups['WikiplacesAdmin'] = 'wikiplace';
$wgSpecialPages['Plans'] = 'SpecialPlans';
$wgSpecialPageGroups['Plans'] = 'wikiplace';
$wgSpecialPages['Invitation'] = 'SpecialInvitation';
$wgSpecialPageGroups['Invitation'] = 'wikiplace';
$wgSpecialPages['Invitations'] = 'SpecialInvitations';
$wgSpecialPageGroups['Invitations'] = 'wikiplace';

// define the default renewal plan
define('WP_FALLBACK_PLAN_ID', 1);

// Define the virtual plan id indicating "do not renew" in wps_renew_wpp_id field
define('WPP_ID_NORENEW', 0);

// Tmr type used for subscriptions in tm
define('WP_SUBSCRIPTION_TMR_TYPE_NEW','wpsnew');
define('WP_SUBSCRIPTION_TMR_TYPE_RENEW','wpsrenew');

// define the group in which to add the user in when she makes her first subscription
// (she will not be removed, even if she has no more active subscription)
define('WP_SUBSCRIBERS_USER_GROUP', 'artist');

// define rights which has to b available for subscribers in their Wikiplaces, BUT to be FORBIDDEN everywhere else
// (if manually granting a right to the subscriber group instead of this array, the right will be available everywhere)
// (WP-ADMIN can still perform them out of wikiplaces)
$wgWpSubscribersExtraRights = array(
	'move',
	'move-subpages',
	'movefile',
	'suppressredirect',
	'delete',
    'autopatrol'
);
foreach ( $wgWpSubscribersExtraRights as $right ) {
	$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP][$right] = true;
}

// define the "artist" right, which let easily identify subscribers from other users
define('WP_ARTIST_RIGHT', 'makeart');
$wgAvailableRights[] = WP_ARTIST_RIGHT;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP][WP_ARTIST_RIGHT] = true;



// all applicable actions except 'read' will be set to this level when creating a page/file in wikiplace namespaces
define('WP_DEFAULT_RESTRICTION_LEVEL', 'member');


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

// blacklist not case sensitive when checking, but all items as to be defined in lower case
$wgWikiplaceNameBlacklist = array(
    strtolower(WP_PUBLIC_FILE_PREFIX),
    strtolower(WP_ADMIN_FILE_PREFIX),
    'wikiplace',
    'seizam',
    'admin',
    'user',
    'file',
    'project',
	'icons', // default apache2 folder /icons/ contains public icons, that may be reused bu users on their wikipages
	'files', // our public files
	);

// i18n message (often a page in MediaWiki namespace) containing templates listing
// (same syntax as MediaWiki:Licenses)
define ('WP_TEMPLATES_FOR_HOMEPAGE', 'Templates for Homepage'); // MediaWiki:Templates for Homepage
define ('WP_TEMPLATES_FOR_SUBPAGE', 'Templates for Subpage'); // MediaWiki:Templates for Subpage

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
	$wgHooks['IsMember'][] = 'WikiplacesHooks::isMember';
	$wgHooks['ImgAuthFullyStreamedFile'][] = 'WikiplacesHooks::onImgAuthFullyStreamedFile';
	$wgHooks['SkinTemplateNavigation'][] = 'WikiplacesHooks::SkinTemplateNavigation'; // Remove delete from action menu if necessary
	$wgHooks['EditPageCopyrightWarning'][] = 'WikiplacesHooks::EditPageCopyrightWarning'; // Cooks the edit page warning

	// upload form and handler hooks
	$wgHooks['UploadCreateFromRequest'][] = 'WikiplaceUpload::installWikiplaceUploadHandler';
	$wgHooks['UploadForm:initial'][] = 'WikiplaceUpload::fetchRequestInformations';
	$wgHooks['UploadForm:BeforeProcessing'][] = 'WikiplaceUpload::fetchRequestInformations';
	$wgHooks['UploadFormInitDescriptor'][] = 'WikiplaceUpload::installWikiplaceUploadFrontend';
}
