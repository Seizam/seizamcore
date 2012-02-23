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
$wgAutoloadClasses['TransactionManagerHooks'] = $dir . 'TransactionManager.hooks.php';

# Attach Hooks
# Adds the necessary tables to the DB
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TransactionManagerHooks::loadExtensionSchemaUpdates';
# On Electronic Payment action
$wgHooks['BeforeTransactionSave'][] = 'TransactionManagerHooks::beforeTransactionSave';

# i18n
$wgExtensionMessagesFiles['TransactionManager'] = $dir . 'TransactionManager.i18n.php';
$wgExtensionAliasesFiles['TransactionManager'] = $dir . 'TransactionManager.alias.php';

# Special Electronic Payment (OUTbound)
$wgAutoloadClasses['SpecialTransactionManager'] = $dir . 'SpecialTransactionManager.php';
$wgSpecialPages['TransactionManager'] = 'SpecialTransactionManager';

$wgSpecialPageGroups['TransactionManager'] = 'other';

# Right for Transaction administration
$wgAvailableRights[] = 'tmadmin';
$wgGroupPermissions['sysop']['tmadmin'] = true;

Class TMRecord {
    # DB Entry

    public $tmr = array(
        # Params related to Message
        'tmr_id' => '', #int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
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
        'tmr_status' => 'KO' # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding, TEst)',
    );

    # Main constructor, often called by hooked funtion in TransactionManagerHooks.

    public function __construct($record) {
        global $wgUser;

        # If there is an id submitted through record, fetch data from db
        if (isset($record['tmr_id']) && $record['tmr_id'] > 0) {
            $this->__constructFromDB($record);
        } else # It is a new record
            $this->__constructFromScratch($record);
        
    }
    
    private function __constructFromDB($record) {
        # Okay, which Record are we talking about?
        $this->tmr['tmr_id'] = $record['tmr_id'];
        
        # Let's fetch the record's data from DB.
        $this->readDB();
        
        # Now we update the record by merging $tmr with $record...
        
        # We don't want anything telling us the record's birthdate!
        unset($record['tmr_date_created']);
        
        # @TODO: Perhaps more fields shouldn't be overwritten (ex: user_id...)?
        
        # And now, we merge.
        $this->tmr = array_merge($this->tmr, $record);
        
        # Finally, update DB.
        $this->updateDB();
    }
    
    private function __constructFromScratch($record) {
        global $wgUser;
        # set tmr_date_created (we are creating the entry)
        $this->tmr['tmr_date_created'] = date("Y-m-d:H:i:s");
        
        # Now we write the record by merging $tmr with $record...
        
        # And now, we merge.
        $this->tmr = array_merge($this->tmr, $record);
        
        # See if we can add some missing data...
        
        # tmr_user_id is empty, let's write it
        if ($this->tmr['tmr_user_id'] == '')
            $this->tmr['tmr_user_id'] = $wgUser->getId();
        
        # tmr_mail is empty, let's write it
        if ($this->tmr['tmr_mail'] == '')
            $this->tmr['tmr_mail'] = $wgUser->getEmail();
        
        # tmr_ip is empty, let's write it
        if ($this->tmr['tmr_ip'] == '')
            $this->tmr['tmr_ip'] = IP::sanitizeIP(wfGetIP());
        
        # Finally, write DB.
        # @TODO: Check if we're not trying to rewrite the same line again...
        $this->writeDB();
    }

    # Record current object to DB and set tmr_id

    private function writeDB() {
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        # PostgreSQL, null for MySQL
        $this->tmr['tmr_id'] = $dbw->nextSequenceValue('tm_record_tmr_id_seq');
        # Setting the date of update
        $this->tmr['tmr_date_modified'] = date("Y-m-d:H:i:s");
        # Writing...
        $return = $dbw->insert('tm_record', $this->tmr);
        # Setting tmr_id from auto incremented id in DB
        $this->tmr['tmr_id'] = $dbw->insertId();
        
        return $return;
    }
    
    # Write current object to DB
    
    private function updateDB() {
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        # Setting the date of update
        $this->tmr['tmr_date_modified'] = date("Y-m-d:H:i:s");
        # Writing...
        $return = $dbw->update('tm_record', $this->tmr, array('tmr_id'=>$this->tmr['tmr_id']));
        
        return $return;
    }

    # Set current object from DB.

    private function readDB() {
        # We are reading, but we need the master table anyway
        # (A record can be updated a lot within instants)
        $dbr = wfGetDB(DB_MASTER);
        $this->tmr = (array)$dbr->selectRow('tm_record', '*', 'tmr_id = ' . $this->tmr['tmr_id']);
    }

}