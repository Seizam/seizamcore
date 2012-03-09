<?php

class WpPlan {  
			  
	private		$id,					//`wpw_id` int(10) unsigned
				$name,					//`wpp_name` varbinary(255)
				$periodMonths,			//`wpp_period_months` tinyint(3) unsigned
				$price,					//`wpp_price` decimal(9,2) unsigned
				$currency,				//`wpp_currency` varbinary(3)
				$startDate,				//`wpp_start_date` datetime NOT NULL 
				$endDate,				//`wpp_end_date` datetime NOT NULL
				$nbWikiplaces,			//`wpp_nb_wikiplaces` tinyint(3) unsigned
				$nbWikiplacesPages,		//`wpp_nb_wikiplace_pages` smallint(5) unsigned
				$diskspace,				//`wpp_diskspace` bigint(20) unsigned				/!\ PHP MAX INT = 2 147 483 647 = 2 GO, so handled as string
				$monthlyPageHits,		//`wpp_monthly_page_hits` bigint(20) unsigned		/!\ PHP MAX INT = 2 147 483 647, so handled as string
				$monthlyBandwidth,		//`wpp_monthly_bandwidth` bigint(20) unsigned		/!\ PHP MAX INT = 2 147 483 647 = 2 GO, so handled as string
				$renewable,				//`wpp_renewable` tinyint(3)
				$invitationOnly;		//`wpp_invitation_only` tinyint(3) unsigned
			
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
			$renewable, $invitationOnly) {
		
		$this->id					= $id;			
		$this->name					= $name;				
		$this->periodMonths			= $periodMonths;
		$this->price				= $price;
		$this->currency				= $currency;
		$this->startDate			= $startDate;
		$this->endDate				= $endDate;
		$this->nbWikiplaces			= $nbWikiplaces;
		$this->nbWikiplacesPages	= $nbWikiplacesPages;
		$this->diskspace			= $diskspace;
		$this->monthlyPageHits		= $monthlyPageHits;
		$this->monthlyBandwidth		= $monthlyBandwidth;
		$this->renewable			= $renewable;
		$this->invitationOnly		= $invitationOnly;
	}
	
	/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'id':
			case 'periodMonths':
			case 'nbWikiplaces':
			case 'nbWikiplacesPages':
				return intval($this->$attribut_name);
				break;
			case 'name':
			case 'price':
			case 'currency':
			case 'startDate':
			case 'endDate':
			case 'diskspace':
			case 'monthlyPageHits':
			case 'monthlyBandwidth':
				return strval($this->$attribut_name);
				break;
			case 'renewable':
			case 'invitationOnly':
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
				!isset($row->wpp_renewable) || !isset($row->wpp_invitation_only) ) {
			throw new MWException( 'Cannot construct the Plan from the supplied row (missing field)' );
		}
				  
		return new self ( $row->wpp_id, $row->wpp_name,$row->wpp_period_months,
				$row->wpp_price, $row->wpp_currency,	
				$row->wpp_start_date, $row->wpp_end_date,
				$row->wpp_nb_wikiplaces, $row->wpp_nb_wikiplace_pages, $row->wpp_diskspace,
				$row->wpp_monthly_page_hits, $row->wpp_monthly_bandwidth,
				$row->wpp_renewable, $row->wpp_invitation_only);
	}
	
	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpPlan if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch WikiPlace matching the identifier (invalid identifier)' );
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
	 *
	 * @return array array of WpPlans
	 */
	public static function getAvailableOffersNow() {
		
		$dbr = wfGetDB(DB_SLAVE);
		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array( 
			"wpp_start_date <= $now",
			"wpp_end_date > $now",
			"wpp_invitation_only" => 0,
		), LIST_AND );
		
		$result = $dbr->select( 'wp_plan', '*',	$conds, __METHOD__ );
		$offers = array();
		foreach ( $result as $row ) {
			$offers[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $offers;

	}
	

	public static function validateSubscribePlanId($id, $allData) {
		
		if (!preg_match('/^[0-9]{1,10}$/',$id) ) {
			return wfMessage( 'wp-plan-subscribe-invalid-plan' )->text();
		}
		
		// var_export($allData);
		// array ('Plan' =&gt; '1')
		
		$dbr = wfGetDB(DB_SLAVE);
		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array( "wpp_id" => $id, "wpp_start_date <= $now", "wpp_end_date > $now" ), LIST_AND );
		
		$result = $dbr->selectRow( 'wp_plan', '*',	$conds, __METHOD__ );
		
		if ( $result === false ) {
			return wfMessage( 'wp-plan-subscribe-invalid-plan' )->text();
		}
		
        return true ;
		
	}
	
	
}