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
    'name' => 'Electronic Payment',
    'author' => array('Clément Dietschy', 'Seizam Sàrl.'),
    'version' => '0.1.0',
    'url' => 'http://www.seizam.com/',
    'descriptionmsg' => 'electronicpayment-desc',
);


$dir = dirname(__FILE__) . '/';

#Load Hooks
$wgAutoloadClasses['ElectronicPaymentHooks'] = $dir . 'ElectronicPayment.hooks.php';

# Attach Hooks
# Adds the necessary tables to the DB
$wgHooks['LoadExtensionSchemaUpdates'][] = 'ElectronicPaymentHooks::loadExtensionSchemaUpdates';

# i18n
$wgExtensionMessagesFiles['ElectronicPayment'] = $dir . 'ElectronicPayment.i18n.php';
$wgExtensionAliasesFiles['ElectronicPayment'] = $dir . 'ElectronicPayment.alias.php';

# Special Electronic Payment (OUTbound)
$wgAutoloadClasses['SpecialElectronicPayment'] = $dir . 'ElectronicPayment_body.php';
$wgSpecialPages['ElectronicPayment'] = 'SpecialElectronicPayment';

$wgSpecialPageGroups['ElectronicPayment'] = 'other';

# Right for Payment administration
$wgAvailableRights[] = 'epadmin';
$wgGroupPermissions['sysop']['epadmin'] = true;

#Settings
# TPE Settings
# Warning !! CMCIC_Config contains the key, you have to protect this file with all the mechanism available in your development environment.
# You may for instance put this file in another directory and/or change its name.
require_once($dir . 'ElectronicPayment.config.php');

#TPE kit
require_once($dir . 'CMCIC_Tpe.inc.php');

