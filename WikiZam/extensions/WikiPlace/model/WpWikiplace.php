<?php

class WpWikiplace {  

	private		$wpw_id,            // int(10) unsigned
				$wpw_owner_user_id, // int(10) unsigned
				$wpw_home_page_id;  // int(10) unsigned
	
	private $name;
	
	/**
	 *
	 * @global type $wgUser
	 * @param type $id
	 * @param type $allData
	 * @return boolean True = well formed, exists, and belongs to current user 
	 */
	public static function validateExistingWikiplaceIDOfCurrentUser($id, $allData) {
        if ( !is_string($id) || !preg_match('/^[1-9]{1}[0-9]{0,9}$/',$id) ) {
			// not well formed
			return wfMessage( 'wp-vlderr-exwpid-format' )->text() ;
		}
		
		$wikiplace = self::getById(intval($id));
		
		if ($wikiplace === null) {
			// doesn't exist
			return wfMessage( 'wp-vlderr-exwpid-notex' )->text() ;
		}
		
		global $wgUser;
		if ($wikiplace->get('wpw_owner_user_id') != $wgUser->getId()) {
			// doesn't belong to current user
			return wfMessage( 'wp-vlderr-exwpid-usernotowner' )->text() ;
		}
			
		return true; // well formed, exists, and belongs to current user
		
	}
	
	
	/**
	 * check that the WikiPlace doesn't already exist
	 * @param type $name
	 * @param type $allData
	 * @return type 
	 */
	public static function validateNewWikiplaceName($name, $allData) {
        if ( !is_string($name) || !preg_match('/^[a-zA-Z0-9]{3,16}$/',$name) ) {
			return wfMessage( 'wp-vlderr-nwpname-format' )->text() ;
		}
		
		$wp = self::getByName($name);
		
		return ( $wp === null ?
			true :
			wfMessage( 'wp-vlderr-nwpname-dup' )->text() ) ;
	}
	

	

	
	
	private function __construct( $id, $ownerUserId, $homePageId ) {

		$this->wpw_id            = $id;
		$this->wpw_owner_user_id = $ownerUserId;
		$this->wpw_home_page_id  = $homePageId;

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
				return intval($this->$attribut_name);
				break;
			case 'name':
				if ($this->name === null) {
					$this->fetchName();
				}
				return $this->name;
		}
		throw new MWException('Unknown attribut '.$attribut_name);
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
			throw new MWException( 'Cannot construct the WikiPlace from the supplied row (null given)' );
		}
		
		if ( !isset($row->wpw_id) || !isset($row->wpw_owner_user_id) || !isset($row->wpw_home_page_id) ) {
			throw new MWException( 'Cannot construct the WikiPlace from the supplied row (missing field)' );
		}
			
		return new self ( intval($row->wpw_id) , intval($row->wpw_owner_user_id) ,  intval($row->wpw_home_page_id) );
		
	}
	
	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch WikiPlace matching the identifier (invalid identifier)' );
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
		$vars = array( 'wpw_id', 'wpw_owner_user_id', 'wpw_home_page_id', 'page_title');
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
			throw new MWException( 'Cannot fectch WikiPlace matching the name (invalid string)' );
		}
		
		return self::getFromDb(array('page_title' => $name));

	}
	

	/**
	 * 
	 * 
	 * @param int $user_id
	 * @return array of WpWikiplaces ("array()" if no wikiplaces)
	 */
	public static function getAllOwnedByUserId($user_id) {
		
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fetch WikiPlaces owned by the specified user (invalid user identifier)' );
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
	public static function identifyContainerWikiPlaceOfThisNewTitle($title) {
		
		if (!WpPage::isThisPageInTheWikiplaceDomain($title)) {
			return null; // not in wikiplace
		}

		$pages = explode( '/', $title->getPrefixedDBkey() );
		
		if (!isset($pages[0])) {
			//this case should never occurs.. but just in case
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
		return WpPage::createWikiPlaceHomePage($name);
		
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
			throw new MWException( 'Cannot create WikiPlace (missing argument)' );
		}
		
		if ( !($homepage instanceof Title) || !is_int($user_id) ) {
			throw new MWException( 'Cannot create WikiPlace (invalid argument)' );
		}
		
		$subscription = WpSubscription::getActiveByUserId($user_id);
		
		if (!$subscription->get('wps_active')) {
			return 1;
		}
		
		$homepageId = $homepage->getArticleID();
		$homepageName = $homepage->getDBkey();
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wpw_id');
		
        $success = $dbw->insert('wp_wikiplace', array(
			'wpw_id'            => $id,
			'wpw_owner_user_id' => $user_id,
			'wpw_home_page_id'  => $homepageId,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return 3;
		}
		
		$wp = new self( $id, $user_id, $homepageId );
		$wp->fetchName(null, $homepage);
		
		WpUsage::createForNewWikiplace($wp, $subscription);
		
		$new_wp_page = WpPage::associateAPageToAWikiplace($homepage, $wp);
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
	public static function doesTheUserCanCreateANewWikiplace($user_id) {
		
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
	
	
	
}
