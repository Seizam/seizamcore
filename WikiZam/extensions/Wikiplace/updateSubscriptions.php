<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateSubscriptions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Automaticaly renew ending subscriptions, and archive old ones.";

	}

	public function execute() {
		
		$when = WpSubscription::getNow();
		$this->output("Considering 'now' = $when\n\n");
		
		
		$this->output( "[".WpSubscription::getNow()." Archiving all subscriptions to renew...]\n" );
		$status = WpSubscription::archiveAllOutdatedToRenew($when);
		if ( ! $status->isGood() ) {
			$this->output( "a problem occured: ".$status->$value."\n" );
		} else {
			$this->output( "OK, ".$status->value." subscriptions archived\n" );
		}
		$this->output( "[".WpSubscription::getNow()." END]\n\n" );
		
		
		$this->output( "[".WpSubscription::getNow()." Renewing subscriptions...]\n" );	
		$subs = WpSubscription::getAllOutdatedToRenew($when);
		$this->output( count($subs)." subscriptions to process, progress:\nwps_id ; OK/KO ; wps_buyer_user_id ; wps_start_date ; wps_end_date ; wps_tmr_id ; wps_tmr_status\n\n" );
		foreach ($subs as $sub) {
			$status = $sub->renew();			
			$this->output( $sub->get('wps_id').';'
					.( $status->isGood() ? 'OK':'KO('.$status->value.')' ).';'
					.$sub->get('wps_buyer_user_id').';'
					.$sub->get('wps_start_date').';'
					.$sub->get('wps_end_date').';'
					.$sub->get('wps_tmr_id').';'
					.$sub->get('wps_tmr_status')."\n");
		}
		$this->output( "\n[".WpSubscription::getNow()." END]\n\n" );
		
		
		$this->output( "[".WpSubscription::getNow()." Deactivating all remaining outdated subscriptions...]\n" );
		$status = WpSubscription::deactivateAllOutdated($when);
		if ( ! $status->isGood() ) {
			$this->output( "a problem occured: ".$status->$value."\n" );
		} else {
			$this->output( "OK, ".$status->value." subscriptions updated\n" );
		}
		$this->output( "[".WpSubscription::getNow()." END]\n" );
		
	}
}

$maintClass = "UpdateSubscriptions";
require_once( RUN_MAINTENANCE_IF_MAIN );
