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
    'author' => array('Clément Dietschy', 'Seizam'),
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
$wgAutoloadClasses['SpecialElectronicPayment'] = $dir . 'SpecialElectronicPayment.php';
$wgSpecialPages['ElectronicPayment'] = 'SpecialElectronicPayment';

$wgSpecialPageGroups['ElectronicPayment'] = 'other';

# Right for Payment administration
$wgAvailableRights[] = 'epadmin';
$wgGroupPermissions['sysop']['epadmin'] = true;

# Settings
# TPE Settings
# Warning !! CMCIC_Config contains the key, you have to protect this file with all the mechanism available in your development environment.
# You may for instance put this file in another directory and/or change its name.
require_once($dir . 'ElectronicPayment.config.php');

# Debug file (written on unauthentificated inbound message)
$wgDebugLogGroups['EPErrors'] = '/var/log/seizam/ep_errors.log'; #@TODO: pretty log management in LocalSettings/ServerSettings
#TPE kit
require_once($dir . 'CMCIC_Tpe.inc.php');


$wgTestEnv = true; #Activate test environment (accept test money)

Class EPMessage {
#Required Params

    public $epm = array('epm_id' => '', #  int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
        'epm_type' => '', #  varchar(4) NOT NULL COMMENT 'Type of message (INcoming, OUTcoming)',
        'epm_date_created' => '', #  datetime NOT NULL COMMENT 'DateTime',
        'epm_epo_id' => '', #  int(10) unsigned NOT NULL COMMENT 'Order Reference',
        'epm_ept' => '', #  int(8) unsigned NOT NULL DEFAULT '0' COMMENT 'EPT id',
        'epm_date_message' => '', #  datetime NOT NULL COMMENT 'Message DateTime',
        'epm_free_text' => '', #  mediumblob COMMENT 'Message Free Text',
        'epm_ip' => '', # tinyblob NOT NULL COMMENT 'Ordering user''s IP',
        'epm_mac' => '', #  varchar(40) DEFAULT NULL COMMENT 'Message Verification Sum',
        'epm_options' => '', #  mediumblob COMMENT 'Message Options',
        'epm_return_code' => '', #  varchar(16) DEFAULT NULL COMMENT 'Message Return Code',
        'epm_cvx' => '', #  varchar(3) DEFAULT NULL COMMENT 'Order CVX',
        'epm_vld' => '', #  varchar(4) DEFAULT NULL COMMENT 'Ordering Card Validity',
        'epm_brand' => '', #  varchar(2) DEFAULT NULL COMMENT 'Ordering Card Brand',
        'epm_status3ds' => '', #  varchar(2) DEFAULT NULL COMMENT 'Order 3DSecure level',
        'epm_numauto' => '', #  varchar(16) DEFAULT NULL COMMENT 'Order Confirmation number',
        'epm_whyrefused' => '', #  varchar(16) DEFAULT NULL COMMENT 'Reason for order refusal',
        'epm_originecb' => '', #  varchar(3) DEFAULT NULL COMMENT 'Geographic origin of card',
        'epm_bincb' => '', #  varchar(16) DEFAULT NULL COMMENT 'Card''s bank''s bin',
        'epm_hpancb' => '', #  varchar(40) DEFAULT NULL COMMENT 'Card''s number hash',
        'epm_originetr' => '', #  varchar(3) DEFAULT NULL COMMENT 'Geographic origin of order',
        'epm_veres' => '', #  varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure VERes',
        'epm_pares' => '', #  varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure PARes',
        'epm_filtercause' => '', #  blob COMMENT 'Filter return array',
        'epm_filtervalue' => '' #  blob COMMENT 'Filter return array values',
    );

    # The related Order (EPOrder)
    public $order;

    # The VEPT
    public $oTpe;
    # The certification/hash logic
    public $oHmac;
    # The receipt returned by EPTBack
    public $epm_receipt;
    # epm_o_date in required format by bank.
    public $epm_date_message_bank_format;
    # String containing all fields hashed for validation
    public $epm_validating_fields;

    # Debug/Dev
    public $CtlHmac;

    
    # Returns EPMessage object

    public static function create($type, $epm) {
        return new EPMessage($type, $epm);
    }

    # Main constructor

    private function __construct($type, $epm) {
        # 3 way of constructing
        switch ($type) {
            # Constructs OutGoing EPMessage. ie: We are sending user to bank payment interface (Special:ElectronicPayment)
            case 'out' :
                $this->__constructOutgoing($epm);
                break;
            # Constructs Incoming EPMessage. ie: The bank is confirming an order to us (Special:EPTBack)
            case 'in' :
                $this->__constructIncoming($epm);
                break;
            # Admin, just read a msg from IP (Special:ElectronicPayment&Status=read&id=7)
            case 'read' :
                $this->__constructFromDB($epm);
        }
    }

    # Constructs Incoming EPMessage. ie: The bank is confirming an order to us (Special:ElectronicPayment&action=EPTBack)

    private function __constructIncoming($epm) {

        # Msg related fields
        $this->epm['epm_type'] = 'in';
        $this->epm_date_message_bank_format = $epm['epm_date_message_bank_format'];

        # We build the order from DB
        $this->order = EPOrder::create($epm);

        # We add the new data to the order
        if (!$this->order->setEPO($epm)) {
            $this->epm_receipt = CMCIC_CGI2_MACNOTOK . "EPOrder Not Valid\n" . print_r($epm, true);
            wfDebugLog('EPErrors', $this->epm_receipt);
            return false;
        }

        # Merge input and msg related fields
        $epm['epm_epo_id'] = $epm['epo_id'];
        $epm_intersected = array_intersect_key($epm, $this->epm);
        $this->epm = array_merge($this->epm, $epm_intersected);

        # Instanciate VEPT class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oTpe = new CMCIC_Tpe();

        # Instanciate HMAC class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oHmac = new CMCIC_Hmac($this->oTpe);

        # Extra Control String for support
        $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));

        # Now that we know everything, we can calculate the control sum for order validation
        if ($this->epm['epm_mac'] != $this->calculateMAC()) {
            $this->epm_receipt = CMCIC_CGI2_MACNOTOK . $this->epm_validating_fields . "\n" . print_r($epm, true);
            wfDebugLog('EPErrors', $this->epm_receipt);
            return false;
        }

        $this->epm_receipt = CMCIC_CGI2_MACOK;
        # Save the message
        $this->writeDB();

        # Finally, do what needs to be done regarding order success status
        if (!$this->order->reactToReturnCode($this)) {
            $this->epm_receipt = CMCIC_CGI2_MACNOTOK . "TMRecord not Valid\n" . print_r($epm, true);
            wfDebugLog('EPErrors', $this->epm_receipt);
            return false;
        }

        # Save the order
        $this->order->updateDB();

        return true;
    }

    # Constructs OutGoing EPMessage. ie: We are sending user to bank payment interface  (Special:ElectronicPayment&action=attempt)

    private function __constructOutgoing($epm) {
        # Here we set all the fields we can. Look in variable declaration for fields meaning
        # Msg related fields
        $this->epm['epm_type'] = 'out';

        # We build the order
        $this->order = EPOrder::create($epm);

        # Merge input and msg related fields
        $epm['epm_epo_id'] = $this->order->epo['epo_id'];
        $epm = array_intersect_key($epm, $this->epm);
        $this->epm = array_merge($this->epm, $epm);

        # Order related fields
        $this->epm['epm_ept'] = CMCIC_TPE; # not necessary but who knows how much VEPT could be used.

        $this->epm_date_message_bank_format = $this->mySqlStringToBankTime($this->epm['epm_date_message']); # bank wants a special format for date
        # Add some useful data to the travel
        $this->epm['epm_free_text'] = '(user: <' . $this->order->epo['epo_user_id'] . '> ip: <' . $this->epm['epm_ip'] . '> mail: <' . $this->order->epo['epo_mail'] . '> lang: <' . $this->order->epo['epo_language'] . '>)';

        # Instanciate VEPT class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oTpe = new CMCIC_Tpe($this->order->epo['epo_language']);

        # Instanciate HMAC class (Code provided by bank in CMCIC_Tpe.inc.php)
        $this->oHmac = new CMCIC_Hmac($this->oTpe);

        # Extra Control String for support
        $this->CtlHmac = sprintf(CMCIC_CTLHMAC, $this->oTpe->sVersion, $this->oTpe->sNumero, $this->oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $this->oTpe->sVersion, $this->oTpe->sNumero)));

        # Now that we know everything, we can calculate the control sum for order validation
        $this->epm['epm_mac'] = $this->calculateMAC();

        # Finally, save the message
        $this->writeDB();
    }

    # Constructor for reading DB. Sysadmin Only.

    private function __constructFromDB($epm) {
        global $wgRequest, $wgUser, $wgOut;
        if ($wgUser->isAllowed('epadmin')) {
            $this->epm['epm_id'] = $epm['epm_id'];
            $this->readDB();
        } else {
            $wgOut->disable();
            echo "Electronic Payment Terminal ERROR : You do not have the right to do this action.";
        }
    }

    # Calculate the control sum for order validation.

    private function calculateMAC() {
        #Structure of String to hash changes between outgoing & incoming
        if ($this->epm['epm_type'] == 'out') {
            # Data to certify
            # Fields left out are for mutli-time payment
            $this->epm_validating_fields = sprintf(CMCIC_CGI1_FIELDS, $this->oTpe->sNumero, $this->epm_date_message_bank_format, $this->order->epo_amount_bank_format, $this->order->epo['epo_currency'], $this->epm['epm_epo_id'], $this->epm['epm_free_text'], $this->oTpe->sVersion, $this->oTpe->sLangue, $this->oTpe->sCodeSociete, $this->order->epo['epo_mail'], ''/* $sNbrEch */, ''/* $sDateEcheance1 */, ''/* $sMontantEcheance1 */, ''/* $sDateEcheance2 */, ''/* $sMontantEcheance2 */, ''/* $sDateEcheance3 */, ''/* $sMontantEcheance3 */, ''/* $sDateEcheance4 */, ''/* $sMontantEcheance4 */, $this->epm['epm_options']);
        } else if ($this->epm['epm_type'] == 'in') {
            # Data to certify
            $this->epm_validating_fields = sprintf(CMCIC_CGI2_FIELDS, $this->oTpe->sNumero, $this->epm_date_message_bank_format, $this->order->epo_amount_bank_format . $this->order->epo['epo_currency'], $this->epm['epm_epo_id'], $this->epm['epm_free_text'], $this->oTpe->sVersion, $this->epm['epm_return_code'], $this->epm['epm_cvx'], $this->epm['epm_vld'], $this->epm['epm_brand'], $this->epm['epm_status3ds'], $this->epm['epm_numauto'], $this->epm['epm_whyrefused'], $this->epm['epm_originecb'], $this->epm['epm_bincb'], $this->epm['epm_hpancb'], $this->epm['epm_ip'], $this->epm['epm_originetr'], $this->epm['epm_veres'], $this->epm['epm_pares']);
        }
        # Hash the validation String
        return $this->oHmac->computeHmac($this->epm_validating_fields);
    }

    # Record current object to DB and set epo_id

    private function writeDB() {
        # Setting the dates
        $this->epm['epm_date_created'] = wfTimestamp(TS_DB);
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        # PostgreSQL, null for MySQL
        $this->epm['epm_id'] = $dbw->nextSequenceValue('ep_message_epm_id_seq');
        # Writing...
        $return = $dbw->insert('ep_message', $this->epm);
        # Setting epo_id from auto incremented id in DB
        $this->epm['epm_id'] = $dbw->insertId();

        return $return;
    }

    # Set current object from DB.

    private function readDB() {
        $dbr = wfGetDB(DB_SLAVE);
        $this->epm = $dbr->selectRow('ep_message', '*', 'epm_id = ' . $this->epm['epm_id']);
    }

    # Takes $time like 'YYYY-mm-dd HH:ii:ss' and outputs 'dd/mm/YYYY:HH:ii:ss'.

    public function mySqlStringToBankTime($time) {
        $matches = array();
        $pattern = "/^(?P<Y>[0-9]{4})-(?P<m>[0-9]{2})-(?P<d>[0-9]{2}) (?P<H>[0-9]{2}):(?P<i>[0-9]{2}):(?P<s>[0-9]{2})$/";
        if (preg_match($pattern, $time, $matches) == 1) {
            return $matches['d'] . '/' . $matches['m'] . '/' . $matches['Y'] . ':' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nmySqlStringToBankTime Error\n";
    }

}

