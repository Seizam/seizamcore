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
    'author' => array('Clément Dietschy', 'Seizam Sàrl.'),
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

# Right for Payment administration
$wgAvailableRights[] = 'epadmin';
$wgGroupPermissions['sysop']['epadmin'] = true;

#Settings
// TPE Settings
// Warning !! CMCIC_Config contains the key, you have to protect this file with all the mechanism available in your development environment.
// You may for instance put this file in another directory and/or change its name.
require_once($dir . 'ElectronicPayment.config.php');

#TPE kit
require_once($dir . 'CMCIC_Tpe.inc.php');

Class EPMessage {

//Required Params

    public $epm = array(
        'epm_id' => '', //int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
        'epm_type' => '', // varchar(4) NOT NULL COMMENT 'Type of message (INcoming, OUTcoming)',
        'epm_date' => '', // datetime NOT NULL COMMENT 'DateTime',
        'epm_user_id' => '', // int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
        'epm_o_ept' => '', // int(8) unsigned NOT NULL DEFAULT '0' COMMENT 'EPT id',
        'epm_o_date' => '', // datetime NOT NULL COMMENT 'Order DateTime',
        'epm_o_amount' => '', // decimal(9,2) NOT NULL COMMENT 'Order Amount',
        'epm_o_currency' => '', // varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Order Amount',
        'epm_o_reference' => '', // int(12) unsigned NOT NULL COMMENT 'Oder Reference',
        'epm_o_mail' => '', // tinyblob NOT NULL COMMENT 'Ordering User''s Mail',
        'epm_o_language' => '', // varchar(2) NOT NULL DEFAULT 'EN' COMMENT 'Order Language',
        'epm_o_mac' => '', // varchar(40) NOT NULL COMMENT 'Order Verification Sum',
        'epm_o_ip' => '', // tinyblob NOT NULL COMMENT 'Ordering user''s IP'
        //Optional Params
        'epm_o_free_text' => '', // mediumblob COMMENT 'Order Free Text',
        'epm_o_options' => '', // mediumblob COMMENT 'Order Options',
        'epm_o_return_code' => '', // varchar(16) DEFAULT NULL COMMENT 'Order Return Code',
        'epm_o_cvx' => '', // varchar(3) DEFAULT NULL COMMENT 'Order CVX',
        'epm_o_vld' => '', // varchar(4) DEFAULT NULL COMMENT 'Ordering Card Validity',
        'epm_o_brand' => '', // varchar(2) DEFAULT NULL COMMENT 'Ordering Card Brand',
        'epm_o_status3ds' => '', // varchar(2) DEFAULT NULL COMMENT 'Order 3DSecure level',
        'epm_o_numauto' => '', // varchar(16) DEFAULT NULL COMMENT 'Order Confirmation number',
        'epm_o_whyrefused' => '', // varchar(16) DEFAULT NULL COMMENT 'Reason for order refusal',
        'epm_o_originecb' => '', // varchar(3) DEFAULT NULL COMMENT 'Geographic origin of card',
        'epm_o_bincb' => '', // varchar(16) DEFAULT NULL COMMENT 'Card''s bank''s bin',
        'epm_o_hpancb' => '', // varchar(40) DEFAULT NULL COMMENT 'Card''s number hash,
        'epm_o_originetr' => '', // varchar(3) DEFAULT NULL COMMENT 'Geographic origin of order',
        'epm_o_veres' => '', // varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure VERes',
        'epm_o_pares' => '', // varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure PARes',
        'epm_o_filtercause' => '', // blob DEFAULT NULL COMMENT 'Filter return array'
        'epm_o_filtervalue' => ''); // blob DEFAULT NULL COMMENT 'Filter return array values',
    //

    //Bank object instances
    public $oTpe;
    public $oHmac;
    public $tmp_o_receipt;
    public $tmp_o_date_bank_format;
    //Debug/Dev
    public $epm_o_validating_fields;
    public $CtlHmac;

    public function __construct($type) {
        switch ($type) {
            case 'out' :
                $this->__constructOutgoing();
                break;
            case 'in' :
                $this->__constructIncoming();
                break;
            case 'read' :
                $this->__constructFromDB();
        }
    }

    private function __constructIncoming() {
        global $wgUser, $wgRequest;


        $this->epm['epm_o_free_text'] = $wgRequest->getText('texte-libre');

        $this->epm['epm_type'] = 'in';
        $this->epm['epm_date'] = date("Y-m-d:H:i:s");
        $this->epm['epm_user_id'] = $this->valueFromFreeText('user');
        $this->epm['epm_o_ept'] = $wgRequest->getText('TPE');
        $this->tmp_o_date_bank_format = $wgRequest->getText('date');
        $this->epm['epm_o_date'] = $this->bankStringToMySqlTime($this->tmp_o_date_bank_format);
        $this->ReadAmountAndCurrencyFromString($wgRequest->getText('montant'));
        $this->epm['epm_o_reference'] = $wgRequest->getText('reference');
        $this->epm['epm_o_mail'] = $this->valueFromFreeText('mail');
        $this->epm['epm_o_language'] = $this->valueFromFreeText('lang');
        $this->epm['epm_o_mac'] = strtolower($wgRequest->getText('MAC'));
        $this->epm['epm_o_ip'] = $wgRequest->getText('ipclient');
        $this->epm['epm_o_return_code'] = $wgRequest->getText('code-retour');
        $this->epm['epm_o_cvx'] = $wgRequest->getText('cvx');
        $this->epm['epm_o_vld'] = $wgRequest->getText('vld');
        $this->epm['epm_o_brand'] = $wgRequest->getText('brand');
        $this->epm['epm_o_status3ds'] = $wgRequest->getText('status3ds');
        $this->epm['epm_o_numauto'] = $wgRequest->getText('numauto');
        $this->epm['epm_o_whyrefused'] = $wgRequest->getText('motirefus');
        $this->epm['epm_o_originecb'] = $wgRequest->getText('originecb');
        $this->epm['epm_o_bincb'] = $wgRequest->getText('bincb');
        $this->epm['epm_o_hpancb'] = $wgRequest->getText('hpancb');
        $this->epm['epm_o_originetr'] = $wgRequest->getText('originetr');
        $this->epm['epm_o_veres'] = $wgRequest->getText('veres');
        $this->epm['epm_o_pares'] = $wgRequest->getText('pares');
        $this->epm['epm_o_filtercause'] = $wgRequest->getText('filtragecause');
        $this->epm['epm_o_filtervalue'] = $wgRequest->getText('filtragevaleur');

        $this->oTpe = new CMCIC_Tpe();
        $this->oHmac = new CMCIC_Hmac($this->oTpe);

        // Control String for support
        $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));

        //Now that we know everything, we can calculate the control sum for order validation
        if ($this->epm['epm_o_mac'] == $this->calculateMAC()) {
            $this->tmp_o_receipt = CMCIC_CGI2_MACOK;
        } else {
            $this->tmp_o_receipt = CMCIC_CGI2_MACNOTOK . $this->epm_o_validating_fields;
            ;
        }

        //Do what needs to be done regarding order success status
        $this->reactToReturnCode();

        //Finally, save the message
        $this->writeDB();
    }

    private function __constructOutgoing() {
        global $wgUser, $wgRequest;

        $this->epm['epm_type'] = 'out';
        $this->epm['epm_date'] = date("Y-m-d:H:i:s");
        $this->epm['epm_user_id'] = $wgUser->getId();
        $this->epm['epm_o_ept'] = CMCIC_TPE;
        $this->epm['epm_o_date'] = $this->epm['epm_date'];
        $this->tmp_o_date_bank_format = $this->mySqlStringToBankTime($this->epm['epm_o_date']);
        $this->epm['epm_o_amount'] = $wgRequest->getText('amount');
        $this->epm['epm_o_currency'] = $wgRequest->getText('currency');
        if ($this->epm['epm_o_currency'] == '')
            $this->epm['epm_o_currency'] = 'EUR';
        $this->epm['epm_o_mail'] = 'contact@seizam.com'; // $wgUser->getEmail();
        $this->epm['epm_o_language'] = $this->assignEPTLanguage();
        $this->epm['epm_o_ip'] = IP::sanitizeIP(wfGetIP());

        $this->epm['epm_o_free_text'] = '(user: <' . $this->epm['epm_user_id'] . '> ip: <' . $this->epm['epm_o_ip'] . '> mail: <' . $this->epm['epm_o_mail'] . '> lang: <' . $this->epm['epm_o_language'] . '>)';

        //Now that the required params are set, the entry can be saved and an Order Reference assigned
        $this->epm['epm_o_reference'] = $this->assignNewOrderReference();

        $this->oTpe = new CMCIC_Tpe($this->epm['epm_o_language']);
        $this->oHmac = new CMCIC_Hmac($this->oTpe);

        // Control String for support
        $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));

        //Now that we know everything, we can calculate the control sum for order validation
        $this->epm['epm_o_mac'] = $this->calculateMAC();

        //Finally, save the message
        $this->writeDB();
    }

    private function __constructFromDB() {
        global $wgRequest, $wgUser, $wgOut;
        if ($wgUser->isAllowed('epadmin')) {
            $this->epm['epm_id'] = $wgRequest->getText('id');
            $this->readDB();
        } else {
            $wgOut->disable();
            echo "Electronic Payment Terminal ERROR : You do not have the right to do this action.";
        }
    }

    // Pick a language for the external payment interface (FR EN DE IT ES NL PT SV availabe) (EN default)
    private function assignEPTLanguage() {
        global $wgLang;
        if ($wgLang->getCode() == 'fr')
            return 'FR';
        else
            return 'EN';
    }