Class EPMessage {
#Required Params

    public $epm = array(
        # Params related to Message
        'epm_id' => '', #int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
        'epm_type' => '', # varchar(4) NOT NULL COMMENT 'Type of message (INcoming, OUTcoming)',
        'epm_date' => '', # datetime NOT NULL COMMENT 'DateTime',
        'epm_user_id' => '', # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
        # Params related to Order
        'epm_o_ept' => '', # int(8) unsigned NOT NULL DEFAULT '0' COMMENT 'EPT id',
        'epm_o_date' => '', # datetime NOT NULL COMMENT 'Order DateTime',
        'epm_o_amount' => '', # decimal(9,2) NOT NULL COMMENT 'Order Amount',
        'epm_o_currency' => '', # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Order Amount',
        'epm_o_reference' => '', # int(12) unsigned NOT NULL COMMENT 'Oder Reference',
        'epm_o_mail' => '', # tinyblob NOT NULL COMMENT 'Ordering User''s Mail',
        'epm_o_language' => '', # varchar(2) NOT NULL DEFAULT 'EN' COMMENT 'Order Language',
        'epm_o_mac' => '', # varchar(40) NOT NULL COMMENT 'Order Verification Sum',
        'epm_o_ip' => '', # tinyblob NOT NULL COMMENT 'Ordering user''s IP'
        # Optional Params
        'epm_o_free_text' => '', # mediumblob COMMENT 'Order Free Text',
        'epm_o_options' => '', # mediumblob COMMENT 'Order Options',
        # Inbound Params
        'epm_o_return_code' => '', # varchar(16) DEFAULT NULL COMMENT 'Order Return Code',
        'epm_o_cvx' => '', # varchar(3) DEFAULT NULL COMMENT 'Order CVX',
        'epm_o_vld' => '', # varchar(4) DEFAULT NULL COMMENT 'Ordering Card Validity',
        'epm_o_brand' => '', # varchar(2) DEFAULT NULL COMMENT 'Ordering Card Brand',
        'epm_o_status3ds' => '', # varchar(2) DEFAULT NULL COMMENT 'Order 3DSecure level',
        'epm_o_numauto' => '', # varchar(16) DEFAULT NULL COMMENT 'Order Confirmation number',
        'epm_o_whyrefused' => '', # varchar(16) DEFAULT NULL COMMENT 'Reason for order refusal',
        'epm_o_originecb' => '', # varchar(3) DEFAULT NULL COMMENT 'Geographic origin of card',
        'epm_o_bincb' => '', # varchar(16) DEFAULT NULL COMMENT 'Card''s bank''s bin',
        'epm_o_hpancb' => '', # varchar(40) DEFAULT NULL COMMENT 'Card''s number hash,
        'epm_o_originetr' => '', # varchar(3) DEFAULT NULL COMMENT 'Geographic origin of order',
        'epm_o_veres' => '', # varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure VERes',
        'epm_o_pares' => '', # varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure PARes',
        'epm_o_filtercause' => '', # blob DEFAULT NULL COMMENT 'Filter return array'
        'epm_o_filtervalue' => '' # blob DEFAULT NULL COMMENT 'Filter return array values',
    );


    # The VEPT
    public $oTpe;
    # The certification/hash logic
    public $oHmac;
    # The receipt returned by EPTBack
    public $tmp_o_receipt;
    # epm_o_date in required format by bank.
    public $tmp_o_date_bank_format;
    # String containing all fields hashed for validation
    public $epm_o_validating_fields;

    # Debug/Dev
    public $CtlHmac;

    public function __construct($type) {
        # 3 way of constructing
        switch ($type) {
            # Constructs OutGoing EPMessage. ie: We are sending user to bank payment interface (Special:ElectronicPayment)
            case 'out' :
                $this->__constructOutgoing();
                break;
            # Constructs Incoming EPMessage. ie: The bank is confirming an order to us (Special:EPTBack)
            case 'in' :
                $this->__constructIncoming();
                break;
            # Admin, just read a msg from IP (Special:ElectronicPayment&Status=read&id=7)
            case 'read' :
                $this->__constructFromDB();
        }
    }

    # Constructs Incoming EPMessage. ie: The bank is confirming an order to us (Special:ElectronicPayment&action=EPTBack)

    private function __constructIncoming() {
        global $wgUser, $wgRequest;

        # Some params are read from free-text, free-text has to be set first.
        $this->epm['epm_o_free_text'] = $wgRequest->getText('texte-libre');

        # Msg related fields
        $this->epm['epm_type'] = 'in';
        $this->epm['epm_date'] = date("Y-m-d:H:i:s");
        $this->epm['epm_user_id'] = $this->valueFromFreeText('user');

        # Order related fields
        $this->epm['epm_o_ept'] = $wgRequest->getText('TPE');
        $this->tmp_o_date_bank_format = $wgRequest->getText('date');
        $this->epm['epm_o_date'] = $this->bankStringToMySqlTime($this->tmp_o_date_bank_format);
        $this->epm['epm_o_reference'] = $wgRequest->getText('reference');

        # Money Issues
        $this->ReadAmountAndCurrencyFromString($wgRequest->getText('montant'));

        # User related data
        $this->epm['epm_o_mail'] = $this->valueFromFreeText('mail');
        $this->epm['epm_o_language'] = $this->valueFromFreeText('lang');
        $this->epm['epm_o_ip'] = $wgRequest->getText('ipclient');

        # Order Validation
        $this->epm['epm_o_mac'] = strtolower($wgRequest->getText('MAC'));

        # Order Confirmation
        $this->epm['epm_o_return_code'] = $wgRequest->getText('code-retour');
        $this->epm['epm_o_cvx'] = $wgRequest->getText('cvx');
        $this->epm['epm_o_vld'] = $wgRequest->getText('vld');
        $this->epm['epm_o_brand'] = $wgRequest->getText('brand');
        $this->epm['epm_o_status3ds'] = $wgRequest->getText('status3ds');
        $this->epm['epm_o_numauto'] = $wgRequest->getText('numauto');
        $this->epm['epm_o_whyrefused'] = $wgRequest->getText('motifrefus');
        $this->epm['epm_o_originecb'] = $wgRequest->getText('originecb');
        $this->epm['epm_o_bincb'] = $wgRequest->getText('bincb');
        $this->epm['epm_o_hpancb'] = $wgRequest->getText('hpancb');
        $this->epm['epm_o_originetr'] = $wgRequest->getText('originetr');
        $this->epm['epm_o_veres'] = $wgRequest->getText('veres');
        $this->epm['epm_o_pares'] = $wgRequest->getText('pares');
        $this->epm['epm_o_filtercause'] = $wgRequest->getText('filtragecause');
        $this->epm['epm_o_filtervalue'] = $wgRequest->getText('filtragevaleur');

        # Instanciate VEPT class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oTpe = new CMCIC_Tpe();

        # Instanciate HMAC class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oHmac = new CMCIC_Hmac($this->oTpe);

        # Extra Control String for support
        $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));

        # Now that we know everything, we can calculate the control sum for order validation
        if ($this->epm['epm_o_mac'] == $this->calculateMAC()) {
            $this->tmp_o_receipt = CMCIC_CGI2_MACOK;
        } else {
            $this->tmp_o_receipt = CMCIC_CGI2_MACNOTOK . $this->epm_o_validating_fields;
        }

        #Do what needs to be done regarding order success status
        $this->reactToReturnCode();

        #Finally, save the message
        $this->writeDB();
    }

    # Constructs OutGoing EPMessage. ie: We are sending user to bank payment interface  (Special:ElectronicPayment&action=attempt)

    private function __constructOutgoing() {
        global $wgUser, $wgRequest;

        # Here we set all the fields we can. Look in variable declaration for fields meaning
        # Msg related fields
        $this->epm['epm_type'] = 'out';
        $this->epm['epm_date'] = date("Y-m-d:H:i:s");
        $this->epm['epm_user_id'] = $wgUser->getId(); # =0 if anonymous
        # Order related fields
        $this->epm['epm_o_ept'] = CMCIC_TPE; # not necessary but who knows how much VEPT could be used.
        $this->epm['epm_o_date'] = $this->epm['epm_date']; # Outgoing order time is creation time.
        $this->tmp_o_date_bank_format = $this->mySqlStringToBankTime($this->epm['epm_o_date']); # bank wants a special format for date
        # Money issues
        $this->epm['epm_o_amount'] = $wgRequest->getText('wpamount'); #How much? @TODO: Validate
        $this->epm['epm_o_currency'] = $wgRequest->getText('wpcurrency'); #Of what? @TODO: Validate
        # Default currency if not submitted.
        if ($this->epm['epm_o_currency'] == '')
            $this->epm['epm_o_currency'] = 'EUR';

        # User related data
        $this->epm['epm_o_mail'] = $wgRequest->getText('wpmail'); # $wgUser->getEmail();#@TODO:Fix for anonymous.
        $this->epm['epm_o_language'] = $this->assignEPTLanguage(); #Sets the interface language
        $this->epm['epm_o_ip'] = IP::sanitizeIP(wfGetIP()); #Saves user's IP.
        # This free text is coming back (Incoming), we use it to store data we want available at all time & everywhere.
        # Example: Easily retrieve User ID even if msg has been lost somewhere.
        $this->epm['epm_o_free_text'] = '(user: <' . $this->epm['epm_user_id'] . '> ip: <' . $this->epm['epm_o_ip'] . '> mail: <' . $this->epm['epm_o_mail'] . '> lang: <' . $this->epm['epm_o_language'] . '>)';

        # Now that the required params are set, the entry can be saved and an Order Reference assigned
        $this->epm['epm_o_reference'] = $this->assignNewOrderReference();

        # Instanciate VEPT class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oTpe = new CMCIC_Tpe($this->epm['epm_o_language']);

        # Instanciate HMAC class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oHmac = new CMCIC_Hmac($this->oTpe);

        # Extra Control String for support
        $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));

        # Now that we know everything, we can calculate the control sum for order validation
        $this->epm['epm_o_mac'] = $this->calculateMAC();

        #Finally, save the message
        $this->writeDB();
    }

    # Constructor for reading DB. Sysadmin Only.

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

    # Pick a language for the external payment interface (FR EN DE IT ES NL PT SV availabe) (EN default)

    private function assignEPTLanguage() {
        global $wgLang;
        if ($wgLang->getCode() == 'fr')
            return 'FR';
        else
            return 'EN';
    }

    #!\\ Careful with collisions (assign when writing, not before);
    # Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max

    private function assignNewOrderReference() {
        return date("His");
    }

    # Calculate the control sum for order validation.

    private function calculateMAC() {
        #Structure of String to hash changes between outgoing & incoming
        if ($this->epm['epm_type'] == 'out') {
            # Data to certify
            # Fields left out are for mutli-time payment
            $this->epm_o_validating_fields = sprintf(CMCIC_CGI1_FIELDS, $this->oTpe->sNumero, $this->tmp_o_date_bank_format, $this->epm['epm_o_amount'], $this->epm['epm_o_currency'], $this->epm['epm_o_reference'], $this->epm['epm_o_free_text'], $this->oTpe->sVersion, $this->oTpe->sLangue, $this->oTpe->sCodeSociete, $this->epm['epm_o_mail'], ''/* $sNbrEch */, ''/* $sDateEcheance1 */, ''/* $sMontantEcheance1 */, ''/* $sDateEcheance2 */, ''/* $sMontantEcheance2 */, ''/* $sDateEcheance3 */, ''/* $sMontantEcheance3 */, ''/* $sDateEcheance4 */, ''/* $sMontantEcheance4 */, $this->epm['epm_o_options']);
        } else if ($this->epm['epm_type'] == 'in') {
            # Data to certify
            $this->epm_o_validating_fields = sprintf(CMCIC_CGI2_FIELDS, $this->oTpe->sNumero, $this->tmp_o_date_bank_format, $this->epm['epm_o_amount'] . $this->epm['epm_o_currency'], $this->epm['epm_o_reference'], $this->epm['epm_o_free_text'], $this->oTpe->sVersion, $this->epm['epm_o_return_code'], $this->epm['epm_o_cvx'], $this->epm['epm_o_vld'], $this->epm['epm_o_brand'], $this->epm['epm_o_status3ds'], $this->epm['epm_o_numauto'], $this->epm['epm_o_whyrefused'], $this->epm['epm_o_originecb'], $this->epm['epm_o_bincb'], $this->epm['epm_o_hpancb'], $this->epm['epm_o_ip'], $this->epm['epm_o_originetr'], $this->epm['epm_o_veres'], $this->epm['epm_o_pares']);
        }
        # Hash the validation String
        return $this->oHmac->computeHmac($this->epm_o_validating_fields);
    }

    # Record current object to DB.

    private function writeDB() {
        $dbw = wfGetDB(DB_MASTER);
        return $dbw->insert('ep_message', $this->epm);
    }

    # Set current object from DB.

    private function readDB() {
        $dbr = wfGetDB(DB_SLAVE);
        $this->epm = $dbr->selectRow('ep_message', '*', 'epm_id = ' . $this->epm['epm_id']);
    }

    # Takes $time like 'dd/mm/YYYY_a_HH:ii:ss' and outputs 'YYYY-mm-dd:HH:ii:ss'.

    private function bankStringToMySqlTime($time) {
        $matches = array();
        $pattern = "/^(?P<d>[0-9]{2})\/(?P<m>[0-9]{2})\/(?P<Y>[0-9]{4})_a_(?P<H>[0-9]{2}):(?P<i>[0-9]{2}):(?P<s>[0-9]{2})$/";
        if (preg_match($pattern, $time, $matches) == 1) {
            return $matches['Y'] . '-' . $matches['m'] . '-' . $matches['d'] . ':' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nbankStringToMySqlTime Error\n";
    }

    # Takes $time like 'YYYY-mm-dd:HH:ii:ss' and outputs 'dd/mm/YYYY:HH:ii:ss'.

    public function mySqlStringToBankTime($time) {
        $matches = array();
        $pattern = "/^(?P<Y>[0-9]{4})-(?P<m>[0-9]{2})-(?P<d>[0-9]{2}):(?P<H>[0-9]{2}):(?P<i>[0-9]{2}):(?P<s>[0-9]{2})$/";
        if (preg_match($pattern, $time, $matches) == 1) {
            return $matches['d'] . '/' . $matches['m'] . '/' . $matches['Y'] . ':' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nmySqlStringToBankTime Error\n";
    }

    # Reads $input like '1234.5678EUR' and puts 1234.5678 into epm_o_amount and EUR into epm_o_currency.

    private function ReadAmountAndCurrencyFromString($input) {
        $matches = array();
        $pattern = "/^(?P<A>[0-9\.]+)(?P<C>[A-Z]{3})$/";
        if (preg_match($pattern, $input, $matches) == 1) {
            $this->epm['epm_o_amount'] = $matches['A'];
            $this->epm['epm_o_currency'] = $matches['C'];
        } else
            return false;
    }

    # Extract 'value' corresponding to $key in epm_o_free_text.
    # Ex: $this->epm['epm_o_free_text'] = '(user: <1> ip: <127.0.0.1> mail: <contact@seizam.com>  lang: <EU>)'
    # valueFromFreeText('ip') returns 127.0.0.1

    private function valueFromFreeText($key) {
        $matches = array();
        $pattern = "/" . $key . ": <([\d\D]*?)>/";
        if (preg_match($pattern, $this->epm['epm_o_free_text'], $matches) == 1) {
            return $matches[1];
        } else
            return "null";
    }

    # That's the place where magic happens.

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