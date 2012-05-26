<?php

class WpPlan {  
			  
	private		$wpp_id, //`wpp_id` int(10) unsigned
				$wpp_name, //`wpp_name` varbinary(255)
				$wpp_period_months, //`wpp_period_months` tinyint(3) unsigned
				$wpp_price,	//`wpp_price` decimal(9,2) unsigned
				$wpp_currency, //`wpp_currency` varbinary(3)
				$wpp_start_date, //`wpp_start_date` datetime NOT NULL 
				$wpp_end_date, //`wpp_end_date` datetime NOT NULL
				$wpp_nb_wikiplaces,	//`wpp_nb_wikiplaces` tinyint(3) unsigned
				$wpp_nb_wikiplace_pages, //`wpp_nb_wikiplace_pages` smallint(5) unsigned
				$wpp_diskspace,	//`wpp_diskspace` int(10) unsigned             /!\  value in MB !!
				$wpp_monthly_page_hits,	//`wpp_monthly_page_hits` bigint(20) unsigned  /!\  PHP MAX INT = 2 147 483 647, so handled as string
				$wpp_monthly_bandwidth,	//`wpp_monthly_bandwidth` int(10) unsigned     /!\  value in MB !!
				$wpp_renew_wpp_id,	// tinyint(3) unsigned
				$wpp_invitation_only; //`wpp_invitation_only` tinyint(3) unsigned
			
	/**
	 *
	 * @param type $id
	 * @param type $name
	 * @param type $periodMonths
	 * @param type $price
	 * @param type $currency
	 * @param type $startDate
	 * @param type $endDate
	 * @param type $nbWikiplaces
	 * @param type $nbWikiplacesPages
	 * @param type $diskspace
	 * @param type $monthlyPageHits
	 * @param type $monthlyBandwidth 
	 */
	private function __construct( $id, $name, $periodMonths, $price, $currency,	
			$startDate, $endDate,
			$nbWikiplaces, $nbWikiplacesPages, $diskspace, $monthlyPageHits, $monthlyBandwidth,
			$renewPlanId, $invitationOnly) {
		
		$this->wpp_id = intval($id);			
		$this->wpp_name = $name;				
		$this->wpp_period_months = intval($periodMonths);
		$this->wpp_price = $price;
		$this->wpp_currency = $currency;
		$this->wpp_start_date = $startDate;
		$this->wpp_end_date = $endDate;
		$this->wpp_nb_wikiplaces = intval($nbWikiplaces);
		$this->wpp_nb_wikiplace_pages = intval($nbWikiplacesPages);
		$this->wpp_diskspace = $diskspace;
		$this->wpp_monthly_page_hits = $monthlyPageHits;
		$this->wpp_monthly_bandwidth = $monthlyBandwidth;
		$this->wpp_renew_wpp_id = intval($renewPlanId);
		$this->wpp_invitation_only = $invitationOnly != 0;
		
	}

	/**
	 * Returns the wpp_id field value.
	 * @return int 
	 */
	public function getId() {
		return intval($this->wpp_id);
	} 
	
	/**
	 * Returns the name as i18n key 
	 * @return string 
	 */
	public function getName() {
		return $this->wpp_name;
	}
	
	/**
	 *
	 * @return array array ( 'amount' => wpp_price, 'currency' => wpp_currency )
	 */
	public function getPrice() {
		return array ( 
			'amount' => $this->wpp_price,
			'currency' => $this->wpp_currency );
	}
	
	/**
	 * The period of the plan, in number of months.
	 * @return int Nb of months
	 */
	public function getPeriod() {
		return $this->wpp_period_months;
	}
	
	/**
	 *
	 * @return int 
	 */
	public function getNbWikiplaces() {
		return $this->wpp_nb_wikiplaces;
	}

	/**
	 *
	 * @return int 
	 */
	public function getNbWikiplacePages() {
		return $this->wpp_nb_wikiplace_pages;
	}
	
	/**
	 *
	 * @return int 
	 */
	public function getDiskspace() {
		return $this->wpp_diskspace;
	}
	
