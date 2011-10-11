<?php
/**
 * @file
 * @ingroup HaloACL
 */

/*  Copyright 2009, ontoprise GmbH
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
 * This file contains the class HACLRight.
 * 
 * @author Thomas Schweitzer
 * Date: 17.04.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * This class describes an inline right in HaloACL.
 * 
 * An inline right contains the following data:
 * 
 * right_id
 *   The ID of the inline right. It is automatically generated when the right is
 *   stored in the database. 
 * actions
 *   A bit-field of flags for the seven actions. The order of bits is: read (6), 
 *   formedit (5), edit (4), create (3), move(2), annotate (1), delete (0). So 
 *   the actions "r|ef|e|d" are encoded to the binary value 1110001. 
 * groups
 *    A comma separated list of page IDs of the groups whose right is defined. 
 *    This can be empty if users are given. 
 * users
 *    A comma separated list of user IDs whose right is defined. This can be 
 *    empty if groups are given. 
 * description
 *    The description that was given for the rule. 
 * name
 * 	  A short name for the right.
 * origin_id
 *    The page ID of the wiki article where this rule was defined (i.e. a 
 *    security descriptor or a predefined right). 
 * 
 * When properties of a right are changed, the method "save" has to be called
 * to store it in the database.
 * 
 * @author Thomas Schweitzer
 * 
 */
class  HACLRight  {
	
	//--- Constants ---
	//---- Operations ----
	const ALL_ACTIONS = 255;
	const READ     = 128;
	const FORMEDIT = 64;
	const WYSIWYG  = 32;
	const ANNOTATE = 16;
	const EDIT     = 8;
	const CREATE   = 4;
	const MOVE     = 2;
	const DELETE   = 1;
	
	//---- Mode parameter for getUsersEx/getGroupsEx ----
	const NAME   = 0; 
	const ID     = 1;
	const OBJECT = 2;
	
			
	//--- Private fields ---
	private $mRightID = -1;		// int: ID of this right. This value is valid after
								//      the right has been saved in the database.
	private $mActions;			// int: A bit-field for the allowed actions.
	private $mGroups;			// array(int): IDs of the groups for which this
								//             right applies
	private $mUsers;			// array(int): IDs of the users for which this
								//             right applies
	private $mDynamicAssigneeQueries; 
								// array(string): Queries for dynamic assignees
								//  			  (Groups and users)
	private $mDynamicAssignees;	// array of IDs of dynamic users and groups
	private $mDescription;		// string: A decription of this right
	private $mOriginID;			// int: ID of the security descriptor or
								//      predefined right that defines this right
	private $mName;				// string: the name of the right
	
	/**
	 * Constructor for HACLRight. 
	 *
	 * @param int $actions
	 * 		Actions that are granted by this rule. This is a bit-field of
	 *      the ORed values HACLRight::READ, HACLRight::FORMEDIT, HACLRight::WYSIWYG,
	 *      HACLRight::EDIT, HACLRight::CREATE, HACLRight::MOVE, HACLRight::ANNOTATE,
	 *      HACLRight::DELETE. According to the hierarchy of rights, missing
	 * 		rights are set automatically.
	 * @param array<int/string>/string $groups
	 * 		An array or a string of comma separated group names or IDs that 
	 *      get this right. Group names are converted and 
	 *      internally stored as group IDs. Invalid values cause an exception.
	 * @param array<int/string>/string $users
	 * 		An array or a string of comma separated of user names or IDs that
	 *      get this right. User names are converted and 
	 *      internally stored as user IDs. Invalid values cause an exception.
	 * @param array<string> $dynamicAssigneeQueries
	 * 		This array may contain queries whose results assign users and groups
	 * 		to this right dynamically.
	 * @param string $description
	 * 		A description of this right
	 * @param int originID
	 * 		ID of the security descriptor or predefined right that defines this 
	 *      right. Don't set this value if you create an inline right. It is set
	 * 		when the right is added to a security descriptor or inline right.
	 * @throws 
	 * 		HACLGroupException(HACLGroupException::UNKOWN_GROUP)
	 * 			... if a given group is invalid
	 * 		HACLException(HACLException::UNKOWN_USER)
	 * 			... if the user is invalid
	 * 	 
	 */		
	function __construct($actions, $groups, $users, $dynamicAssigneeQueries, 
	                     $description, $name, $originID=0) {
				
		$this->mActions = $this->completeActions($actions);
		
		$this->setGroups($groups);
		$this->setUsers($users);
		
		if (!$dynamicAssigneeQueries) {
			$dynamicAssigneeQueries = array();
		}
		$this->mDynamicAssigneeQueries = $dynamicAssigneeQueries;
		$this->mDescription = $description;
		$this->mOriginID    = $originID;
		$this->mName		= $name;
		
	}
	
