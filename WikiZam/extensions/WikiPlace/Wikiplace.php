<?php

/*
 * Wikiplace extension, developped by Yann Missler, Seizam SARL
 * www.seizam.com
 */

if (!defined('MEDIAWIKI')) {
	echo "WikiPlace extension\n";
    die(-1);
}

$wgExtensionCredits['other'][] = array(
   'path' => __FILE__,
   'name' => 'WikiPlace',
   'author' =>'Yann Missler, Seizam SARL', 
   'url' => 'http://www.seizam.com', 
   'description' => 'Provide a personal place in MediaWiki.',
   'version'  => 'alpha',
   );

$_dir = dirname( __FILE__ ).'/';

# Load extension's classes
$wgAutoloadClasses['WikiplaceHooks']			= $_dir . 'WikiplaceHooks.php';
$wgAutoloadClasses['SpecialWikiplace']			= $_dir . 'SpecialWikiplace.php';
$wgAutoloadClasses['WpWikiplace']				= $_dir . 'model/WpWikiplace.php';

# i18n
$wgExtensionMessagesFiles['Wikiplace']			= $_dir . 'Wikiplace.i18n.php';

# Name aliases
$wgExtensionAliasesFiles['Wikiplace']			= $_dir . 'Wikiplace.alias.php';

# Add the SpecialPage
$wgSpecialPages['Wikiplace']					= 'SpecialWikiplace';
$wgSpecialPageGroups['Wikiplace']				= 'other';

# Attach our own functions to hooks
$wgHooks['LoadExtensionSchemaUpdates'][] = 'WikiplaceHooks::onLoadExtensionSchemaUpdates'; // Schema updates for update.php

