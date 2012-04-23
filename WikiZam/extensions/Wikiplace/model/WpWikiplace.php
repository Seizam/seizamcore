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
	
/*
	public function set($attribut_name, $value, $update_now = true) {
		$db_value = null;
		switch ($attribut_name) {
			case 'wpu_active':
				if (!is_bool($value)) { throw new MWException('Value error (boolean needed) for '.$attribut_name); }
				$db_value = ( $value ? 1 : 0 );
				break;
			case 'wpu_start_date':
			case 'wpu_end_date':
			case 'wpu_updated':
				if (!is_string($value)) { throw new MWException('Value error (string needed) for '.$attribut_name);	}
				break;
			default:
				throw new MWException('Cannot change the value of attribut '.$attribut_name);
		}
		
		$this->$attribut_name							= $value;
		$this->attributes_to_update[$attribut_name]		= ($db_value !== null) ? $db_value : $value; // used by wps_active to convert from boolean to int
		
		if ($update_now) {
			
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin();

			$success = $dbw->update(
					'wp_usage',
					$this->attributes_to_update,
					array( 'wpu_id' => $this->wpu_id) );
			
			$dbw->commit();

			if ( !$success ) {	
				throw new MWException('Error while saving Usage report to database.');
			}		
			
			$this->attributes_to_update = array();
		
		}
		
		return $value; // maybe useful, one day ...
	}
*/
	
	
	/**
	 * For now, updates only the page hits. In futur release, it will also update the bandwith usage.
	 */
	private function updateUsage() {
	
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
		
		wfDebugLog( 'wikiplace', "WpWikiplace->updateUsage for wp[$this->wpw_id] from $this->wpw_report_updated($this->wpw_monthly_page_hits) to $now($hits)");

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
	 * Should be called by cron. Its execution can take more more more than 30 seconds.
	 * For the moment, it only updates the page hits.
	 * In futur release, in will also update the bandwidth usage.
	 * @return int Nb of updates
	 * @todo updates also the bandwith usage
	 */
	public static function updateAllOutdatedUsages() {
		
		/** @todo update bandwidth usage */ 
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
		$now = $dbw->addQuotes(WpSubscription::getNow());
		$one_hour_ago = $dbw->addQuotes(WpSubscription::getNow(0,0,-1));
		
		$sql = "
CREATE TEMPORARY TABLE wp_tmp_page_hits (
SELECT wppa_wpw_id AS wikiplace_id, SUM(page.page_counter) AS page_hits
FROM wp_wikiplace
  INNER JOIN wp_page
  ON wpw_id = wppa_wpw_id
  AND wpw_report_updated < $one_hour_ago
    INNER JOIN page
    ON wppa_page_id = page_id
GROUP BY wppa_wpw_id ) ;";
		
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result !== true) {
			throw new MWException('Error while computing usages');
		}
		
		$to_update = $dbw->affectedRows();

		$sql = "
UPDATE wp_wikiplace
SET wpw_monthly_page_hits = ( (
  SELECT page_hits
  FROM wp_tmp_page_hits
  WHERE wikiplace_id = wpw_id ) - wpw_previous_total_page_hits ) ,
wpw_report_updated = $now
WHERE wpw_report_updated < $one_hour_ago ;" ;
				
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result !== true) {
			throw new MWException('Error while updating outdated wikiplace usages');
		}
		
		$updated = $dbw->affectedRows();
		
		$dbw->commit();
		
		if ($to_update != $updated) {
			throw new MWException("Wikiplace usages updated, but $to_update updates expected and $updated  updated.");
		}
		
		return $updated;
		
	}
	
	
		/**
	 * Warning, after adding, the attribut value is not valid
	 * Warning, if $value is handled as int, it should not be > 2 147 483 647 because PHP may be 32bits
	 * @param string $value 
	 */
