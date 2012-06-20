<?php

class WpInvitationCategory {
	
	private	$wpic_id,
			$wpic_start_date,
			$wpic_end_date,
			$wpic_desc,
			$wpic_monthly_limit,
			$wpic_public;
	
	private $plansIds;
	
	private function __construct( $id, $start, $end, $desc, $monthlyLimit, $public ) {
		
		$this->wpic_id = intval($id);
        $this->wpic_start_date = $start;
        $this->wpic_end_date = $end;
        $this->wpic_desc = $desc;
        $this->wpic_monthly_limit = intval($monthlyLimit);
        $this->wpic_public = ( $public !== 0 );
		
		$this->plansIds = null;
		
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
				|| !isset($row->wpic_monthly_limit)
				|| !isset($row->wpic_public) ) {
            throw new MWException('Missing field in SQL row.');
        }

        return new self(
				$row->wpic_id,
				$row->wpic_start_date,
				$row->wpic_end_date,
				$row->wpic_desc,
				$row->wpic_monthly_limit,
				$row->wpic_public );
    }
	
/*
	public static function newFromPlan($plan) {
		
        if (! $plan instanceof WpPlan) {
            throw new MWException('Plan required.');
        }

        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow(
				array ( 'wp_invitation_category', 'wp_wpi_wpp'),
				'*',
				array('wpip_wpp_id' => $plan->getId()),
				__METHOD__,
				array(),
				array('wp_wpi_wpp' => array('INNER JOIN', 'wpic_id = wpip_wpic_id')) );

        if ($result === false) {
            // not found, so return null
            return null;
        }

        return self::constructFromDatabaseRow($result);
	}
 */
	
	public static function newPublicCategory() {
		$dbr = wfGetDB(DB_SLAVE);
		$now = $dbr->addQuotes(WpSubscription::now());
        $results = $dbr->select(
				array ( 'wp_invitation_category', 'wp_wpi_wpp'),
				'*',
				array(
					'wpic_public' => 1,
					'wpic_start_date <= '.$now,
					'wpic_end_date > '.$now),
				__METHOD__,
				array(),
				array('wp_wpi_wpp' => array('LEFT JOIN', 'wpic_id = wpip_wpic_id')) );

        if ($results === false) {
            // not found, so return null
            return null;
        }

        $category = null;
		foreach ($results as $row) {
			
			if ( $category == null ) {
				$category = self::constructFromDatabaseRow($row);
				$category->plansIds = array();
				
			} 
				
			if ( $row->wpip_wpp_id != null ) {
				$category->plansIds[] = $row->wpip_wpp_id;
			} 

		}
		return $category;
	}
	
	public static function factoryAdminCategories() {
		$dbr = wfGetDB(DB_SLAVE);
		$now = $dbr->addQuotes(WpSubscription::now());
        $results = $dbr->select(
				array ( 'wp_invitation_category', 'wp_wpi_wpp'),
				'*',
				array(
					'wpic_public' => 0,
					'wpic_start_date <= '.$now,
					'wpic_end_date > '.$now ),
				__METHOD__,
				array('ORDER BY' => 'wpic_id'),
				array('wp_wpi_wpp' => array('LEFT JOIN', 'wpic_id = wpip_wpic_id')) );

        if ($results === false) {
            // not found, so return null
            return array();
        }

        $categories = array();
		$category = null;
        foreach ($results as $row) {
			
			if ( $category == null ) {
				$category = self::constructFromDatabaseRow($row);
				$category->plansIds = array();
				
			} elseif( $category->getId() != $row->wpic_id) {
				$categories[] = $category;
				$category = self::constructFromDatabaseRow($row);
				$category->plansIds = array();
			}
				
			if ( $row->wpip_wpp_id != null ) {
				$category->plansIds[] = $row->wpip_wpp_id;
			} 

		}
		if ( $category != null ) {
			$categories[] = $category;
		}

        return $categories;
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
	
}