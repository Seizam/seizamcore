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
		
		$this->output( "[START]\n" );
		
		$subs = WpSubscription::getAll('I know what i am doing');

		foreach ($subs as $sub) {
			$this->output( 
					$sub->get('wps_id') . "/" .
					$sub->get('plan')->get('wpp_name') . "/" .
					$sub->get('wps_buyer_user_id') . "\n" );
		}
		
		$this->output( "[END]\n" );
	}
}

$maintClass = "UpdateSubscriptions";
require_once( RUN_MAINTENANCE_IF_MAIN );
