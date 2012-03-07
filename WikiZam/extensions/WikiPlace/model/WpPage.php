<?php

class WpPage {  

	private		$id,			//`wppa_id` int(10) unsigned NOT NULL AUTO_INCREMENT 
				$wikiplaceId,	//`wppa_wpw_id` int(10) unsigned
				$pageId ;		//`wppa_page_id` int(10) unsigned
	
	
	private function __construct( $id, $wikiplaceId, $pageId ) {

		$this->id			= $id;
		$this->wikiplaceId	= $wikiplaceId;
		$this->pageId		= $pageId;

	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getWikiplaceId() {
		return $this->wikiplaceId;
	}
	
	public function getPageId() {
		return $this->pageId;
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
			
		return new self ( intval($row->wppa_id) , intval($row->wppa_wpw_id) ,  intval($row->wppa_page_id) );
		
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
	 * @param int $ownerUserId
	 * @param string $name
	 * @return WpWikiplace the newly created wikiplace or null if an error occured 
	 */
	public static function create($wikiplaceId, $pageId) {
		if ( ($wikiplaceId === null) || ($pageId === null) ) {
			throw new MWException( 'Cannot create WikiPlace page (missing argument)' );
		}
		
		if ( !is_int($wikiplaceId) || !is_int($pageId) ) {
			throw new MWException( 'Cannot create WikiPlace page(invalid argument)' );
		}
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wppa_id');
		
        $success = $dbw->insert('wp_page', array(
			'wppa_id'				=> $id,
			'wppa_wpw_id'			=> $wikiplaceId,
			'wppa_page_id'			=> $pageId,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}		
				
		return new self( $id, $wikiplaceId, $pageId );
			
	}
	
	
}