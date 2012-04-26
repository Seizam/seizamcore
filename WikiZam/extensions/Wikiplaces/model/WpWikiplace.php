<?php

class WpWikiplace {  

	private	$wpw_id,            // int(10) unsigned
			$wpw_owner_user_id, // int(10) unsigned
			$wpw_home_page_id,  // int(10) unsigned
			$wpw_wps_id,
			$wpw_previous_total_page_hits,
			$wpw_monthly_page_hits,
			$wpw_previous_total_bandwidth,
			$wpw_monthly_bandwidth,
			$wpw_report_updated,
			$wpw_date_expires;
	
	private $subscription;
	
	private $name;
	
	private $attributes_to_update;
	
	
	
	private function __construct( $id, $ownerUserId, $homePageId,
			$subscriptionId,
			$previousTotalPageHits, $monthlyPageHits,
			$previousTotalBandwidth, $monthlyBandwidth,
			$reportUpdated, $dateExpires) {

		$this->wpw_id = $id;
		$this->wpw_owner_user_id = $ownerUserId;
		$this->wpw_home_page_id = $homePageId;
		
		$this->wpw_wps_id = $subscriptionId;
		$this->wpw_previous_total_page_hits = $previousTotalPageHits;
		$this->wpw_monthly_page_hits = $monthlyPageHits;
		$this->wpw_previous_total_bandwidth = $previousTotalBandwidth;
		$this->wpw_monthly_bandwidth = $monthlyBandwidth;
		$this->wpw_report_updated = $reportUpdated;
		$this->wpw_date_expires = $dateExpires;
		
		$this->attributes_to_update = array();

	}
	
