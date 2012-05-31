<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateSubscriptions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Automaticaly renew ending subscriptions, and archive old ones.";

	}

	public function execute() {
		
		$when = WpSubscription::now();
		$this->output("[".WpSubscription::now().": Criteria end_date = $when]\n");
		
		
		$this->output( "[".WpSubscription::now().": Searching subscriptions to renew...]\n" );	
		$subs = WpSubscription::factoryAllOutdatedToRenew($when);
		$this->output( "[".WpSubscription::now().": ".count($subs)." subscription(s) to renew]\n" );
		foreach ($subs as $sub) {
			
			$result = $sub->renew();
			
			if ( $result === true ) {
				
				// renewal OK
				$tmr_status = $sub->getTmrStatus();
				
				$this->output( "wps_id[{$sub->getId()}], renewal OK, wps_wpp_id[{$sub->getPlanId()}], ".( $sub->isActive() ? 'ACTIVE' : 'UNACTIVE' ) .", tmr_status = {$tmr_status}\n");
				
				// renewal OK ==> tmr_status is OK or PE
				if ( $tmr_status == 'OK') {
					$sub->sendOnPlanRenewalOK();
				} else {
					$sub->sendOnPlanRenewalPE();
				}
				
			} else {
				
				// there was a problem
				$this->output( "wps_id[{$sub->getId()}], ERROR = $result\n" );
				$sub->sendOnPlanRenewalError($result);
				
			}
			
		}		
		
		$this->output( "[".WpSubscription::now().": Deactivating all remaining outdated subscriptions...]\n" );
		if ( ($nb=WpSubscription::deactivateAllOutdated($when)) === false ) {
			$this->output( "ERROR\n" );
		} else {
			$this->output( "OK, $nb subscription(s) deactivated\n" );
		}
		$this->output( "[".WpSubscription::now().": END]\n" );
		
	}
}

$maintClass = "UpdateSubscriptions";
require_once( RUN_MAINTENANCE_IF_MAIN );
