<?php

class WpPage {  

	private	$wppa_id,				// int(10) unsigned NOT NULL AUTO_INCREMENT 
	        $wppa_wpw_id,			// int(10) unsigned
	        $wppa_page_id;			// int(10) unsigned
				
	private $page_namespace;	// int(11)
	private $page_title;
	private $wikiplace;
	
	private static $title_being_created;
	private static $wikiplace_of_title_being_created;
	
	/**
	 * Checks that the page doesn't exist
	 * @param type $name without "wikiplace/" in
	 * @param type $allData
	 * @return type 
	 */
	public static function validateNewWikiplaceSubPageName($name, $allData) {

		if ( !isset($allData['WikiplaceId']) || !preg_match('/^[0-9]{1,10}$/',$allData['WikiplaceId']) ) {
			return wfMessage( 'wikiplace-validate-error-wikiplacepagename' )->text();
		}
		
        if ( !is_string($name) || !preg_match('/^[a-zA-Z0-9]{3,16}$/',$name) ) {
			return wfMessage( 'wikiplace-validate-error-wikiplacepagename' )->text() ;
		}
		
		return ( ( self::getSubPageByNameInWikiplaceId($name, intval($allData['WikiplaceId']) ) === null ) ?
			true :
			wfMessage( 'wikiplace-validate-error-wikiplacepagename' )->text() ) ;
		
	}
	
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
			throw new MWException( 'Cannot construct the WikiPlace page from the supplied row (null given)' );
		}
		
		if ( !isset($row->wppa_id) || !isset($row->wppa_wpw_id) || !isset($row->wppa_page_id) ) {
			throw new MWException( 'Cannot construct the WikiPlace page from the supplied row (missing field)' );
		}
			
		return new self ( intval($row->wppa_id) , intval($row->wppa_wpw_id) ,  intval($row->wppa_page_id) , intval($row->wppa_page_namespace) );
		
	}
	
	/**
	 * Search a sub page
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getSubPageByNameInWikiplaceId($subpage_name, $wikiplace_id) {
				
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
				array( 'page_title' =>  $subpage_name , 'page_namespace' => NS_MAIN , 'wppa_wpw_id' => $wikiplace_id ),
				__METHOD__,
				array(),
				array( 'page' => array('INNER JOIN','wppa_page_id = page_id') ) );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	
	
		/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot fectch WikiPlace page matching the identifier (invalid identifier)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_page', '*',	array( 'wppa_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}

		/**
	 *
	 * @param WpWikiplace $wikiplace
	 * @param string $new_page_name If null, the new page is the home page ( 'wikiplace_name' )
	 * @return Mixed Title if creation seems to be ok, but a problem can occurs in hook
		 * 1 if creation failed, but error not known
		 * 2 if creation failed because the title already exist 
	 */
	public static function createPage($wikiplace, $new_page_name = null ) {
		
		if ( ($wikiplace == null) || !($wikiplace instanceof WpWikiplace) || ( $new_page_name!==null && !is_string($new_page_name) ) ) {
			throw new MWException( 'Cannot create WikiPlace page (wrong argument)' );
		}
		
		$title = Title::newFromText( $wikiplace->get('wpw_name') . ( ($new_page_name !== null) ? '/'.$new_page_name : '' ) );
		
		if (!($title instanceof Title)) {
			// not good syntax, but this case should not occurs because the validate passes
			return 1;
		}
		
		if ($title->isKnown()) {
			return 2;		
		}
		
		self::$title_being_created = $title;
		self::$wikiplace_of_title_being_created = $wikiplace;
		
		// now store the new page in mediawiki, this will trigger the WikiplaceHook, wich will 
		// allow the page saving
		$article = new Article($title);
		$article->doEdit('Welcome to Seizam! This is the home page of your wikiPlace.', '',EDIT_NEW);
		
		return $title;
	}
	
	/**
	 *
	 * @param Title $title
	 * @return boolean 
	 */
	public static function isItAWikiplacePage($title) {
		if ( ($title === null) || !($title instanceof Title)) {
			throw new MWException( 'wrong title argument' );
		}
		return $title->getNamespace() == NS_MAIN;
	}
	
	/**
	 *
	 * @param Title $title
	 * @return boolean 
	 */
	public static function canThisWikiplacePageBeingCreated($title) {
		if ( ($title === null) || !($title instanceof Title)) {
			throw new MWException( 'wrong title argument' );
		}
		if (self::$title_being_created === null) {
			return false;
		}
		return $title->getFullText() == self::$title_being_created->getFullText();
	}
	
	/**
	 *
	 * @param Title $title
	 * @return type 
	 */
	public static function continueCreation($title) {
		
		if ( ($title === null) || !($title instanceof Title)) {
			throw new MWException( 'Cannot create WikiPlace page (wrong title argument)' );
		}
		
		if ( (self::$title_being_created == null) || (self::$title_being_created->getFullText() != $title->getFullText()) ) {
			throw new MWException( 'Cannot create WikiPlace page (not created using the Special:WikiPlace form)' );
		}
		
		// now store the new page in our extension
		return self::create(self::$wikiplace_of_title_being_created, $title);
		
	}
	
	
	
	/**
	 *
	 * @param Wikiplace $wikiplace
	 * @param Title $page_title
	 * @return WpPage the newly created page or null if an error occured 
	 */
	private static function create($wikiplace, $page_title) {
		if ( ($wikiplace === null) || ($page_title === null) ) {
			throw new MWException( 'Cannot create WikiPlace page (missing argument)' );
		}
		
		if ( !($wikiplace instanceof WpWikiplace) || !($page_title instanceof Title) ) {
			throw new MWException( 'Cannot create WikiPlace page(invalid argument)' );
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
	
	
}