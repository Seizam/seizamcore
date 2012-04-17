<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateUsages extends Maintenance {
	
	public function __construct() {
		
		parent::__construct();
		$this->mDescription = "Script to update Usages.";
		
	}

	public function execute() {

		$this->output( "[".WpPlan::getNow()." START]\n" );		
		$nb = WpUsage::updateAllOutdatedCounters();	
		$this->output( "[".WpPlan::getNow()." END $nb usage rows updated]\n" );
		
	}
}

$maintClass = "UpdateUsages";
require_once( RUN_MAINTENANCE_IF_MAIN );