Class EPOrder {

    private $epo_id = '';
    public $epo = array(
        'epo_id' => '', # int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key (the reference)',
        'epo_tmr_id' => '', # int(10) unsigned NULL COMMENT 'Foreign key to tm_record',
        'epo_date_created' => '', # datetime NOT NULL COMMENT 'DateTime created',
        'epo_date_modified' => '', # datetime NOT NULL COMMENT 'DateTime modified',
        'epo_date_paid' => '', # datetime NOT NULL COMMENT 'DateTime paid',
        'epo_user_id' => '0', # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
        'epo_mail' => '', # tinyblob NOT NULL COMMENT 'Ordering User''s Mail',
        'epo_amount' => '', # decimal(9,2) unsigned NOT NULL COMMENT 'Order Amount',
        'epo_currency' => 'EUR', # varchar(3) NOT NULL DEFAULT 'EUR',
        'epo_language' => 'EN', # varchar(2) NOT NULL DEFAULT 'EN' COMMENT 'Order Language',
        'epo_status' => 'KO' # varchar(2) NOT NULL DEFAULT 'ko' COMMENT 'Record status (OK, KO, PEnding, TEst)',
    );
    
    # epo_amount in required format by bank.
    public $epo_amount_bank_format;

    /**
     * @return EPOrder
     */
    public static function create($epm) {
        if (is_int($epm))
            $epm = array('epo_id' => $epm);
        return new EPOrder($epm);
    }

    private function __construct($epm) {
        # 2 way of constructing
        if (isset($epm['epo_id']) && $epm['epo_id'] > 0) {
            $this->epo_id = $epm['epo_id'];
            $this->__constructFromDB();
        } else { # It is a new order
            $this->__constructFromScratch($epm);
        }
    }

    private function __constructFromDB() {
        # Let's fetch the order's data from DB.
        $this->readDB();
    }

    # set EPMessage from array
    
    public function setEPO($epm) {
        # We keep the amount as received from bank
        $this->epo_amount_bank_format = $epm['epo_amount_bank_format'];
        
        # Then we keep only what we want from $epm
        $epo = array_intersect_key($epm, $this->epo);

        # We don't want anything telling us these:
        unset($epo['epo_id']);
        unset($epo['epo_tmr_id']);
        if ($epo['epo_user_id'] === $this->epo['epo_user_id'] &&
                $epo['epo_mail'] === $this->epo['epo_mail'] &&
                $epo['epo_amount'] === $this->epo['epo_amount'] &&
                $epo['epo_currency'] === $this->epo['epo_currency']) {
            # And now, we merge.
            $this->epo = array_merge($this->epo, $epo);

            return true;
        }
        return false;
    }

    private function __constructFromScratch($epm) {
        # We keep the amount as received from bank
        $this->epo_amount_bank_format = $epm['epo_amount_bank_format'];
        
        # First we keep only what we want from $epm
        $epo = array_intersect_key($epm, $this->epo);

        # Now we write the record by merging $this->epo with $order...
        # And now, we merge.
        $this->epo = array_merge($this->epo, $epo);

        $this->writeDB();
    }

    # Record current object to DB and set epo_id

    private function writeDB() {
        # Setting the dates
        $this->epo['epo_date_created'] = $this->epo['epo_date_modified'] = wfTimestamp(TS_DB);
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        # PostgreSQL, null for MySQL
        $this->epo_id = $dbw->nextSequenceValue('ep_order_epo_id_seq');
        # Writing...
        $return = $dbw->insert('ep_order', $this->epo);
        # Setting epo_id from auto incremented id in DB
        $this->epo_id = $this->epo['epo_id'] = $dbw->insertId();

        return $return;
    }

    public function updateDB() {
        # Setting the date of update
        unset($this->epo['epo_date_created']);
        $this->epo['epo_date_modified'] = wfTimestamp(TS_DB);
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        # Writing...
        $return = $dbw->update('ep_order', $this->epo, array('epo_id' => $this->epo_id));

        return $return;
    }

    # Set current object from DB.

    private function readDB() {
        # We are reading, but we need the master table anyway
        # (An order can be updated a lot within instants)
        $dbr = wfGetDB(DB_MASTER);
        $this->epo = (array) $dbr->selectRow('ep_order', '*', array('epo_id' => $this->epo_id));
    }

    # That's the place where magic happens.

    public function reactToReturnCode(EPMessage $message) {
        switch ($message->epm['epm_return_code']) {
            case "Annulation":
                //$record['tmr_desc'] = 'ep-tm-fail'; # varchar(64) NOT NULL COMMENT 'Record Description',
                $this->epo['epo_status'] = 'KO'; # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding)',
                return true;

            case "payetest":
                $this->epo['epo_status'] = 'TE'; # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding)',
                global $wgTestEnv;
                if ($wgTestEnv) $this->epo['epo_status'] = 'OK';
                $this->epo['epo_date_paid'] = $message->epm['epm_date_message'];
                return $this->saveTransaction('ep-tm-test', $message);

            case "paiement":
                $this->epo['epo_status'] = 'OK'; # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding)',
                $this->epo['epo_date_paid'] = $message->epm['epm_date_message'];
                return $this->saveTransaction('ep-tm-success', $message);
        }
    }

    # @TODO: DOCUMENT

    private function saveTransaction($desc, EPMessage $message) {
        # Construct Record For Transaction Manager
        $tmr = array(
            'tmr_type' => 'payment', # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
            # Paramas related to User
            'tmr_user_id' => intval($this->epo['epo_user_id']), # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
            'tmr_mail' => $this->epo['epo_mail'], # tinyblob COMMENT 'User''s Mail',
            'tmr_ip' => $message->epm['epm_ip'], # tinyblob COMMENT 'User''s IP'
            # Params related to Record
            'tmr_amount' => $this->epo['epo_amount'], # decimal(9,2) NOT NULL COMMENT 'Record Amount',
            'tmr_currency' => $this->epo['epo_currency'], # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
            'tmr_desc' => $desc, # varchar(64) NOT NULL COMMENT 'Record Description',
            'tmr_status' => $this->epo['epo_status'] # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding)',
        );

        # Send to Transaction Manager and fetch assigned reference.
        if (!wfRunHooks('CreateTransaction', array(&$tmr)) && isset($tmr['tmr_id']) && $tmr['tmr_id'] > 0) {
            $this->epo['epo_tmr_id'] = $tmr['tmr_id'];
            return true;
        }
        
        return false;
    }

    public function getId() {
        return $this->epo_id;
    }

}