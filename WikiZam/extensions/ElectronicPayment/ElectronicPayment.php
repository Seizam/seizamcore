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

#Load Hooks
$wgAutoloadClasses['ElectronicPaymentHooks'] = $dir . 'ElectronicPayment.hooks.php';

# Attach Hooks
// Adds the necessary tables to the DB  --> NOT CURRENTLY USED
$wgHooks['LoadExtensionSchemaUpdates'][] = 'ElectronicPaymentHooks::loadExtensionSchemaUpdates';

#i18n
$wgExtensionMessagesFiles['ElectronicPayment'] = $dir . 'ElectronicPayment.i18n.php';
$wgExtensionAliasesFiles['ElectronicPayment'] = $dir . 'ElectronicPayment.alias.php';

# Special Electronic Payment (OUTbound)
$wgAutoloadClasses['SpecialElectronicPayment'] = $dir . 'ElectronicPayment_body.php';
$wgSpecialPages['ElectronicPayment'] = 'SpecialElectronicPayment';

$wgSpecialPageGroups['ElectronicPayment'] = 'other';

# Special EPTBak (INbound)
$wgAutoloadClasses['SpecialEPTBack'] = $dir . 'EPTBack_body.php';
$wgSpecialPages['EPTBack'] = 'SpecialEPTBack';

$wgSpecialPageGroups['EPTBack'] = 'other';

#Settings
// TPE Settings
// Warning !! CMCIC_Config contains the key, you have to protect this file with all the mechanism available in your development environment.
// You may for instance put this file in another directory and/or change its name.
require_once($dir.'ElectronicPayment.config.php');

#TPE kit
require_once($dir.'CMCIC_Tpe.inc.php');

Class EPMessage {
  
  //Required Params
  public $epm_type; // varchar(4) NOT NULL COMMENT 'Type of message (INcoming, OUTcoming)',
  public $epm_date; // datetime NOT NULL COMMENT 'DateTime',
  public $epm_user_id; // int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
  public $epm_o_date; // datetime NOT NULL COMMENT 'Order DateTime',
  public $epm_o_amount; // varchar(12) NOT NULL COMMENT 'Order Amount',
  public $epm_o_reference; // int(12) unsigned NOT NULL COMMENT 'Oder Reference',
  public $epm_o_mail; // tinyblob NOT NULL COMMENT 'Ordering User''s Mail',
  public $epm_o_language; // varchar(2) NOT NULL DEFAULT 'EN' COMMENT 'Order Language',
  public $epm_o_mac; // varchar(40) NOT NULL COMMENT 'Order Verification Sum',
  public $epm_o_ip; // tinyblob NOT NULL COMMENT 'Ordering user''s IP'
  
  //Optional Params
  public $epm_o_free_text = ''; // mediumblob COMMENT 'Order Free Text',
  public $epm_o_options = ''; // mediumblob COMMENT 'Order Options',
  public $epm_o_return_code; // varchar(16) DEFAULT NULL COMMENT 'Order Return Code',
  public $epm_o_cvx; // varchar(3) DEFAULT NULL COMMENT 'Order CVX',
  public $epm_o_vld; // varchar(4) DEFAULT NULL COMMENT 'Ordering Card Validity',
  public $epm_o_brand; // varchar(2) DEFAULT NULL COMMENT 'Ordering Card Brand',
  public $epm_o_status3ds; // varchar(2) DEFAULT NULL COMMENT 'Order 3DSecure level',
  public $epm_o_numauto; // varchar(16) DEFAULT NULL COMMENT 'Order Confirmation number',
  
  //Logical Params
  public $epm_o_currency = 'EUR';
  public $epm_o_raw_amount;
  public $CtlHmac;
  
  //Bank object instances
  public $oTpe;
  public $oHmac;
  public $PHP1_FIELDS;
  
  public function __construct($rawamount) {
      global $wgCMCIC_config, $wgUser;
      
      $this->epm_type = 'out';
      $this->epm_date = date("d/m/Y:H:i:s");
      $this->epm_user_id = $wgUser->getId();
      $this->epm_o_date = $this->epm_date;
      $this->epm_o_raw_amount = $rawamount;
      $this->epm_o_amount = $this->epm_o_raw_amount.$this->epm_o_currency;
      $this->epm_o_mail = 'contact@seizam.com';// $wgUser->getEmail();
      $this->epm_o_language = $this->assignEPTLanguage();
      $this->epm_o_ip = IP::sanitizeIP( wfGetIP() );
      
      $this->epm_o_free_text = 'User: '.$this->epm_user_id.'  IP: '.$this->epm_o_ip;
      
      //Now that the required params are set, the entry can be saved and an Order Reference assigned
      $this->epm_o_reference = $this->assignNewOrderReference();
      
      $this->oTpe = new CMCIC_Tpe($this->epm_o_language);
      $this->oHmac = new CMCIC_Hmac($this->oTpe);
      
      // Control String for support
      $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));
      
      //Now that we know everything, we can calculate the control sum for order validation
      $this->epm_o_mac = $this->calculateMAC();
  }
  
  // Pick a language for the external payment interface (FR EN DE IT ES NL PT SV availabe) (EN default)
  private function assignEPTLanguage() {
      global $wgLang;
      if ($wgLang->getCode()=='fr') return 'FR';
      else return 'EN';
  }  
  
  //!\\ Careful with collisions (assign when writing, not before);
  // Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max
  private function assignNewOrderReference() {
      return "ref" . date("His");
  }
  
  // Calculate the control sum for order validation
  private function calculateMAC() {
        // Data to certify
        $this->PHP1_FIELDS = sprintf(CMCIC_CGI1_FIELDS, $this->oTpe->sNumero, $this->epm_o_date, $this->epm_o_raw_amount, $this->epm_o_currency, $this->epm_o_reference, $this->epm_o_free_text, $this->oTpe->sVersion, $this->oTpe->sLangue, $this->oTpe->sCodeSociete, $this->epm_o_mail, ''/*$sNbrEch*/, ''/*$sDateEcheance1*/, ''/*$sMontantEcheance1*/, ''/*$sDateEcheance2*/, ''/*$sMontantEcheance2*/, ''/*$sDateEcheance3*/, ''/*$sMontantEcheance3*/, ''/*$sDateEcheance4*/, ''/*$sMontantEcheance4*/, $this->epm_o_options);
        return $this->oHmac->computeHmac($this->PHP1_FIELDS);
  }
}