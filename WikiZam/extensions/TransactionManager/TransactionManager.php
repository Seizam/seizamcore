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
    
    private $tmr_id;

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
    
    /**
     * @return TMRecord
     */
    public static function create($tmr) {
        if (is_int($tmr))
            $tmr = array('tmr_id' => $tmr);
        return new TMRecord($tmr);
    }

    # Main constructor, often called by hooked funtion in TransactionManagerHooks.

    private function __construct($tmr) {

        # If there is an id submitted through record, fetch data from db
        if (isset($tmr['tmr_id']) && $tmr['tmr_id'] > 0) {
            $this->tmr_id = $tmr['tmr_id'];
            $this->__constructFromDB();
        } else # It is a new record
            $this->__constructFromScratch($tmr);
        
    }
    
    private function __constructFromDB() {
        # Let's fetch the order's data from DB.
        $this->readDB();
    }
    
    # Set TMP and update database from array
    
    public function update($tmr) {
        # Check if we have an existing object entering
        # if not, that means the object has been created with __constructFromScratch and is already in DB
        if (isset($tmr['tmr_id']) && $tmr['tmr_id'] > 0) {
            if ($this->set($tmr)) {
                return $this->updateDB();
            }
        }
        
        return false;
    }
    
    # Set TMRecord from array
    
    public function set($tmr) {
        # First we keep only what we want from $tmr
        $tmr = array_intersect_key($tmr, $this->tmr);

        # We don't want anything telling us these:
        # TODO: Add unique Hash identification?
        unset($tmr['tmr_id']);
        if ($tmr['tmr_user_id'] === $this->tmr['tmr_user_id']) {
            # And now, we merge.
            $this->tmr = array_merge($this->tmr, $tmr);

            return true;
        }
        return false;
    }
    
    private function __constructFromScratch($tmr) {
        global $wgUser;
        
        # First we keep only what we want from $tmr
        $tmr = array_intersect_key($tmr, $this->tmr);
        
        # Now we write the record by merging $this->tmr with $tmr...
        
        # And now, we merge.
        $this->tmr = array_merge($this->tmr, $tmr);
        
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
        # Setting the date of update
        $this->tmr['tmr_date_created'] = date("Y-m-d:H:i:s");
        $this->tmr['tmr_date_modified'] = $this->tmr['tmr_date_created'];
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        # PostgreSQL, null for MySQL
        $this->tmr['tmr_id'] = $dbw->nextSequenceValue('tm_record_tmr_id_seq');
        # Writing...
        $return = $dbw->insert('tm_record', $this->tmr);
        # Setting tmr_id from auto incremented id in DB
        $this->tmr['tmr_id'] = $dbw->insertId();
        
        return $return;
    }
    
    # Write current object to DB
    
    private function updateDB() {
        # Setting the date of update
        unset($this->tmr['tmr_date_created']);
        $this->tmr['tmr_date_modified'] = date("Y-m-d:H:i:s");
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
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