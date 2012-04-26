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
$wgAutoloadClasses['SpecialWikiplacesPlan'] = $_dir . 'SpecialWikiplacesPlan.php';

$wgAutoloadClasses['WpWikiplace'] = $_dir . 'model/WpWikiplace.php';
$wgAutoloadClasses['WpWikiplaceTablePager'] = $_dir . 'model/WpWikiplaceTablePager.php';

$wgAutoloadClasses['WpPage'] = $_dir . 'model/WpPage.php';
$wgAutoloadClasses['WpPageTablePager'] = $_dir . 'model/WpPageTablePager.php';

$wgAutoloadClasses['WpPlan'] = $_dir . 'model/WpPlan.php';

$wgAutoloadClasses['WpSubscription'] = $_dir . 'model/WpSubscription.php';
$wgAutoloadClasses['WpOldSubscription'] = $_dir . 'model/WpOldSubscription.php';
$wgAutoloadClasses['WpSubscriptionsTablePager'] = $_dir . 'model/WpSubscriptionsTablePager.php';

$wgAutoloadClasses['WpOldUsage'] = $_dir . 'model/WpOldUsage.php';



# i18n
$wgExtensionMessagesFiles['Wikiplaces'] = $_dir . 'Wikiplaces.i18n.php';

# Name aliases
$wgExtensionAliasesFiles['Wikiplaces'] = $_dir . 'Wikiplaces.alias.php';

# Add the SpecialPage
$wgSpecialPages['Wikiplaces'] = 'SpecialWikiplaces';
$wgSpecialPageGroups['Wikiplaces'] = 'other';
$wgSpecialPages['WikiplacesPlan'] = 'SpecialWikiplacesPlan';
$wgSpecialPageGroups['WikiplacesPlan'] = 'other';

# Attach our own functions to hooks
$wgHooks['LoadExtensionSchemaUpdates'][] = 'WikiplacesHooks::onLoadExtensionSchemaUpdates'; // Schema updates for update.php
$wgHooks['ArticleInsertComplete'][] = 'WikiplacesHooks::onArticleInsertComplete';
$wgHooks['userCan'][] = 'WikiplacesHooks::userCanCreate';
$wgHooks['TransactionUpdated'][] = 'WikiplacesHooks::onTransactionUpdated';
$wgHooks['IsOwner'][] = 'WikiplacesHooks::isOwner';


// define the group to put the user in when she makes her first subscription
// (not removed later, even if she has no more active subscription)
define('WP_SUBSCRIBERS_USER_GROUP', 'artist');