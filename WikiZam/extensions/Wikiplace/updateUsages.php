<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateUsages extends Maintenance {
	
	public function __construct() {
		
		parent::__construct();
		$this->mDescription = "Update outdated usages + archive and reset ending usages.";
		
	}

	public function execute() {

		$this->output( "[Update usages START at ".WpSubscription::getNow()."]\n" );		
		$nb = WpWikiplace::updateAllOutdatedUsages();	
		$this->output("$nb rows updated\n");
		$this->output( "[END at ".WpSubscription::getNow()."]\n" );
		
		$this->output( "[Archive and reset usages START at ".WpSubscription::getNow()."]\n" );		
		$nb = WpWikiplace::archiveAndResetMonthlyUsages();
		$this->output("$nb rows updated\n");
		$this->output( "[END at ".WpSubscription::getNow()."]\n" );
		
	}
}

$maintClass = "UpdateUsages";
require_once( RUN_MAINTENANCE_IF_MAIN );
