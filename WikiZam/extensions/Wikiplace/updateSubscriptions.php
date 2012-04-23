<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateSubscriptions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Automaticaly renew ending subscriptions, and archive old ones.";

	}

	public function execute() {
		
		$when = WpSubscription::getNow();
		$this->output("Considering 'now' = $when\n");
		
		
		
		$this->output( "[Archive all subscriptions to renew START at ".WpSubscription::getNow()."]\n" );
		
		$nb = WpSubscription::archiveAllToRenew($when);
		$this->output( "$nb subscriptions archived\n" );
		
		$this->output( "[END at ".WpSubscription::getNow()."]\n" );
		
		
		
		$this->output( "[Renewing subscriptions START at ".WpSubscription::getNow()."]\n" );	
		
		$subs = WpSubscription::getAllToRenew($when);
		
		$this->output( count($subs)." subscriptions to process, progress:\nwps_id ; OK/KO ; wps_buyer_user_id ; wps_tmr_id ; wps_tmr_status\n" );		
		foreach ($subs as $sub) {
			$status = $sub->renew($when);			
			$this->output( $sub->get('wps_id').';'
					.( $status->isGood() ? 'OK':'KO('.$status->value.')' ).';'
					.$sub->get('wps_buyer_user_id').';'
					.$sub->get('wps_tmr_id').';'
					.$sub->get('wps_tmr_status')."\n");
		}
		
		$this->output( "[END at ".WpSubscription::getNow()."]\n" );
		
		
		
		$this->output( "[Deactivate all outdated subscriptions START at ".WpSubscription::getNow()."]\n" );
		$nb = WpSubscription::deactivateAllOutdated($when);
		$this->output( "$nb subscriptions updated" );
		
		$this->output( "[END at ".WpSubscription::getNow()."]\n" );
	}
}

$maintClass = "UpdateSubscriptions";
require_once( RUN_MAINTENANCE_IF_MAIN );