	/**
	 * Returns the renewal suggested plan. Please note that the returned plan
	 * can be different of the renewal plan id stored in wpp_renew_wpp_id field.
	 * This function ensure that the returned plan is renewable. In the worst 
	 * case, it returns an instance of WP_FALLBACK_PLAN_ID plan.
	 * @param string $when Optional, to ensure that the returned plan is available at this date  
	 * @return WpPlan 
	 */
	public function renewalPlan($when = null) {
		
		if ( $this->isRenewable($when) ) {
			return $this;
		}

		$checked = array ();  // avoid infinite loop
		$next_plan_id = $this->wpp_renew_wpp_id;
		$next_plan = null;
		
		while ( $next_plan_id != null && !in_array( $next_plan_id, $checked) ) {
			$checked[] = $next_plan_id;
			
			wfDebugLog( 'wikiplaces' , 'in loop with '.$next_plan_id);
			
			$next_plan = WpPlan::newFromId($next_plan_id);
			if ( $next_plan == null ) {
				$next_plan_id = null; // it's over
			}
			
			if ( $next_plan->isRenewable($when) ) {
				$this->wpp_renew_wpp_id = $next_plan_id;
				return $next_plan;
				
			} else {
				$next_plan_id = $next_plan->wpp_renew_wpp_id; // continue search
			}
		}
		
		if ( $next_plan == null ) {
			$this->wpp_renew_wpp_id = WP_FALLBACK_PLAN_ID;
			$next_plan = WpPlan::newFromId(WP_FALLBACK_PLAN_ID);
		}

		return $next_plan;
		
	}
	

	
	/**
	 * Returns true if this plan is renewable. If it is not, the renewal
	 * suggested plan ID can be known using <b>getRenewalSuggestedPlanId()</b>
	 * @param string $when Optional, to ensure that the plan is available at this date 
	 * @return boolean 
	 */
	public function isRenewable($when = null) {
		
		if ( ( $when != null ) && ( ($when < $this->wpp_start_date) || ($when > $this->wpp_end_date) ) ) {
			return false;
		}
		return ( $this->wpp_renew_wpp_id == 0 );
		
	}
	
	
	/**
	 * Checks that this plan is available for this user, and give sufficient quotas.
	 * Can be used for both first subscription and renewal
	 * <b>INVITATION SYSTEM TO BE IMPLEMENTED HERE</b>
	 * Check that a user can subscribe to a specific plan.
	 * Invitation system can take place here, if we use e-mail address as invitation identifier
	 * @todo: implement invitation system here
	 * @param User $user Optional, will be used later for invations
	 * @return boolean 
	 */
	public function canBeTakenAsFirst($user) {
		
		$now = WpSubscription::now();
		
		return ( ( $this->wpp_start_date <= $now )
				&& ( $this->wpp_end_date > $now )
				&& ( !$this->wpp_invitation_only ) );

	}
	
