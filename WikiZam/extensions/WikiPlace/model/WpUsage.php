<?php

class WpUsage {  
		  
	private		$wpu_id,
				$wpu_wps_id,
				$wpu_wpw_id,
				$wpu_start_date,
				$wpu_end_date,
				$wpu_active,
				$wpu_monthly_page_hits, // MySql: bigint(64 bits) =>  PHP float 
				$wpu_monthly_bandwidth, // MySql: bigint(64 bits) =>  PHP float 
				$wpu_updated;

	private $subscription;
	private $wikiplace; 
	
	private $attributes_to_update;
		

	private function __construct(
			$id, $subscriptionId, $wikiplaceId,
			$startDate, $endDate, $active,
			$monthlyPageHits, $monthlyBandwidth, $updated) {
		
		$this->wpu_id = $id;			
		$this->wpu_wps_id = $subscriptionId;				
		$this->wpu_wpw_id = $wikiplaceId;
		$this->wpu_start_date = $startDate;
		$this->wpu_end_date = $endDate;
		$this->wpu_active = $active;
		$this->wpu_monthly_page_hits = $monthlyPageHits;
		$this->wpu_monthly_bandwidth = $monthlyBandwidth;
		$this->wpu_updated = $updated;
		
		$this->attributes_to_update = array();

	}
	
	/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wpu_id':
			case 'wpu_wps_id':
			case 'wpu_wpw_id':
				return intval($this->$attribut_name);
				break;
			case 'wpu_active':
				return ($this->$attribut_name !== '0');
				break;
			case 'wpu_start_date':
			case 'wpu_end_date':
				return $this->$attribut_name;
			case 'wpu_monthly_page_hits':
			case 'wpu_monthly_bandwidth':
			case 'wpu_updated':
				// update if necessary
				if ( $this->wpu_updated < WpPlan::getNow(0,-5,0) ) { // 5 min
					$this->updateCounters();
				}
				return $this->$attribut_name;
				break;
			case 'subscription':
				if ($this->subscription === null) {
					$this->fetchSubscription();
				}
				return $this->subscription;
			case 'wikiplace':
				if ($this->wikiplace === null) {
					$this->fetchWikiplace();
				}
				return $this->wikiplace;
				break;
		}
		throw new MWException('Unknown attribut '.$attribut_name);
	}
	
	
	private function updateCounters() {
	
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		
		$result = $dbr->selectRow( 
				array( 'wp_page' , 'page' ),
				array( 'SUM(page_counter) AS hits'),
				array( 'wppa_wpw_id' => $this->wpu_wpw_id ),
				__METHOD__,
				array(),
				array( 'page' => array('INNER JOIN','wppa_page_id = page_id') ) );
		
		if ( $result === false ) {
			throw new MWException('Error while computing page_hits.');
		}
		
		$monthly_page_hits = $result->hits;
		/** @todo compute bandwidth usage */ 
		$monthly_bandwidth = '0'; // for the moment, not computed
		$updated = WpPlan::getNow();
		
		wfDebugLog( 'wikiplace', 'WpUsage->updateCounters for wpw_id='.$this->wpu_wpw_id.' from '.
				$this->wpu_updated.'('.$this->wpu_monthly_page_hits.') to '.
				$updated.'('.$monthly_page_hits.')');

		$success = $dbw->update(
				'wp_usage',
				array(
					'wpu_monthly_page_hits' => $monthly_page_hits,
					'wpu_monthly_bandwidth' => $monthly_bandwidth,
					'wpu_updated' => $updated,
				),
				array( 'wpu_id' => $this->wpu_id) );

		$dbw->commit();

		if ( !$success ) {	
			throw new MWException('Error while saving Usage report to database.');
		}
		
		$this->wpu_monthly_page_hits = $monthly_page_hits;
		$this->wpu_monthly_bandwidth = $monthly_bandwidth;
		$this->wpu_updated = $updated;
	}
	
	/**
	 * Should be called by cron. Its execution can take more more more than 30 seconds.
	 * For the moment, it only updates the page hits counter.
	 * @return int Nb of updates
	 * @todo updates also the bandwith counter
	 */
	public static function updateAllOutdatedCounters() {
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
		$now = $dbw->addQuotes(WpPlan::getNow());
		$one_hour_ago = $dbw->addQuotes(WpPlan::getNow(0,0,-1)); // 1 hour
		
		$sql = "
CREATE TEMPORARY TABLE wp_tmp_page_hits (
  SELECT wppa_wpw_id AS wikiplace_id, SUM(page.page_counter) AS page_hits
  FROM page
  INNER JOIN wp_page
  ON wppa_page_id = page_id
  INNER JOIN wp_usage
  ON wpu_wpw_id = wppa_wpw_id
  WHERE wpu_updated < $one_hour_ago
  GROUP BY wppa_wpw_id ) ;";
		
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result !== true) {
			throw new MWException('Error while computing usage counters');
		}
		
		$to_update = $dbw->affectedRows();

		$sql = "
