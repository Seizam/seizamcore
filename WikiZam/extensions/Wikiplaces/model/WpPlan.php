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
		
		$this->wpp_id = $id;			
		$this->wpp_name = $name;				
		$this->wpp_period_months = $periodMonths;
		$this->wpp_price = $price;
		$this->wpp_currency = $currency;
		$this->wpp_start_date = $startDate;
		$this->wpp_end_date = $endDate;
		$this->wpp_nb_wikiplaces = $nbWikiplaces;
		$this->wpp_nb_wikiplace_pages = $nbWikiplacesPages;
		$this->wpp_diskspace = $diskspace;
		$this->wpp_monthly_page_hits = $monthlyPageHits;
		$this->wpp_monthly_bandwidth = $monthlyBandwidth;
		$this->wpp_renew_wpp_id = $renewPlanId;
		$this->wpp_invitation_only = $invitationOnly;
		
	}
	
	/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wpp_id':
			case 'wpp_period_months':
			case 'wpp_nb_wikiplaces':
			case 'wpp_nb_wikiplace_pages':
			case 'wpp_renew_wpp_id':
				return intval($this->$attribut_name);
				break;
			case 'wpp_name':
			case 'wpp_price':
			case 'wpp_currency':
			case 'wpp_start_date':
			case 'wpp_end_date':
			case 'wpp_diskspace':
			case 'wpp_monthly_page_hits':
			case 'wpp_monthly_bandwidth':
				return strval($this->$attribut_name);
				break;

			case 'wpp_invitation_only':
				return $this->$attribut_name != 0;
				break;
		}
		throw new MWException('Unknown attribut');
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
	 * @param int $id 
	 * @return WpPlan if found, or null if not
	 */
	public static function getById($id) {
				
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
	 */
	public static function getAvailableOffersNow( $nb_wikiplaces = 0, $nb_wikiplace_pages = 0, $diskspace = 0) {
		
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
	 * Checks that this plan is available for this user, and give sufficient quotas
	 * <b>INVITATION SYSTEM TO BE IMPLEMENTED HERE</b>
	 * Check that a user can subscribe to a specific plan.
	 * Invitation system can take place here, if we use e-mail address as invitation identifier
	 * @todo: implement invitation system here
	 * @param int $plan_id wpp_id (a plan identifier)
	 * @param User $user
	 * @param int $nb_wikiplaces
	 * @param int $nb_wikiplace_pages
	 * @param int $diskspace in MB
	 * @return boolean 
	 */
	public static function canBeSubscribed($plan_id, $user, $nb_wikiplaces = 0, $nb_wikiplace_pages = 0, $diskspace = 0) {
		
		$dbr = wfGetDB(DB_SLAVE);
		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array(
			"wpp_id" => $plan_id,
			"wpp_start_date <= $now",
			"wpp_end_date > $now",
			"wpp_nb_wikiplaces >= $nb_wikiplaces",
			"wpp_nb_wikiplace_pages >= $nb_wikiplace_pages",
			"wpp_diskspace >= $diskspace"	
		), LIST_AND);
		
		$result = $dbr->selectRow( 'wp_plan', '*',	$conds, __METHOD__ );
		return ( $result !== false );

	}
	
	
}