	/**
	 * Checks that this plan can be taken as renewal by this user, and give sufficient quotas.
	 * <b>INVITATION SYSTEM TO BE IMPLEMENTED HERE</b>
	 * Invitation system can take place here, if we use e-mail address as invitation identifier
	 * @todo: implement invitation system here
	 * @param User $user Optional, will be used later for invations
	 * @param int $nb_wikiplaces Optional, to ensure quotas are respected, default = 0
	 * @param int $nb_wikiplace_pages Optional, to ensure quotas are respected, default = 0
	 * @param int $diskspace in MB Optional, to ensure quotas are respected, default = 0
	 * @param string $when Optional, to ensure that the plan is available at this date, default = now
	 * @return boolean 
	 */
	public function canBeTakenAsRenewal($user, $nb_wikiplaces = 0, $nb_wikiplace_pages = 0, $diskspace = 0, $when = null) {
		
		if ( $when == null ) {
			$when = WpSubscription::now();
		}
		
		return ( $this->wpp_renew_wpp_id == 0 
				&&( $this->wpp_start_date <= $when )
				&& ( $this->wpp_end_date > $when )
				&& ( $this->wpp_nb_wikiplaces >= $nb_wikiplaces )
				&& ( $this->wpp_nb_wikiplace_pages >= $nb_wikiplace_pages )
				&& ($this-> wpp_diskspace >= $diskspace ) 
				&& ( !$this->wpp_invitation_only ) );

	}
	
	
	/**
	 * Get the WpPlan instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	public static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Plan from the supplied row (null given)' );
		}

		if ( !isset($row->wpp_id) || !isset($row->wpp_name) || !isset($row->wpp_period_months) || 
				!isset($row->wpp_price) || !isset($row->wpp_currency) || !isset($row->wpp_start_date) || 
				!isset($row->wpp_end_date) || !isset($row->wpp_nb_wikiplaces) || !isset($row->wpp_nb_wikiplace_pages) || 
				!isset($row->wpp_diskspace) || !isset($row->wpp_monthly_page_hits) || !isset($row->wpp_monthly_bandwidth) ||
				!isset($row->wpp_renew_wpp_id) || !isset($row->wpp_invitation_only) ) {
			throw new MWException( 'Cannot construct the Plan from the supplied row (missing field)' );
		}
				  
		return new self ( $row->wpp_id, $row->wpp_name,$row->wpp_period_months,
				$row->wpp_price, $row->wpp_currency,	
				$row->wpp_start_date, $row->wpp_end_date,
				$row->wpp_nb_wikiplaces, $row->wpp_nb_wikiplace_pages, $row->wpp_diskspace,
				$row->wpp_monthly_page_hits, $row->wpp_monthly_bandwidth,
				$row->wpp_renew_wpp_id, $row->wpp_invitation_only);
	}
	
	/**
	 * Restore from DB, using id
	 * @param int $id wpp_id field value
	 * @return WpPlan The WpPlan if found, or null if not
	 */
	public static function newFromId($id) {
				
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch Wikiplace matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_plan', '*',	array( 'wpp_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	



	
	
		/**
	 * Returns available offers, with at least theses quotas (by default: no minimum requirement)
	 * @param int $nb_wikiplaces
	 * @param int $nb_wikiplace_pages
	 * @param int $diskspace in MB
	 * @return array array of WpPlans 
	 * @todo also add plan available using an invitation
	 */
	public static function factoryAvailableForFirstSubscription( $nb_wikiplaces = 0, $nb_wikiplace_pages = 0, $diskspace = 0) {
		
		$dbr = wfGetDB(DB_SLAVE);
		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array( 
			"wpp_start_date <= $now",
			"wpp_end_date > $now",
			"wpp_invitation_only" => 0,
			"wpp_nb_wikiplaces >= $nb_wikiplaces",
			"wpp_nb_wikiplace_pages >= $nb_wikiplace_pages",
			"wpp_diskspace >= $diskspace"
		), LIST_AND );
		
		$result = $dbr->select( 'wp_plan', '*',	$conds, __METHOD__ );
		$offers = array();
		foreach ( $result as $row ) {
			$offers[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $offers;

	}
	
	/**
	 * Returns offers, that can be talen as renewal, and will be still accessible at $when, with at least theses quotas
	 * @param int $nb_wikiplaces Optional, default = 0
	 * @param int $nb_wikiplace_pages  Optional, default = 0
	 * @param int $diskspace in MB  Optional, default = 0
	 * @param string $when Optional, to ensure that the plan is available at this date, default = now
	 * @return array array of WpPlans 
	 * @todo also add plan available using an invitation
	 */
	public static function factoryAvailableForRenewal( $nb_wikiplaces = 0, $nb_wikiplace_pages = 0, $diskspace = 0, $when = null) {
		
		if ( $when == null ) {
			$when = WpSubscription::now();
		}
		$dbr = wfGetDB(DB_SLAVE);
		$when = $dbr->addQuotes($when);
		$conds = $dbr->makeList(array( 
			"wpp_renew_wpp_id" => 0,
			"wpp_start_date <= $when",
			"wpp_end_date > $when",
			"wpp_invitation_only" => 0,
			"wpp_nb_wikiplaces >= $nb_wikiplaces",
			"wpp_nb_wikiplace_pages >= $nb_wikiplace_pages",
			"wpp_diskspace >= $diskspace"
		), LIST_AND );
		
		$result = $dbr->select( 'wp_plan', '*',	$conds, __METHOD__ );
		$offers = array();
		foreach ( $result as $row ) {
			$offers[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $offers;

	}
	
	
	
}