	//--- getter/setter ---

	public function getRightID()		{return $this->mRightID;}
	public function getActions()		{return $this->mActions;}
	public function getDescription()	{return $this->mDescription;}
	public function getName()			{return $this->mName;}
	public function getOriginID()		{return $this->mOriginID;}
	
	/**
	 * Returns <true> if this right has queries for dynamic assignees.
	 */
	public function hasDynamicAssignees() {
		return count($this->mDynamicAssigneeQueries) > 0;
	}
	
	public function getDynamicAssigneeQueries()	
										{ return $this->mDynamicAssigneeQueries; }
	
	/**
	 * Returns an array of IDs of dynamically assigned users and groups.
	 * This array has the following	layout:
	 * 		array("groups" => array(List of groups),
	 * 		      "users"  => array(List of users) )
	 * This array is stored in an internal field so that the query for members
	 * must only be performed once.
	 */
	public function getDynamicAssignees() {
		if (!isset($this->mDynamicAssignees)) {
			$this->mDynamicAssignees = $this->queryDynamicAssignees();
		}
		return $this->mDynamicAssignees;
	}
	
	/**
	 * Returns an array of IDs of assigned groups. If $dynamicAssignees is <true>,
	 * all dynamic groups are returned as well.
	 * 
	 * @param int $dynamicAssignees
	 * 		Default value is <false>.
	 * @return array<int>
	 * 		IDs of all assigned groups.
	 * 	
	 */
	public function getGroups($dynamicAssignees = false) {
		if ($dynamicAssignees) {
			$da = $this->getDynamicAssignees();
			return array_merge($this->mGroups, $da['groups']);
		}
		return $this->mGroups;
	}
	
	
	/**
	 * Returns an array of IDs of assigned users. If $dynamicAssignees is <true>,
	 * all dynamic users are returned as well.
	 * 
	 * @param int $dynamicAssignees
	 * 		Default value is <false>.
	 * @return array<int>
	 * 		IDs of all assigned users.
	 * 	
	 */
	public function getUsers($dynamicAssignees = false)	{
		if ($dynamicAssignees) {
			$da = $this->getDynamicAssignees();
			return array_merge($this->mUsers, $da['users']);
		}
		return $this->mUsers;
	}
		
	/**
	 * Don't call this method!!
	 * Sets the ID of this inline right. The ID is set when the right is stored 
	 * in the database.
	 *
	 * @param int $rightID
	 * 		ID of this right.
	 */		
	public function setRightID($rightID) {
		$this->mRightID = $rightID;
	}
	
	public function setActions($actions) {
		$this->mActions = $this->completeActions($actions);
	}
	
	public function setDescription($description) {$this->mDescription = $description;}
	public function setName($name) {$this->mName = $name;}
	
	/**
	 * Don't call this method!!
	 * Sets the ID of SD or PR that defines this inline right. The ID is set when
	 * the right is added to an SD or PR.
	 *
	 * @param int $originId
	 * 		ID of security descriptor or predefined right that defines this right.
	 */		
	public function setOriginID($originId)		{$this->mOriginID = $originId;}
	
	
	//--- Public methods ---
	
