<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateUsages extends Maintenance {
	
	public function __construct() {
		
		parent::__construct();
		$this->mDescription = "Script to update Usages.";
		
	}

	public function execute() {

		$this->output( "[Update usages ".WpPlan::getNow()." START]\n" );		
		$nb = WpWikiplace::updateAllOutdatedUsages();	
		$this->output( "[".WpPlan::getNow()." END $nb rows updated]\n" );
		
		$this->output( "[Archive and reset usages ".WpPlan::getNow()." START]\n" );		
		$nb = WpWikiplace::archiveAndResetMonthlyUsages();	
		$this->output( "[".WpPlan::getNow()." END $nb rows updated]\n" );
		
	}
}

$maintClass = "UpdateUsages";
require_once( RUN_MAINTENANCE_IF_MAIN );
