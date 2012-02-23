<?php
/**
 * Hooks for ElectronicPayment extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}
class TransactionManagerHooks {


        /* Static Methods */
	
	
	/**
	 * LoadExtensionSchemaUpdates hook
	 * 
	 * Adds the necessary tables to the DB
	 * 
	 */

	public static function loadExtensionSchemaUpdates( $updater ) {
        $updater->addExtensionUpdate( array( 'addTable', 'tm_record',
                dirname( __FILE__ ) . '/schema/mysql/tm_record.sql', true ) );
        return true;
        }
        
        
        public static function beforeTransactionSave(&$tmr){
            # Construct TMRecord
            $record = TMRecord::create($tmr);
            # Set TMRecord from input array
            $record->update($tmr);
            # Overwrite input array with new values
            $tmr = $record->tmr;
            return false;
        }
}