	/**
	 * Creates a new right object based on the ID of the right. The right must
	 * exists in the database.
	 * 
	 * @param int $rightID
	 * 		ID of the right.
	 * 
	 * @return HACLRight
	 * 		A new right object.
	 * 
	 * @throws
	 * 		HACLRightException(HACLRightException::UNKNOWN_RIGHT)
	 * 			... if there is no right with this ID in the database
	 */
	public static function newFromID($rightID) {
		$right = HACLStorage::getDatabase()->getRightByID($rightID);
		if ($right == null) {
			throw new HACLRightException(HACLRightException::UNKNOWN_RIGHT, $rightID);
		}
		return $right;
	}

	/**
	 * Returns the ID of an action for the given name of an action 
	 *
	 * @param string $actionName
	 * 		The action, the user wants to perform. One of "read", "formedit", 
	 *      "wysiwyg", "edit", "annotate", "create", "move" and "delete".
	 * 
	 * @return int
	 * 		The ID of the action or 0 if the names is invalid.
	 * 
	 */
	public static function getActionID($actionName) {
		$actionID = 0;
		switch ($actionName) {
			case "read":
				$actionID = HACLRight::READ;
				break;
			case "formedit":
				$actionID = HACLRight::FORMEDIT;
				break;
			case "wysiwyg":
				$actionID = HACLRight::WYSIWYG;
				break;
			case "edit":
				$actionID = HACLRight::EDIT;
				break;
			case "annotate":
				$actionID = HACLRight::ANNOTATE;
				break;
			case "create":
				$actionID = HACLRight::CREATE;
				break;
			case "move":
				$actionID = HACLRight::MOVE;
				break;
			case "delete":
				$actionID = HACLRight::DELETE;
				break;
		}
		return $actionID;		
	}

	/**
	 * Checks if the given user can modify this right. Inline rights are defined
	 * in security descriptors (or predefined rights) which store who can modify
	 * their content.
	 *
	 * @param User/string/int $user
	 * 		User-object, name of a user or ID of a user who wants to modify this
	 * 		right. If <null>, the currently logged in user is assumed.
	 * 
	 * @param boolean $throwException
	 * 		If <true>, the exception 
	 * 		HACLSDException(HACLSDException::USER_CANT_MODIFY_SD)
	 * 		is thrown, if the user can't modify the group.
	 * 
	 * @return boolean
	 * 		One of these values is returned if no exception is thrown:
	 * 		<true>, if the user can modify this right and
	 * 		<false>, if not
	 * 
	 * @throws 
	 * 		HACLException(HACLException::UNKOWN_USER)
	 * 		If requested: HACLSDException(HACLSDException::USER_CANT_MODIFY_SD) 
	 *  
	 */
	public function userCanModify($user, $throwException = false) {

        // Ask the origin (security desriptor/ predefined right) of this inline 
        // right, if the user can modify this right

		$sd = HACLSecurityDescriptor::newFromID($this->mOriginID);
		return $sd->userCanModify($user, $throwException);
	}

	/**
	 * Checks if the given user has this right.
	 *
	 * @param User/string/int $user
	 * 		User-object, name of a user or ID of a user who wants to modify this
	 * 		group. If <null>, the currently logged in user is assumed.
	 * 
	 * @return boolean
	 * 		One of these values is returned if no exception is thrown:
	 * 		<true>, if the user gets this right
	 * 		<false>, if not
	 * 
	 * @throws 
	 * 		HACLException(HACLException::UNKOWN_USER)
	 * 		HACLRightException(HACLRightException::RIGHT_NOT_GRANTED)
	 * 			...if the right is not granted and an exception is requested
	 * 
	 */
	public function grantedForUser($user, $throwException = false) {
		// Get the ID of the user who wants to get this right
		list($userID, $userName) = haclfGetUserID($user);
		// Check if the right is directly granted for the user
		if (in_array($userID, $this->mUsers)) {
			return true;
		}
		
		// Check if this right is granted to registered users (ID = -1)
		if ($userID > 0 && in_array(-1, $this->mUsers)) {
			return true;
		}
		
		// Check if the user belongs to a group that gets this right
		$db = HACLStorage::getDatabase();
		foreach ($this->mGroups as $groupID) {
			$group = HACLGroup::newFromID($groupID);
			if ($group->hasUserMember($userID, true)) {
				return true;
			}
		}
		
		// Check dynamic assignees
		$da = $this->queryDynamicAssignees();
		// Is the user a dynamically assigned user?
		if (in_array($userID, $da['users'])) {
			return true;
		}

		// Is the user a member of a dynamically assigned group?
		foreach ($da['groups'] as $groupID) {
			$group = HACLGroup::newFromID($groupID);
			if ($group->hasUserMember($userID, true)) {
				return true;
			}
		}
		
		if ($throwException) {
			if (empty($userName)) {
				// only user id is given => retrieve the name of the user
				$user = User::newFromId($userID);
				$userName = ($user) ? $user->getId() : "(User-ID: $userID)";
			}
			throw new HACLRightException(HACLRightException::RIGHT_NOT_GRANTED, 
			                             $this->mRightID, $userName);
		}
		return false;
	}
	
