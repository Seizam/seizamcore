<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateUsages extends Maintenance {
	
	public function __construct() {
		
		parent::__construct();
		$this->mDescription = "Update outdated usages + archive and reset ending usages.";
		
	}

	public function execute() {

		$this->output( "[".WpSubscription::now().": Updating usages...]\n" );		
		$ok = WpWikiplace::updateOutdatedUsages();	
		if ( $ok === false ) {
			$this->output("ERROR: $ok\n");
		} else {
			$this->output("OK: $ok record(s) updated\n");
		}
			
		
		$this->output( "[".WpSubscription::now().": Archiving and resetting monthly usages...]\n" );		
		$ok = WpWikiplace::archiveAndResetExpiredUsages();
		if ( $ok === false ) {
			$this->output("ERROR: $ok\n");
		} else {
			$this->output("OK: $ok record(s) archived and reset\n");
		}
		$this->output( "[".WpSubscription::now().": END]\n" );
		
	}
}

$maintClass = "UpdateUsages";
require_once( RUN_MAINTENANCE_IF_MAIN );
