<?php

class WpPage {  

	private	$wppa_id,				// int(10) unsigned NOT NULL AUTO_INCREMENT 
	        $wppa_wpw_id,			// int(10) unsigned
	        $wppa_page_id;			// int(10) unsigned
				
	private $page_namespace;	// int(11)
	private $page_title;
	private $wikiplace;
		
	private function __construct( $id, $wikiplaceId, $pageId ) {

		$this->wppa_id      = $id;
		$this->wppa_wpw_id  = $wikiplaceId;
		$this->wppa_page_id = $pageId;

	}
	
	private function fetchWikiplace($databaseRow = null) {
		
		if ($databaseRow !== null) {
			
			$wikiplace = WpWikiplace::constructFromDatabaseRow($databaseRow);
			
			if ($this->wikiplace->get('wpw_id') != $this->wppa_wpw_id) {
				throw new MWException('The wikiplace given is not the one attached to the page.');
				
			} else {
				$this->wikiplace = $wikiplace;
			}
			
		} else {
			
			$this->wikiplace = WpWikiplace::getById($this->wppa_wpw_id);
			
			if ($this->wikiplace === null) {
				// there is a big problem... the page belongs to nothing!
				throw new MWException('Unknown wikiplace');
			} 
		}
		
	}
	
	/**
	 * Fetch the page from the Title, or from the database row, or by reading db internally
	 * @param type $databaseRow
	 * @param Title $title 
	 */
	private function fetchPage($databaseRow = null, $title = null) {
		
		if ($title != null) {
			if (!($title instanceof Title)) {
				throw new MWException('The given Title argument is not a valid Title object.');
			}
			$this->page_namespace = $title->getNamespace();	
			$this->page_title     = $title->getText();
		}
		
		if ($databaseRow === null) {
			
			$dbr = wfGetDB(DB_SLAVE);
			$result = $dbr->selectRow( 'page', array('page_id', 'page_namespace','page_title'),	array( 'page_id' =>  $this->wppa_page_id ), __METHOD__ );
			if ( $result === false ) {
				throw new MWException('Page not found.');
			} 
			$databaseRow = $result;
			
		} else {
			
			if ( !isset($databaseRow->page_id) || !isset($databaseRow->page_title) || !isset($databaseRow->page_namespace) ) {
				throw new MWException('Invalid argument, missing page field.');
			} 
			if ($databaseRow->page_id != $this->wppa_page_id) {
				throw new MWException('The given page does not match with the current record.');
			} 
			$this->page_namespace = $databaseRow->page_namespace;	
			$this->page_title     = $databaseRow->page_title;
			
		} 

	}
	