	/**
	 * Sets the groups that get this right. The method "save" has to be called
	 * to store the right in the database.
	 *
	 * @param array<int/string>/string $groups
	 * 		An array or a string of comma separated group names or IDs that 
	 *      get this right. Group names are converted and 
	 *      internally stored as group IDs. Invalid values cause an exception.
	 * @throws 
	 * 		HACLGroupException(HACLGroupException::UNKOWN_GROUP)
	 * 			... if a given group is invalid
	 */
	public function setGroups($groups) {
		if (empty($groups)) {
			$this->mGroups = array();
			return;
		}
		if (is_string($groups)) {
			// Groups are given as comma separated string
			// Split into an array
			$groups = explode(',', $groups);
		}
		if (is_array($groups)) {
			$this->mGroups = $groups;
			for ($i = 0; $i < count($groups); ++$i) {
				$mg = $groups[$i];
				if (is_int($mg)) {
					// do nothing
				} else if (is_numeric($mg)) {
					$this->$groups[$i] = (int) $mg;
				} else if (is_string($mg)) {
					// convert a group name to a group ID
					$gid = HACLGroup::idForGroup(trim($mg));
					if (!$gid) {
						throw new HACLGroupException(HACLGroupException::UNKOWN_GROUP, $mg);
					}
					$this->mGroups[$i] = $gid; 
				}
			}
		} else {
			$this->mGroups = array();
		}

	}
	
	/**
	 * Sets the users that get this right. The method "save" has to be called
	 * to store the right in the database.
	 *
	 * @param array<int/string>/string $users
	 * 		An array or a string of comma separated of user names or IDs that
	 *      get this right. User names are converted and 
	 *      internally stored as user IDs. Invalid values cause an exception.
	 * @throws 
	 * 		HACLException(HACLException::UNKOWN_USER)
	 * 			... if the user is invalid
	 *  
	 */
	public function setUsers($users) {
		if (empty($users)) {
			$this->mUsers = array();
			return;
		}
		if (is_string($users)) {
			// Users are given as comma separated string
			// Split into an array
			$users = explode(',', $users);
		}
		if (is_array($users)) {
			$this->mUsers = $users;
			for ($i = 0; $i < count($users); ++$i) {
				$mu = $users[$i];
				list($uid, $uname) = haclfGetUserID(trim($mu));
				$this->mUsers[$i] = $uid;
			}
		} else {
			$this->mUsers = array();
		}
	}
	
	

	/**
	 * Don't call this method!!
	 *  
	 * Saves this right in the database. 
	 * 
	 * If the right already exists  the right's definition is changed. This 
	 * method is called, when the right is added to a security descriptor or a 
	 * predefined right. 
	 *
	 * @throws 
	 * 		Exception (on failure in database level) 
	 * 
	 */
	public function save() {
		$this->mRightID = HACLStorage::getDatabase()->saveRight($this);
	}
	

