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
}
