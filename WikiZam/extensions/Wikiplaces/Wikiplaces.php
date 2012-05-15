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
   'description' => 'Provide a personal place in MediaWiki.',
   'version'  => 'alpha',
   );

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



# i18n
$wgExtensionMessagesFiles['Wikiplaces'] = $_dir . 'Wikiplaces.i18n.php';

# Name aliases
$wgExtensionAliasesFiles['Wikiplaces'] = $_dir . 'Wikiplaces.alias.php';

# Add SpecialPages
$wgSpecialPages['Wikiplaces'] = 'SpecialWikiplaces';
$wgSpecialPageGroups['Wikiplaces'] = 'other';
$wgSpecialPages['Subscriptions'] = 'SpecialSubscriptions';
$wgSpecialPageGroups['Subscriptions'] = 'other';
$wgSpecialPages['WikiplacesAdmin'] = 'SpecialWikiplacesAdmin';
$wgSpecialPageGroups['WikiplacesAdmin'] = 'other';
$wgSpecialPages['Offers'] = 'SpecialOffers';
$wgSpecialPageGroups['Offers'] = 'other';

# Attach our own functions to hooks
$wgHooks['LoadExtensionSchemaUpdates'][] = 'WikiplacesHooks::onLoadExtensionSchemaUpdates'; // Schema updates for update.php
$wgHooks['ArticleInsertComplete'][] = 'WikiplacesHooks::onArticleInsertComplete';
$wgHooks['TitleMoveComplete'][] = 'WikiplacesHooks::onTitleMoveComplete';
$wgHooks['ArticleDeleteComplete'][] = 'WikiplacesHooks::onArticleDeleteComplete';
$wgHooks['ArticleUndelete'][] = 'WikiplacesHooks::onArticleUndelete';
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'WikiplacesHooks::skinTemplateOutputPageBeforeExec'; //Amend template for WP related front-end element (eg. background)

$wgHooks['userCan'][] = 'WikiplacesHooks::userCan';
$wgHooks['TransactionUpdated'][] = 'WikiplacesHooks::onTransactionUpdated';
$wgHooks['IsOwner'][] = 'WikiplacesHooks::isOwner';

$wgHooks['SkinTemplateNavigation'][] = 'WikiplacesHooks::SkinTemplateNavigation'; // Remove delete from action menu if necessary

// right for accessing wp admin page, bypass move/delete limitations
define('WP_ADMIN_RIGHT', 'wp-admin');
$wgAvailableRights[] = WP_ADMIN_RIGHT; 
$wgGroupPermissions['sysop'][WP_ADMIN_RIGHT] = true;

// define the group in which to add the user in when she makes her first subscription
// (she will not be removed, even if she has no more active subscription)
define('WP_SUBSCRIBERS_USER_GROUP', 'artist');

$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['move']	= true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['move-subpages'] = true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['suppressredirect'] = true;
$wgGroupPermissions[WP_SUBSCRIBERS_USER_GROUP]['delete'] = true;

// all applicable actions except 'read' will be set to this level when creating a page/file in wikiplace namespaces
define('WP_DEFAULT_RESTRICTION_LEVEL', 'owner');



# Extra namespace for Wikiplace configuration (bg, nav...) settings
define("NS_WIKIPLACE", 70);
define("NS_WIKIPLACE_TALK", 71);
 
$wgExtraNamespaces[NS_WIKIPLACE] = "Wikiplace";
$wgExtraNamespaces[NS_WIKIPLACE_TALK] = "Wikiplace_talk";   # underscore required

/*
 * @TODO create a generic right that covers all actions only an artist can perform
$wgNamespaceProtection[NS_WIKIPLACE] = array( 'editwikiplace' );
$wgNamespaceProtection[NS_WIKIPLACE_TALK] = array( 'editwikiplace' );
*/

$wgNamespacesWithSubpages[NS_WIKIPLACE] = true; 
$wgNamespacesWithSubpages[NS_WIKIPLACE_TALK] = true;


define('WPBACKGROUNDKEY', 'background'); // Background configuration is at seizam.com/WpName/WPBACKGROUNDKEY
define('WPNAVIGATIONKEY', 'navigation'); // Navigation configuration is at seizam.com/WpName/WPNAVIGATIONKEY



# Array of namespaces influenced by this extension
$wgWikiplaceNamespaces = array(NS_MAIN, NS_TALK, NS_FILE, NS_FILE_TALK, NS_WIKIPLACE, NS_WIKIPLACE_TALK);