	/**
	 * Returns all users who own this right. This does not include the users that
	 * are collected in groups. 
	 *
	 * @param int $mode
	 * 		HACLRight::NAME:   The names of all users are returned.
	 * 		HACLRight::ID:     The IDs of all users are returned.
	 * 		HACLRight::OBJECT: User-objects for all users are returned.
	 * 
	 * @return array(string/int/User)
	 * 		List of all direct users in this group.
	 * 	 
	 */
	public function getUsersEx($mode) {
		if ($mode === self::ID) {
			return $this->mUsers;
		}
		// retrieve the IDs of all users in this group
		$users = array();
		
		foreach ($this->mUsers as $u) {
			if ($mode === self::NAME) {
				$users[] = User::whoIs($u);
			} else if ($mode === self::OBJECT) {
				$users[] = User::newFromId($u);
			}  
		}
		return $users;
	}

	/**
	 * Returns all groups who who own this right. These are only the groups that
	 * are directly assigned to this right. This method does not collect all
	 * groups recursively from the hierarchy of groups. 
	 *
	 * @param int $mode
	 * 		HACLRight::NAME:   The names of all groups are returned.
	 * 		HACLRight::ID:     The IDs of all groups are returned.
	 * 		HACLRight::OBJECT: HACLGroup-objects for all groups are returned.
	 * 
	 * @return array(string/int/HACLGroup)
	 * 		List of all direct groups in this group.
	 * 	 
	 */
	public function getGroupsEx($mode) {
		// retrieve the IDs of all groups in this group
		if ($mode === self::ID) {
			return $this->mGroups;
		}
		$groups = array();
		foreach ($this->mGroups as $g) {
			if ($mode === self::NAME) {
				$groups[] = HACLGroup::nameForID($g);
			} else if ($mode === self::OBJECT) {
				$groups[] = HACLGroup::newFromID($g);
			}  
		}
		return $groups;
		
	}
	
	/**
	 * Executes the queries for dynamic assignees and returns them in an array.
	 * 
	 * @param int $mode
	 * 		HACLRight::NAME:   The names of all user and groups are returned.
	 * 		HACLRight::ID:     The IDs of all users and groups are returned (default).
	 * 		HACLRight::OBJECT: User/Group-objects for all users and groups are returned.
	 * 
	 * @return array(string => array(string/int/User/HACLGroup))
	 * 		List of all dynamic assignees of this right. This array has the following
	 * 		layout:
	 * 		array("groups" => array(List of groups),
	 * 		      "users"  => array(List of users) )
	 * 		There may be duplicate users and groups in the result.
	 */
	public function queryDynamicAssignees($mode = HACLRight::ID) {
		$result = array(
			"groups" => array(),
			"users"  => array()
		);
		
    	// Iterate over all queries
		foreach ($this->mDynamicAssigneeQueries as $daq) {
			$assignees = $this->executeDAQuery($daq);
			// convert names of assignees according to $mode
			
			foreach ($assignees as $a) {
				list($obj, $type) = $this->convertName($a, $mode);
				$type = ($type == HACLGroup::USER)
							? 'users' : 'groups';
				$result[$type][] = $obj;
			}
		}
		
		return $result;
	}	
	
	
	/**
	 * Deletes this right from the database. All references to this right are 
	 * deleted as well.
	 * 
	 * @param User/string/int $user
	 * 		User-object, name of a user or ID of a user who wants to delete this
	 * 		group. If <null>, the currently logged in user is assumed.
	 * 
	 * @throws
	 * 	HACLSDException(HACLSDException::USER_CANT_MODIFY_SD) 
	 *
	 */
	public function delete($user = null) {
		$this->userCanModify($user, true);
		return HACLStorage::getDatabase()->deleteRight($this->mRightID);
	}
	
	//--- Private methods ---
	
	/**
	 * Completes a given set of actions according to the hierarchy of actions.
	 *
	 * @param int $actions
	 * 		Bit field of actions
	 * @return int
	 * 		Bitfield of all derived actions
	 */
	private function completeActions($actions) {
		// Complete the hierarchy of rights
		if ($actions & (HACLRight::CREATE | HACLRight::MOVE | HACLRight::DELETE)) {
			$actions |= HACLRight::READ | HACLRight::FORMEDIT | 
			            HACLRight::ANNOTATE | HACLRight::WYSIWYG | HACLRight::EDIT;
		}
		if ($actions & HACLRight::EDIT) {
			$actions |= HACLRight::READ | HACLRight::FORMEDIT| 
			            HACLRight::ANNOTATE | HACLRight::WYSIWYG;
		}
		if ($actions & HACLRight::FORMEDIT) {
			$actions |= HACLRight::READ;
		}
		if ($actions & HACLRight::ANNOTATE) {
			$actions |= HACLRight::READ;
		}
		if ($actions & HACLRight::WYSIWYG) {
			$actions |= HACLRight::READ;
		}
		return $actions;
	}
	
