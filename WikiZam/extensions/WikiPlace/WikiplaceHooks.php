<?php


if (!defined('MEDIAWIKI')) {
	die(-1);
}

class WikiplaceHooks {

	public static function onHook( $arg, $arg ) {
		return true;
	}

	# Schema updates for update.php
	static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		
		$tables = array(
			'wp_plan', 
			'wp_subscription',
//			'wp_usage',
			'wp_wikiplace',
//			'wp_page'
		);
		
		$mysql_dir = dirname( __FILE__ ).'/schema/mysql';
		foreach ($tables as $table) {
			$updater->addExtensionUpdate( array( 'addTable', $table , "$mysql_dir/$table.sql", true ) );
		}
		
		return true;
	}
	
}
