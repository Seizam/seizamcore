<?php

class WpWikiplace {  

	private		$wpw_id,			// int(10) unsigned
				$wpw_owner_user_id,	// int(10) unsigned
				$wpw_name ;			// varbinary(255)
	
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
	

	

	
	
	private function __construct( $id, $ownerUserId, $name ) {

		$this->wpw_id				= $id;
		$this->wpw_owner_user_id	= $ownerUserId;
		$this->wpw_name				= $name;

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
				return intval($this->$attribut_name);
				break;
			case 'wpw_name':
				return $this->$attribut_name;
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
			throw new MWException( 'Cannot construct the WikiPlace from the supplied row (null given)' );
		}
		
		if ( !isset($row->wpw_id) || !isset($row->wpw_owner_user_id) || !isset($row->wpw_name) ) {
			throw new MWException( 'Cannot construct the WikiPlace from the supplied row (missing field)' );
		}
			
		return new self ( intval($row->wpw_id) , intval($row->wpw_owner_user_id) ,  strval($row->wpw_name) );
		
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
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_wikiplace', '*',	array( 'wpw_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}
	
	/**
	 *
	 * @param String $name
	 * @return WpWikiplace 
	 */
	public static function getByName($name) {
				
		if ( ($name === null) || !is_string($name) ) {
			throw new MWException( 'Cannot fectch WikiPlace matching the name (invalid string)' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_wikiplace', '*',	array( 'wpw_name' =>  $name ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

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
		
		$dbr = wfGetDB(DB_SLAVE);
		$results = $dbr->select( 'wp_wikiplace', '*',	array( 'wpw_owner_user_id' =>  $user_id ), __METHOD__ );
		
		$wikiplaces = array();
		
		foreach ( $results as $row ) {
			$wikiplaces[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $results );
		
		return $wikiplaces;

	}
	
	/**
	 *
	 * @param int $ownerUserId
	 * @param string $name
	 * @return WpWikiplace the newly created wikiplace or null if an error occured 
	 */
	public static function create($ownerUserId, $name) {
		
		if ( ($ownerUserId === null) || ($name === null) ) {
			throw new MWException( 'Cannot create WikiPlace (missing argument)' );
		}
		
		if ( !is_int($ownerUserId) || !is_string($name) ) {
			throw new MWException( 'Cannot create WikiPlace (invalid argument)' );
		}
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wpw_id');
		
        $success = $dbw->insert('wp_wikiplace', array(
			'wpw_id'			=> $id,
			'wpw_owner_user_id'	=> $ownerUserId,
			'wpw_name'			=> $name,
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {	
			return null;
		}
		
		$wp = new self( $id, $ownerUserId, $name );
		
		WpPage::createPage($wp);
				
		return $wp;
			
	}
	
	
}