//!\\ Careful with collisions (assign when writing, not before);
// Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max
    private function assignNewOrderReference() {
        return date("His");
    }

// Calculate the control sum for order validation
    private function calculateMAC() {
        // Data to certify
        if ($this->epm['epm_type'] == 'out') {
            $this->epm_o_validating_fields = sprintf(CMCIC_CGI1_FIELDS, $this->oTpe->sNumero, $this->tmp_o_date_bank_format, $this->epm['epm_o_amount'], $this->epm['epm_o_currency'], $this->epm['epm_o_reference'], $this->epm['epm_o_free_text'], $this->oTpe->sVersion, $this->oTpe->sLangue, $this->oTpe->sCodeSociete, $this->epm['epm_o_mail'], ''/* $sNbrEch */, ''
                    /* $sDateEcheance1 */, ''/* $sMontantEcheance1 */, ''/* $sDateEcheance2 */, ''/* $sMontantEcheance2 */, ''/* $sDateEcheance3 */, ''/* $sMontantEcheance3 */, ''/* $sDateEcheance4 */, ''/* $sMontantEcheance4 */, $this->epm['epm_o_options']);
        } else if ($this->epm['epm_type'] == 'in') {
            $this->epm_o_validating_fields = sprintf(CMCIC_CGI2_FIELDS, $this->oTpe->sNumero, $this->tmp_o_date_bank_format, $this->epm['epm_o_amount'] . $this->epm['epm_o_currency'], $this->epm['epm_o_reference'], $this->epm['epm_o_free_text'], $this->oTpe->sVersion, $this->epm['epm_o_return_code'], $this->epm['epm_o_cvx'], $this->epm['epm_o_vld'], $this->epm['epm_o_brand'], $this->epm['epm_o_status3ds'], $this->epm['epm_o_numauto'], $this->epm['epm_o_whyrefused'], $this->epm['epm_o_originecb'], $this->epm['epm_o_bincb'], $this->epm['epm_o_hpancb'], $this->epm['epm_o_ip'], $this->epm['epm_o_originetr'], $this->epm['epm_o_veres'], $this->epm['epm_o_pares']);
        }
        return $this->oHmac->computeHmac($this->epm_o_validating_fields);
    }

    private function writeDB() {
        $dbw = wfGetDB(DB_MASTER);
        return $dbw->insert('ep_message', $this->epm);
    }

    private function readDB() {
        $dbr = wfGetDB(DB_SLAVE);
        $this->epm = $dbr->selectRow('ep_message', '*', 'epm_id = ' . $this->epm['epm_id']);
    }

    private function bankStringToMySqlTime($time) {
        $matches = array();
        $pattern = "/^(?P<d>[0-9]{2})\/(?P<m>[0-9]{2})\/(?P<Y>[0-9]{4})_a_(?P<H>[0-9]{2}):(?P<i>[0-9]{2}):(?P<s>[0-9]{2})$/";
        if (preg_match($pattern, $time, $matches) == 1) {
            return $matches['Y'] . '-' . $matches['m'] . '-' . $matches['d'] . ':' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nbankStringToMySqlTime Error\n";
    }

    public function mySqlStringToBankTime($time) {
        $matches = array();
        $pattern = "/^(?P<Y>[0-9]{4})-(?P<m>[0-9]{2})-(?P<d>[0-9]{2}):(?P<H>[0-9]{2}):(?P<i>[0-9]{2}):(?P<s>[0-9]{2})$/";
        if (preg_match($pattern, $time, $matches) == 1) {
            return $matches['d'] . '/' . $matches['m'] . '/' . $matches['Y'] . ':' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nmySqlStringToBankTime Error\n";
    }

    private function ReadAmountAndCurrencyFromString($input) {
        $matches = array();
        $pattern = "/^(?P<A>[0-9\.]+)(?P<C>[A-Z]{3})$/";
        if (preg_match($pattern, $input, $matches) == 1) {
            $this->epm['epm_o_amount'] = $matches['A'];
            $this->epm['epm_o_currency'] = $matches['C'];
        } else
            return false;
    }

    private function valueFromFreeText($key) {
        $matches = array();
        $pattern = "/" . $key . ": <([\d\D]*?)>/";
        if (preg_match($pattern, $this->epm['epm_o_free_text'], $matches) == 1) {
            return $matches[1];
        } else
            return "null";
    }

    private function reactToReturnCode() {
        switch ($this->epm['epm_o_return_code']) {
            case "Annulation":
                break;

            case "payetest":
                break;

            case "paiement":
                break;
        }
    }

}