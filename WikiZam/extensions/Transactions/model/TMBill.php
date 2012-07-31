<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/*
 * Transaction Manager Bill Main Class
 */

class TMBill {

    /** @var int */
    private $id; #tmb_id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
    const ID_FIELD = 'tmb_id';

    /** @var string */
    private $dateCreated; #tmb_date_created datetime NOT NULL COMMENT 'DateTime of creation',
    const DATE_CREATED_FIELD = 'tmb_date_created';

    /**
     * @param int $id
     * @param string $dateCreated 
     */
    private function __construct($id, $dateCreated) {
        $this->id = intval($id);
        $this->dateCreated = $dateCreated;
    }

    /**
     * @return int 
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * @todo turn into getRecordS()
     * @return TMRecord 
     */
    public function getRecord() {
        return TMRecord::newFromBillId($this->id);
    }
    
    /**
     * @return string 
     */
    public function getDateCreated() {
        return $this->dateCreated;
    }
    
    /**
     * Create a new entry in tm_bill and return the object
     * @return TMBill 
     */
    public static function newFromScratch() {
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        # PostgreSQL, null for MySQL
        $id = $dbw->nextSequenceValue('tm_bill_tmb_id_seq');
        
        # Building the row
        $entry = array();
        $entry[self::DATE_CREATED_FIELD] = wfTimestamp(TS_DB);

        # Writing...
        $success = $dbw->insert('tm_bill', $entry);

        # Setting tmr_id from auto incremented id in DB
        $entry[self::ID_FIELD] = $dbw->insertId();

        $dbw->commit();

        if (!$success) {
            return null;
        }

        return self::newFromEntry($entry);
    }
    
    /**
     *
     * @param array $entry
     * @return TMBill 
     */
    public static function newFromEntry($entry) {
        return new self($entry[self::ID_FIELD],$entry[self::DATE_CREATED_FIELD]);
    }
    
    /**
     *
     * @param ResultWrapper $row
     * @return TMBill 
     */
    public static function newFromRow($row) {
        $row = (array) $row;
        return new self($row[self::ID_FIELD],$row[self::DATE_CREATED_FIELD]);
    }
    
    /**
     * @todo query not here but in the get()
     * @param int $id 
     * @return TMBill 
     */
    public static function newFromId($id) {
        if (($id === null) || !is_int($id) || ($id < 1)) {
            return null;
        }

        # We need to read, but with money issues, best read from master.
        $dbr = wfGetDB(DB_MASTER);
        $result = $dbr->selectRow('tm_bill', '*', array('tmb_id' => $id), __METHOD__);

        if ($result === false) {
            // not found, so return null
            return null;
        }

        return self::newFromRow($result);
    }

}