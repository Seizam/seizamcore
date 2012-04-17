<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateSubscriptions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Script to update Subscriptions.";
		$this->addOption( 'reportonly', 'Generate a report but do not update DB' );
//		$this->addOption( 'semiprotect', 'Adds semi-protection' );
//		$this->addOption( 'u', 'Username to protect with', false, true );
//		$this->addOption( 'r', 'Reason for un/protection', false, true );
//		$this->addArg( 'title', 'Title to protect', true );
	}

	public function execute() {
//		global $wgUser;
//		$wgUser = User::newFromName( 'Maintenance script' );

//		$userName = $this->getOption( 'u', 'Maintenance script' );
//		$reason = $this->getOption( 'r', '' );

		$update_db = !($this->hasOption( 'reportonly' ));
		
		$this->output( "For the moment, it just displays all subscriptions.\n" );
		
		$this->output( "[".WpPlan::getNow()." START]\n" );
		
		$subs = WpSubscription::getAll('I know what i am doing');

		$this->output( "ID / NAME / BUYER USER ID / ACTIVE / TMR STATUS / START DATE / END DATE\n" );
		
		foreach ($subs as $sub) {
			$this->output( 
					$sub->get('wps_id') . " / " .
					$sub->get('plan')->get('wpp_name') . " / " .
					$sub->get('wps_buyer_user_id') . " / " .
					$sub->get('wps_active') . " / " .
					$sub->get('wps_tmr_status') . " / " .
					$sub->get('wps_start_date') . " / " .
					$sub->get('wps_end_date') . "\n" );
		}
		
		$this->output( "[".WpPlan::getNow()." END]\n" );
	}
}

$maintClass = "UpdateSubscriptions";
require_once( RUN_MAINTENANCE_IF_MAIN );
