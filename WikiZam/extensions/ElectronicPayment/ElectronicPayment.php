<?php
/**
 * ElectronicPayment extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Clément Dietschy
 * 
 * @license GPL v2 or later
 * @version 0.1.0
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

/* Setup */

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'ElectronicPayment',
	'author' => array( 'Clément Dietschy','Seizam Sàrl.'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'electronicpayment-desc',
);


$dir = dirname(__FILE__) . '/';

$wgExtensionMessagesFiles['ElectronicPayment'] = $dir . 'ElectronicPayment.i18n.php';
$wgExtensionAliasesFiles['ElectronicPayment'] = $dir . 'ElectronicPayment.alias.php';

$wgAutoloadClasses['ElectronicPaymentHooks'] = $dir . 'ElectronicPayment.hooks.php';

$wgAutoloadClasses['SpecialElectronicPayment'] = $dir . 'ElectronicPayment_body.php';
$wgSpecialPages['ElectronicPayment'] = 'SpecialElectronicPayment';

$wgSpecialPageGroups['ElectronicPayment'] = 'other';

#Settings
// TPE Settings
// Warning !! CMCIC_Config contains the key, you have to protect this file with all the mechanism available in your development environment.
// You may for instance put this file in another directory and/or change its name.
require_once("ElectronicPayment.config.php");

#TPE kit
require_once("CMCIC_Tpe.inc.php");


# Attach Hooks

// Adds the necessary tables to the DB  --> NOT CURRENTLY USED
// $wgHooks['LoadExtensionSchemaUpdates'][] = 'SeizamACLHooks::loadExtensionSchemaUpdates';

