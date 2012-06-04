<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class CheckNextRenewals extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( "deadline", "Number of days. Period in which to check subscriptions deadline (min=0).", true, true );
		$this->mDescription = "Check subscriptions renewals coming soon. Send email to user if the selected renewal plan is wrong (quotas, availability, invitations).";

	}

	public function execute() {
		
		$deadline = intval( $this->getOption( 'deadline', 0 ) );
		if ($deadline < 0) {
			$deadline = 0;
		}
				
		$when = WpSubscription::now(0, 0, 0, $deadline);
		
		$this->output( "[".WpSubscription::now().": Searching subscriptions to renew before $when which has not been notified ...]\n" );	
		$subs = WpSubscription::factoryActiveEndSoonToNotify($when);
		
		$this->output( "[".WpSubscription::now().": ".count($subs)." subscription(s) to check...]\n" );
		
		foreach ($subs as $sub) {
			
			$next_plan_id = $sub->getRenewalPlanId();
			$msg = "wps_id[{$sub->getId()}], ";
			
			if ( $next_plan_id == WPP_ID_NORENEW ) {
				
				$msg .= 'do not renew';
				$sub->sendOnNoRenewalSoon();
				$sub->setRenewalPlanNotified();
				
			} else {
				
				$msg .= "renew_wpp_id[$next_plan_id]: ";
				
				// a plan is defined has renewal, check if it's a good choice...
				
				$renewal_date = $sub->getEnd();
				$next_plan = $sub->getRenewalPlan();
				$user_id = $sub->getBuyerUserId();
				
				if ( $next_plan == null ) { // should not occur, but ensure database is not corrupted, correct if needed
					
					// change to the current plan suggested renewal one
					$curr_plan = $sub->getPlan();
					$new_next_plan;
					if ($curr_plan == null) { // should not occur, but ... just in case
						$new_next_plan = WpPlan::newFromId(WP_FALLBACK_PLAN_ID);
					} else {
						$new_next_plan = $curr_plan->getRenewalPlan($renewal_date);
					}
					
					$new_next_plan_id = $new_next_plan->getId();
					
					// update and flag as problem reported, as we will send an email to user
					$sub->setRenewalPlanId($new_next_plan_id, true); 
							
					$msg .= "doesn't exist, changed to = $new_next_plan_id";
					
					$sub->sendOnRenewalSoonWarning('wp-plan-not-available-renewal', $new_next_plan);
					
				} elseif ( ! $next_plan->hasSufficientQuotas( // ensure the next plan has sufficent quotas
						WpWikiplace::countWikiplacesOwnedByUser($user_id),
						WpPage::countPagesOwnedByUser($user_id),
						WpPage::countDiskspaceUsageByUser($user_id))) {
					
					// change to the current plan suggested renewal one
					$curr_plan = $sub->getPlan();
					$new_next_plan = $curr_plan->getRenewalPlan($renewal_date);
					
					$new_next_plan_id = $new_next_plan->getId();
					
					// update and flag as problem reported, as we will send an email to user
					$sub->setRenewalPlanId($new_next_plan_id, true); 
							
					$msg .= "unsufficient quotas, changed to = $new_next_plan_id";
					
					$sub->sendOnRenewalSoonWarning('wp-insufficient-quota', $next_plan);
					
				} elseif ( ! $next_plan->isAvailableForRenewal($renewal_date) ) { // ensure the next plan will still be available
					
					// change to the planned renwal plan suggested renewal one
					$new_next_plan = $next_plan->getRenewalPlan($renewal_date);
					
					$new_next_plan_id = $new_next_plan->getId();
					
					// update and flag as problem reported, as we will send an email to user
					$sub->setRenewalPlanId($new_next_plan_id, true); 
							
					$msg .= "will not be available, changed to = $new_next_plan_id";
					
					$sub->sendOnRenewalSoonWarning('wp-plan-not-available-renewal', $next_plan);
					
				} else {
					
					// it seems to be ok :) 
					$msg .= 'renewal will be ok';
					$sub->sendOnRenewalSoonValid();
					$sub->setRenewalPlanNotified();
					
				}
				
			}
			
			$this->output( "$msg\n" );
			
		}
		$this->output( "[".WpSubscription::now().": END]\n" );
	
	}
}

$maintClass = "CheckNextRenewals";
require_once( RUN_MAINTENANCE_IF_MAIN );
