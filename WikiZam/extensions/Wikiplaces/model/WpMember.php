<?php

class WpMember {

	private $wpm_id; // int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key'
	private $wpm_wpw_id; // int(10) unsigned NOT NULL COMMENT 'Foreign key: associated WikiPlace'
	private $wpm_user_id; // int(10) unsigned NOT NULL COMMENT 'Foreign key: associated user'
	
	private $user;
	private $wikiplace;

	/**
	 * 
	 * @param string|int $id
	 * @param string|int $wikiplaceId
	 * @param string|int $userId
	 */
	private function __construct($id, $wikiplaceId, $userId) {
		$this->wpm_id = intval($id);
		$this->wpm_wpw_id = intval($wikiplaceId);
		$this->wpm_user_id = intval($userId);
	}

	/**
	 * Database field <code>wpm_id</code>
	 * @return int
	 */
	public function getId() {
		return $this->wpm_id;
	}

	/**
	 * Database field <code>wpm_wpw_id</code>
	 * @return int
	 */
	public function getWikiPlaceId() {
		return $this->wpm_wpw_id;
	}
	
	/**
	 * 
	 * @return WpWikiplace
	 */
	public function getWikiPlace() {
		if ( is_null($this->wikiplace) ) {
			$this->wikiplace = WpWikiplace::getById($this->wpm_wpw_id);
		}
		return $this->wikiplace;
	}

	/**
	 * Database field <code>wpm_user_id</code>
	 * @return int
	 */
	public function getUserId() {
		return $this->wpm_user_id;
	}
	
	/**
	 * 
	 * @return User
	 */
	public function getUser() {
		if ( is_null($this->user) ) {
			$this->user = User::newFromId($this->wpm_user_id);
		}
		return $this->user;
	}

	/**
	 * After using this method, the WpMember instance should not be used anymore.
	 * @return boolean <code>true</code> if successfull.
	 */
	public function delete() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		$success = $dbw->delete('wp_member', array('wpm_id' => $this->wpm_id), __METHOD__);
		$dbw->commit();
		return $success;
	}
	
	/**
	 * 
	 * @param WpWikiplace|int $wikiplace An instance of WpWikiplace (safer), or the wikiplace id (int, no checks)
	 * @param User|int $user An instance of existing User (safer), or the user id (int, no checks)
	 * @return WpMember The new WpMember or null if a problem occurs
	 */
	public static function create($wikiplace, $user ) {

		if (is_int($wikiplace)) {
			$wikiplaceId = $wikiplace;
			$wikiplace = null;			
		} elseif ( $wikiplace instanceof WpWikiplace ) {
			$wikiplaceId = $wikiplace->getId();		
		} else {
			return null; // invalid argument
		}		
		// from now:  $wikiplace instanceof WpWikiplace  and  $wikiplaceId is an int
		
		if (is_int($user)) {
			$userId = $user;
			$user = null;
		} elseif ( $user instanceof User) {
			$userId = $user->getId();
		} else {
			return null; // invalid argument
		}	
		// from now:  $user instanceof User  and  $userId is an int
		
		if ( $userId == 0 ) {
			return null; // the user is anonymous or nonexistent
		}
	
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		$id = $dbw->nextSequenceValue('wpm_id');
		$success = $dbw->insert('wp_member', array(
			'wpm_id' => $id,
			'wpm_wpw_id' => $wikiplaceId,
			'wpm_user_id' => $userId	), __METHOD__);
		$id = $dbw->insertId();
		$dbw->commit();
		if (!$success) {
			return null; // sql error
		}

		// ok :)
		$member = new WpMember($id, $wikiplaceId, $userId);
		$member->wikiplace = $wikiplace; // null or WpWikiplace instance
		$member->user = $user; // null or User instance
		return $member;
	}

	/**
	 * 
	 * @param int|WpWikiplace $wikiplace An instance of WpWikiplace, or the wikiplace id (int)
	 * @param int|User $user An instance of User, or the user id (int)
	 * @return boolean Returns false if user is not memeber of the wikiplace
	 */
	public static function IsMember($wikiplace, $user) {
		return WpMember::GetFromWikiPlaceAndUser($wikiplace, $user) instanceof WpMember;
	}
	/**
	 * 
	 * @param int|WpWikiplace $wikiplace An instance of WpWikiplace, or the wikiplace id (int)
	 * @param int|User $user An instance of User, or the user id (int)
	 * @return WpMember An instance of WpMember, or <code>null</code> if user is not member of the wikiplace
	 */
	public static function GetFromWikiPlaceAndUser($wikiplace, $user) {
		
		if (is_int($wikiplace)) {
			$wikiplaceId = $wikiplace;		
		} elseif ( $wikiplace instanceof WpWikiplace ) {
			$wikiplaceId = $wikiplace->getId();		
		} else {
			throw new MWException('Invalid $wikiplace argument.');
		}		
		
		if (is_int($user)) {
			$userId = $user;
		} elseif ( $user instanceof User) {
			$userId = $user->getId();
		} else {
			throw new MWException('Invalid $user argument.');
		}	
		// from now:  $user instanceof User  and  $userId is an int
		
		if ( $userId == 0 ) {
			return false; // anonymous is never member of a wikiplace
		}
		
		return WpMember::search( array(
			'wpm_wpw_id' => $wikiplaceId,
			'wpm_user_id' => $userId ) );
		
	}

	/**
	 * 
	 * @param int $id
	 * @return WpMember|null The WpMember, or null if it doesn't exist
	 */
	public static function GetFromId($id) {
		return WpMember::search( array('wpm_id' => $id) );
	}
	
	/**
	 * 
	 * @param array $conds Array of conditions
	 * @param boolean $multiple Optional, default is <code>false</code> <ul>
	 * <li><code>false</code> => returns the WpMember or <code>null</code> if not found</li>
	 * <li><code>true</code> => returns an array containing multiple WpMember, or empty array if none found</li>
	 * </ul>
	 * @return array|WpMember Type depend of $multiple
	 */
	private static function search($conds, $multiple = false) {

		$dbr = wfGetDB(DB_SLAVE);

		$tables = array('wp_member');
		$vars = array('wpm_id', 'wpm_wpw_id', 'wpm_user_id');
		$fname = __METHOD__;

		if ($multiple) {
			$results = $dbr->select($tables, $vars, $conds, $fname);
			$members = array();
			foreach ($results as $row) {
				$members[] = self::constructFromDatabaseRow($row);
			}
			$dbr->freeResult($results);
			return $members;
			
		} else {
			$result = $dbr->selectRow($tables, $vars, $conds, $fname);
			if ($result === false) {
				// not found, so return null
				return null;
			}
			return self::constructFromDatabaseRow($result);
		}
	}
	
	/**
	 * Get the WpMember instance from a SQL row
	 * @param ResultWrapper $row
	 * @return WpMember 
	 */
	private static function constructFromDatabaseRow($row) {

		if ($row === null) {
			throw new MWException('Cannot construct the page, no databse row given.');
		}

		if (!isset($row->wpm_id) || !isset($row->wpm_wpw_id) || !isset($row->wpm_user_id)) {
			throw new MWException('Cannot construct the page from the supplied row, missing field.');
		}

		return new WpMember($row->wpm_id, $row->wpm_wpw_id, $row->wpm_user_id);
	}
	

}