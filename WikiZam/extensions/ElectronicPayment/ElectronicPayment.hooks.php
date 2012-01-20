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
class ElectronicPaymentHooks {


        /* Static Methods */
	
	
	/**
	 * LoadExtensionSchemaUpdates hook
	 * 
	 * Adds the necessary tables to the DB
	 * 
	 */

	public static function loadExtensionSchemaUpdates( $updater ) {
        $updater->addExtensionUpdate( array( 'addTable', 'ep_message',
                dirname( __FILE__ ) . '/schema/mysql/ep_message.sql', true ) );
        return true;
        }
}
