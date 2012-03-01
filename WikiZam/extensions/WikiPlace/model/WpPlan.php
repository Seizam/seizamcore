<?php

class WpPlan {  

	/*
  `wpp_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpp_name` varbinary(255) NOT NULL COMMENT 'Plan''s name',
  `wpp_period_months` tinyint(3) unsigned NOT NULL COMMENT 'Nb of month of plan''s period',
  `wpp_price` decimal(9,2) unsigned NOT NULL COMMENT 'Price per period',
  `wpp_nb_wikiplace` tinyint(3) unsigned NOT NULL COMMENT 'Nb of WikiPlaces ownable by the subscriber of the plan',
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
		$currency, //varchar(3)
				$nbWikiplace,			//`wpp_nb_wikiplace` tinyint(3) unsigned 
				$nbWikiplacesPages,		//`wpp_nb_wikiplace_pages` smallint(5) unsigned
				$diskspace,				//`wpp_diskspace` bigint(20) unsigned
				$monthlyPageHits,		//`wpp_monthly_page_hits` bigint(20) unsigned
				$monthlyBandwidth;		//`wpp_monthly_bandwidth` bigint(20) unsigned
			
	
	private function __construct( $id, $name ) {

		$this->id			= $id;
		$this->name			= $name;

	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getOwnerUserId() {
		return $this->ownerUserId;
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
		
		// TODO
		if ( !isset($row->wpp_id) || !isset($row->wpp_name) ) {
			throw new MWException( 'Cannot construct the Plan from the supplied row (missing field)' );
		}
			
		return new self ( intval($row->wpp_id) , strval($row->wpp_name) );
		
	}
	
	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getById($id) {
				
/*		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch WikiPlace matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_wikiplace', '*',	array( 'wpw_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);
*/
	}
	

	/**
	 * 
	 * 
	 * @param int $user_id
	 * @return array of WpWikiPlaces ("array()" if no wikiplaces)
	 */
	public static function getAvailableOffersNow() {
		
		$dbr = wfGetDB(DB_SLAVE);
		
		// TODO
		$result = $dbr->select( 'wp_plan', '*',	array( ), __METHOD__ );
		
		$offers = array();
		
		foreach ( $result as $row ) {
			$offers[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $offers;

	}
	
	/**
	 * @param string $name
	 * @return WpPlan the newly created wikiplace or null if an error occured 
	 */
	public static function create($name) {
		
		if ( ($name === null) ) {
			throw new MWException( 'Cannot create Plan (missing argument)' );
		}
		
		if ( !is_string($name) ) {
			throw new MWException( 'Cannot create Plan (invalid argument)' );
		}
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wpp_id');
		
        $success = $dbw->insert('wp_plan', array(
			'wpp_id'			=> $id,
			'wpw_name'			=> $name,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}		
				
		return new self( $id, $name );
			
	}
	
	
}