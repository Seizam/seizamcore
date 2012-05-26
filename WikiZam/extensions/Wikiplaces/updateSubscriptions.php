<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateSubscriptions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Automaticaly renew ending subscriptions, and archive old ones.";

	}

	public function execute() {
		
		$when = WpSubscription::now();
		$this->output("Considering 'now' = $when\n\n");
		
		
		$this->output( "[".WpSubscription::now()." Renewing subscriptions...]\n" );	
		$subs = WpSubscription::factoryAllOutdatedToRenew($when);
		$this->output( count($subs)." subscriptions to process, progress:\nwps_id ; OK/KO ; wps_buyer_user_id ; wps_start_date ; wps_end_date ; wps_tmr_id ; wps_tmr_status\n\n" );
		foreach ($subs as $sub) {
			$result = $sub->renew();			
			$this->output( $sub->getId().';'
					.( ($result===true) ? 'OK' : $result ).';'
					.$sub->getBuyerUserId().';'
					.$sub->getStart().';'
					.$sub->getEnd().';'
					.$sub->getTmrId().';'
					.$sub->getTmrStatus()."\n");
		}
		$this->output( "\n[".WpSubscription::now()." END]\n\n" );
		
		
		$this->output( "[".WpSubscription::now()." Deactivating all remaining outdated subscriptions...]\n" );
		if ( ($nb=WpSubscription::deactivateAllOutdated($when)) === false ) {
			$this->output( "a problem occured\n" );
		} else {
			$this->output( "OK, $nb subscriptions updated\n" );
		}
		$this->output( "[".WpSubscription::now()." END]\n" );
		
	}
}

$maintClass = "UpdateSubscriptions";
require_once( RUN_MAINTENANCE_IF_MAIN );
