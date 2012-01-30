<?php

/**
 * Banking Configuration for SeizamACL extension, could be replaced by $wgSEPTConfig = array() in LocalSettings.
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

/* * *************************************************************************************
 * Warning !! CMCIC_Config contains the key, you have to protect this file with all     *   
 * the mechanism available in your development environment.                             *
 * You may for instance put this file in another directory and/or change its name       *
 * ************************************************************************************* */

define("CMCIC_CLE", "E8CBA93707F5416F819CF3886E9369BD36379B9C");
define("CMCIC_TPE", "6527604");
define("CMCIC_VERSION", "3.0");
define("CMCIC_SERVEUR", "https://ssl.paiement.cic-banques.fr/test/");
define("CMCIC_CODESOCIETE", "seizamsarl");
define("CMCIC_URLOK", "http://papilusion.seizam.com/index.php?title=Special:ElectronicPayment&action=success");
define("CMCIC_URLKO", "http://papilusion.seizam.com/index.php?title=Special:ElectronicPayment&action=fail");

$wgCMCIC_config = array('key' => CMCIC_CLE,
 'ept' => CMCIC_TPE,
 'version' => CMCIC_VERSION,
 'server' => CMCIC_SERVEUR,
 'companyCode' => CMCIC_CODESOCIETE,
 'urlOK' => CMCIC_URLOK,
 'urlKO' => CMCIC_URLKO);

