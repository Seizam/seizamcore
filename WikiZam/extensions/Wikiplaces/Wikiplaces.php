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

$wgHooks['userCan'][] = 'WikiplacesHooks::userCan';
$wgHooks['TransactionUpdated'][] = 'WikiplacesHooks::onTransactionUpdated';
$wgHooks['IsOwner'][] = 'WikiplacesHooks::isOwner';

define('WP_ADMIN_RIGHT', 'wp-admin');
$wgAvailableRights[] = WP_ADMIN_RIGHT; 
$wgGroupPermissions['sysop'][WP_ADMIN_RIGHT] = true;

// define the group in which to add the user in when she makes her first subscription
// (whe will not be removed, even if she has no more active subscription)
define('WP_SUBSCRIBERS_USER_GROUP', 'artist');

// all applicable actions except 'read' will be set to this level when creating a page/file in wikiplace namespaces
define('WP_DEFAULT_RESTRICTION_LEVEL', 'owner');