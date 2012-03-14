<?php

class WpSubscription {  
		  
	private		$wps_id,                // int(10) unsigned
				$wps_wpp_id,            // int(10) unsigned
				$wps_buyer_user_id,     // int(10) unsigned
				$wps_tmr_id,            // int(10) unsigned
				$wps_tmr_status,        // varchar(2)
				$wps_date_created,      // datetime
				$wps_start_date,        // datetime
				$wps_next_monthly_tick, // dattime
				$wps_end_date,          // datetime
				$wps_active;            // tinyint(3) unsigned

	private $plan;
	private $attributes_to_update;
		
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
			$transactionId, $transactionStatus, $createdDate,
			$startDate, $nextMonthlyTick, $endDate,
			$active ) {
		
		$this->wps_id                   = $id;			
		$this->wps_wpp_id               = $planId;				
		$this->wps_buyer_user_id        = $buyerUserId;
		$this->wps_tmr_id               = $transactionId;
		$this->wps_tmr_status           = $transactionStatus;
		$this->wps_date_created         = $createdDate;
		$this->wps_start_date           = $startDate;
		$this->wps_next_monthly_tick    = $nextMonthlyTick;
		$this->wps_end_date             = $endDate;
		$this->wps_active               = $active;
		
		$this->attributes_to_update = array();

	}
	
	/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wps_id':
			case 'wps_wpp_id':
			case 'wps_buyer_user_id':
			case 'wps_tmr_id':
				return intval($this->$attribut_name);
				break;
			case 'wps_active':
				return ($this->$attribut_name !== '0');
				break;
			case 'wps_date_created':
			case 'wps_start_date':
			case 'wps_next_monthly_tick':
			case 'wps_end_date':
			case 'wps_tmr_status':
				return $this->$attribut_name;
			case 'plan':
				if ($this->plan === null) {
					$this->fetchPlan();
				}
				return $this->plan;
				break;
		}
		throw new MWException('Unknown attribut '.$attribut_name);
	}
	
	/**
	 * 
	 * @param string $attribut_name
	 * @param mixed $value
	 * @param boolean $update_now By default, update the db now, but if multiple set() calls, the db can be updated only last time by setting 
	 * this argument value to false for the first calls
	 * @return type 
	 */
	public function set($attribut_name, $value, $update_now = true) {
		$db_value = null;
		switch ($attribut_name) {
			case 'wps_active':
				if (!is_bool($value)) { throw new MWException('Value error (boolean needed) for '.$attribut_name); }
				$db_value = ( $value ? 1 : 0 );
				break;
			case 'wps_start_date':
				if (!is_string($value)) { throw new MWException('Value error (string needed) for '.$attribut_name);	}
				break;
			case 'wps_next_monthly_tick':
				if (!is_string($value)) { throw new MWException('Value error (string needed) for '.$attribut_name);	}
				break;				
			case 'wps_end_date':
				if (!is_string($value)) { throw new MWException('Value error (string needed) for '.$attribut_name);	}
				break;
			case 'wps_tmr_status':
				if (!is_string($value)) { throw new MWException('Value error (string needed) for '.$attribut_name);	}
				break;
			default:
				throw new MWException('Cannot change the value of attribut '.$attribut_name);
		}
		
		$this->$attribut_name = $value;
		$this->attributes_to_update[$attribut_name] = ($db_value !== null) ? $db_value : $value; // used by wps_active to convert from boolean to int
		
		if ($update_now) {
			
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin();

			$success = $dbw->update(
				'wp_subscription',
				$this->attributes_to_update,
				array( 'wps_id' => $this->wps_id) );
			
			$dbw->commit();

			if ( !$success ) {	
				throw new MWException('Error while saving Subscription to database.');
			}		
			
			$this->attributes_to_update = array();
		
		}
		
		return $value; // maybe useful, one day ...
	}
	
	private function fetchPlan($databaseRow = null) {
		
		if ($databaseRow !== null) {
			$this->plan = WpPlan::constructFromDatabaseRow($databaseRow);
			
		} else {
			$this->plan = WpPlan::getById($this->wps_wpp_id);
		}
		
		if ($this->plan === null) {
			// there is a big problem... someone has bought something we don't know!
			throw new MWException('Unknown plan');
		} 

	}
	
	/**
	 * Get the WpPlan instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	public static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Subscription from the supplied row (null given)' );
		}

		if ( !isset($row->wps_id) || !isset($row->wps_wpp_id) || !isset($row->wps_buyer_user_id) ||
				!isset($row->wps_tmr_id) || !isset($row->wps_tmr_status) || !isset($row->wps_date_created) ||
	//			!isset($row->wps_start_date) || !isset($row->wps_end_date) ||
				!isset($row->wps_active) ) {
			throw new MWException( 'Cannot construct the Subscription from the supplied row (missing field)' );
		}
			
		return new self ( $row->wps_id, $row->wps_wpp_id, $row->wps_buyer_user_id, $row->wps_tmr_id, $row->wps_tmr_status, $row->wps_date_created, $row->wps_start_date, $row->wps_next_monthly_tick, $row->wps_end_date, $row->wps_active );
		  
	}
	
	
	public static function getByTransactionId($id) {
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch WikiPlace matching the transaction identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_subscription', '*',	array( 'wps_tmr_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);
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
	 * @return WpSubscription or null 
	 */
	public static function getActiveByUserId($user_id) {
			
		if ( ($user_id === null) || !is_numeric($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fetch subscriptions matching the user identifier (invalid identifier)' );
		}	

		$dbr = wfGetDB(DB_SLAVE) ;

		$now =  $dbr->addQuotes( WpPlan::getNow() );
		$conds = $dbr->makeList( array(
			"wps_buyer_user_id"	=> $user_id, 
			"wps_active" => 1, 
		), LIST_AND );

		$result = $dbr->selectRow( 'wp_subscription', '*',	$conds, __METHOD__ );

		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);
	}
	
	
	/**
	 * This function is DB killer, and should only be used in test environment!
	 * @param type $are_you_sure
	 * @param type $really_sure
	 * @return type 
	 */
	public static function getAll($are_you_sure = 'no') {
		
		wfDebugLog( 'wikiplace', 'WpSubscription::getAll WARNING $are_you_sure='.$$are_you_sure);
		
		if ( $are_you_sure != 'I know what i am doing')
			return array(); //good idea :)
		
		$dbr = wfGetDB(DB_SLAVE);
		$results = $dbr->select( 'wp_subscription', '*', '1', __METHOD__ );
		
		$subs = array();
		foreach ( $results as $row ) {
			$subs[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $results );
		
		return $subs;

	}
	
	/**
	 * Can the user make a first subscription? (first sub != renewal)
	 * @param integer $user_id
	 * @param type $db_accessor null = fefault = check on slave
	 * @return boolean True = can have new one, False = not 
	 */
	public static function canMakeAFirstSubscription($user_id, $db_accessor = null) {
				
		if ( ($user_id === null) || !is_numeric($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot check subscriptions matching the user identifier (invalid identifier)' );
		}	
		
		$dbr = ( $db_accessor != null ? $db_accessor : wfGetDB(DB_SLAVE) ) ;

		$now =  $dbr->addQuotes( WpPlan::getNow() );
		$conds = $dbr->makeList( array(
			"wps_buyer_user_id"	=> $user_id, 
			$dbr->makeList( array(
				"wps_active" => 1, 
				$dbr->makeList(array(			
					"wps_active" => 0,  
					"wps_tmr_status != 'KO'" , 
					$dbr->makeList(array(
							"wps_start_date IS NULL", 
							"wps_start_date >= $now", 
					), LIST_OR ),
				), LIST_AND )
			), LIST_OR )
		), LIST_AND );
		
		$results = $dbr->select( 'wp_subscription', '*',	$conds, __METHOD__ );
		
		$return = $dbr->numRows($results) == 0;
		
		$dbr->freeResult( $results );
		
		return $return;

/*		
		$active = 0; // already active susbcription = cannot subscribe anymore
		$future = 0; // pending first subscription OR renewal = cannot subscribe anymore
		foreach ( $results as $row ) {
			
			if ($row->wps_active == 0) {
				$future++;
			} elseif ($row->wps_active == 1) {
				$active++;
			} else {
				throw new MWException( 'There is an error in the databse about the active state.' );
			}
				
		}
		
		if ($future > 1) {
				throw new MWException( 'The user has already many futures subscriptions. She should have only one.' );
		}
		if ($active > 1) {
				throw new MWException( 'The user has already many actives subscriptions. She should have only one.' );
		}
		
		return ( ($active == 0) && ($future == 0) );
*/				
	}
	
	
	/**
	 * Subscribe to a first plan, or upgrade the current plan to a upper one
	 * Currently, can only subscribe to a frst plan
	 * @param User $use The user who buy the plan, and will use it (later, it will be possible 
	 * that one user buy for another one, but for now, a user can only buy for her)
	 * @param WpPlan $plan The plan
	 * @return WpSubscription the newly created subscription, or null if not possible
	 */
	public static function subscribe($user, $plan) {
		
		if ( ($user === null) || !($user instanceof User) ||
				($plan === null) || !($plan instanceof WpPlan) ) {
			throw new MWException( 'Cannot subscribe, invalid argument.' );
		}
		
		$user_id = $user->getId();
		$db_master = $dbw = wfGetDB(DB_MASTER);
		// is it a first subscription(not a plan change ?
		
		// if the user can make a first subscription, this is a first subscription (will be activated as soon as paid)
		if (self::canMakeAFirstSubscription($user_id, $db_master)) {
			
			// this is a first subscriptioon
			
			// ok, let's pay
			$tmr = array(
				# Params related to Message
				'tmr_type'		=> 'subscrip',

				# Paramas related to User
				'tmr_user_id'	=> $user->getId(), 
				'tmr_mail'		=> $user->getEmail(),
				'tmr_ip'		=> IP::sanitizeIP(wfGetIP()), 

				# Params related to Record
				'tmr_amount'	=> - $plan->get('wpp_price'),
				'tmr_currency'	=> $plan->get('wpp_currency'), 
				'tmr_desc'		=> 'wp-plan-name-'.$plan->get('wpp_name'), 
				'tmr_status'	=> 'PE', // PEnding
			);
			wfRunHooks('CreateTransaction', array(&$tmr));
		
			// already paid, or waiting a payment ?
			switch ($tmr['tmr_status']) {

				case 'OK': // already paid by user
					$now =  WpPlan::getNow() ;
					return WpSubscription::create(
							$plan->get('wpp_id'), 
							$user_id,
							$tmr['tmr_id'],
							'OK', // paid
							$now, // start
							WpPlan::calculateTick($now,1), // tick
							WpPlan::calculateTick($now, $plan->get('wpp_period_months')), // end
							true, // active
							$db_master
					);
					break;

				case 'PE': // waiting payment
					return WpSubscription::create(
							$plan->get('wpp_id'),
							$user_id,
							$tmr['tmr_id'],
							'PE', // not paid
							null, // will start when paid
							null, // no tick
							null, // no end
							false, // not active
							$db_master
					);
					break;
			
			}
		
			// if we arrive here, the status of the payment is unknown
			throw new MWException( 'Error while recording the transaction, unknwon status.' );
			
			
		} else {
			
			// this is something else than a first subscriptioon
			
			// for the moment nothing can be done, but later, users will be able to change their current active plan
			// the code will takes place here
			return null; // not an error, but the user cannot subscribe to the plan, that's it
		}
		
	}

	
	                           
	/**
	 *
	 * @param type $planId
	 * @param type $buyerUserId
	 * @param type $transactionId
	 * @param type $transactionStatus
	 * @param type $startDate
	 * @param type $endDate
	 * @param type $active
	 * @param type $db_master The wfGetDB(DB_MASTER) if already have (avoid multiple master db connection)
	 * @return self 
	 */
	private static function create( $planId, $buyerUserId, $transactionId, $transactionStatus, $startDate, $nextTickDate, $endDate, $active, $db_master = null ) {
		
		if ( ($planId === null) || ($buyerUserId === null) || ($transactionId === null) || ($transactionStatus === null) || ($active === null) ) {
			throw new MWException( 'Cannot create Subscription (missing argument)' );
		}
		
		if ( !is_numeric($planId) || !is_numeric($buyerUserId) || !is_numeric($transactionId) || !is_string($transactionStatus) || 
				( ($startDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $startDate ) ) || 
				( ($nextTickDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $nextTickDate ) ) || 
				( ($endDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $endDate ) ) || 
				!is_bool($active) ) {
			throw new MWException( 'Cannot create Subscription (invalid argument)' );
		}
						
		$dbw = ( ($db_master != null) ? $db_master : wfGetDB(DB_MASTER) );
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_subscription_wps_id_seq');
		$now =  WpPlan::getNow() ;
		
        $success = $dbw->insert('wp_subscription', array(
			'wps_id'                => $id,
			'wps_wpp_id'            => $planId,
			'wps_buyer_user_id'     => $buyerUserId,
			'wps_tmr_id'            => $transactionId,
			'wps_tmr_status'        => $transactionStatus,
			'wps_date_created'      => $now,
			'wps_start_date'        => $startDate,
			'wps_next_monthly_tick' => $nextTickDate,
			'wps_end_date'          => $endDate,
			'wps_active'            => $active,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}		
				
		return new self( $id, $planId, $buyerUserId,
			$transactionId, $transactionStatus, $now,
			$startDate, $nextTickDate, $endDate, $active );
		
	}
	
	
}