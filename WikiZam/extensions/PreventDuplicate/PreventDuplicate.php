<?php

/*
 * Wikiplace extension, developped by Yann Missler, Seizam SARL
 * www.seizam.com
 */

if (!defined('MEDIAWIKI')) {
	echo "Prevent Duplicate extension\n";
    die(-1);
}

$wgExtensionCredits['other'][] = array(
   'path' => __FILE__,
   'name' => 'Prevent Duplicate',
   'author' => array('Yann Missler', 'Seizam'), 
   'url' => 'http://www.seizam.com', 
   'descriptionmsg' => 'pvdp-desc',
   'version'  => '0.1.0',
   );

$_dir = dirname( __FILE__ ).'/';
$wgAutoloadClasses['PreventDuplicateHooks'] = $_dir . 'PreventDuplicate.hooks.php';
$wgExtensionMessagesFiles['PreventDuplicate'] = $_dir.'PreventDuplicate.i18n.php'; 

$wgExtensionFunctions[] = 'setupPreventDuplicate';

function setupPreventDuplicate() {
	// check requirements before install
	// TitleKey: we use its title database
	// AntiSpoof: ensure there is no user with the same name but different case
	if ( class_exists('TitleKey') && class_exists('AntiSpoof') ) {
		
		global $wgHooks;
		$wgHooks['getUserPermissionsErrors'][] = 'PreventDuplicateHooks::blockCreateDuplicate';
		$wgHooks['BeforeInitialize'][] = 'PreventDuplicateHooks::redirectDuplicate';
		
	} else {
		
		wfDebugLog('preventduplicate', 'ERROR, TitleKey and AntiSpoof extensions required in order to use PreventDuplicate extension');
		
	}
}