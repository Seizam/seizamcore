<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/*
 * Transaction Manager Record Main Class
 */

class TMRecord {

    private $id; #int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
    # The DB Record
    private $tmr = array(
        # Params related to Message
        'tmr_type' => null, # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
        'tmr_date_created' => null, # datetime NOT NULL COMMENT 'DateTime of creation',
        'tmr_date_modified' => null, # datetime NOT NULL COMMENT 'DateTime of last modification',
        # Paramas related to User
        'tmr_user_id' => null, # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
        'tmr_mail' => null, # tinyblob COMMENT 'User''s Mail',
        'tmr_ip' => null, # tinyblob COMMENT 'User''s IP'
        # Params related to Record
        'tmr_amount' => null, # decimal(9,2) NOT NULL COMMENT 'Record Amount',
        'tmr_currency' => null, # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
        'tmr_mac' => null, # varchar(40) COMMENT 'Record Verification Sum',
        'tmr_desc' => null, # varchar(64) NOT NULL COMMENT 'Record Description',
        'tmr_status' => null, # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding, TEst)',
        'tmr_tmb_id' => null #int(10) unsigned COMMENT 'Foreign key to tm_bill'
    );

    private function __construct($id, $tmr) {
        $this->id = $id;

        # Keeping only the field that we want
        $tmr = array_intersect_key($tmr, $this->tmr);

        $this->tmr = $tmr;
    }

    public function getId() {
        return $this->id;
    }

    public function getTMR() {
        return array_merge($this->tmr, array('tmr_id' => $this->id));
    }

    public function getStatus() {
        return $this->tmr['tmr_status'];
    }

    public function getUserId() {
        if (!isset($this->tmr['tmr_user_id'])) {
            throw new MWException('No user ID related to Record ' . $this->id . '.');
        }

        return $this->tmr['tmr_user_id'];
    }
    
    public function getTMBId() {
        return $this->tmr['tmr_tmb_id'];
    }

    /**
     * Get the TMRecord instance from a SQL row
     * @param ResultWrapper $row
     * @return self 
     */
    private static function constructFromDatabaseRow($row) {

        if ($row === null) {
            throw new MWException('Cannot construct the TMRecord from the supplied row (null given)');
        }

        if (!isset($row->tmr_id)) {
            throw new MWException('Cannot construct the TMRecord from the supplied row (missing id field)');
        }

        return new self(intval($row->tmr_id), (array) $row);
    }

    /**
     * Restore from DB, using id
     * @param int $id 
     * @return TMRecord
     */
    public static function getById($id) {

        if (($id === null) || !is_int($id) || ($id < 1)) {
            throw new MWException('Cannot fectch TMRecord matching the identifier (invalid identifier)');
        }

        # We need to read, but with money issues, best read from master.
        $dbr = wfGetDB(DB_MASTER);
        $result = $dbr->selectRow('tm_record', '*', array('tmr_id' => $id), __METHOD__);

        if ($result === false) {
            // not found, so return null
            return null;
        }

        return self::constructFromDatabaseRow($result);
    }

    public static function newFromBillId($id) {
        if (($id === null) || !is_int($id) || ($id < 1)) {
            throw new MWException('Cannot fectch TMRecord matching the identifier (invalid identifier)');
        }

        # We need to read, but with money issues, best read from master.
        $dbr = wfGetDB(DB_MASTER);
        $result = $dbr->selectRow('tm_record', '*', array('tmr_tmb_id' => $id), __METHOD__);

        if ($result === false) {
            // not found, so return null
            return null;
        }

        return self::constructFromDatabaseRow($result);
    }

