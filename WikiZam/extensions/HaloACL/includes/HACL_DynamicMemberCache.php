<?php
/*  Copyright 2011, ontoprise GmbH
*  This file is part of the HaloACL-Extension.
*
*   The HaloACL-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The HaloACL-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * This file contains the class HACLDynamicMemberCache
 * 
 * @author Thomas Schweitzer
 * Date: 10.02.2011
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---

/**
 * This class caches dynamic members of groups. Dynamic members are defined
 * by results of queries. Without the cache the queries have to be executed
 * each time the members of a group are queried.
 * It is assumed that the semantic data that defines the members does not change
 * during a web request.
 * This class is a singleton.
 * 
 * @author Thomas Schweitzer
 * 
 */
class HACLDynamicMemberCache  {
	
	//--- Constants ---
		
	//--- Private fields ---

	// HACLDynamicMemberCache: The only instance of this class
	private static $mInstance = null;
	
	// array<int groupID => array<'users' => array<int>, 'group' => array<int>>>
	// This array stores all member IDs (user and group IDs) for the groups which
	// are identified by their ID.
	private $mGroupMembers = array();

	/**
	 * Constructor for HACLDynamicMemberCache. This object is a singleton.
	 */
	private function __construct() {
	}

	//--- Getters / setters

	//--- Public methods ---
	
	/**
	 * Return the only instance of this class.
	 * @return HACLDynamicMemberCache
	 * 		The singleton
	 */
	public static function getInstance() {
		if (is_null(self::$mInstance)) {
			self::$mInstance = new self;
		}
		return self::$mInstance;
	}
	

	//--- getter/setter ---
	
	//--- Public methods ---
	
	/**
	 * Checks if the group with the ID $groupID is already cached.
	 * @param int $groupID
	 * 		ID of the group
	 * @param bool 
	 * 		<true> if the group is cached
	 * 		<false> otherwise
	 */
	public function isCached($groupID) {
		return array_key_exists($groupID, $this->mGroupMembers);
	}
	
	/**
	 * Clears the cache for one or all groups.
	 * @param int $groupID
	 * 		ID of the group whose cache is cleared or <null> for all groups.
	 */
	public function clearCache($groupID = null) {
		if ($groupID == null) {
			// clear the cache for all groups
			$this->mGroupMembers = array();
		} else {
			unset($this->mGroupMembers[$groupID]);
		}
	}
	
	/**
	 * Adds the given $members to the cache of the group with the ID $groupID.
	 * All members are stored by their ID. The given names are converted 
	 * accordingly.
	 * 
	 * @param $groupID
	 * 		ID of the group whose members are cached.
	 * @param array<string> $members
	 * 		Array of full member names e.g User:Peter or ACL:Group/X.
	 * 		The namespace is needed to decide if a member is a user or a group
	 */
	public function addMembers($groupID, array $members) {
		if (!array_key_exists($groupID, $this->mGroupMembers)) {
			// Create a new cache array for the group
			$this->mGroupMembers[$groupID] = array(
				"groups" => array(),
				"users"  => array()
			);
		}
		$gmc = &$this->mGroupMembers[$groupID];

		// Add the ID of all members to the cache
		foreach ($members as $m) {
			$idType = $this->convertNameToID($m);
			if (!is_null($idType)) {
				$type = ($idType[1] == HACLGroup::USER)
							? 'users' : 'groups';
				$gmc[$type][] = $idType[0];
			}
		}
			
	}
	
	/**
	 * Returns the dynamic members of the group with the ID $groupID.
	 * 
	 * @param int $groupID
	 * 		ID of the group
	 * @param int $mode
	 * 		HACLGroup::NAME:   The names of all user and groups are returned.
	 * 		HACLGroup::ID:     The IDs of all users and groups are returned (default).
	 * 		HACLGroup::OBJECT: User/Group-objects for all users and groups are returned.
	 * 
	 * @return array(string => array(string/int/User/HACLGroup))
	 * 		List of all dynamic members of this group. This array has the following
	 * 		layout:
	 * 		array("groups" => array(List of groups),
	 * 		      "users"  => array(List of users) )
	 * 		There may be duplicate users and groups in the result.
	 * 		<null> if the group is not cached
	 * 
	 */
	public function getMembers($groupID, $mode) {
		if (!$this->isCached($groupID)) {
			return null;
		}
		if ($mode === HACLGroup::ID) {
			return $this->mGroupMembers[$groupID];
		}
		// Convert group members
		$users = $this->mGroupMembers[$groupID]['users'];
		$users = $this->convertIDs($users, $mode, HACLGroup::USER);
		$groups = $this->mGroupMembers[$groupID]['groups'];
		$groups = $this->convertIDs($groups, $mode, HACLGroup::GROUP);
		return array(
			'users' => $users,
			'groups' => $groups
		);
	}

	//--- Private methods ---
	
	/**
	 * Converts the full name of a user or group to its ID.
	 * 
	 * @param string $memberName
	 * 		Full name of a member (user or group)
	 * @return array<int, string> ID, type
	 * 		ID and type (HACLGroup::USER or HACLGroup::GROUP) of the member
	 * 		<null> if name is neither a group nor a user
	 */
	private function convertNameToID($memberName) {
    	global $wgContLang, $haclgContLang;
		$userNS = $wgContLang->getNsText(NS_USER);
    	$aclNS = $haclgContLang->getNamespaces();
    	$aclNS = $aclNS[HACL_NS_ACL];
    	
    	// All names start with their namespaces
    	if (strpos($memberName, $userNS) === 0) {
    		// found a user name
    		$plainName = substr($memberName, strlen($userNS)+1);
    		$id = USER::idFromName($plainName);
    		if (is_null($id)) {
    			return null;
    		}
    		return array($id, HACLGroup::USER);
    	} else if (strpos($memberName, $aclNS) === 0) {
    		// found an ACL name
    		$plainName = substr($memberName, strlen($aclNS)+1);
    		$id = HACLGroup::idForGroup($plainName);
    		if (is_null($id)) {
    			return null;
    		}
  			return array($id, HACLGroup::GROUP);
    	}
    	 
    	return null;
	}
	
	/**
	 * Converts an array of group or user IDs to their names (without namespace)
	 * or to User or HACLGroup objects.
	 * 
	 * @param array<int> $ids
	 * 		Array of IDs
	 * @param int $mode
	 * 		Requested format:
	 * 			HACLGroup::NAME:   The names of all user and groups are returned.
	 * 			HACLGroup::OBJECT: User/Group-objects for all users and groups are returned.
	 *
	 * @param string $type
	 * 		Type of the given IDs: HACLGroup::USER or HACLGroup::GROUP
	 */
	private function convertIDs(array $ids, $mode, $type) {
		$result = array();
		foreach ($ids as $id) {
			if ($type === HACLGroup::USER) {
				if ($mode === HACLGroup::NAME) {
					$result[] = User::whoIs($id);
				} else {
					$result[] = User::newFromId($id);
				}
			} else if ($type === HACLGroup::GROUP) {
				if ($mode === HACLGroup::NAME) {
					$result[] = HACLGroup::nameForID($id);
				} else {
					$result[] = HACLGroup::newFromID($id);
				}
			}
		}
		return $result;
    	
	}
	
}
