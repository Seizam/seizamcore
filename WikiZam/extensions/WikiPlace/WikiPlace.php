<?php

/*
 * WikiPlace extension, developped by Yann Missler, Seizam SARL
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
$wgAutoloadClasses['WikiPlaceHooks']			= $_dir . 'WikiPlaceHooks.php';
$wgAutoloadClasses['SpecialWikiPlace']			= $_dir . 'SpecialWikiPlace.php';
$wgAutoloadClasses['WpWikiPlace']				= $_dir . 'model/WpWikiPlace.php';

# i18n
$wgExtensionMessagesFiles['WikiPlace']			= $_dir . 'WikiPlace.i18n.php';

# Name aliases
$wgExtensionAliasesFiles['WikiPlace']			= $_dir . 'WikiPlace.alias.php';

# Add the SpecialPage
$wgSpecialPages['WikiPlace']					= 'SpecialWikiPlace';
$wgSpecialPageGroups['WikiPlace']				= 'other';

# Attach our own functions to hooks
$wgHooks['LoadExtensionSchemaUpdates'][] = 'WikiPlaceHooks::onLoadExtensionSchemaUpdates'; // Schema updates for update.php

