<?php
/**
 * Hooks for SeizamACL extension
 * 
 * @file
 * @ingroup Extensions
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}
class SeizamACLHooks {
    
        /* Static Methods */
	
	/**
	 * LoadExtensionSchemaUpdates hook
	 * 
	 * Adds the necessary tables to the DB
	 * 
	 */
	
	public static function loadExtensionSchemaUpdates( $updater ) {
        $updater->addExtensionUpdate( array( 'addTable', 'szacl_owner',
                dirname( __FILE__ ) . '/schema/mysql/szacl_owner.sql', true ) );
        return true;
}
	
}