    /**
     *
     * @param array $tmr
     * @return TMRecord the newly created TMRecord or null if an error occured 
     */
    public static function create($tmr) {

        if (!is_array($tmr) ||
                !isset($tmr['tmr_type']) ||
                !isset($tmr['tmr_user_id']) ||
                !isset($tmr['tmr_amount']) ||
                !isset($tmr['tmr_currency']) ||
                !isset($tmr['tmr_desc']) ||
                !isset($tmr['tmr_status'])) {
            throw new MWException('Cannot create TMRecord (missing argument)');
        }

        if (!is_string($tmr['tmr_type']) ||
                !is_int($tmr['tmr_user_id']) ||
                !is_numeric($tmr['tmr_amount']) ||
                $tmr['tmr_currency'] !== 'EUR' ||
                !is_string($tmr['tmr_desc']) ||
                !in_array($tmr['tmr_status'], array('OK', 'KO', 'PE', 'TE'))) {
            throw new MWException('Cannot create TMRecord (invalid argument)');
        }

        if ($tmr['tmr_amount'] <= 0 && $tmr['tmr_status'] !== 'PE') {
            throw new MWException('Cannot create TMRecord (expense should be PEnding)' . print_r($tmr, true));
        }

        # Setting the date of update
        $tmr['tmr_date_created'] = $tmr['tmr_date_modified'] = wfTimestamp(TS_DB);

        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        # PostgreSQL, null for MySQL
        $id = $dbw->nextSequenceValue('tm_record_tmr_id_seq');

        # Writing...
        $success = $dbw->insert('tm_record', $tmr);

        # Setting tmr_id from auto incremented id in DB
        $id = $dbw->insertId();

        $dbw->commit();

        if (!$success) {
            return null;
        }

        return new self($id, $tmr);
    }

    /**
     *
     * @param array $tmr
     * @return TMRecord the newly created TMRecord or null if an error occured 
     */
    private static function constructExistingFromArray($tmr) {

        if (!is_array($tmr) ||
                isset($tmr['tmr_id'])) {
            throw new MWException('Cannot create TMRecord (missing argument)');
        }

        if (is_int($tmr['tmr_id'])) {
            throw new MWException('Cannot create TMRecord (invalid argument)');
        }

        # Setting the record Id
        $id = $tmr['tmr_id'];

        # We don't want these fields to be changed
        unset($tmr['tmr_type']);
        unset($tmr['tmr_user_id']);
        unset($tmr['tmr_amount']);
        unset($tmr['tmr_currency']);

        # Validating arguments
        if ((isset($tmr['tmr_mail']) && !is_string($tmr['tmr_mail'])) ||
                (isset($tmr['tmr_ip']) && !is_string($tmr['tmr_ip'])) ||
                (isset($tmr['tmr_mac']) && !is_string($tmr['tmr_mac'])) ||
                (isset($tmr['tmr_desc']) && !is_string($tmr['tmr_desc'])) ||
                (isset($tmr['tmr_status']) && !in_array($tmr['tmr_status'], array('OK', 'KO', 'PE', 'TE')))) {
            throw new MWException('Cannot create TMRecord (invalid argument)');
        }

        return new self($id, $tmr);
    }

    /**
     *
     * @return boolean 
     */
    private function updateDB() {
        # Setting the date of update
        unset($this->tmr['tmr_date_created']);
        $this->tmr['tmr_date_modified'] = wfTimestamp(TS_DB);

        # We need to write, therefore we need the master
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        # Writing...
        $return = $dbw->update('tm_record', $this->tmr, array('tmr_id' => $this->id));

        $dbw->commit();

        return $return;
    }

    /**
     * Get array of TMRecord /!\ONLY FOR EUROS
     * @TODO Make it work for every currency
     * @param int $user_id
     * @return TMRecord[] ("array()" if no record)
     */
    public static function getAllOwnedByUserId($user_id, $conditions = null) {

        if (($user_id === null) || !is_int($user_id) || ($user_id < 1)) {
            throw new MWException('Cannot fetch TMRecord owned by the specified user (invalid user identifier)');
        }

        if (isset($conditions))
            $conditions = array_merge(array('tmr_user_id' => $user_id, 'tmr_currency' => 'EUR'), $conditions);

        # We need to read, but with money issues, best read from master.
        $dbr = wfGetDB(DB_MASTER);
        $result = $dbr->select('tm_record', '*', $conditions, __METHOD__);

        $tmrecords = array();

        foreach ($result as $row) {
            $tmrecords[] = self::constructFromDatabaseRow($row);
        }

        $dbr->freeResult($result);

        return $tmrecords;
    }

    /**
     * Calculate account balance /!\ONLY FOR EUROS
     * @TODO Make it work for every currency
     * @return float
     */
    private static function getBalanceFromDB($user_id, $conditions = null) {
        $conditions = array_merge(array('tmr_user_id' => $user_id, 'tmr_currency' => 'EUR'), $conditions);


        # We need to read, but with money issues, best read from master.
        $dbr = wfGetDB(DB_MASTER);
        $dbr->begin();
        $result = $dbr->select('tm_record', 'SUM(tmr_amount) AS balance', $conditions);
        $dbr->commit();

        # Returning an Int
        return is_null($result->current()->balance) ? 0 : $result->current()->balance;
    }

