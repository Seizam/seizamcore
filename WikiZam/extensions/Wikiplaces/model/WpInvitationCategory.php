<?php

class WpInvitationCategory {
	
	private	$wpic_id,
			$wpic_start_date,
			$wpic_end_date,
			$wpic_desc,
			$wpic_monthly_limit;
	
	private $plans;
	
	private function __construct( $id, $start, $end, $desc, $monthlyLimit ) {
		
		$this->wpic_id = intval($id);
        $this->wpic_start_date = $start;
        $this->wpic_end_date = $end;
        $this->wpic_desc = $desc;
        $this->wpic_monthly_limit = intval($monthlyLimit);
		
		$this->plans = null;
		
	}
	
	 /**
     * Contruct a new instance from a SQL row
     * @param ResultWrapper $row
     * @return WpInvitationCategory 
     */
    public static function constructFromDatabaseRow($row) {

        if ($row === null) {
            throw new MWException('No SQL row.');
        }
        if (!isset($row->wpic_id) 
				|| !isset($row->wpic_start_date)
				|| !isset($row->wpic_end_date)
				|| !isset($row->wpic_desc)
				|| !isset($row->wpic_monthly_limit) ) {
            throw new MWException('Missing field in SQL row.');
        }

        return new self(
				$row->wpic_id,
				$row->wpic_start_date,
				$row->wpic_end_date,
				$row->wpic_desc,
				$row->wpic_monthly_limit);
    }

	/**
	 *
	 * @return int 
	 */
	public function getId() {
		return $this->wpic_id;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function getStartDate() {
		return $this->wpic_start_date;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function getEndDate() {
		return $this->wpic_end_date;
	}
	
	/**
	 *
	 * @return int 
	 */
	public function getMonthlyLimit() {
		return $this->wpic_monthly_limit;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function getDescription() {
		return $this->wpic_desc;
	}
	
	/**
	 *
	 * @return array Array of Plans 
	 */
	public function getPlans() {
		if ($this->plans == null) {
			
			$this->plans = array();
			
			$databaseBase = wfGetDB(DB_SLAVE);
			$results = $databaseBase->select(
				array ( 'wp_wpi_wpp', 'wp_plan'),
				'*',
				array( 'wpip_wpic_id' => $this->wpic_id ),
				__METHOD__,
				array(),
				array( 'wp_plan' => array('INNER JOIN', 'wpip_wpp_id = wpp_id') ) );

			if ($results !== false) {

				foreach ($results as $row) {
					$plan = WpPlan::constructFromDatabaseRow($row);
					if ($plan != null) {
						$this->plans[] = $plan;
					}
				}

			}
		}
		return $this->plans;
	}

	/**
	 *
	 * @param int $id
	 * @return WpInvitationCategory 
	 */
	public static function newFromId($id) {
		return self::newFromConds(array( 'wpic_id' => $id )); 
	}
	
	/**
	 *
	 * @return WpInvitationCategory 
	 */
	public static function newPublicCategory() {
		$dbr = wfGetDB(DB_SLAVE);
		$now = $dbr->addQuotes(WpSubscription::now());
		return self::newFromConds(array(
					'wpic_monthly_limit > 0',
					'wpic_start_date <= ' . $now,
					'wpic_end_date > ' . $now), $dbr);
	}

	/**
	 *
	 * @param array $conds SQL conditions
	 * @param DatabaseBase $databaseBase Optional (default = DB_SLAVE)
	 * @return WpInvitationCategory  
	 */
	private static function newFromConds($conds, $databaseBase = null) {
		if ( $databaseBase == null ) {
			$databaseBase = wfGetDB(DB_SLAVE);
		}
        $results = $databaseBase->select(
				array ( 'wp_invitation_category', 'wp_wpi_wpp', 'wp_plan'),
				'*',
				$conds,
				__METHOD__,
				array(),
				array(
					'wp_wpi_wpp' => array('LEFT JOIN', 'wpic_id = wpip_wpic_id'),
					'wp_plan' => array('LEFT JOIN', 'wpip_wpp_id = wpp_id')) );

        if ($results === false) {
            // not found, so return null
            return null;
        }

        $category = null;
		foreach ($results as $row) {
			
			if ( $category == null ) {
				$category = self::constructFromDatabaseRow($row);
				$category->plans = array();
			} 
				
			if ( $row->wpp_id != null ) {
				$plan = WpPlan::constructFromDatabaseRow($row);
				if ($plan != null) {
					$category->plans[] = $plan;
				}
			} 

		}
		return $category;
	}
		
	/**
	 *
	 * @return WpInvitationCategory Array of WpInvitationCategory (indexes are categories identifiers)
	 */
	public static function factoryAllAvailable($inldudeAdmin = false) {
		$dbr = wfGetDB(DB_SLAVE);
		$now = $dbr->addQuotes(WpSubscription::now());
		
		$conds = array(
					'wpic_start_date <= '.$now,
					'wpic_end_date >= '.$now );
		if ( ! $inldudeAdmin ) {
			$conds[] = 'wpic_monthly_limit > 0';
		}
		
        $results = $dbr->select(
				array ( 'wp_invitation_category', 'wp_wpi_wpp', 'wp_plan'),
				'*',
				$conds,
				__METHOD__,
				array('ORDER BY' => 'wpic_id'),
				array(
					'wp_wpi_wpp' => array('LEFT JOIN', 'wpic_id = wpip_wpic_id'),
					'wp_plan' => array('LEFT JOIN', 'wpip_wpp_id = wpp_id')) );
		
		return self::factoryFromResults($results);
	}
	
	/**
	 *
	 * @param ResultWrapper $results Results from SQL select
	 * @param DatabaseBase $databaseBase Optoional, default = wfGetDB(DB_SLAVE)
	 * @return array Array of WpInvitationCategory (indexes are categories identifiers)
	 */
	private static function factoryFromResults($results) {
		
		if ( ! $results instanceof ResultWrapper) {
			return array();
		}

        $categories = array();
		$category = null;
        foreach ($results as $row) {
			
			if ( $category == null ) {
				$category = self::constructFromDatabaseRow($row);
				
			} elseif( $category->getId() != $row->wpic_id) {
				$categories[$category->getId()] = $category;
				$category = self::constructFromDatabaseRow($row);
				$category->plans = array();
			}
				
			if ( $row->wpp_id != null ) {
				$plan = WpPlan::constructFromDatabaseRow($row);
				if ($plan != null) {
					$category->plans[] = $plan;
				}
			} 

		}
		if ( $category != null ) {
			$categories[$category->getId()] = $category;
		}

        return $categories;
	}
	
}