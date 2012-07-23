<?php

/**
 * Hooks for Transactions extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

class TransactionsHooks {
    /* Static Methods */

    /**
     * LoadExtensionSchemaUpdates hook
     * 
     * Adds the necessary tables to the DB
     * 
     */
    public static function loadExtensionSchemaUpdates($updater) {
        $updater->addExtensionUpdate(array('addTable', 'tm_record',
            dirname(__FILE__) . '/schema/mysql/tm_record.sql', true));
        return true;
    }

    /**
     * createTransaction hook
     * 
     * Write Transaction to the DB when required by other extension
     * 
     */
    public static function createTransaction(&$tmr) {
        # Construct TMRecord
        $record = TMRecord::create($tmr);
        # React to new record
        $record->react();
        # Overwrite input array with new values
        $tmr = $record->getTMR();
        # React to created transaction
        return false;
    }

    /**
     * electronicPaymentAttempt hook
     * 
     * Collect PEnding transactions and return them
     * 
     * @Deprecated
     * 
     */
    public static function electronicPaymentAttempt($user_id, &$transactions) {
        foreach (TMRecord::getPendingTransactions($user_id) as $tmr) {
            $transactions[] = $tmr;
        }
        return false;
    }
    
    /**
     *
     * @param int $tmr_id
     * @param User $user
     * @param boolean $result 
     */
    public static function cancelTransaction($tmr_id, &$user, &$result) {
        $result = TMRecord::getById($tmr_id)->cancel($user);
        return false;
    }

}