    /**
     * Calculate true account balance (status=PE+OK) /!\ONLY FOR EUROS
     * @TODO Make it work for every currency
     * @return float
     */
    public static function getTrueBalanceFromDB($user_id) {
        return self::getBalanceFromDB($user_id, array("tmr_status='OK' OR (tmr_amount<0 AND tmr_status='PE')"));
    }

    /**
     * Do the necessary after record logic /!\ONLY FOR EUROS
     * @TODO Make it work for every currency
     * @return boolean
     */
    public function react() {
        // No reaction if not in Euro
        if ($this->tmr['tmr_currency'] !== 'EUR') {
            return false;
        }

        // No reaction if status ===KO
        if ($this->getStatus() === 'KO') {
            return false;
        }

        // If we have money incoming, perhaps some PEnding expenses can be Validated
        if ($this->tmr['tmr_amount'] > 0 && $this->getStatus() === 'OK') {
            return $this->reactToIncome();
        }

        // If we have money outgoing, it's PEnding and could be Validated
        if ($this->tmr['tmr_amount'] <= 0 && $this->getStatus() === 'PE') {
            return $this->reactToExpense();
        }
    }

    /**
     * Do the necessary after income logic /!\ONLY FOR EUROS
     * @TODO Make it work for every currency
     * @return boolean
     */
    private function reactToIncome() {
        $return = false;
        $pendingExpenses = self::getAllOwnedByUserId($this->getUserId(), array('tmr_status' => 'PE', 'tmr_amount <= 0'));
        $balanceOk = self::getBalanceFromDB($this->getUserId(), array('tmr_status' => 'OK'));
        foreach ($pendingExpenses as $expense) {
            $balanceOk += $expense->attemptPEtoOK($balanceOk);
        }
        
        # Setting the bill id (for a refund), bill id for expense is done later in react()
        if ($this->tmr['tmr_type'] == TM_REFUND_TYPE && $this->tmr['tmr_amount'] > 0) {
            $this->tmr['tmr_tmb_id'] = TMBill::newFromScratch()->getId();
            $this->updateDB();
        }
        
        return $return;
    }

    /**
     * Turn PE to OK is $balanceOk is positive /!\ONLY FOR EUROS
     * @TODO Make it work for every currency
     * @return int the amount of validated transaction
     */
    private function attemptPEtoOK($balanceOk) {
        $amount = 0;
        if ($this->tmr['tmr_currency'] === 'EUR' #Only Euro for the moment
                && $this->tmr['tmr_amount'] <= 0 #This is about (pending) expenses only
                && $this->tmr['tmr_status'] === 'PE' #This is about pending (expenses) only
                && ($this->tmr['tmr_amount'] + $balanceOk) >= 0) { #We can validate if the balance is positive or null
            $this->toOK();
            //amount to return
            $amount = $this->tmr['tmr_amount'];
            //Notify the other extensions
            $tmr = $this->tmr;
            $tmr['tmr_id'] = $this->id;
            wfRunHooks('TransactionUpdated', array($tmr));
        }
        return $amount;
    }

    private function toOK() {
        //new status
        $this->tmr['tmr_status'] = 'OK';
        //Do the billing
		if ( $this->tmr['tmr_amount'] != 0 ) {
	        $this->tmr['tmr_tmb_id'] = TMBill::newFromScratch()->getId();
		}
        //Update de DB
        $this->updateDB();
    }

    /**
     * Do the necessary after expense logic /!\ONLY FOR EUROS
     * ie. Turn PE to OK if account balance is positive
     * @TODO Do it on create, this logic writes DB twice in a row...
     * @TODO Make it work for every currency
     * @return boolean
     */
    private function reactToExpense() {
        $return = false;
        if (self::getTrueBalanceFromDB($this->getUserId()) >= 0) {
            $this->toOK();
            $return = true;
        }
        return $return;
    }

    /**
     * Check if user is owner OR user is admin AND tmr_status = PE;
     * @param User $user
     * @return boolean 
     */
    public function canCancel($user) {
        return ($this->getUserId() == $user->getId() || $user->isAllowed(TM_ADMIN_RIGHT)) && $this->getStatus() == 'PE';
    }

    /**
     * Turn tmr_status to KO (only if user can & tmr_status = PE)
     * @param User $user
     * @return boolean True or error string 
     */
    public function cancel($user) {
        if (!$this->canCancel($user)) {
            return 'Error: Transaction cannot be cancelled';
        }
        $this->tmr['tmr_status'] = 'KO';
        return $this->updateDB();
    }

}