/*	public function addBandiwidthConsumption($value) {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		$success = $dbw->update(
				'wp_usage',
				'wpu_monthly_bandwidth = wpu_monthly_bandwidth + '.$value,
				array( 'wpu_id' => $this->wpu_id) );

		$dbw->commit();
		
		if ( !$success ) {	
			throw new MWException('Error while updating bandwidth usage.');
		}
	
	}
*/	
	
	/**
	 * Reset all usages when outdated
	 * @return int Nb of Wikiplace reset
	 */
	public static function archiveAndResetMonthlyUsages() {
		
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
					'wpou_wps_id' => 'wpw_wps_id',
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
	 * Fetch a name from a db row or a Title objet
	 * @param type $databaseRow
	 * @param type $title 
	 */
	private function fetchName($databaseRow = null, $title = null) {
		
		if ($title !== null) {
			
			if ( !($title instanceof Title)) {
				throw new MWException('cannot fetch name, invalid title argument');
			}
			if ($title->getArticleID() != $this->wpw_home_page_id) {
				throw new MWException('cannot fetch name from this title object, this title is not the homepage ('.
						$title->getArticleID().'!='.$this->wpw_home_page_id.')');
			}
			$this->name = $title->getText();
			
			
		} else {
			
			
			if ($databaseRow === null) {
			
				$dbr = wfGetDB(DB_SLAVE);
				$databaseRow = $dbr->selectRow( 'page', 'page_title', array( 'page_id' =>  $this->wpw_home_page_id ), __METHOD__ );
				if ( $databaseRow === false ) {
					throw new MWException('Page not found.');
				} 
				
			}
		
			if (!isset($databaseRow->page_title)) {
				throw new MWException('cannot fetch the name, invalid database row');
			}

			// str_replace seen in Title line 305
			// necessary because the db contains the title with underscores
			$this->name = str_replace( '_', ' ', $databaseRow->page_title );
					
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
	 * Get the container wikiplace of the Title, null if the title is not in a wikiplace
	 * @param Title $title
	 * @return boolean/WpWikiplace Wikiplace Object or true if this page is the homepage or false if correpsond to nothing 
	 */
	public static function extractWikiplaceRoot($title) {
		
		if (!WpPage::isInWikiplaceNamespaces($title)) {
			return null; // not in wikiplace
		}

		$pages;
		
		switch ($title->getNamespace()) {
			case NS_MAIN:
			case NS_TALK:
				$pages = explode( '/', $title->getDBkey() );
				break;
			
			case NS_FILE:
			case NS_FILE_TALK:
				$pages = explode( '.', $title->getDBkey() );
				break;
				
			default:
				throw new MWException('this namespace cannot store wikiplace item');
		}
		
		
		if (!isset($pages[0])) {
			return null;
		}

		return self::getByName($pages[0]);
		
	}

	
		/**
	 *
	 * @param type $name
	 * @return Title The created homapge Title if OK, null if error 
	 */
	public static function initiateCreation($name) {
		
		// the creation of the homapage will trigger the page creation hook, 
		// wich will call self::create(...) wich will process the real creation of the wikiplace
		return WpPage::createHomepage($name);
		
	}
	
	/**
	 * Create a wikiplace from this homepage, owned by this user
	 * @param Title $homepage
	 * @param int $user_id
	 * @return WpWikiplace/int The created Wikiplace if creation succesfull, or an int error code
	 * 1 = no active subscription for this user
	 */
	public static function create($homepage, $user_id) {
		
		if ( ($homepage === null) || ($user_id === null) ) {
			throw new MWException( 'Cannot create Wikiplace (missing argument)' );
		}
		
		if ( !($homepage instanceof Title) || !is_int($user_id) ) {
			throw new MWException( 'Cannot create Wikiplace (invalid argument)' );
		}
		
		$subscription = WpSubscription::getActiveByUserId($user_id);
		
		if ( $subscription === null ) {
			return 1;
		}
		
		$homepageId = $homepage->getArticleID();
		$homepageName = $homepage->getDBkey();
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
			return 3;
		}
		
		$wp = new self( $id, $user_id, $homepageId,
				$wps_id, 0, 0, 0, 0, $now, $wpw_date_expires);
		$wp->fetchName(null, $homepage);
				
		$new_wp_page = WpPage::attachNewPageToWikiplace($homepage, $wp);
		
		if ($new_wp_page === null) {
			throw new MWException('Cannot associate the homepage to the newly created wikiplace .');
		}
				
		return $wp;
			
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
