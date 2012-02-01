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
$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'Transaction Manager',
    'author' => array('Clément Dietschy', 'Seizam Sàrl.'),
    'version' => '0.1.0',
    'url' => 'http://www.seizam.com/',
    'descriptionmsg' => 'tm-desc',
);


$dir = dirname(__FILE__) . '/';

#Load Hooks
$wgAutoloadClasses['TransactionManagerHooks'] = $dir . 'ElectronicPayment.hooks.php';

# Attach Hooks
# Adds the necessary tables to the DB
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TransactionManagerHooks::loadExtensionSchemaUpdates';

# i18n
$wgExtensionMessagesFiles['TransactionManager'] = $dir . 'TransactionManager.i18n.php';
$wgExtensionAliasesFiles['TransactionManager'] = $dir . 'TransactionManager.alias.php';

# Special Electronic Payment (OUTbound)
$wgAutoloadClasses['SpecialTransactionManager'] = $dir . 'TransactionManager_body.php';
$wgSpecialPages['TransactionManager'] = 'SpecialTransactionManager';

$wgSpecialPageGroups['TransactionManager'] = 'other';

# Right for Transaction administration
$wgAvailableRights[] = 'tmadmin';
$wgGroupPermissions['sysop']['tmadmin'] = true;

Class TMRecord {

    # DB Entry
    public $tmr = array(
        # Params related to Message
        'tmr_id' => '', #int(12) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
        'tmr_type' => '', # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
        'tmr_date_created' => '', # datetime NOT NULL COMMENT 'DateTime of creation',
        'tmr_date_modified' => '', # datetime NOT NULL COMMENT 'DateTime of last modification',
        # Paramas related to User
        'tmr_user_id' => '', # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
        'tmr_mail' => '', # tinyblob COMMENT 'User''s Mail',
        'tmr_ip' => '', # tinyblob COMMENT 'User''s IP'
        # Params related to Record
        'tmr_amount' => '', # decimal(9,2) NOT NULL COMMENT 'Record Amount',
        'tmr_currency' => 'EUR', # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
        'tmr_mac' => '', # varchar(40) COMMENT 'Record Verification Sum',
        'tmr_desc' => '', # varchar(64) NOT NULL COMMENT 'Record Description',
        'tmr_status' => 'ko' # varchar(2) NOT NULL COMMENT 'Record status (ok, ko, pending)',
        );

    # Main constructor, often called by hooked funtion in TransactionManagerHooks
    public function __construct($input) {
        global $wgUser;
        $this->tmr = array_merge($this->tmr, $input);
        $this->tmr['tmr_date_created'] = $this->tmr['tmr_date_modified'] = date("Y-m-d:H:i:s");
        $this->tmr['tmr_user_id'] = $wgUser->getId();
        $this->tmr['tmr_mail'] = $wgUser->getEmail();
        $this->tmr['tmr_ip'] = IP::sanitizeIP(wfGetIP());
        
        $this->writeDB();
    }

    # Record current object to DB.
    private function writeDB() {
        $dbw = wfGetDB(DB_MASTER);
        return $dbw->insert('tm_record', $this->tmr);
    }

    # Set current object from DB.
    private function readDB() {
        $dbr = wfGetDB(DB_SLAVE);
        $this->epm = $dbr->selectRow('tm_record', '*', 'tmr_id = ' . $this->tmr['tmr_id']);
    }


}