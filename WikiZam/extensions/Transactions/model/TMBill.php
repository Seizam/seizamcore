<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/*
 * Transaction Manager Bill Main Class
 */

class TMBill {

    /**
     *
     * @var int 
     */
    private $id; #tmb_id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'

    private function __construct() {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }
    
    public function newFromScratch() {
        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        # PostgreSQL, null for MySQL
        $id = $dbw->nextSequenceValue('tm_bill_tmb_id_seq');

        # Writing...
        $success = $dbw->insert('tm_bill', array('tmb_id'=>null));

        # Setting tmr_id from auto incremented id in DB
        $id = $dbw->insertId();

        $dbw->commit();

        if (!$success) {
            return null;
        }

        return new self($id);
    }

}