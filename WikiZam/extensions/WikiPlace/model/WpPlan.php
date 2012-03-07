<?php

class WpPlan {  

	/*
  `wpp_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpp_name` varbinary(255) NOT NULL COMMENT 'Plan''s name',
  `wpp_period_months` tinyint(3) unsigned NOT NULL COMMENT 'Nb of month of plan''s period',
  `wpp_price` decimal(9,2) unsigned NOT NULL COMMENT 'Price per period',
  `wpp_currency` varbinary(3) NOT NULL COMMENT 'Currency of the price',
  `wpp_available_start` datetime NOT NULL COMMENT 'When begin to be available for subscriptions',
  `wpp_available_end` datetime
  `wpp_nb_wikiplace` tinyint(3) unsigned NOT NULL COMMENT 'Nb of WikiPlaces ownable by the subscriber of the plan'
  `wpp_nb_wikiplace_pages` smallint(5) unsigned NOT NULL COMMENT 'Nb of total WikiPlace''s pages ownable by the subscriber of the plan',
  `wpp_diskspace` bigint(20) unsigned NOT NULL COMMENT 'In bytes, disk space quota',
  `wpp_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Quota, pages''hits per month',
  `wpp_monthly_bandwidth` bigint(20) unsigned NOT NULL COMMENT 'In bytes, quota, downloads bandwidth per month',
  PRIMARY KEY (`wpp_id`)
	 */
			  
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
				$monthlyBandwidth;		//`wpp_monthly_bandwidth` bigint(20) unsigned		/!\ PHP MAX INT = 2 147 483 647 = 2 GO, so handled as string
			
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
			$nbWikiplaces, $nbWikiplacesPages, $diskspace, $monthlyPageHits, $monthlyBandwidth) {
		
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
		}
		throw new MWException('Unknown attribut');
	}
	
	/**
	 * Get the WpPlan instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	private static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Plan from the supplied row (null given)' );
		}

		if ( !isset($row->wpp_id) || !isset($row->wpp_name) || !isset($row->wpp_period_months) || !isset($row->wpp_price) || !isset($row->wpp_currency) || !isset($row->wpp_start_date) || !isset($row->wpp_end_date) || !isset($row->wpp_nb_wikiplaces) || !isset($row->wpp_nb_wikiplace_pages) || !isset($row->wpp_diskspace) || !isset($row->wpp_monthly_page_hits) || !isset($row->wpp_monthly_bandwidth) ) {
			throw new MWException( 'Cannot construct the Plan from the supplied row (missing field)' );
		}
			
		return new self ( $row->wpp_id, $row->wpp_name,$row->wpp_period_months,
				$row->wpp_price, $row->wpp_currency,	
				$row->wpp_start_date, $row->wpp_end_date,
				$row->wpp_nb_wikiplaces, $row->wpp_nb_wikiplace_pages, $row->wpp_diskspace,
				$row->wpp_monthly_page_hits, $row->wpp_monthly_bandwidth );
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
		$conds = $dbr->makeList(array( "wpp_start_date <= $now", "wpp_end_date > $now" ), LIST_AND );
		
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