		/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wppa_id':
			case 'wppa_wpw_id':
			case 'wppa_page_id':
				return intval($this->$attribut_name);
				break;
			case 'page_namespace':
			case 'page_title':
				if ($this->$attribut_name === null) {
					$this->fetchPage();
				}
				return $this->$attribut_name;
				break;
		}
		throw new MWException('Unknown attribut '.$attribut_name);
	}
	
	
	/**
	 * Get the Wikiplace instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	private static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the Wikiplace page from the supplied row (null given)' );
		}
		
		if ( !isset($row->wppa_id) || !isset($row->wppa_wpw_id) || !isset($row->wppa_page_id) ) {
			throw new MWException( 'Cannot construct the Wikiplace page from the supplied row (missing field)' );
		}
			
		return new self ( intval($row->wppa_id) , intval($row->wppa_wpw_id) ,  intval($row->wppa_page_id) );
		
	}
	
	
	/*
	public static function getByNameInWikiplace($subpage_name, $wikiplace_id) {
				
		if ( ($wikiplace_id === null) || !is_int($wikiplace_id) || ($wikiplace_id < 1) ) {
			throw new MWException( 'Invalid wikiplace identifier' );
		}
		if ( ($subpage_name === null) || !is_string($subpage_name) ) {
			throw new MWException( 'Invalid subpage name' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
					
		$result = $dbr->selectRow( 
				array ( 'wp_page' , 'page' ),
				'*',
				array( 'page_title' =>  $subpage_name , 'page_namespace' => WP_PAGE_NAMESPACE , 'wppa_wpw_id' => $wikiplace_id ),
				__METHOD__,
				array(),
				array( 'page' => array('INNER JOIN','wppa_page_id = page_id') ) );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	
	*/
	
	/**
	 *
	 * @param type $conds
	 * array( 'page_title' =>  $subpage_name , 'page_namespace' => WP_PAGE_NAMESPACE , 'wppa_wpw_id' => $wikiplace_id ),
	 * @param type $multiple
	 * @return type 
	 */
	private static function getFromDb($conds, $multiple = false) {
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$tables = array ( 'wp_page', 'page' );
		$vars = array( 'wppa_id','wppa_wpw_id', 'wppa_page_id',    'page_id', 'page_namespace','page_title');
		$fname = __METHOD__;
		$options = array();
		$join_conds = array( 'page' => array('INNER JOIN','wppa_page_id = page_id') );
		
		if ($multiple) {
			
			$results = $dbr->select($tables, $vars, $conds, $fname, $options, $join_conds);
			$pages = array();
			foreach ( $results as $row ) {
				$page = self::constructFromDatabaseRow($row);
				$page->fetchPage($row);
				$pages[] = $page;
			}
			$dbr->freeResult( $results );
			return $pages;
			
		} else {
			
			$result = $dbr->selectRow($tables, $vars, $conds, $fname, $options, $join_conds);
			if ( $result === false ) {
				// not found, so return null
				return null;
			}
			return self::constructFromDatabaseRow($result);
			
		}
				
	}
	
	
		/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch Wikiplace page matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_page', '*',	array( 'wppa_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}

	
	public static function getByName($name) {
				
		if ( ($name === null) || !is_string($name) ) {
			throw new MWException( 'Cannot fectch Wikiplace page matching the name (invalid argument)' );
		}
		
		return self::getFromDb(array('page_title' => $name));

	}
	
	
	/**
	 * @param string $new_wikiplace_name The new 'wikiplace_name'
	 * @return Title/int The created home page, or
	 * int 1 if creation failed, but error not known
	 * int 2 if creation failed because the title already exist 
	 */
	public static function createHomepage( $new_wikiplace_name = null ) {
		
		if ( ( $new_wikiplace_name!==null && !is_string($new_wikiplace_name) ) ) {
			throw new MWException( 'Cannot create Wikiplace homepage (wrong argument)' );
		}
		
		$title = Title::newFromText( $new_wikiplace_name );
		
		if (!($title instanceof Title)) {
			// not good syntax, but this case should not occurs because the validate passes
			return 1;
		}
		
		if ($title->isKnown()) {
			return 2;		
		}
		
		$text = '{{subst:Wikiplace Root}}';

		// now store the new page in mediawiki, this will trigger the WikiplaceHook, wich will 
		// allow the page saving
		$article = new Article($title);
		$article->doEdit($text, '',EDIT_NEW);
		
		return $title;
		
	}
	
	/**
	 * Create a new wikiplace subpage and return the created Title
	 * @param WpWikiplace $wikiplace
	 * @param string $new_page_name
	 * @return Status When good, the value is the created Title object
	 */
	public static function createSubpage( $wikiplace, $new_page_name ) {
		
		if ( ($wikiplace === null) || !($wikiplace instanceof WpWikiplace) ||
				( $new_page_name === null) || !is_string($new_page_name) ) {
			throw new MWException( 'Cannot create Wikiplace page (wrong argument)' );
		}
		
		$title = Title::newFromText( $wikiplace->get('name') . '/' . $new_page_name );
		
		// $title can be Title, or null on an error.
		
		if (!($title instanceof Title)) {
			$status = Status::newFatal('unknown-error');
			$status->value = 'unknown-error';
			return $status;
		}
		
		if ($title->isKnown()) {
			$status = Status::newFatal('already-exists');
			$status->value = 'already-exists';
			return $status;	
		}
		
		$text = 'This is the default text of a new Wikiplace page.';
		
		// now store the new page in mediawiki, this will trigger the WikiplaceHook, wich will 
		// allow the page saving
		$article = new Article($title);
		$article->doEdit($text, '',EDIT_NEW);
		
		return Status::newGood($title);
		
	}
	
	/**
	 *
	 * @param Title $title
	 * @return boolean true = a wikiplacer home page
	 */
	public static function isHomepage($title) {
		if ( ($title === null) || !($title instanceof Title)) {
			throw new MWException( 'wrong title argument' );
		}
		return ( ($title->getNamespace() == NS_MAIN) && (count(explode( '/', $title->getPrefixedDBkey() )) == 1) );
	}
	
	
	/**
	 * Remove the Wikiplace name from the page name, and return the subpage name with a leading slash
	 * ex: getSubPageNameOnly('Wikiplace1/a_sub_page') returns '/a_sub_page'
	 * ex: getSubPageNameOnly('Wikiplace1') returns '/'
	 * @param string $page_name 
	 * @return string the sub page name
	 */
	public static function getSubpageNamePartOnly($title) {
		if ( ($title === null) || !($title instanceof Title) ) {
			throw new MWException( 'cannot get subpage name part, invalid argument' );
		}
		
		$full_page_name = $title->getPrefixedText();
		
		$tmp;
		$after='';
		
		switch($title->getNamespace()) {

			case NS_FILE_TALK:
				$after = ' (talk)';
			case NS_FILE:
				$tmp = explode( '.', $full_page_name );
				break;
			
			case NS_TALK:
				$after = ' (talk)';
			case NS_MAIN:
				$tmp = explode( '/', $full_page_name );
				break;
			
			default:
				throw new MWException( 'cannot get subpage name part, invalid namespace' );
		}
	
		$len = strlen($tmp[0]);
		if ($len == strlen($full_page_name)) {
			return '/'.$after;
		}
		
		return '/'.substr($full_page_name, $len + 1).$after;
	}
	
		/**
	 * 
	 * @param Title $title
	 * @return boolean
	 */
	public static function isInWikiplaceNamespaces($title) {
		
		if ( ($title === null) || !($title instanceof Title)) {
			throw new MWException( 'wrong title argument' );
		}
		
		return in_array( $title->getNamespace(), array(
			NS_MAIN,
			NS_TALK,
			NS_FILE,
			NS_FILE_TALK,
		));
	}
	


	/**
	 * count all pages owned by user
	 * @param type $user_id
	 * @return int the number of pages 
	 */
	public static function countPagesOwnedByUser($user_id) {
				
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
				array( 
					'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id'),
					'page' => array('INNER JOIN','wppa_page_id = page_id'),
					) );
		
		if ($result === null) {
			return 0;
		}
		
		return intval($result->total);
		
	}

	
	/**
	 * Get current diskpace usage
	 * @param type $user_id
	 * @return int The diskpace usage <b>in MB</b>
	 */
	public static function getDiskspaceUsageByUser($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'invalid user identifier' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 
				array('wp_wikiplace','wp_page','page','image'),
				array(
					'sum(img_size) >> 20  as total'
					),
				array(
					'wpw_owner_user_id' =>  $user_id,
					'page_namespace' => NS_FILE,
					),
				__METHOD__,
				array(),
				array( 
					'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id'),
					'page' => array('INNER JOIN','wppa_page_id = page_id'),
					'image' => array('INNER JOIN','page_title = img_name'),
					) );
		
		if ($result === null) {
			return 0;
		}
		
		return intval($result->total);
		
	}
	
	/**
	 * Check the user has an active subscription and page creation quota is not exceeded
	 * @param type $user_id
	 * @return boolean
	 */
	public static function userCanCreateNewPage($user_id) {
		
		$sub = WpSubscription::getActiveByUserId($user_id);

		if ($sub === null) { 
			return false;
		}

		$max_pages = $sub->get('plan')->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = self::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return false;
		}

		return true;
		
	}
	
	public static function userCanUploadNewFile($user_id) {
		
		$sub = WpSubscription::getActiveByUserId($user_id);

		if ($sub === null) { 
			return false;
		}

		$max_pages = $sub->get('plan')->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = self::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return false;
		}
		
		$max_diskspace = $sub->get('plan')->get('wpp_diskspace');
		$user_diskspace_usage = self::getDiskspaceUsageByUser($user_id);

		if ($user_diskspace_usage >= $max_diskspace) { 
			return false;
		}

		return true;
		
	}
	

	
	/**
	 * Called 
	 * @param Title $title
	 * @param WpWikiplace $wikiplace
	 * @return type 
	 */
	public static function attachNewPageToWikiplace($title, $wikiplace) {
		
		if ( ($title === null) || !($title instanceof Title) || ($wikiplace === null) || !($wikiplace instanceof WpWikiplace) ) {
			throw new MWException( 'Cannot associate page to a Wikiplace (wrong argument)' );
		}
		
		// store the new page in our extension
		return self::create($title, $wikiplace);
		
	}
	
	
	
	/**
	 *
	 * @param Wikiplace $wikiplace
	 * @param Title $page_title
	 * @return WpPage the newly created page or null if an error occured 
	 */
	private static function create($page_title, $wikiplace) {
		if ( ($wikiplace === null) || ($page_title === null) ) {
			throw new MWException( 'Cannot create Wikiplace page (missing argument)' );
		}
		
		if ( !($wikiplace instanceof WpWikiplace) || !($page_title instanceof Title) ) {
			throw new MWException( 'Cannot create Wikiplace page(invalid argument)' );
		}
		
		$pageId	= $page_title->getArticleID();
		$pageNamespace = $page_title->getNamespace();
		$wikiplaceId = $wikiplace->get('wpw_id');
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wppa_id');
		
        $success = $dbw->insert('wp_page', array(
			'wppa_id'             => $id,
			'wppa_wpw_id'         => $wikiplaceId,
			'wppa_page_id'        => $pageId
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}		
				
		$return = new self( $id, $wikiplaceId, $pageId, $pageNamespace );
		$return->fetchPage(null, $page_title);
		
		return $return;
			
	}
	
	/**
	 * Find the user identifier of the wikiplace page owner
	 * @param Title $title
	 * @return boolean/int int the user id, or false if the page is not a wikiplace page
	 */
	public static function findPageOwnerUserId($title) {
		
		if ( ($title == null) || !($title instanceof Title)) {
			throw new MWException('Cannot find the owner, invalid argument.');
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectField( 
				array( 'wp_page', 'wp_wikiplace' ),
				'wpw_owner_user_id',
				array ( 
					'wppa_page_id' => $title->getArticleID(),
					'wppa_wpw_id = wpw_id' ),
				__METHOD__ );
	
		if ( $result === false ) {
			// not found, so return null
			return false;
		}
		
		return intval($result);
		
	}
	
}