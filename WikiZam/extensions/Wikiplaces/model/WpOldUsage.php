<?php

class WpOldUsage {  
		  
	private		$wpou_id,
				$wpou_wps_id,
				$wpou_wpw_id,
				$wpou_end_date,
				$wpou_monthly_page_hits, // MySql: bigint(64 bits) =>  PHP float 
				$wpou_monthly_bandwidth; // MySql: bigint(64 bits) =>  PHP float 

	private $subscription;
	private $wikiplace; 
	
		

	private function __construct(
			$id, $subscriptionId, $wikiplaceId,
			$endDate,
			$monthlyPageHits, $monthlyBandwidth) {
		
		$this->wpou_id = $id;			
		$this->wpou_wps_id = $subscriptionId;				
		$this->wpou_wpw_id = $wikiplaceId;
		$this->wpou_end_date = $endDate;
		$this->wpou_monthly_page_hits = $monthlyPageHits;
		$this->wpou_monthly_bandwidth = $monthlyBandwidth;

	}
	
	/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wpou_id':
			case 'wpou_wps_id':
			case 'wpou_wpw_id':
				return intval($this->$attribut_name);
				break;
			case 'wpou_end_date':
			case 'wpou_monthly_page_hits':
			case 'wpou_monthly_bandwidth':
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
	
	

	private function fetchSubscription($databaseRow = null) {
		
		if ($databaseRow !== null) {
			if ($databaseRow->wps_id != $this->wpou_wps_id) {
				throw new MWException('The given subscription does not match with the current usage.');
			}
			$this->subscription = WpSubscription::constructFromDatabaseRow($databaseRow);
			
		} else {
			$this->subscription = WpSubscription::getById($this->wpou_wps_id);
		}
		
		if ($this->subscription === null) {
			throw new MWException('Unknown subscription');
		} 

	}
	
	private function fetchWikiplace($databaseRow = null) {
		
		if ($databaseRow !== null) {
			if ($databaseRow->wpw_id != $this->wpou_wpw_id) {
				throw new MWException('The given wikiplace does not match with the current usage.');
			}
			$this->wikiplace = WpWikiplace::constructFromDatabaseRow($databaseRow);
			
		} else {
			$this->wikiplace = WpWikiplace::getById($this->wpou_wpw_id);
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

		if ( !isset($row->wpou_id) || !isset($row->wpou_wps_id) || !isset($row->wpou_wpw_id) ||
				!isset($row->wpou_end_date) || 
				!isset($row->wpou_monthly_page_hits) || !isset($row->wpou_monthly_bandwidth) ) {
			throw new MWException( 'Cannot construct the Usage from the supplied row (missing field)' );
		}
			
		return new self ( $row->wpou_id, $row->wpou_wps_id, $row->wpou_wpw_id,
				$row->wpou_end_date, $row->wpou_monthly_page_hits, $row->wpou_monthly_bandwidth);
		  
	}
	
	
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fetch Usage matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_old_usage', '*',	array( 'wpou_id' =>  $id ), __METHOD__ );
		
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
	public static function getByWikiplaceId($id) {
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch Usage matching the wikiplace identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_old_usage', '*',	array( 'wpou_wpw_id' =>  $id ), __METHOD__ );
		
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
/*	public static function getAll($are_you_sure = 'no') {
		
		wfDebugLog( 'wikiplaces', 'WpUsage::getAll WARNING $are_you_sure='.$$are_you_sure);
		
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
*/
	
	


	
	/**
	 * When creating a wikiplace using a subscription
	 * @param type $wikiplace
	 * @param WpSubscription $subscription Active subscription, but maybe tmr_status != OK
	 */
/*	public static function createFromWikiplace($wikiplace, $subscription) {
		
		if ( ($wikiplace === null) || !($wikiplace instanceof WpWikiplace) ||
				($subscription === null) || !($subscription instanceof WpSubscription) ) {
			throw new MWException('Cannot create usage report, invalid argument.');
		}
		
		if (!$subscription->get('wps_active')) {
			throw new MWException('Cannot create usage report, subscription has to be active.');
		}
		
		$now = WpSubscription::getNow();
		
		return self::create(
				$subscription->get('wps_id'),
				$wikiplace->get('wpw_id'),
				$now,
				$subscription->get('wps_next_monthly_tick'),
				true, 0, 0, $now);
		
	}
*/	
	
	/**
	 * Called when creating a wikiplace, or when renewing reports
	 */
/*	private static function create( $subscriptionId, $wikiplaceId, $startDate, $endDate, $active, $monthlyPageHits, $monthlyBandwidth, $updated ) {
		
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
		$now =  WpSubscription::getNow() ;
		
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
*/	
	
}