	/**
	 * Executes a query for dynamic assignees and returns the resulting user and
	 * group names.
	 * 
	 * @param string $query
	 * 		The query for assignees.
	 * @return array<string>
	 * 		An array of group and user names. May be empty.
	 */
	private function executeDAQuery($query) {
		
		$smwStore = smwfGetStore();
		if ($smwStore instanceof HACLSMWStore) {
			// disable protection of query results to avoid recursion
			$pa = $smwStore->setProtectionActive(false);
		} else {
			$smwStore = null;
		}
		// Disable the result filter for the same reason
		$rfd = HACLResultFilter::setDisabled(true);

		if (preg_match('/{{#ask:\s*(.*?)\s*}}/', $query, $matches) == 1) {
			// Query is in ask format
			$query = $matches[1];
			$params = explode('|', $query);
			$params[] = "format=list";
			$res = SMWQueryProcessor::getResultFromFunctionParams($params, SMW_OUTPUT_WIKI);
		} else if (preg_match('/{{#sparql:\s*(.*?)\s*}}/', $query, $matches) == 1) {
			// Query is in sparql format
			$query = $matches[1];
			$params = explode('|', $query);
			$params[] = "format=list";
			$res = SMWSPARQLQueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI);
		}
		
		if (!is_null($smwStore)) {
			$smwStore->setProtectionActive($pa);
		}
		HACLResultFilter::setDisabled($rfd);

		if (empty($res)) {
			return array();
		}
		$res = explode(',', $res);
		foreach ($res as $k => $v) {
			$res[$k] = trim($v);
		}
		return $res;
		
	}
	
	/**
	 * Converts a full user or group name to an ID, a simple name (without namespace),
	 * or an object (User or HACLGroup).
	 * 
	 * @param string $name
	 * 		Full name of a user (e.g. User:Peter) or a group (e.g. ACL:Group/TeamA)
	 * @param HACLRight::Mode $mode
	 * 		Wanted conversion to HACLRight::ID, HACLRight::NAME or HACLRight::OBJECT.
	 * @return array(Object, string)
	 * 		Object is the representation as name, ID or object and type is one of
	 * 		HACLGroup::USER or HACLGroup::GROUP.
	 * 		<null> if name is neither a group nor a user
	 */
	private function convertName($name, $mode) {
    	global $wgContLang, $haclgContLang;
		$userNS = $wgContLang->getNsText(NS_USER);
    	$aclNS = $haclgContLang->getNamespaces();
    	$aclNS = $aclNS[HACL_NS_ACL];
    	
    	// All names start with their namespaces
    	if (strpos($name, $userNS) === 0) {
    		// found a user name
    		$plainName = substr($name, strlen($userNS)+1);
	    	switch ($mode) {
	    		case HACLRight::NAME:
	    			return array($plainName, HACLGroup::USER);
	    		case HACLRight::ID:
	    			return array(USER::idFromName($plainName), HACLGroup::USER);
	    		case HACLRight::OBJECT:
	    			return array(USER::newFromName($plainName), HACLGroup::USER);
	    	}
    	} else if (strpos($name, $aclNS) === 0) {
    		// found an ACL name
    		$plainName = substr($name, strlen($aclNS)+1);
	    	switch ($mode) {
	    		case HACLRight::NAME:
	    			return array($plainName, HACLGroup::GROUP);
	    		case HACLRight::ID:
	    			return array(HACLGroup::idForGroup($plainName), HACLGroup::GROUP);
	    		case HACLRight::OBJECT:
	    			return array(HACLGroup::newFromName($plainName), HACLGroup::GROUP);
	    	}
    	}
    	 
    	return null;
	}

}