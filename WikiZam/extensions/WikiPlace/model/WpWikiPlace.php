<?php

class WpWikiPlace {  

	private		$id,			//`wpw_id` int(10) unsigned
				$ownerUserId,	//`wpw_owner_user_id` int(10) unsigned
				$name ;			//`wpw_name` varbinary(255)
	
	private function __construct( $id, $ownerUserId, $name ) {

		$this->id			= $id;
		$this->ownerUserId	= $ownerUserId;
		$this->name			= $name;

	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getOwnerUserId() {
		return $this->ownerUserId;
	}
	
	/**
	 * Get the WikiPlace instance from a SQL row
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
	 * @return WpWikiPlace 
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
	 * @return array of WpWikiPlaces ("array()" if no wikiplaces)
	 */
	public static function getAllOwnedByUserId($user_id) {
		
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Cannot fetch WikiPlaces owned by the specified user (invalid user identifier)' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select( 'wp_wikiplace', '*',	array( 'wpw_owner_user_id' =>  $user_id ), __METHOD__ );
		
		$wikiplaces = array();
		
		foreach ( $result as $row ) {
			$wikiplaces[] = self::constructFromDatabaseRow($row);
		}
		
		$dbr->freeResult( $result );
		
		return $wikiplaces;

	}
	
	/**
	 *
	 * @param int $ownerUserId
	 * @param string $name
	 * @return WpWikiPlace the newly created wikiplace or null if an error occured 
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
				
		return new self( $id, $ownerUserId, $name );
			
	}
	
	
}