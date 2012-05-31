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
	
	private $name;
	private $attributes_to_update;	
	
	private function __construct( $id, $ownerUserId, $homePageId,
			$subscriptionId,
			$previousTotalPageHits, $monthlyPageHits,
			$previousTotalBandwidth, $monthlyBandwidth,
			$reportUpdated, $dateExpires) {

		$this->wpw_id = intval($id);
		$this->wpw_owner_user_id = intval($ownerUserId);
		$this->wpw_home_page_id = intval($homePageId);
		
		$this->wpw_wps_id = intval($subscriptionId);
		$this->wpw_previous_total_page_hits = $previousTotalPageHits;
		$this->wpw_monthly_page_hits = $monthlyPageHits;
		$this->wpw_previous_total_bandwidth = $previousTotalBandwidth;
		$this->wpw_monthly_bandwidth = $monthlyBandwidth;
		$this->wpw_report_updated = $reportUpdated;
		$this->wpw_date_expires = $dateExpires;
		
		$this->attributes_to_update = array();

	}
	
	
	/**
	 * Returns the identifier
	 * @return int 
	 */
	public function getId() {
		return $this->wpw_id;
	}
	
	/**
	 * Returns the wikiplace name, in db_key form
	 * @return type 
	 */
	public function getName() {
		if ($this->name === null) {
			$this->loadName();
		}
		return $this->name;
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

		$now = WpSubscription::now();
		
		wfDebugLog( 'wikiplaces-debug', "WpWikiplace[$this->wpw_id]->updateUsage(): updating from $this->wpw_report_updated($this->wpw_monthly_page_hits) to $now($hits)");

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
	 * Force archiving current usage, then reset, even if 'expires_date' is not outdated.
	 * It uses $now as end date in archives table. <b>Doesn't update 'wpw_expires_date'</b>
	 * @param string $now DATETIME Sql timestamp. If null, WpSubscription::getNow() is used.
	 * @return boolean true if OK, false if db error occured
	 */
	public function forceArchiveAndResetUsage( $now = null ) {
			
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		if ( $now == null) {
			$now =  WpSubscription::now();
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
			return false;
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
			return false;
		}

		$dbw->commit();

		return true;
		
	}

	public function isOwner($user_id) {
		return ( $this->wpw_owner_user_id == $user_id );
	}
	
	/**
	 * Fetch the Wikiplace name, using given data is available or read from DB if none given
	 * @param StdClass/WikiPage/Title $data
	 */
	private function loadName($data = null) {
		
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
	 * @return WpWikiplace 
	 */
	public static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Wikiplace from the supplied row, null given.' );
		}
		
		if ( !isset($row->wpw_id) || !isset($row->wpw_owner_user_id) || !isset($row->wpw_home_page_id) || !isset($row->wpw_wps_id) ||
				!isset($row->wpw_previous_total_page_hits) || !isset($row->wpw_monthly_page_hits) ||
				!isset($row->wpw_previous_total_bandwidth) || !isset($row->wpw_monthly_bandwidth) || 
				!isset($row->wpw_report_updated) || !isset($row->wpw_date_expires) ) {
						
			throw new MWException( 'Cannot construct the Wikiplace from the supplied row, missing field.' );
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
			throw new MWException( 'Cannot search Wikiplace, invalid identifier.' );
		}
		
		return self::search( array( 'wpw_id' =>  $id ) );

	}
	
	/**
	 *
	 * @param array $cond 
	 * array( 'page_title' =>  $name )
	 * array( 'wpw_owner_user_id' =>  $user_id )
	 * @param boolean $multiple Return an array of Wikiplace or a single Wikiplace object
	 * @return WpWikiplace 
	 */
	private static function search($conds, $multiple = false) {
		
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
				$wp->loadName($row);
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
			$wp->loadName($result);
			return $wp;
		}
		
	}
	
	/**
	 * Return the wikiplace of this name, or null if not exist
	 * @param String $name
	 * @return WpWikiplace 
	 */
	public static function newFromName($name) {
				
		if ( ($name === null) || !is_string($name) ) {
			throw new MWException( 'Cannot search Wikiplace, invalid name.' );
		}
		
		return self::search( array('page_title' => $name) );

	}
	

	/**
	 * 
	 * 
	 * @param int $user_id
	 * @return array of WpWikiplaces ("array()" if no wikiplaces)
	 */
	public static function factoryAllOwnedByUserId($user_id) {
		
		if ( ($user_id === null) || !is_int($user_id) ) {
			throw new MWException( 'Cannot search Wikiplaces, invalid owner user identifier.' );
		}	
		
		if ($user_id < 1) {
			return array();
		}
		
		return self::search( array( 'wpw_owner_user_id' =>  $user_id ), true);

	}
	
	
	/**
	 * Trigger homepage creation, which will than trigger Wikiplace creation using hook.
	 * @param string $name
	 * @param User $user The user who creates the wikiplace
	 * @return Title/string The created homepage Title if creation OK, a string message if an error occured
	 */
	public static function initiateCreation($name, $user) {
		
		// the creation of the homapage will trigger the page creation hook, 
		// wich will call WpWikiplace::create() wich will process the real creation of the wikiplace
		return WpPage::createHomepage($name, $user);
		
	}
	
	/**
	 * Create a wikiplace from homepage, owned by this user
	 * @param int $homepage_id The Article id of the homepage
	 * @param WpSubscription $subscription
	 * @return WpWikiplace The created Wikiplace, or null if a db error occured
	 */
	public static function create($homepage_id, $subscription) {
		
		if (  ! is_int($homepage_id)  ||  ! ($subscription instanceof WpSubscription)  ) {
			throw new MWException( 'Cannot create Wikiplace, invalid argument.' );
		}
		
		$user_id = $subscription->getBuyerUserId();
		$wps_id = $subscription->getId();
		$wpw_report_updated = WpSubscription::now();
		$wpw_date_expires = self::calculateNextDateExpiresFromSubscription($subscription);
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
        $id = $dbw->nextSequenceValue('wpw_id');
			
        $success = $dbw->insert('wp_wikiplace', array(
			'wpw_id' => $id,
			'wpw_owner_user_id' => $user_id,
			'wpw_home_page_id' => $homepage_id,
			
			'wpw_wps_id' => $wps_id,
			'wpw_previous_total_page_hits' => 0,
			'wpw_monthly_page_hits' => 0,
			'wpw_previous_total_bandwidth' => 0,
			'wpw_monthly_bandwidth' => 0,
			'wpw_report_updated' => $wpw_report_updated,
			'wpw_date_expires' => $wpw_date_expires
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}
		
		$wp = new self( 
				$id,
				$user_id,
				$homepage_id,
				$wps_id,
				0,
				0,
				0,
				0,
				$wpw_report_updated,
				$wpw_date_expires);

		return $wp;
			
	}
	
		/**
	 * Its execution can take more more more than 30 seconds, so should be called by 
	 * a cron, except if $wpw_owner_user_id is given. 
	 * For the moment, it only updates the page hits.
	 * In futur release, in will also update the bandwidth usage.
	 * @todo updates also the bandwith usage
	 * @param int $wpw_owner_user_id Can only update usages of a specific user (default: null = all)
	 * @param int $lifespan_minutes Lifespan above wich to consider a usage outdated (default: 60 minutes)
	 * @return int/string int:Nb of updates if OK, string: the error message
	 */
	public static function updateOutdatedUsages( $wpw_owner_user_id = null, $lifespan_minutes = 60 ) {
		
		/** @todo update bandwidth usage */ 
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
		$now = $dbw->addQuotes(WpSubscription::now());
		$outdated = $dbw->addQuotes(WpSubscription::now(0,-$lifespan_minutes,0));
		
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
			return 'error while computing new usages value';
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
			return 'error while updating outdated wikiplace usages';
		}
		
		$updated = $dbw->affectedRows();
		
		$dbw->commit();
		
		if ($to_update != $updated) {
			return "usages updated, but $to_update updates expected and $updated really updated";
		}
		
		return $updated;
		
	}
	
	
	/**
	 * Increase the Wikiplace bandiwth counter by adding $size bytes to it
	 * @param string $wikiplace_name The Wikiplace name in db_key form
	 * @param int $size The size in bytes (it should be string instead of int when PHP is 32 bits)
	 * @return boolean True if the matching wikiplace has been updated, false if an error occured
	 */
	public static function updateBandwidthUsage($wikiplace_name, $size) {
		
		$dbw = wfGetDB(DB_MASTER);
		
		$wikiplace_name = $dbw->addQuotes( $wikiplace_name );
		
		$dbw->begin();
		
		$sql = "UPDATE wp_wikiplace INNER JOIN page ON ( wpw_home_page_id = page_id AND page_title = $wikiplace_name ) SET wpw_monthly_bandwidth = ( wpw_monthly_bandwidth + ( $size >> 10 ) ) ; " ;
		
		$result = $dbw->query($sql, __METHOD__);
		if ($result !== true) {
			wfDebugLog('wikiplaces', "updateBandwidthUsage: update request ERROR, wp=$wikiplace_name, size=$size bytes");
			return false;
		}
		
		$updated = $dbw->affectedRows();
		
		$dbw->commit();
		
		if ($updated != 1) {
			wfDebugLog('wikiplaces', "updateBandwidthUsage(): ERROR $updated row(s) updated, wp=$wikiplace_name, size=$size bytes");
			return false;
		}
		
		wfDebugLog('wikiplaces-debug', "updateBandwidthUsage: OK, wp=$wikiplace_name, size=$size bytes");
		return true; //always
	}

	
	/**
	 * Reset all usages when outdated
	 * @return int/string int:Nb of Wikiplace usages reset if OK, string: the message if an error occured
	 */
	public static function archiveAndResetExpiredUsages() {
		
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin();
			
			$now =  $dbw->addQuotes( WpSubscription::now() );

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
				return 'error while renewing archiving outdated usages.';
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
				return 'error while resetting outdated usages';
			}
			
			$updated = $dbw->affectedRows();
			
			$dbw->commit();
			
			return $updated;
		
	}
	
		/**
	 *
	 * @param WpSubscription $subscription
	 * @return string MySQL DATETIME string format 
	 */
	public static function calculateNextDateExpiresFromSubscription($subscription) {
		
		// contains the needed day/hour/minute/second
		$end = date_create_from_format( 'Y-m-d H:i:s', $subscription->getEnd(), new DateTimeZone( 'GMT' ) );
		
		$now = new DateTime( 'now', new DateTimeZone( 'GMT' ) );
		
		$expire = date_create_from_format( 'Y-m-d H:i:s', 
				$now->format( 'Y-m-' ) . $end->format( 'd H:i:s' ) ,
				new DateTimeZone( 'GMT' ) );
		
		if ( $expire < $now ) {
			$expire->modify('+1 month');
		}
		
		return $expire->format( 'Y-m-d H:i:s' );
		
	}
	
	/**
	 * Check if this Wikiplace name in db_key form is blacklisted
	 * @global array $wgWikiplaceNameBlacklist
	 * @param string $name
	 * @return boolean 
	 */
	public static function isBlacklistedWikiplaceName($name) {
        global $wgWikiplaceNameBlacklist;
        return in_array( strtolower($name), $wgWikiplaceNameBlacklist );
    }
	
	/**
	 * Parse the db_key depending on the namespace and return an array of all elements. Work with both homepages and subpages db_key.
	 * Note that this function can return a Wikiplace name even if the page doesn't not already 
	 * belongs to ( = newly created page ) or even if the Wikiplace doesn't already exists.
	 * @param string $db_key should be $wikipage->getTitle()->getDBkey()
	 * @param int $namespace should be $wikipage->getTitle()->getNamespace()
	 * @return Array The array of all elements in the name (array has at least 1 string at index 0)
	 */
	public static function explodeWikipageKey($db_key, $namespace) {
		
		$hierarchy;
		
		switch ( $namespace ) {
			case NS_FILE:
			case NS_FILE_TALK:
				$hierarchy = explode( '.', $db_key );
				break;
				
			default:
				$hierarchy = explode( '/', $db_key );
				break;
		}
		
		if (!isset($hierarchy[0]) || !WpPage::isInWikiplaceNamespaces($namespace) ) {
            throw new MWException("Cannot split WikipageKey from key=$db_key and ns=$namespace.");
		}

		return $hierarchy;
	}
	
	/**
	 *
	 * @param int $user_id
	 * @return array Array of string 
	 */
	public static function listAvailableFilePrefix($user_id) {
		if ( ($user_id === null) || !is_int($user_id) ) {
			throw new MWException( 'Cannot search Wikiplaces, invalid owner user identifier.' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$tables = array( 'wp_wikiplace', 'page' );
		$vars = array( 'page_title'	);
		$conds = array( 'wpw_owner_user_id' =>  $user_id );
		$fname = __METHOD__;
		$options = array();
		$join_conds = array( 'page' => array('INNER JOIN', 'wpw_home_page_id = page_id')); /** @todo:maybe a left join? */
		
		
		$results = $dbr->select($tables, $vars, $conds, $fname, $options, $join_conds);
		$prefixes = array( WP_PUBLIC_FILE_PREFIX => WP_PUBLIC_FILE_PREFIX );
		foreach ( $results as $row ) {
			$t = Title::newFromDBkey($row->page_title);
			if ( $t != null ) {
				$prefixes[$row->page_title] = $t->getText() ;
			}
		}

		return $prefixes;
	}
	
	
	
	/**
	 * Parse the db_key depending on the namespace, extract the name of the wikiplace
	 * that the page should belong. Work with both homepages and subpages db_key.
	 * Note that this function can return a Wikiplace name even if the page doesn't not already 
	 * belongs to ( = newly created page ) or even if the Wikiplace doesn't already exists.
	 * @param string $db_key should be $wikipage->getTitle()->getDBkey()
	 * @param int $namespace should be $wikipage->getTitle()->getNamespace()
	 * @return String The wikiplace name or null the page doesn't belong to an exsiting Wikiplace
	 */
	public static function extractWikiplaceRoot($db_key, $namespace) {
		
		$hierarchy = self::explodeWikipageKey($db_key, $namespace);
		
		if (!isset($hierarchy[0]) || !WpPage::isInWikiplaceNamespaces($namespace) ) {
            throw new MWException("Cannot extract WpRoot from key=$db_key and ns=$namespace.");
		}

		return $hierarchy[0];
		
	}
	
	/**
	 *
	 * @param int $user_id
	 * @return int Return 0 if the user doesn't exist or doesn't have any wikiplaces
	 */
	public static function countWikiplacesOwnedByUser($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Invalid user identifier.' );
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
	 * Parse the db_key depending on the namespace, extract the name of the wikiplace
	 * that the page should belong, and return the Wikiplace with that name. 
	 * Note that this function can return a Wikiplace even if the page doesn't not already 
	 * belongs to ( = newly created page ), but the Wikiplace already exists.
	 * @param string $db_key should be $wikipage->getTitle()->getDBkey()
	 * @param int $namespace should be $wikipage->getTitle()->getNamespace()
	 * @return WpWikiplace The Wikiplace or null the page doesn't belong to an exsiting Wikiplace
	 */
	public static function getBySubpage($db_key, $namespace) {
		$name = self::extractWikiplaceRoot($db_key, $namespace);
		if ($name == null) {
			return null;
		}
		return self::newFromName($name);
		
	}


}