		/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wpw_id':
			case 'wpw_owner_user_id':
			case 'wpw_home_page_id':
			case 'wpw_wps_id':
				return intval($this->$attribut_name);
				break;
			case 'wpw_monthly_page_hits':
			case 'wpw_monthly_bandwidth':
				// update if report is more than 5 min age
				if ($this->wpw_report_updated < WpSubscription::getNow(0, -5, 0)) { 
					$this->updateUsage();
				}
			case 'wpw_previous_total_page_hits':
			case 'wpw_previous_total_bandwidth':
			case 'wpw_report_updated':
			case 'wpw_date_expires':
				return $this->$attribut_name;
				break;
			case 'name':
				if ($this->name === null) {
					$this->fetchName();
				}
				return $this->name;
			case 'subscription':
				if ($this->subscription === null) {
					$this->fetchSubscription();
				}
				return $this->subscription;

		}
		throw new MWException('Unknown attribut '.$attribut_name);
	}
	
	
	/**
	 * For now, updates only the page hits. In futur release, it will also update the bandwith usage.
	 */
	public function updateUsage() {
	
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		
		// mysql computes the new value because it can be larger than PHP_INT_MAX, and we handle the result as string
		
		$result = $dbr->selectRow( 
				array( 'wp_page' , 'page' ),
				array( '( SUM(page_counter) - '.$this->wpw_previous_total_page_hits.') AS hits'),
				array( 'wppa_wpw_id' => $this->wpw_id ),
				__METHOD__,
				array(),
				array( 'page' => array('INNER JOIN','wppa_page_id = page_id') ) );
		
		if ( $result === false ) {
			throw new MWException('Error while computing page_hits.');
		}
		
		$hits = $result->hits; // handle as string because it can be larger than PHP_INT_MAX
		
		/** @todo compute bandwidth usage */ 

		$now = WpSubscription::getNow();
		
		wfDebugLog( 'wikiplaces', "WpWikiplace->updateUsage for wp[$this->wpw_id] from $this->wpw_report_updated($this->wpw_monthly_page_hits) to $now($hits)");

		$success = $dbw->update(
				'wp_wikiplace',
				array(
					'wpw_monthly_page_hits' => $hits,
					'wpw_report_updated' => $now,
				),
				array( 'wpw_id' => $this->wpw_id) );

		$dbw->commit();

		if ( !$success ) {	
			throw new MWException('Error while updating wikiplace usages to database.');
		}
		
		$this->wpw_monthly_page_hits = $hits;
		$this->wpw_report_updated = $now;
	}
	
	

	/**
	 * Its execution can take more more more than 30 seconds, so should be called by 
	 * a cron, except if $wpw_owner_user_id is given. 
	 * For the moment, it only updates the page hits.
	 * In futur release, in will also update the bandwidth usage.
	 * @todo updates also the bandwith usage
	 * @param int $wpw_owner_user_id Can only update usages of a specific user (default: null = all)
	 * @param int $lifespan_minutes Lifespan above wich to consider a usage outdated (default: 60 minutes)
	 * @return Status Nb of updates as Status value if is good
	 */
	public static function updateOutdatedUsages( $wpw_owner_user_id = null, $lifespan_minutes = 60 ) {
		
		/** @todo update bandwidth usage */ 
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
		$now = $dbw->addQuotes(WpSubscription::getNow());
		$outdated = $dbw->addQuotes(WpSubscription::getNow(0,-$lifespan_minutes,0));
		
		$sql = "
CREATE TEMPORARY TABLE wp_tmp_page_hits (
SELECT wppa_wpw_id AS wikiplace_id, SUM(page.page_counter) AS page_hits
FROM wp_wikiplace
  INNER JOIN wp_page
  ON ".( ($wpw_owner_user_id!=null) ? " wpw_owner_user_id = $wpw_owner_user_id AND " : '')
."wpw_report_updated < $outdated AND wpw_id = wppa_wpw_id
    INNER JOIN page
    ON wppa_page_id = page_id
GROUP BY wppa_wpw_id ) ;";
		
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result !== true) {
			$msg = 'Problem while computing new usages value.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}
		
		$to_update = $dbw->affectedRows();

		$sql = "
UPDATE wp_wikiplace
SET wpw_monthly_page_hits = ( (
  SELECT page_hits
  FROM wp_tmp_page_hits
  WHERE wikiplace_id = wpw_id ) - wpw_previous_total_page_hits ) ,
wpw_report_updated = $now
WHERE wpw_report_updated < $outdated ;" ;
				
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result !== true) {
			$msg = 'Problem while updating outdated wikiplace usages.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}
		
		$updated = $dbw->affectedRows();
		
		$dbw->commit();
		
		if ($to_update != $updated) {
			throw new MWException("Wikiplace usages updated, but $to_update updates expected and $updated  updated.");
		}
		
		return $updated;
		
	}

	
	/**
	 * Reset all usages when outdated
	 * @return int Nb of Wikiplace reset
	 */
	public static function archiveAndResetExpiredUsages() {
		
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin();
			
			$now =  $dbw->addQuotes( WpSubscription::getNow() );

			// archiving current usages

			// 3rd arg : must be an associative array of the form
			// array( 'dest1' => 'source1', ...). Source items may be literals
			// rather than field names, but strings should be quoted with
			// DatabaseBase::addQuotes()
			$success = $dbw->insertSelect( 'wp_old_usage', 'wp_wikiplace',
				array(
					'wpou_wpw_id' => 'wpw_id',
					'wpou_end_date' => $now,
					'wpou_monthly_page_hits' => 'wpw_monthly_page_hits',
					'wpou_monthly_bandwidth' => 'wpw_monthly_bandwidth',
				),
				array( 'wpw_date_expires < '.$now ),
				__METHOD__,
				array( 'IGNORE' )
			);
			
			if ( !$success ) {	
				throw new MWException('Error while renewing archiving outdated usages.');
			}
			
			// renewing all active outdated records
			$success = $dbw->update(
					'wp_wikiplace',
					array(
						'wpw_date_expires = DATE_ADD(wpw_date_expires,INTERVAL 1 MONTH)',
						'wpw_monthly_page_hits' => 0,
						'wpw_monthly_bandwidth' => 0,
						'wpw_previous_total_page_hits' => '( wpw_monthly_page_hits + wpw_previous_total_page_hits)',
						'wpw_previous_total_bandwidth' => '( wpw_monthly_bandwidth + wpw_previous_total_bandwidth)',
					),
					array( 'wpw_date_expires < '.$now ) );
				
			if ( !$success ) {	
				throw new MWException('Error while resetting outdated usages.');
			}
			
			$updated = $dbw->affectedRows();
			
			$dbw->commit();
			
			return $updated;
		
	}
	
	
	/**
	 * Force archiving current usage, then reset, even if 'expires_date' is not outdated.
	 * It uses $now as end date in archives table. <b>Doesn't update 'wpw_expires_date'</b>
	 * @param string $now DATETIME Sql timestamp. If null, WpSubscription::getNow() is used.
	 * @return Status Status
	 */
	public function forceArchiveAndResetUsage( $now = null ) {
			
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		if ( $now == null) {
			$now =  WpSubscription::getNow();
		} 
		$now =  $dbw->addQuotes( $now );

		// archiving current usages

		// 3rd arg : must be an associative array of the form
		// array( 'dest1' => 'source1', ...). Source items may be literals
		// rather than field names, but strings should be quoted with
		// DatabaseBase::addQuotes()
		$success = $dbw->insert( 'wp_old_usage', 
			array(
				'wpou_wpw_id' => $this->wpw_id,
				'wpou_end_date' => $now,
				'wpou_monthly_page_hits' => $this->wpw_monthly_page_hits,
				'wpou_monthly_bandwidth' => $this->wpw_monthly_bandwidth,
			),
			__METHOD__ );
		
		if ( !$success ) {	
			$msg = 'Error while archiving usage.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}
		// reset usage
		$success = $dbw->update(
				'wp_wikiplace',
				array(
					'wpw_date_expires = DATE_ADD(wpw_date_expires,INTERVAL 1 MONTH)',
					'wpw_monthly_page_hits' => 0,
					'wpw_monthly_bandwidth' => 0,
					'wpw_previous_total_page_hits' => '( wpw_monthly_page_hits + wpw_previous_total_page_hits)',
					'wpw_previous_total_bandwidth' => '( wpw_monthly_bandwidth + wpw_previous_total_bandwidth)',
				),
				array( 'wpw_id' => $this->wpw_id ) );

		if ( !$success ) {	
			$msg = 'Error while resetting usage.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}

		$dbw->commit();

		return Status::newGood();
		
	}
	
	private function fetchSubscription($databaseRow = null) {
		
		if ($databaseRow !== null) {
			if ($databaseRow->wps_id != $this->wpu_wps_id) {
				throw new MWException('The given subscription does not match with the current usage.');
			}
			$this->subscription = WpSubscription::constructFromDatabaseRow($databaseRow);
			
		} else {
			$this->subscription = WpSubscription::getById($this->wpu_wps_id);
		}
		
		if ($this->subscription === null) {
			throw new MWException('Unknown subscription');
		} 

	}
	
	
	
	public function isOwner($user_id) {
		return ( $this->wpw_owner_user_id == $user_id );
	}
	
	/**
	 * Fetch the Wikiplace name, using given data is available or read from DB if none given
	 * @param StdClass/WikiPage/Title $data
	 */
	private function fetchName($data = null) {
		
		if ($data === null) {
			$dbr = wfGetDB(DB_SLAVE);
			$databaseRow = $dbr->selectRow('page', 'page_title', array('page_id' => $this->wpw_home_page_id), __METHOD__);
			if ($databaseRow === false) {
				throw new MWException('Page not found.');
			}
		}
		
		if ( $data instanceof WikiPage) {	
			$data = $data->getTitle();
		}
		
		if ( $data instanceof Title ) {
			
			if ($data->getArticleID() != $this->wpw_home_page_id) {
				throw new MWException('cannot fetch name, the given title is not the homepage ('.
						$data->getArticleID().'!='.$this->wpw_home_page_id.')');
			}
			$this->name = $data->getText();
			
			
		} elseif ( isset($data->page_title) ) {
			
			// str_replace as seen in Title line 305
			// necessary because the db contains the title with underscores
			$this->name = str_replace( '_', ' ', $data->page_title );
			
		} else {
			
			throw new MWException('cannot fetch name: '.var_export($data, true));
					
		}

	}
	
	/**
	 * Get the Wikiplace instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	public static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Wikiplace from the supplied row (null given)' );
		}
		
		if ( !isset($row->wpw_id) || !isset($row->wpw_owner_user_id) || !isset($row->wpw_home_page_id) || !isset($row->wpw_wps_id) ||
				!isset($row->wpw_previous_total_page_hits) || !isset($row->wpw_monthly_page_hits) ||
				!isset($row->wpw_previous_total_bandwidth) || !isset($row->wpw_monthly_bandwidth) || 
				!isset($row->wpw_report_updated) || !isset($row->wpw_date_expires) ) {
						
			throw new MWException( 'Cannot construct the Wikiplace from the supplied row (missing field)' );
		}
			
		return new self ( intval($row->wpw_id) , intval($row->wpw_owner_user_id) ,  intval($row->wpw_home_page_id), intval($row->wpw_wps_id),
			$row->wpw_previous_total_page_hits, $row->wpw_monthly_page_hits,
			$row->wpw_previous_total_bandwidth, $row->wpw_monthly_bandwidth,
			$row->wpw_report_updated, $row->wpw_date_expires);
		
	}
	
	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fetch Wikiplace matching the identifier (invalid identifier)' );
		}
		
		return self::getFromDb( array( 'wpw_id' =>  $id ) );

	}
	
	/**
	 *
	 * @param array $cond 
	 * array( 'page_title' =>  $name )
	 * array( 'wpw_owner_user_id' =>  $user_id )
	 * @param boolean $multiple Return an array of Wikiplace or a single Wikiplace object
	 * @return WpWikiplace 
	 */
	private static function getFromDb($conds, $multiple = false) {
		
		$dbr = wfGetDB(DB_SLAVE);
		//todo
		
		$tables = array( 'wp_wikiplace', 'page' );
		$vars = array( 'wpw_id', 'wpw_owner_user_id', 'wpw_home_page_id', 'page_title',
			'wpw_wps_id', 'wpw_previous_total_page_hits', 'wpw_monthly_page_hits', 'wpw_previous_total_bandwidth', 'wpw_monthly_bandwidth',
			'wpw_report_updated','wpw_date_expires'	);
		$fname = __METHOD__;
		$options = array();
		$join_conds = array( 'page' => array('INNER JOIN', 'wpw_home_page_id = page_id')); /** @todo:maybe a left join? */
		
		if ($multiple) {
			$results = $dbr->select($tables, $vars, $conds, $fname, $options, $join_conds);
			$wikiplaces = array();
			foreach ( $results as $row ) {
				$wp = self::constructFromDatabaseRow($row);
				$wp->fetchName($row);
				$wikiplaces[] = $wp;
			}

			$dbr->freeResult( $results );

			return $wikiplaces;

		} else {
			$result = $dbr->selectRow($tables, $vars, $conds, $fname, $options, $join_conds); 
			if ( $result === false ) {
				// not found, so return null
				return null;
			}
			$wp = self::constructFromDatabaseRow($result);
			$wp->fetchName($result);
			return $wp;
		}
		
	}
	
	/**
	 * Return the wikiplace of this name, or null if not exist
	 * @param String $name
	 * @return WpWikiplace 
	 */
	public static function getByName($name) {
				
		if ( ($name === null) || !is_string($name) ) {
			throw new MWException( 'Cannot fectch Wikiplace matching the name (invalid string)' );
		}
		
		return self::getFromDb( array('page_title' => $name) );

	}
	

	/**
	 * 
	 * 
	 * @param int $user_id
	 * @return array of WpWikiplaces ("array()" if no wikiplaces)
	 */
	public static function getAllOwnedByUserId($user_id) {
		
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fetch Wikiplaces owned by the specified user (invalid user identifier)' );
		}	
		
		return self::getFromDb( array( 'wpw_owner_user_id' =>  $user_id ), true);

	}
	
	
	
	/**
	 *
	 * @param Title $title The homepage of this wikiplace
	 */
/*	public function setHomepage($title) {
		
		if ( ($title === null) || !($title instanceof Title) ) {
			throw new MWException('cannot set homapge, invalid Title argument');
		}
		
		$page_id = $title->getArticleID();
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		$success = $dbw->update(
			'wp_wikiplace',
			array( 'wpw_home_page_id' => $page_id ),
			array( 'wpw_id' => $this->wpw_id) );
						

		$dbw->commit();

		if ( !$success ) {	
			throw new MWException('Error while saving Wikiplace to database.');
		}		

		$this->wpw_home_page_id = $page_id;
		
	}
*/	
	
				/**
	 * 
	 * @param Title $title
	 * @return boolean/WpWikiplace Wikiplace Object or true if this page is the homepage or false if correpsond to nothing 
	 */
	
	/**
	 * Get the container Wikiplace, or null if the title db key can't belong to an existing Wikiplace
	 * 
	 * @param string $db_key can be $wikipage->getTitle()->getDBkey()
	 * @param int $namespace can be $wikipage->getTitle()->getNamespace()
	 * @return WpWikiplace 
	 */
	public static function extractWikiplaceRoot($db_key, $namespace) {
		
		$hierarchy;
		
		switch ( $namespace ) {
			case NS_MAIN:
			case NS_TALK:
				$hierarchy = explode( '/', $db_key );
				break;
			
			case NS_FILE:
			case NS_FILE_TALK:
				$hierarchy = explode( '.', $db_key );
				break;
				
			default:
				throw new MWException('this namespace cannot store wikiplace page');
		}
		
		if ( ! isset($hierarchy[0]) ) {
			return null;
		}

		return self::getByName($hierarchy[0]);
		
	}

	
	/**
	 * Trigger homepage creation, wich will trigger Wikiplace creation.
	 * @param string $name
	 * @return Status The created homepage Title as Status value if OK
	 */
	public static function initiateCreation($name) {
		
		// the creation of the homapage will trigger the page creation hook, 
		// wich will call WpWikiplace::create() wich will process the real creation of the wikiplace
		return WpPage::createHomepage($name);
		
	}
	
	/**
	 * Create a wikiplace from this homepage, owned by this user
	 * @param WikiPage $homepage
	 * @param int $user_id
	 * @return Status 
	 */
	public static function create($homepage, $user_id) {
		
		if ( ($homepage === null) || ($user_id === null) ) {
			throw new MWException( 'Cannot create Wikiplace (missing argument)' );
		}
		
		if ( !($homepage instanceof WikiPage) || !is_int($user_id) ) {
			throw new MWException( 'Cannot create Wikiplace (invalid argument)' );
		}
		
		$subscription = WpSubscription::getActiveByUserId($user_id);
		
		if ( $subscription === null ) {
			$msg = 'The user has no active subscription. Cannot create Wikiplace.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}
		
		$homepageTitle = $homepage->getTitle();
		$homepageId = $homepageTitle->getArticleID();
		$homepageName = $homepageTitle->getDBkey();
		$now = WpSubscription::getNow();
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wpw_id');
		
		$wps_id = $subscription->get('wps_id');
		$wpw_date_expires = self::calculateDateExpiresFromSubscription($subscription);
		
        $success = $dbw->insert('wp_wikiplace', array(
			'wpw_id' => $id,
			'wpw_owner_user_id' => $user_id,
			'wpw_home_page_id' => $homepageId,
			
			'wpw_wps_id' => $wps_id,
			'wpw_previous_total_page_hits' => 0,
			'wpw_monthly_page_hits' => 0,
			'wpw_previous_total_bandwidth' => 0,
			'wpw_monthly_bandwidth' => 0,
			'wpw_report_updated' => $now,
			'wpw_date_expires' => $wpw_date_expires
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			$msg = 'Problem while creating Wikiplace record.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}
		
		$wp = new self( $id, $user_id, $homepageId,
				$wps_id, 0, 0, 0, 0, $now, $wpw_date_expires);
		$wp->fetchName($homepage);
				
		$new_wp_page = WpPage::create($homepage, $wp);
		
		if ($new_wp_page === null) {
			$msg = 'Problem while associating the homepage to the newly created Wikiplace .';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;
		}
		
		$status = $wp->forceArchiveAndResetUsage($now);
		if ( ! $status->isGood() ) {
			$msg = 'Problem while initiating Wikiplace usage.';
			$status = Status::newFatal($msg);
			$status->value = $msg;
			return $status;			
		}
				
		return Status::newGood($wp);
			
	}
	
	
	public static function countWikiplacesOwnedByUser($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'invalid user identifier' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 
				'wp_wikiplace',
				array(
					'count(*) as total'
					),
				array( 'wpw_owner_user_id' =>  $user_id ),
				__METHOD__,
				array(),
				array() );
		
		if ($result === null) {
			return 0;
		}
		
		return intval($result->total);
		
	}
	
	
	/**
	 * Check the user has an active subscription and 
	 * wikiplace creation quota is not exceeded and 
	 * page creation quota is not exceeded
	 * @param type $user_id
	 * @return boolean
	 */
	public static function userCanCreateWikiplace($user_id) {
		
		if ( !is_int($user_id) || ($user_id < 1)) {
			throw new MWException('cannot check wikiplace creation, invalid argument');
		}
		
		$sub = WpSubscription::getActiveByUserId($user_id);
		if ($sub === null) { 
			return false;
		}	

		$plan = $sub->get('plan');
		
		$max_wikiplaces = $plan->get('wpp_nb_wikiplaces');
		$user_wikiplaces_nb = WpWikiplace::countWikiplacesOwnedByUser($user_id);

		if ($user_wikiplaces_nb >= $max_wikiplaces) { 
			return false;
		}

		$max_pages = $plan->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return false;
		}

		return true; // all ok

	}
	
	
	public static function calculateDateExpiresFromSubscription($subscription) {
		
		// contains the needed day/hour/minute/second
		$end = date_create_from_format( 'Y-m-d H:i:s', $subscription->get('wps_end_date'), new DateTimeZone( 'GMT' ) );
		
		$now = new DateTime( 'now', new DateTimeZone( 'GMT' ) );
		
		$expire = date_create_from_format( 'Y-m-d H:i:s', 
				$now->format( 'Y-m-' ) . $end->format( 'd H:i:s' ) ,
				new DateTimeZone( 'GMT' ) );
		
		if ( $expire < $now ) {
			$expire->modify('+1 month');
		}
		
		return $expire->format( 'Y-m-d H:i:s' );
		
	}
	
	
	
}
