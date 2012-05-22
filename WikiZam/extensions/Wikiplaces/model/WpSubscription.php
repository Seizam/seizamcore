<?php

class WpSubscription {  
		  
	private		$wps_id, // int(10) unsigned
				$wps_wpp_id, // int(10) unsigned
				$wps_buyer_user_id, // int(10) unsigned
				$wps_tmr_id, // int(10) unsigned
				$wps_tmr_status, // varchar(2)
				$wps_start_date, // datetime
				$wps_end_date, // datetime
				$wps_active, // tinyint(3) unsigned
				$wps_renew_wpp_id;// int(10) unsigned
			
	private $plan;
	private $next_plan;
	private $buyer;
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
			$transactionId, $transactionStatus,
			$startDate, $endDate,
			$active, $renewPlanId ) {
		
		$this->wps_id = $id;			
		$this->wps_wpp_id = $planId;				
		$this->wps_buyer_user_id = $buyerUserId;
		$this->wps_tmr_id = $transactionId;
		$this->wps_tmr_status = $transactionStatus;
		$this->wps_start_date = $startDate;
		$this->wps_end_date = $endDate;
		$this->wps_active = $active;
		$this->wps_renew_wpp_id = $renewPlanId;
		
		
		$this->attributes_to_update = array();

	}
	
	
	/**
	 * 
	 * @param string $attribut_name <ul><li>wps_id</li>
	 * <li>wps_wpp_id</li>
	 * <li>wps_buyer_user_id</li>
	 * <li>wps_tmr_id</li>
	 * <li>wps_renew_wpp_id</li>
	 * <li>wps_active</li>
	 * <li>wps_start_date</li>
	 * <li>wps_end_date</li>
	 * <li>wps_tmr_status</li>
	 * <li>plan</li>
	 * <li>next_plan</li>
	 * <li>buyer</li>
	 * </ul>
	 * @return mixed 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wps_id':
			case 'wps_wpp_id':
			case 'wps_buyer_user_id':
			case 'wps_tmr_id':
			case 'wps_renew_wpp_id':
				return intval($this->$attribut_name);
				break;
			case 'wps_active':
				return ($this->$attribut_name !== '0');
				break;
			case 'wps_start_date':
			case 'wps_end_date':
			case 'wps_tmr_status':
				return $this->$attribut_name;
			case 'plan':
				if ($this->plan === null) {
					$this->fetchPlan();
				}
				return $this->plan;
				break;
			case 'next_plan':
				if ($this->wps_renew_wpp_id == 0) {
					return null;
				}
				if ($this->next_plan === null) {
					$this->fetchNextPlan();
				}
				return $this->next_plan;
				break;
			case 'buyer':
				if ($this->buyer === null) {
					$this->fetchBuyer();
				}
				return $this->buyer;
				break;
		}
		throw new MWException('Unknown attribut '.$attribut_name);
	}
	
	/**
	 * 
	 * @param string $attribut_name 
	 * <ul>
	 * <li>wps_renew_wpp_id</li>
	 * <li>wps_tmr_id</li>
	 * <li>wps_wpp_id</li>
	 * <li>wps_active</li>
	 * <li>wps_start_date</li>
	 * <li>wps_end_date</li>
	 * <li>wps_tmr_status</li>
	 * </ul>
	 * @param mixed $value
	 * @param boolean $update_now By default, update the db now, but if multiple set() calls, the db can be updated only last time by setting 
	 * this argument value to false for the first calls
	 */
	public function set($attribut_name, $value, $update_now = true) {
		$db_value = null;
		switch ($attribut_name) {
			case 'wps_renew_wpp_id':
				if ( !is_numeric($value) || ($value<0) )  { throw new MWException('Value error (int >= 0 needed) for '.$attribut_name); }
				$db_value = intval($value);
				$this->next_plan = null;
				break;
			case 'wps_wpp_id':
				if ( !is_numeric($value) || ($value<0) )  { throw new MWException('Value error (int >= 0 needed) for '.$attribut_name); }
				$db_value = intval($value);
				$this->plan = null;
				break;
			case 'wps_tmr_id':
				if ( !is_numeric($value) || ($value<0) )  { throw new MWException('Value error (int >= 0 needed) for '.$attribut_name); }
				$db_value = intval($value);
				break;
			case 'wps_active':
				if (!is_bool($value)) { throw new MWException('Value error (boolean needed) for '.$attribut_name); }
				$db_value = ( $value ? 1 : 0 );
				break;
			case 'wps_start_date':		
			case 'wps_end_date':
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
				throw new MWException('Error while updating Subscription to database.');
			}		
			
			$this->attributes_to_update = array();
		
		}

	}
	
	

	/**
	 * Load the plan (wps_wpp_id)
	 * @param ResultWrapper $databaseRow Use this database row if given, or perform a new database request
	 */
	private function fetchPlan($databaseRow = null) {
		
		if ($databaseRow !== null) {
			
			$this->plan = WpPlan::constructFromDatabaseRow($databaseRow);
			
			if ($this->plan->get('wpp_id') != $this->get('wps_wpp_id')) {
				throw new MWException('The given plan is not the subscribed one.');
			}
			
		} else {
			$this->plan = WpPlan::getById($this->wps_wpp_id);
		}
		
		if ($this->plan === null) {
			throw new MWException('Unknown plan.');
		} 

	}
	
	/**
	 * Load the buyer user
	 * @param User $user If null, consctruct a new User object
	 */
	private function fetchBuyer($user = null) {
		
		if ( ! $user instanceof User ) {
			$user = User::newFromId($this->wps_buyer_user_id);
			if ( ! $user->loadFromId() ) {
				throw new MWException('Unknown buyer User, id='.$this->wps_buyer_user_id);
			}
		}
		
		if ( $user->getId() != $this->wps_buyer_user_id ) {
			throw new MWException('Invalid buyer User given in arg (expected='.$this->wps_buyer_user_id.' passed='.$user->getId().')');
		}
		
		$this->buyer = $user;

	}

	/**
	 * Fetch the next plan, as defined by wps_renew_wpp_id field. This function can be called even
	 * if no next plan is defined (in this case, nothing is fetch)
	 * @param ResultWrapper $databaseRow Use this database row if given, or perform a new database request
	 */
	private function fetchNextPlan($databaseRow = null) {
		
		if ($this->wps_renew_wpp_id == 0) {
			// no next plan defined, so nothing to fetch
			return;
		}
		
		if ($databaseRow !== null) {
			
			$this->next_plan = WpPlan::constructFromDatabaseRow($databaseRow);
			
			if ($this->next_plan->get('wpp_id') != $this->get('wps_renew_wpp_id')) {
				throw new MWException('The given plan is not the next one.');
			}
			
		} else {
			$this->next_plan = WpPlan::getById($this->wps_renew_wpp_id);
		}
		
		if ($this->next_plan === null) {
			throw new MWException('Unknown next plan.');
		} 

	}
	
	/**
	 * Contruct a new instance from a SQL row
	 * @param ResultWrapper $row
	 * @return WpSubscription 
	 */
	public static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Subscription from the supplied row (null given).' );
		}

		// wps_start_date and wps_end_date can be null, but nothing else
		if ( !isset($row->wps_id) || !isset($row->wps_wpp_id) || !isset($row->wps_buyer_user_id) ||
				!isset($row->wps_tmr_id) || !isset($row->wps_tmr_status) ||
				!isset($row->wps_active) || !isset($row->wps_renew_wpp_id) ) {
			throw new MWException( 'Cannot construct the Subscription from the supplied row (missing field).' );
		}
			
		return new self ( $row->wps_id, $row->wps_wpp_id, $row->wps_buyer_user_id, $row->wps_tmr_id, $row->wps_tmr_status, $row->wps_start_date, $row->wps_end_date, $row->wps_active, $row->wps_renew_wpp_id );
		  
	}
	
	/**
	 * Get the subscription associated to the given TMR_id
	 * @param int $id TMR_id
	 * @return WpSubscription The requested WpSubscription, or null if none associated to the TMR_id 
	 */
	public static function getByTransactionId($id) {
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot search subscription, invalid transaction identifier.' );
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
	 * Get the subscription having the given id
	 * @param int $id 
	 * @return WpSubscription if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot search subscription, invalid identifier.' );
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
	 * Get the active subscription of a user
	 * @param int $user_id
	 * @return WpSubscription The user active subscription or null if she has no active one 
	 */
	public static function getActiveByUserId($user_id) {
			
		if ( ($user_id === null) || !is_numeric($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot search subscription, invalid user identifier.' );
		}	

		$dbr = wfGetDB(DB_SLAVE) ;

		$now =  $dbr->addQuotes( self::getNow() );
		$conds = $dbr->makeList( array(
			"wps_buyer_user_id"	=> $user_id, 
			"wps_active" => 1, 
		), LIST_AND );

		$result = $dbr->selectRow( 'wp_subscription', '*',	$conds, __METHOD__ );

		if ( $result === false ) {
			return null;
		}
		
		return self::constructFromDatabaseRow($result);
	}
	
	/**
	 * Get the last subscription of a user, which can be unactive.
	 * @param int $user_id
	 * @return WpSubscription The user active subscription or null if she has no active one 
	 */
	public static function getLastSubscription($user_id) {
			
		if ( ($user_id === null) || !is_numeric($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot search subscription, invalid user identifier.' );
		}	

		$dbr = wfGetDB(DB_SLAVE) ;

		$now =  $dbr->addQuotes( self::getNow() );
		$conds = $dbr->makeList( array(
			"wps_buyer_user_id"	=> $user_id,  
		), LIST_AND );

		$result = $dbr->selectRow( 'wp_subscription', '*',	$conds, __METHOD__, 
				array( 'ORDER BY' => ' wps_active DESC, wps_start_date DESC' )  );

		if ( $result === false ) {
			return null;
		}
		
		return self::constructFromDatabaseRow($result);
	}
	
	
	
	/**
	 * <b>WARNING:</b>This function is DB killer, and should only be used in test environment!
	 * @param boolean $are_you_sure If you really want to get all, set to 'yes' 
	 * @return array Array of WpSubscription 
	 */
	public static function getAll($are_you_sure = 'no') {
		
		wfDebugLog( 'wikiplaces', 'WpSubscription::getAll WARNING $are_you_sure='.$are_you_sure);
		
		if ( $are_you_sure != 'yes') {
			return array();
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$results = $dbr->select( 'wp_subscription', '*', '1', __METHOD__ );
		
		$subs = array();
		foreach ( $results as $row ) {
			$subs[] = self::constructFromDatabaseRow($row);
		}
		
		return $subs;

	}
	

	
	/**
	 * Can the user take a subscription? (not renewal or a plan change, but a simple subscription)
	 * Can be a first subscription, or a new subscription with an unactive ended one
	 * @param User $user
	 * @return boolean/string true = can subscribe , string = reason (i18n message key) why cannot subscribe:
	 * <ul>
	 * <li>wp-subscribe-loggedout</li>User need to be logged in to subscribe
	 * <li>wp-subscribe-email</li>User has not yet confirmed her email address
	 * <li>wp-subscribe-already</li>User has already an active or a "payment pending" subscription
	 * </ul>
	 */
	public static function canSubscribe($user) {
				
		if ( ! $user instanceof User ) {
			throw new MWException( 'Invalid user argument.' );
		}	
		
		if ( ! $user->isLoggedIn() ) {
			return 'wp-subscribe-loggedout';
		}
		
		if ( ! $user->isEmailConfirmed() ) {
			return 'wp-subscribe-email';
		}
		
		$dbr = wfGetDB(DB_MASTER) ;

		$now =  $dbr->addQuotes( self::getNow() );
		$conds = $dbr->makeList( array(
			"wps_buyer_user_id"	=> $user->getId(), 
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
		
		if ( $dbr->numRows($results) != 0 ) {
			return 'wp-subscribe-already';
		}
		
		return true;
				
	}
	

	
	
	
	
	/**
	 * Renew the subscription. This method should be called by a cron, for ONLY each getAllOutdatedToRenew()<br/>
	 * WARNING: <ul>
	 * <li>this function assumes that the current subscription <b>can</b> AND <b>need</b> to be renewed</li>
	 * <li><b>only use it to renew the subscription when it ends normally</b> (this function doesn't re-credit
	 * user account balance and it doesn't change the wikiplaces 'monthly tick')</li>
	 * <li>it archives the current subcription if renewal can be processed</b></li>
	 * <li>this function alter the current db record (start_date, ...) but the primary key stay untouched</li>
	 * <li>it creates a new TMR</li>
	 * </ul>
	 * @return boolean/string true if ok, i18n message key string if an error occured
	 * <ul>
	 * <li>'wp-internal-error' if cannot find buyer user of the current subscription</li>
	 * <li>'wp-no-next-plan' if no next plan specified</li>
	 * <li>'wp-payment-error' if new tmr_status is neither OK or PE</li>
	 * </ul>
	 */
	public function renew() {
		
		// ensure we know the user
		$user_id = $this->get('wps_buyer_user_id');
		$user = User::newFromId($user_id);
		if ( ! $user->loadFromId() ) { 
			return 'wp-internal-error';
		}
		$this->fetchBuyer($user);
		$user_email = $user->getEmail();
		
		
		// ensure we know the next plan
		$next_plan = $this->get('next_plan');
		if ($next_plan == null) { 
			$this->set('wps_renew_wpp_id', 0); //will not try anymore to renew
			return 'wp-no-next-plan';
		}
		
		
		// ensure next plan as sufficient quotas
		$nb_wp = WpWikiplace::countWikiplacesOwnedByUser($user_id);
		$nb_pages = WpPage::countPagesOwnedByUser($user_id);
		$diskspace = WpPage::getDiskspaceUsageByUser($user_id);
		if ( ( $next_plan->get('wpp_nb_wikiplaces') < $nb_wp ) || 
				( $next_plan->get('wpp_nb_wikiplace_pages') < $nb_pages ) ||
				( $next_plan->get('wpp_diskspace') < $diskspace ) ) {
			
			/** @todo: implement what to do if quotas are not sufficient
			$this->set('wps_renew_wpp_id', 0); //will not try anymore to renew
			return 'wp-insufficient-next-plan-quotas';
			 */
			
		}
		
		
		// payment
		$tmr = self::createTMR($user_id, $user_email, $next_plan);

		if ( ($tmr['tmr_status']!='OK') && ($tmr['tmr_status']!='PE') ) { // not ( OK or PE ) so it cannot be renewed 			
			
			$this->set('wps_renew_wpp_id', 0); //will not try anymore to renew			
			return 'wp-payment-error';
			
		}
		
				
		// everything is ok, let's renew!
		$this->archive();
		
		$start =  self::calculateStartDateFromPreviousEnd( $this->wps_end_date );
		$end = self::calculateEndDateFromStart( $start, $next_plan->get('wpp_period_months') );

		$this->set('wps_wpp_id', $next_plan->get('wpp_id'), false);
		$this->plan = $next_plan;
		$this->set('wps_tmr_id', $tmr['tmr_id'], false);
		$this->set('wps_tmr_status', $tmr['tmr_status'], false);
		$this->set('wps_start_date', $start, false);
		$this->set('wps_end_date', $end, false);
		$this->set('wps_renew_wpp_id', $next_plan->get('wpp_renew_wpp_id')); // 3rd arg != false, so saving record now
		if ($this->wps_renew_wpp_id != $next_plan->get('wpp_id')) {
			$this->next_plan = null;
		}
		
		return true;
				
	}
	
	
	/**
	 * Copy the subscriptions to the archive table
	 * @param boolean $and_delete Do delete after archive?
	 * @return boolean true if ok, false if an error occured
	 */
	public function archive($and_delete = false) {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		// 3rd arg : must be an associative array of the form
		// array( 'dest1' => 'source1', ...). Source items may be literals
		// rather than field names, but strings should be quoted with
		// DatabaseBase::addQuotes()
		$success = $dbw->insertSelect( 'wp_old_subscription', 'wp_subscription',
			array(
				'wpos_wpp_id' => 'wps_wpp_id',
				'wpos_buyer_user_id' => 'wps_buyer_user_id',
				'wpos_tmr_id' => 'wps_tmr_id',
				'wpos_tmr_status' => 'wps_tmr_status',
				'wpos_start_date' => 'wps_start_date',
				'wpos_end_date' => 'wps_end_date'
			),
			array( 'wps_id' => $this->wps_id ),
			__METHOD__ );

		$updated = $dbw->affectedRows();
		
		if ( !$success || ( $updated != 1) ) {	
			return false;
		}
		
		if ( $and_delete ) {
	        $success = $dbw->delete('wp_subscription', array( 'wps_id' => $this->wps_id ), __METHOD__ );
		}
		
		$dbw->commit();
		
		return $success ;
		
	}
	
	
	
	/**
	 * @param string $now MySQL datetime string (can be WpSubscription::getNow() )
	 * @return Array Array of WpSubscription
	 */
	public static function getAllOutdatedToRenew( $now ) {
		
		$dbr = wfGetDB(DB_MASTER) ;
		$now = $dbr->addQuotes($now);
		$conds = $dbr->makeList( array(
			'wps_renew_wpp_id != 0',
			'wps_active ' => 1,
			"wps_end_date < $now"
		), LIST_AND );
		
		$results = $dbr->select( 
				array('wp_subscription', 'wp_plan'),
				'*', $conds, __METHOD__, array(),
				array( 'wp_plan' => array('LEFT JOIN','wps_renew_wpp_id = wpp_id') ) );
		
		$subs = array();
		foreach ( $results as $row ) {
			$sub = self::constructFromDatabaseRow($row);
			$sub->fetchNextPlan($row);
			$subs[] = $sub;
		}
		
		return $subs;		
	}
	
	
	/**
	 * Unactive all subscriptions having wps_end_date < $now. Their records are not archived
	 * because they can still have Wikiplaces attached to them.
	 * @param string $now SQL DATETIME (can be WpSubscription::getNow() )
	 * @return int/boolean nb of unactivated subscriptions if ok, "false" if an error occured
	 */
	public static function deactivateAllOutdated( $now = null ) {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		$now =  $dbw->addQuotes( $now );

		// renewing all active outdated records
		$success = $dbw->update(
				'wp_subscription',
				array( 'wps_active' => 0 ), // value
				array( 'wps_active' => 1, "wps_end_date < $now" ), // conds
				__METHOD__ );

		if ( !$success ) {	
			return false;
		}

		$updated = $dbw->affectedRows();

		$dbw->commit();

		return $updated;
	}
	
	
	/**
	 * Subscribe to a plan (= no current active plan)
	 * WARNING, you should ensure the user can subscribe before calling this: use canSubscribe() and canSubscribeTo()
	 * @param User $use The user who buy the plan, and will use it 
	 * @param WpPlan $plan
	 * @return WpSubscription the newly created subscription if ok, or null if an error occured (db error)
	 */
	public static function subscribe($user, $plan) {
		
		if ( ($user === null) || !($user instanceof User) ||
				($plan === null) || !($plan instanceof WpPlan) ) {
			throw new MWException( 'Cannot subscribe, invalid argument.' );
		}
		
		$user_id = $user->getId();
		$db_master = $dbw = wfGetDB(DB_MASTER);

		// archive the current sub if necessary
		// not that even if this sub is active, it will be archived
		// so, be sure that you need to call this subscribe() !
		$current_sub = WpSubscription::getLastSubscription($user_id);
					
		$tmr = self::createTMR($user_id, $user->getEmail(), $plan);

		// already paid, or waiting a payment ?
		switch ($tmr['tmr_status']) {

			case 'OK': // already paid by user
				$now =  self::getNow() ;
				if ($current_sub != null) {
					$current_sub->archive(true);
				}
				$sub = self::create(
						$plan->get('wpp_id'), 
						$user_id,
						$tmr['tmr_id'],
						'OK', // paid
						$now, // start
						self::calculateEndDateFromStart($now, $plan->get('wpp_period_months')), // end
						true, // active
						$plan->get('wpp_renew_wpp_id'),
						$db_master
				);
				if ( $sub == null ) {
					return null;
				}
				self::addSubscribersGroupToUser($user);
				if ( ! $sub->sendActivationNotification() ) {
					// error while sending notification
					wfDebugLog( 'wikiplaces' , 'WpSubscription ERROR while sending activation notification to ['
							.$user->getId().']'.$user->getRealName());
				}
				return $sub;

			case 'PE': // waiting payment
				self::addSubscribersGroupToUser($user);
				if ($current_sub != null) {
					$current_sub->archive(true);
				}
				return self::create(
						$plan->get('wpp_id'),
						$user_id,
						$tmr['tmr_id'],
						'PE', // not paid
						null, // will start when paid
						null, // unknown for now
						false, // not active
						$plan->get('wpp_renew_wpp_id'),
						$db_master
				);

		}

		// if we arrive here, the payment status is unknown
		throw new MWException( 'Error while subscribing, the transaction status is unknown.' );

	}
	
	/**
	 * Put the user in the effective group 'artist' if she is not already in.
	 * @param User $user 
	 * @return boolean false if she is already in the group, true if just added
	 */
	private static function addSubscribersGroupToUser($user) {
		if (!in_array(WP_SUBSCRIBERS_USER_GROUP, $user->getGroups())) {
			$user->addGroup(WP_SUBSCRIBERS_USER_GROUP);
			return true;
		}
		return false;
	}
	
	/**
	 * Create a transaction record and return it. 
	 * @param int $user_id
	 * @param string $user_email
	 * @param WpPlan $plan
	 * @return array TMR as array
	 */
	private static function createTMR( $user_id, $user_email, $plan ) {
				
		$tmr = array(
			# Params related to Message
			'tmr_type'		=> 'subscrip',

			# Paramas related to User
			'tmr_user_id'	=> $user_id, 
			'tmr_mail'		=> $user_email,
			'tmr_ip'		=> IP::sanitizeIP(wfGetIP()), 

			# Params related to Record
			'tmr_amount'	=> - $plan->get('wpp_price'),
			'tmr_currency'	=> $plan->get('wpp_currency'), 
			'tmr_desc'		=> 'wp-plan-name-'.$plan->get('wpp_name'), 
			'tmr_status'	=> 'PE', // PEnding
		);
		
		wfRunHooks('CreateTransaction', array(&$tmr));
		
		return $tmr;

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
	 * @return the created WpSubscription, or null if a db error occured
	 */
	private static function create( $planId, $buyerUserId, $transactionId, $transactionStatus, 
			$startDate, $endDate, $active, $renewPlanId, $db_master = null ) {
		
		if ( ($planId === null) || ($buyerUserId === null) || ($transactionId === null) ||
				($transactionStatus === null) || ($active === null) || ($renewPlanId === null) ) {
			throw new MWException( 'Cannot create Subscription, missing argument.' );
		}
		
		if ( !is_numeric($planId) || !is_numeric($buyerUserId) || !is_numeric($transactionId) || !is_string($transactionStatus) || 
				( ($startDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $startDate ) ) || 
				( ($endDate !== null) && !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $endDate ) ) || 
				!is_bool($active) || !is_numeric($renewPlanId) ) {
			throw new MWException( 'Cannot create Subscription, invalid argument.' );
		}
						
		$dbw = ( ($db_master != null) ? $db_master : wfGetDB(DB_MASTER) );
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_subscription_wps_id_seq');
		
        $success = $dbw->insert('wp_subscription', array(
			'wps_id' => $id,
			'wps_wpp_id' => $planId,
			'wps_buyer_user_id' => $buyerUserId,
			'wps_tmr_id' => $transactionId,
			'wps_tmr_status' => $transactionStatus,
			'wps_start_date' => $startDate,
			'wps_end_date' => $endDate,
			'wps_active' => $active ? 1 : 0,
			'wps_renew_wpp_id' => $renewPlanId,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return false;
		}		
				
		return new self( $id, $planId, $buyerUserId,
			$transactionId, $transactionStatus,
			$startDate, $endDate, $active, $renewPlanId );
		
	}
	
	/**
	 *
	 * @param string $start_date MySQL DATETIME formated date
	 * @param type $nb_of_month 
	 * @return string MySQL DATETIME formated end date
	 */
	public static function calculateEndDateFromStart($start_date, $nb_of_month) {

		$end = date_create_from_format( 'Y-m-d H:i:s', $start_date, new DateTimeZone( 'GMT' ) );
		if ( $end->format('j') > 28) { // if day > 28
			$end->modify('first day of next month');
		}
		$end->modify( "+$nb_of_month month -1 second" );
		return $end->format( 'Y-m-d H:i:s' );
		
	}
	
	/**
	 *
	 * @param string $start_date MySQL DATETIME formated date
	 * @param type $nb_of_month 
	 * @return string MySQL DATETIME formated start date
	 */
	public static function calculateStartDateFromPreviousEnd($previous_end_date) {

		$start = date_create_from_format( 'Y-m-d H:i:s', $previous_end_date, new DateTimeZone( 'GMT' ) );
		$start->modify( "+1 second" );
		return $start->format( 'Y-m-d H:i:s' );
		
	}
	
	
	/**
	 *
	 * @param int $seconds + or - seconds shift
	 * @param int $minutes + or - minutes shift
	 * @param int $hours + or - hours shift
	 * @return string MySQL DATETIME string
	 */
	public static function getNow($seconds = 0, $minutes = 0, $hours = 0) {
		
		if ( !is_int($seconds) || !is_int($minutes) || !is_int($hours) ) {
			throw new MWException("Cannot compute 'now with delay', invalid argument.");
		}
		
		$start = new DateTime( 'now', new DateTimeZone( 'GMT' ) );
		
		if ( ($seconds != 0) || ($minutes != 0) || ($hours != 0) ) {
			$start->modify( "$seconds second $minutes minute $hours hour" );
		}
		
		return $start->format( 'Y-m-d H:i:s' );
	}
	

	/**
	 * This function should be called by hook. 
	 * Update the current subscription according to new tmr status. 
	 * (can activate or unactive the subscription)
	 * @param type $tmr
	 * @return void
	 */
	public function onTransactionUpdated( $tmr ) {
		
		wfDebugLog( 'wikiplaces', 'onTransactionUpdated:'
		.' tmr_id='.$tmr['tmr_id']
		.' wps_id='.$this->wps_id 
		.' old_tmr_status='.$this->wps_tmr_status
		.' new_tmr_status='.$tmr['tmr_status'] );
				
		switch ($this->wps_tmr_status) {
			
			case 'PE':
				// was pending
				switch ($tmr['tmr_status']) {
				
					case 'OK':
						// PE -> OK
						
						if ($this->wps_start_date == null) {
							// first subscription
							$start = WpSubscription::getNow();
							$end = WpSubscription::calculateEndDateFromStart($start, $this->get('plan')->get('wpp_period_months'));
							$this->set('wps_start_date',	$start, false ); // 3rd param = false = do not update db now
							$this->set('wps_end_date', $end, false ); 
							$this->set('wps_active',	true, false ); 
							$this->set('wps_tmr_status', 'OK'); // no 3rd p = update db now
							try {
								$this->sendActivationNotification();
							} catch (Exception $e) {
								wfDebugLog('wikiplaces', 'onTransactionUpdated: ERROR SENDING EMAIL "'.$e->getMessage().'"'
										.' tmr_id=' . $tmr['tmr_id']
										.' wps_id='.$this->wps_id 
										.' old_tmr_status='.$this->wps_tmr_status
										.' new_tmr_status='.$tmr['tmr_status'] );
							}
						} else {
							// if startDate not null, this is a renewal so it's already activated
							$this->set('wps_tmr_status', 'OK'); // no 3rd p = update db now
						}
						
						return false; // this is our transaction, no more hook process to be done	
						
					case 'KO':
						// PE -> KO
						$this->set('wps_tmr_status', 'KO', false);
						$this->set('wps_end_date', WpSubscription::getNow(), false ); 
						$this->set('wps_active', false);  // in case of a renewal, it can be activated even if pending, so need to ensure that is false
						try {
							$this->sendTransactionErrorNotification();
						} catch (Exception $e) {
							wfDebugLog('wikiplaces', 'onTransactionUpdated: ERROR SENDING EMAIL "'.$e->getMessage().'"'
									.' tmr_id=' . $tmr['tmr_id']
									.' wps_id='.$this->wps_id 
									.' old_tmr_status='.$this->wps_tmr_status
									.' new_tmr_status='.$tmr['tmr_status'] );
						}
						
						return false; // this is our transaction, no more process to be done	
						
					case 'PE':
						// PE -> PE   =>   don't care
						return false;
				}
				break;
			
		}
		
		// if we arrive here, this transaction is about a subscription, but we do not know what to do
		throw new MWException('The transaction was updated, but its new status is not managed (old='.$this->tmr_status.'new='.$tmr['tmr_status'].').');	
		
	}
	
	/**
	 * Send an email when the subscription is activated, when:
	 * <ul>
	 * <li>User makes a first subscription, and the subscription is activated (=only when tmr_status is OK)</li>
	 * <li>System activates the "next plan" (=tmr_status can be OK or PE)</li>
	 * </ul>
	 * @global type $wgContLang
	 * @return boolean true=ok, false=error 
	 */
	public function sendActivationNotification( ) {
		
		global $wgContLang;

		$user = $this->get('buyer');
		$plan = $this->get('plan');

		return $user->sendMail(
				wfMessage( 'wpm-activation-subj' )->text(),
				wfMessage( 'wpm-activation-body' , $user->getName() , $plan->get('wpp_name') , $this->get('wps_end_date') )->text())->isGood();
		
	}
	
	/**
	 * Send an email when the subscription payment status is errored.
	 * @global type $wgContLang
	 * @return boolean true=ok, false=error 
	 */
	public function sendTransactionErrorNotification( ) {
		
		global $wgContLang;

		$user = $this->get('buyer');

		return $user->sendMail(
				wfMessage( 'wpm-payfail-subj' )->text(),
				wfMessage( 'wpm-payfail-body' , $user->getName() )->text())->isGood();
		
	}
	
	
	/**
	 * Check the user has an active subscription, page creation quota is not exceeded and
	 * diskpace quota is not exceeded.
	 * @param int $user_id
	 * @return boolean/string True if user can, string message explaining why she can't
	 * <ul>
	 * <li><b>wp-no-active-sub</b> user has no active subscription</li>
	 * <li><b>wp-page-quota-exceeded</b> page quota exceeded</li>
	 * <li><b>wp-diskspace-quota-exceeded</b> diskspace quota exceeded</li>
	 * </ul>
	 */
	public static function userCanUploadNewFile($user_id) {
		
		$sub = self::getActiveByUserId($user_id);

		if ($sub === null) { 
			return 'wp-no-active-sub';
		}

		$max_pages = $sub->get('plan')->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return 'wp-page-quota-exceeded';
		}
		
		$max_diskspace = $sub->get('plan')->get('wpp_diskspace');
		$user_diskspace_usage = WpPage::getDiskspaceUsageByUser($user_id);

		if ($user_diskspace_usage >= $max_diskspace) { 
			return 'wp-diskspace-quota-exceeded';
		}

		return true;
		
	}
	
	/**
	 * Check the user has an active subscription and page creation quota is not exceeded
	 * @param int $user_id
	 * @return boolean/string True if user can, string message explaining why she can't
	 * <ul>
	 * <li><b>wp-no-active-sub</b> user has no active subscription</li>
	 * <li><b>wp-page-quota-exceeded</b> page quota exceeded</li>
	 * </ul>
	 */
	public static function userCanCreateNewPage($user_id) {
		
		$sub = self::getActiveByUserId($user_id);

		if ($sub === null) { 
			return 'wp-no-active-sub';
		}

		$max_pages = $sub->get('plan')->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return 'wp-page-quota-exceeded';
		}

		return true;
		
	}
	
		/**
	 * Check the user has an active subscription and 
	 * wikiplace creation quota is not exceeded and 
	 * page creation quota is not exceeded
	 * @param type $user_id
	 * @return boolean/string True if user can, string message explaining why she can't:
	 * <ul>
	 * <li><b>wp-no-active-sub</b> user has no active subscription</li>
	 * <li><b>wp-wikiplace-quota-exceeded</b> wikiplace creation quota exceeded</li>
	 * <li><b>wp-page-quota-exceeded</b> page creation quota exceeded</li>
	 * </ul>
	 */
	public static function userCanCreateWikiplace($user_id) {
		
		if ( !is_int($user_id) || ($user_id < 1)) {
			throw new MWException('Cannot check if user can create a Wikiplace, invalid user identifier.');
		}
		
		$sub = self::getActiveByUserId($user_id);
		if ($sub === null) { 
			return 'wp-no-active-sub';
		}	

		$plan = $sub->get('plan');
		
		$max_wikiplaces = $plan->get('wpp_nb_wikiplaces');
		$user_wikiplaces_nb = WpWikiplace::countWikiplacesOwnedByUser($user_id);

		if ($user_wikiplaces_nb >= $max_wikiplaces) { 
			return 'wp-wikiplace-quota-exceeded';
		}

		$max_pages = $plan->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return 'wp-page-quota-exceeded';
		}

		return true; // all ok

	}

}