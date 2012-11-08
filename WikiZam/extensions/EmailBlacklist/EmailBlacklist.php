<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

/**
 * @file
 * @ingroup Extensions
 */

$wgExtensionCredits['antispam'][] = array(
	'path'           => __FILE__,
	'name'           => 'Email Blacklist',
	'author'         => array( 'Clément Dietschy', 'Seizam' ),
	'version'        => '1.0',
	'url'            => 'https://www.seizam.com',
	'descriptionmsg' => 'emailblacklist-desc',
);


$dir = dirname( __FILE__ );

$wgAutoloadClasses['EmailBlacklist']      = $dir . '/EmailBlacklist.list.php';
$wgAutoloadClasses['EmailBlacklistHooks'] = $dir . '/EmailBlacklist.hooks.php';
$wgExtensionMessagesFiles['EmailBlacklist'] = $dir . '/EmailBlacklist.i18n.php';

$wgAvailableRights[] = 'eboverride';	// Implies tboverride-account
$wgGroupPermissions['sysop']['eboverride'] = true;

$wgHooks['AbortNewAccount'][] = 'EmailBlacklistHooks::abortNewAccount';
$wgHooks['AbortAutoAccount'][] = 'EmailBlacklistHooks::abortNewAccount';
$wgHooks['EditFilter'][] = 'EmailBlacklistHooks::validateBlacklist';
$wgHooks['ArticleSaveComplete'][] = 'EmailBlacklistHooks::clearBlacklist';