UPDATE wp_usage
SET wpu_monthly_page_hits = (
  SELECT page_hits
  FROM wp_tmp_page_hits
  WHERE wikiplace_id = wpu_wpw_id ) ,
wpu_updated = $now 
WHERE wpu_updated < $one_hour_ago ;" ;
				
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result !== true) {
			throw new MWException('Error while updating outdated usage counters');
		}
		
		$updated = $dbw->affectedRows();
		
		$dbw->commit();
		
		if ($to_update != $updated) {
			throw new MWException('Usage counters updated, but '.$to_update.' updates expected and '.$updated.' records updated.');
		}
		
		return $updated;
		
	}
	
	/**
	 * Warning, after increment, the attribut value is not valid!
	 */
/*	public function incremementPageHit() {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		$success = $dbw->update(
				'wp_usage',
				'wpu_monthly_page_hits = wpu_monthly_page_hits + 1',
				array( 'wpu_id' => $this->wpu_id) );

		$dbw->commit();
		
		if ( !$success ) {	
			throw new MWException('Error while updating page hits counter.');
		}
		
	}
*/	
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
			throw new MWException('Error while updating bandwidth counter.');
		}
	
	}
*/	
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
	
	private function fetchWikiplace($databaseRow = null) {
		
		if ($databaseRow !== null) {
			if ($databaseRow->wpw_id != $this->wpu_wpw_id) {
				throw new MWException('The given wikiplace does not match with the current usage.');
			}
			$this->wikiplace = WpWikiplace::constructFromDatabaseRow($databaseRow);
			
		} else {
			$this->wikiplace = WpWikiplace::getById($this->wpu_wpw_id);
		}
		
		if ($this->wikiplace === null) {
			throw new MWException('Unknown wikiplace');
		} 

	}
	
	/**
	 * Get the WpPlan instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	private static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Usage from the supplied row (null given)' );
		}

		if ( !isset($row->wpu_id) || !isset($row->wpu_wps_id) || !isset($row->wpu_wpw_id) ||
				!isset($row->wpu_start_date) || !isset($row->wpu_end_date) || !isset($row->wpu_active) ||
				!isset($row->wpu_monthly_page_hits) || !isset($row->wpu_monthly_bandwidth) ||
				!isset($row->wpu_updated) ) {
			throw new MWException( 'Cannot construct the Usage from the supplied row (missing field)' );
		}
			
		return new self ( $row->wpu_id, $row->wpu_wps_id, $row->wpu_wpw_id, $row->wpu_start_date, $row->wpu_end_date, $row->wpu_active, $row->wpu_monthly_page_hits, $row->wpu_monthly_bandwidth, $row->wpu_updated );
		  
	}
	
	
	public static function getById($id) {
				
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fetch Usage matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_usage', '*',	array( 'wpu_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	
	/**
	 *
	 * @param type $id
	 * @return WpUsage can be null if the wikiplace doesn't has an active subscription or if called while generating new reports, or if the wikiplace doesn't have active subscription 
	 */
	public static function getActiveByWikiplaceId($id) {
		if ( ($id === null) || !is_numeric($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch Usage matching the wikiplace identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_usage', '*',	array( 'wpu_wpw_id' =>  $id, 'wpu_active' => 1 ), __METHOD__ );
		
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
		
		wfDebugLog( 'wikiplace', 'WpUsage::getAll WARNING $are_you_sure='.$$are_you_sure);
		
		if ( $are_you_sure != 'I know what i am doing')
			return array(); //good idea :)
		
		$dbr = wfGetDB(DB_SLAVE);
		$results = $dbr->select( 'wp_usage', '*', '1', __METHOD__ );
		
		$subs = array();
		foreach ( $results as $row ) {
			$subs[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $results );
		
		return $subs;

	}
	
	
	/**
	 *
	 * @param WpSubscription $subscription
	 * @param string $date default = null = summarize the active usage reports, not null = summarize all usage reports actives at the date
	 * @return array monthly_page_hits, monthly_bandwidth 
	 */
	public static function getUsagesCountersSummary($subscription, $date = null) {
		throw new MWException('todo');
	}
	
	
	/**
	 * Renew all outdated active reports
	 */
	public static function generateNewMonthlyUsages() {
		
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin();
			
			$now =  $dbw->addQuotes( WpPlan::getNow() );
			
			// generate new reports when active subscription found

			// archiving current, but with active = 0

			// 3rd arg : must be an associative array of the form
			// array( 'dest1' => 'source1', ...). Source items may be literals
			// rather than field names, but strings should be quoted with
			// DatabaseBase::addQuotes()
			$success = $dbw->insertSelect( 'wp_usage', 'wp_usage',
				array(
					'wpu_wps_id'            => 'wpu_wps_id',
					'wpu_wpw_id'            => 'wpu_wpw_id',
					'wpu_start_date'        => 'wpu_start_date',
					'wpu_end_date'          => 'wpu_end_date',
					'wpu_active'            => 0,
					'wpu_monthly_page_hits' => 'wpu_monthly_page_hits',
					'wpu_monthly_bandwidth' => 'wpu_monthly_bandwidth',
					'wpu_updated'           => 'wpu_updated',
				),
				array( 'wpu_active' => 1, 'wpu_end_date < '.$now ),
				__METHOD__,
				array( 'IGNORE' )
			);
			
			// renewing all active outdated records
			$success = $dbw->update(
					'wp_usage',
					array(
						'wpu_start_date = DATE_ADD(wpu_end_date,INTERVAL 1 SECOND)',
						'wpu_end_date = DATE_ADD(wpu_end_date,INTERVAL 1 MONTH)',
						'wpu_monthly_page_hits' => 0,
						'wpu_monthly_bandwidth' => 0,
					),
					array( 'wpu_active' => 1 , 'wpu_end_date < '.$now ) );
				
			if ( !$success ) {	
				throw new MWException('Error while renewing active outdated usage reports.');
			}
			
			$dbw->commit();
		
	}

	
	/**
	 * When creating a wikiplace using a subscription
	 * @param type $wikiplace
	 * @param WpSubscription $subscription Active subscription, but maybe tmr_status != OK
	 */
	public static function createForNewWikiplace($wikiplace, $subscription) {
		
		if ( ($wikiplace === null) || !($wikiplace instanceof WpWikiplace) ||
				($subscription === null) || !($subscription instanceof WpSubscription) ) {
			throw new MWException('Cannot create usage report, invalid argument.');
		}
		
		if (!$subscription->get('wps_active')) {
			throw new MWException('Cannot create usage report, subscription has to be active.');
		}
		
		$now = WpPlan::getNow();
		
		return self::create(
				$subscription->get('wps_id'),
				$wikiplace->get('wpw_id'),
				$now,
				$subscription->get('wps_next_monthly_tick'),
				true, 0, 0, $now);
		
	}
	
	
	/**
	 * Called when creating a wikiplace, or when renewing reports
	 */
	private static function create( $subscriptionId, $wikiplaceId, $startDate, $endDate, $active, $monthlyPageHits, $monthlyBandwidth, $updated ) {
		
		if ( ($subscriptionId === null) || ($wikiplaceId === null) || ($startDate === null) || ($endDate === null) || ($active === null) || ($monthlyPageHits === null) || ($monthlyBandwidth === null) || ($updated === null) ) {
			throw new MWException( 'Cannot create Usage (missing argument)' );
		}
		
		if ( !is_numeric($subscriptionId) || !is_numeric($wikiplaceId) ||
				( !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $startDate ) ) || 
				( !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $endDate ) ) || 
				!is_bool($active) ||
				( ($monthlyPageHits !== null) && !preg_match( '/^([0-9]{1,20})$/', $monthlyPageHits ) ) || 
				( ($monthlyBandwidth !== null) && !preg_match( '/^([0-9]{1,20})$/', $monthlyBandwidth ) ) || 
				( !preg_match( '/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $updated ) ) ) {
			throw new MWException( 'Cannot create Usage (invalid argument)' );
		}
						
		$dbw = wfGetDB(DB_MASTER) ;
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_usage_wpu_id_seq');
		$now =  WpPlan::getNow() ;
		
        $success = $dbw->insert('wp_usage', array(
			'wpu_id'                => $id,
			'wpu_wps_id'            => $subscriptionId,
			'wpu_wpw_id'            => $wikiplaceId,
			'wpu_start_date'        => $startDate,
			'wpu_end_date'          => $endDate,
			'wpu_active'            => $active,
			'wpu_monthly_page_hits' => $monthlyPageHits,
			'wpu_monthly_bandwidth' => $monthlyBandwidth,
			'wpu_updated'           => $updated,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}		
				
		return new self( $id, $subscriptionId, $wikiplaceId, $startDate, $endDate, $active, $monthlyPageHits, $monthlyBandwidth, $updated );
		
	}
	
	
}