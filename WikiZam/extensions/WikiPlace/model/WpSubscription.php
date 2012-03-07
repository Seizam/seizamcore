<?php

class WpSubscription {  

	/*
CREATE TABLE IF NOT EXISTS `wp_subscription` (
  `wps_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wps_wpp_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: primary key of the subscribed plan',
  `wps_buyer_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the user who buyed the plan',
  `wps_tmr_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the transaction record of the buy',
  `wps_paid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 = not paid, 1 = paid',
  `wps_date_created` datetime NOT NULL COMMENT 'When the record was created',
  `wps_start_date` datetime NOT NULL COMMENT 'When the subscription starts (can be different from wps_date_created) ',
  `wps_end_date` datetime NOT NULL COMMENT 'When the subscription ends',
  `wps_active` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 = currently not used, 1 = currently in use',
	 */
			  
	private		$id,					//`wps_id` int(10) unsigned
				$planId,				//`wps_wpp_id` int(10) unsigned
				$buyerUserId,			//'wps_buyer_user_id` int(10) unsigned
				$transactionId,			//`wps_tmr_id` int(10) unsigned
				$paid,					//`wps_paid` tinyint(3) unsigned
				$createdDate,			//`wps_date_created` datetime
				$startDate,				//`wps_start_date` datetime
				$endDate,				//`wps_end_date` datetime
				$active;				//`wps_active` tinyint(3) unsigned
			
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
	private function __construct(
			$id, $planId, $buyerUserId,
			$transactionId, $paid, $createdDate,
			$startDate, $endDate, $active ) {
		
		$this->id				= $id;			
		$this->planId			= $planId;				
		$this->buyerUserId		= $buyerUserId;
		$this->transactionId	= $transactionId;
		$this->paid				= $paid;
		$this->createdDate		= $createdDate;
		$this->startDate		= $startDate;
		$this->endDate			= $endDate;
		$this->active			= $active;

	}
	
	/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'id':
			case 'planId':
			case 'buyerUserId':
			case 'transactionId':
				return intval($this->$attribut_name);
				break;
			case 'paid':
			case 'active':
				return ($this->$attribut_name !== '0');
				break;
			case 'createdDate':
			case 'startDate':
			case 'endDate':
				return $this->$attribut_name;
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
			throw new MWException( 'Cannot construct the Subscription from the supplied row (null given)' );
		}

		if ( !isset($row->wps_id) || !isset($row->wps_wpp_id) || !isset($row->wps_buyer_user_id) ||
				!isset($row->wps_tmr_id) || !isset($row->wps_paid) || !isset($row->wps_date_created) ||
	//			!isset($row->wps_start_date) || !isset($row->wps_end_date) ||
				!isset($row->wps_active) ) {
			throw new MWException( 'Cannot construct the Subscription from the supplied row (missing field)' );
		}
			
		return new self ( $row->wps_id, $row->wps_wpp_id, $row->wps_buyer_user_id, $row->wps_tmr_id, $row->wps_paid, $row->wps_date_created, $row->wps_start_date, $row->wps_end_date, $row->wps_active );
		  
	}
	
	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpPlan if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch WikiPlace matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_subscription', '*',	array( 'wps_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	

	/**
	 *
	 * @param int $user_id
	 * @return WpSubscription the currently active subscription buyed by the user
	 */
	public static function getUserFormers($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fectch Subscription matching the user identifier (invalid identifier)' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array( "wps_active" => 0,  "wps_buyer_user_id" => $user_id, "wps_end_date < $now"), LIST_AND );
		$result = $dbr->select( 'wp_subscription', '*',	$conds, __METHOD__ );
		
		$subs = array();
		foreach ( $result as $row ) {
			$subs[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $subs;

	}
	
	/**
	 *
	 * @param int $user_id
	 * @return WpSubscription the currently active subscription buyed by the user, or null if no active subscription
	 */
	public static function getUserActive($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fectch Subscription matching the user identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$conds = $dbr->makeList(array( "wps_active" => 1,  "wps_buyer_user_id" => $user_id), LIST_AND );
		$result = $dbr->selectRow( 'wp_subscription', '*',	$conds, __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	
	public static function getUserFuturs($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fectch Subscription matching the user identifier (invalid identifier)' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array( "wps_active" => 0,  "wps_buyer_user_id" => $user_id, $dbr->makeList(array(
			"wps_start_date IS NULL", "wps_start_date >= $now" ), LIST_OR ) ), LIST_AND );
		$result = $dbr->select( 'wp_subscription', '*',	$conds, __METHOD__ );
		
		$subs = array();
		
		foreach ( $result as $row ) {
			$subs[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $subs;

	}
	
	
	/**
	 *
	 * @param type $user_id
	 * @param type $db_accessor null = fefault = check on slave
	 * @return boolean True = can have new one, False = not 
	 */
	public static function canHaveNewSubscription($user_id, $db_accessor = null) {
		
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot check subscriptions matching the user identifier (invalid identifier)' );
		}	
		
		$dbr = ( $db_accessor != null ? $db_accessor : wfGetDB(DB_SLAVE) ) ;

		$now =  $dbr->addQuotes( wfTimestamp(TS_DB) );
		$conds = $dbr->makeList(array( 
			"wps_active" => 0,  
			"wps_buyer_user_id"	=> $user_id, 
			$dbr->makeList(array(
				"wps_start_date IS NULL", 
				"wps_start_date >= $now" ), 
				LIST_OR ) ),
			LIST_AND );
		$results = $dbr->select( 'wp_subscription', '*',	$conds, __METHOD__ );
		
		return ( $dbr->numRows($results) === 0 );
				
	}
	
	
	public static function create( $planId,	$buyerUserId, $transactionId, $paid, $startDate, $endDate, $active ) {
		
		$_startDate = array();
		$_endDate = array();
		
		if ( ($planId === null) || ($buyerUserId === null) || ($transactionId === null) || ($paid === null) || 
				// ($startDate === null) || ($endDate === null) || // can be null
				($active === null) ) {
			throw new MWException( 'Cannot create Subscription (missing argument)' );
		}
		
		if ( !is_int($planId) || !is_int($buyerUserId) || !is_int($transactionId) || !is_bool($paid) || 
				( ($startDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $startDate, $_startDate ) ) || 
				( ($endDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $endDate, $_endDate ) ) || 
				!is_bool($active) ) {
			throw new MWException( 'Cannot create Subscription (invalid argument)' );
		}
						
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
				
		if (!self::canHaveNewSubscription($buyerUserId, $dbw)) {
			return null;
		}
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_subscription_wps_id_seq');
		$now =  wfTimestamp(TS_DB) ;
		
        $success = $dbw->insert('wp_subscription', array(
			'wps_id'				=> $id,
			'wps_wpp_id'			=> $planId,
			'wps_buyer_user_id'		=> $buyerUserId,
			'wps_tmr_id'			=> $transactionId,
			'wps_paid'				=> $paid,
			'wps_date_created'		=> $now,
			'wps_start_date'		=> $startDate,
			'wps_end_date'			=> $endDate,
			'wps_active'			=> $active,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}		
				
		return new self( $id, $planId, $buyerUserId,
			$transactionId, $paid, $now,
			$startDate, $endDate, $active );
		
	}
	
	/**
	 *
	 * @param type $startDate 
	 */
	static function generateEndDate($startDate) {

		$start = date_create( $startDate, new DateTimeZone( 'GMT' ) );
		$start->modify( 'next month -1 second' );
		return $start->format( 'Y-m-d H:i:s' );
		
	}
	
	
}