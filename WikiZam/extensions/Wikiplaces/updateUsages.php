<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateUsages extends Maintenance {
	
	public function __construct() {
		
		parent::__construct();
		$this->mDescription = "Update outdated usages + archive and reset ending usages.";
		
	}

	public function execute() {

		$this->output( "[Update usages START at ".WpSubscription::now()."]\n" );		
		$ok = WpWikiplace::updateOutdatedUsages();	
		if ( $ok === false ) {
			$this->output("error: $ok\n");
		} else {
			$this->output("$ok rows updated\n");
		}
		$this->output( "[END at ".WpSubscription::now()."]\n" );
		
		
		$this->output( "[Archive and reset usages START at ".WpSubscription::now()."]\n" );		
		$ok = WpWikiplace::archiveAndResetExpiredUsages();
		if ( $ok === false ) {
			$this->output("error: $ok\n");
		} else {
			$this->output("$ok rows updated\n");
		}
		$this->output( "[END at ".WpSubscription::now()."]\n" );
		
	}
}

$maintClass = "UpdateUsages";
require_once( RUN_MAINTENANCE_IF_MAIN );
