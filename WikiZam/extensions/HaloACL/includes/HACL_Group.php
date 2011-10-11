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
 * This file contains the class HACLGroup.
 *
 * @author Thomas Schweitzer
 * Date: 03.04.2009
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

//--- Includes ---
global $haclgIP;
//require_once("$haclgIP/...");

/**
 * This class describes a group in HaloACL.
 *
 * A group is always represented by an article in the wiki, so the group's
 * description contains the page ID of this article and the name of the group.
 *
 * Only authorized users and groups of users can modify the definition of the
 * group. Their IDs are stored in the group as well.
 *
 * @author Thomas Schweitzer
 *
 */
class  HACLGroup {

//--- Constants ---
    const NAME   = 0;		// Mode parameter for getUsers/getGroups
    const ID     = 1;		// Mode parameter for getUsers/getGroups
    const OBJECT = 2;		// Mode parameter for getUsers/getGroups
    const USER   = 'user';  // Child type for users
    const GROUP  = 'group'; // Child type for groups
    const TYPE_HALO_ACL = 'HaloACL';

    //--- Private fields ---
    private $mGroupID;    		// int: Page ID of the article that defines this group
    private $mGroupName;		// string: The name of this group
    private $mManageGroups;		// array(int): IDs of the groups that can modify
							    //		the definition of this group
    private $mManageUsers;		// array(int): IDs of the users that can modify
							    //		the definition of this group
	private $mCanBeModified;	// bool: <true> if this group can be modified
	private $mType;				// string: The type of this group e.g. HaloACL or LDAP

	private static $mAllowUnauthorizedGroupChange = false;
							// Under certain circumstances (e.g.
							// when using dynamic groups) it must be possible that
							// unauthorized users can change the group
	// array(string): Queries for dynamic members (Groups and users)								
	private $mDynamicMemberQueries = array(); 

    /**
     * Constructor for HACLGroup
     *
     * @param int/string $groupID
     * 		Article's page ID. If <null>, the class tries to find the correct ID
     * 		by the given $groupName. Of course, this works only for existing
     * 		groups.
     * @param string $groupName
     * 		Name of the group
     * @param array<int/string>/string $manageGroups
     * 		An array or a string of comma separated group names or IDs that
     *      can modify the group's definition. Group names are converted and
     *      internally stored as group IDs. Invalid values cause an exception.
     * @param array<int/string>/string $manageUsers
     * 		An array or a string of comma separated of user names or IDs that
     *      can modify the group's definition. User names are converted and
     *      internally stored as user IDs. Invalid values cause an exception.
     * @param bool $canBeModified
     * 		<true> if this group can be modified in general
     * @param string $type
     * 		The type of the group, e.g. HaloACL or LDAP etc.
     * @throws
     * 		HACLGroupException(HACLGroupException::UNKOWN_GROUP)
     * 		HACLException(HACLException::UNKOWN_USER)
     *
     */
    function __construct($groupID, $groupName, $manageGroups, $manageUsers, 
    					 $canBeModified = true, $type = self::TYPE_HALO_ACL) {

        if (is_null($groupID)) {
            $groupID = self::idForGroup($groupName);
        }
        $this->mGroupID = 0+$groupID;
        $this->mGroupName = $groupName;
        $this->setManageGroups($manageGroups);
        $this->setManageUsers($manageUsers);
		$this->mCanBeModified = $canBeModified;
		$this->mType = $type;
    }

    //--- getter/setter ---

    public function getGroupID() {return $this->mGroupID;}
    public function getGroupName() {return $this->mGroupName;}
    public function getManageGroups() {return $this->mManageGroups;}
    public function getManageUsers() {return $this->mManageUsers;}
    public function getType() 		{return $this->mType;}
    public function getDynamicMemberQueries()	{ return $this->mDynamicMemberQueries; }
    public function canBeModified()	{return $this->mCanBeModified;}
	public static function setAllowUnauthorizedGroupChange($aucg) { 
		$current = self::$mAllowUnauthorizedGroupChange;
		self::$mAllowUnauthorizedGroupChange = $aucg; 
		return $current;
	}
	public function hasDynamicMembers() { return count($this->mDynamicMemberQueries) > 0; }
    
    /**
     * Returns the name of this group without the prefix e.g. "Group/"
     * @return string
     * 		Name of the group without prefix
     */
    public function getGroupNameWithoutPrefix() {
    	return self::removeNamePrefix($this->mGroupName);
    }

    //	public function setXY($xy)               {$this->mXY = $xy;}

    //--- Public methods ---

    /**
     * Creates a new group object based on the name of the group. The group must
     * exists in the database.
     *
     * @param string $groupName
     * 		Name of the group.
     *
     * @return HACLGroup
     * 		A new group object.
     *
     * @throws
     * 		HACLGroupException(HACLGroupException::UNKNOWN_Group)
     * 			...if the requested group in the not the database.
     *
     */
    public static function newFromName($groupName) {
        $group = HACLStorage::getDatabase()->getGroupByName($groupName);
        if ($group === null) {
            throw new HACLGroupException(HACLGroupException::UNKNOWN_GROUP, $groupName);
        }
        $group->initDynamicMemberQueriesFromDB();        
        
        return $group;
    }

    /**
     * Creates a new group object based on the ID of the group. The group must
     * exists in the database.
     *
     * @param int $groupID
     * 		ID of the group i.e. the ID of the article that defines the group.
     *
     * @return HACLGroup
     * 		A new group object.
     *
     * @throws
     * 		HACLGroupException(HACLGroupException::INVALID_GROUP_ID)
     * 			...if the requested group in the not the database.
     */
    public static function newFromID($groupID) {
        $group = HACLStorage::getDatabase()->getGroupByID($groupID);
        if ($group === null) {
            throw new HACLGroupException(HACLGroupException::INVALID_GROUP_ID, $groupID);
        }
        $group->initDynamicMemberQueriesFromDB();        
        return $group;
    }

    /**
     * Returns the page ID of the article that defines the group with the object,
     * name or ID $group.
     *
     * @param mixed (int/string/HACLGroup) $group
     * 		ID, name or object for the group whose ID is returned.
     *
     * @return int
     * 		The ID of the group's article or <null> if there is no ID for the group.
     *
     */
    public static function idForGroup($group) {
    	if (is_int($group)) {
    		// group ID given
    		return $group;
    	} else if (is_string($group)) {
    		// Name of group given
    		$id = haclfArticleID($group, HACL_NS_ACL);
    		if ($id == 0) {
    			try {
    				$g = HACLGroup::newFromName($group);
    			} catch (HACLGroupException $e) {
    				return null;
    			}
    			$id = $g->getGroupID();
    		}
    		return $id;
    	} else if (is_a($group, 'HACLGroup')) {
    		// group object given
    		return $group->getGroupID();
    	}
    	// This should not happen
    	return null;
    }

    /**
     * Returns the name of the group with the ID $groupID.
     *
     * @param int $groupID
     * 		ID of the group whose name is requested
     *
     * @return string
     * 		Name of the group with the given ID or <null> if there is no such
     * 		group defined in the database.
     */
    public static function nameForID($groupID) {
        return HACLStorage::getDatabase()->groupNameForID($groupID);
    }
    
    /**
     * Creates an empty article for the group with the name $groupName (without
     * namespace). If the article already exist, no new article is created.
     * This is needed for groups that have no ID yet.
     * 
     * @param $groupName
     * 		Name of the group
     * 
     * @return bool success
     * 		<true>, if the article already exists or if it was successfully created
     * 		<false>, if creation failed.
     */
    public static function createEmptyArticle($groupName) {
		global $haclgContLang;
    	$articleName = $haclgContLang->getNamespaces();
    	$articleName = $articleName[HACL_NS_ACL];
    	$articleName .= ':'.$groupName;
    	
		// save the article
		$title = Title::newFromText($articleName);
		$article = new Article($title);
		
		if ($article->exists()) {
			return true;
		}
		$status = $article->doEdit("", 'Group initialization', 
		                            $article->exists() ? EDIT_UPDATE : EDIT_NEW);

		if (!$status->isOK()) {
			return false;
		}		
		                            
		// Create an empty group to combine the page ID with the group name in
		// the database
		$id = $article->getID();
		$group = new HACLGroup($id, $groupName, "", "");
		$augc = self::setAllowUnauthorizedGroupChange(true);
		$group->save();
		self::setAllowUnauthorizedGroupChange($augc);
		
		return true;
    	
    }
    
    /**
     * Returns the name of the group without the prefix e.g. "Group/"
     * @param string $name
     * 		A name with the typical group prefix.
     * @return string
     * 		Name without prefix
     */
    public static function removeNamePrefix($name) {
    	global $haclgContLang;
    	$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP)."/";

   		if (strpos($name, $prefix) === 0) {
   			// Remove the prefix of the naming convention e.g. "Group/"
   			return substr($name, strlen($prefix));
   		}
   		 
    	return $name;
    }

    /**
     * Checks if the group with the ID $groupID exists in the database.
     *
     * @param int $groupID
     * 		ID of the group
     *
     * @return bool
     * 		<true> if the group exists
     * 		<false> otherwise
     */
    public static function exists($groupID) {
        return HACLStorage::getDatabase()->groupExists($groupID);
    }
    
	/**
	 * Checks if there are several definitions for the group with the specified
	 * $groupName. This can happen if the same group is defined in a wiki article
	 * and on the LDAP server.
	 * 
	 * @param string $groupName
	 * 		The name of the group that is checked.
	 * @return bool
	 * 		true: The group is defined in the wiki and on the LDAP server.
	 * 		false: There is only one or no definition for the group
	 */
	public static function isOverloaded($groupName) {
        return HACLStorage::getDatabase()->isOverloaded($groupName);		
	}
	
	/**
	 * Searches for all groups whose name contains the search string $search.
	 * 
	 * @param string $search
	 * 		The group name must contain the string. Comparison is case insensitive.
	 * 
	 * @return array(string => int)
	 * 		A map from group names to group IDs of groups that match the search 
	 * 		string.
	 */
	public static function searchGroups($search) {
		// get all matching group names
		$db = HACLStorage::getDatabase();
		$groups = $db->searchMatchingGroups($search);
		
		// make sure the match is not in the prefix
		global $haclgContLang;
    	$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP).'/';
    	$pl = strlen($prefix);
    	foreach ($groups as $gn => $gid) {
    		$gno = $gn;
    		if (strpos($gn, $prefix) === 0) {
    			$gn = substr($gn, $pl);
    		}
    		if (stripos($gn, $search) === false) {
    			unset($groups[$gno]);
    		}
    	}
    	
    	return $groups;
		
	}

	/**
	 * This method checks the integrity of this group. The integrity can be violated
	 * by missing groups and users.
	 * 
	 * return mixed bool / array
	 * 	<true> if the group is valid,
	 *  array(string=>bool) otherwise
	 * 		The array has the keys "groups", "users" with boolean values.
	 * 		If the value is <true>, at least one of the corresponding entities 
	 * 		is missing.
	 */
	public function checkIntegrity() {
		$missingGroups = false;
		$missingUsers = false;
		$db = HACLStorage::getDatabase();
		
		//== Check integrity of group managers ==
		 
		// Check for missing managing groups
		foreach ($this->mManageGroups as $gid) {
			if (!$db->groupExists($gid)) {
				$missingGroups = true;
				break;
			}
		}
		
		// Check for missing managing users
		foreach ($this->mManageUsers as $uid) {
			if ($uid > 0 && User::whoIs($uid) === false) {
				$missingUsers = true;
				break;
			}
		}
		
		//== Check integrity of group's content  ==
		$groupIDs = $this->getGroups(self::ID);
		// Check for missing groups
		foreach ($groupIDs as $gid) {
			if (!$db->groupExists($gid)) {
				$missingGroups = true;
				break;
			}
		}
		
		// Check for missing users
		$userIDs = $this->getUsers(self::ID);
		foreach ($userIDs as $uid) {
			if ($uid > 0 && User::whoIs($uid) === false) {
				$missingUsers = true;
				break;
			}
		}
		
		if (!$missingGroups && !$missingUsers) {
			return true;
		}
		return array('groups' => $missingGroups,
		             'users'  => $missingUsers);
	}
    
    /**
     * Checks if the given user can modify this group.
     *
     * @param User/string/int $user
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @param boolean $throwException
     * 		If <true>, the exception
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     * 		is thrown, if the user can't modify the group.
     *
     * @return boolean
     * 		One of these values is returned if no exception is thrown:
     * 		<true>, if the user can modify this group and
     * 		<false>, if not
     *
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 		If requested: HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     *
     */
    public function userCanModify($user, $throwException = false) {
		if (self::$mAllowUnauthorizedGroupChange === true) {
			// Unauthorized change is temporarily allowed
			return true;
		}
    	
    	if (defined('DO_MAINTENANCE') && !defined('UNIT_TEST_RUNNING')) {
    		return true;
    	}
    // Get the ID of the user who wants to add/modify the group
        list($userID, $userName) = haclfGetUserID($user);
        // Check if the user can modify the group
        if (in_array($userID, $this->mManageUsers)) {
            return true;
        }
        if ($userID > 0 && in_array(-1, $this->mManageUsers)) {
        // registered users can modify the SD
            return true;
        }

        // Check if the user belongs to a group that can modify the group
        $db = HACLStorage::getDatabase();
        foreach ($this->mManageGroups as $groupID) {
            if ($db->hasGroupMember($groupID, $userID, self::USER, true)) {
                return true;
            }
        }

        // Sysops and bureaucrats can modify the SD
        $user = User::newFromId($userID);
        $groups = $user->getGroups();
        if (in_array('sysop', $groups) || in_array('bureaucrat', $groups)) {
            return true;
        }

        if ($throwException) {
            if (empty($userName)) {
            // only user id is given => retrieve the name of the user
                $user = User::newFromId($userID);
                $userName = ($user) ? $user->getId() : "(User-ID: $userID)";
            }
            throw new HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP,
            $this->mGroupName, $userName);
        }
        return false;
    }

    /**
     * Saves this group in the database. A group needs a name and at least one group
     * or user who can modify the definition of this group. If no group or user
     * is given, the specified or the current user gets this right. If no user is
     * logged in, the operation fails.
     *
     * If the group already exists and the given user has the right to modify the
     * group, the groups definition is changed.
     *
     *
     * @param User/string $user
     * 		User-object or name of the user who wants to save this group. If this
     * 		value is empty or <null>, the current user is assumed.
     *
     * @throws
     * 		HACLGroupException(HACLGroupException::NO_GROUP_ID)
     * 		HACLException(HACLException::UNKOWN_USER)
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     * 		Exception (on failure in database level)
     *
     */
    public function save($user = null) {

    	// Get the page ID of the article that defines the group
        if ($this->mGroupID == 0) {
            throw new HACLGroupException(HACLGroupException::NO_GROUP_ID, $this->mGroupName);
        }

        $this->userCanModify($user, true);

        HACLStorage::getDatabase()->saveGroup($this);
        HACLDynamicMemberCache::getInstance()->clearCache($this->mGroupID);

    }

    /**
     * Sets the users who can manage this group. The group has to be saved
     * afterwards to persists the changes in the database.
     *
     * @param mixed string|array(mixed int|string|User) $manageUsers
     * 		If a single string is given, it contains a comma-separated list of
     * 		user names.
     * 		If an array is given, it can contain user-objects, names of users or
     *      IDs of a users. If <null> or empty, the currently logged in user is
     *      assumed.
     *      There are two special user names:
     * 			'*' - anonymous user (ID:0)
     *			'#' - all registered users (ID: -1)
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 			...if a user does not exist.
     */
    public function setManageUsers($manageUsers) {
        if (!empty($manageUsers) && is_string($manageUsers)) {
        // Managing users are given as comma separated string
        // Split into an array
            $manageUsers = explode(',', $manageUsers);
        }
        if (is_array($manageUsers)) {
            $this->mManageUsers = $manageUsers;
            for ($i = 0; $i < count($manageUsers); ++$i) {
                $mu = $manageUsers[$i];
                if (is_string($mu)) {
                    $mu = trim($mu);
                }
                $uid = haclfGetUserID($mu);
                $this->mManageUsers[$i] = $uid[0];
            }
        } else {
            $this->mManageUsers = array();
        }

    }

    /**
     * Sets the groups who can manage this group. The group has to be saved
     * afterwards to persists the changes in the database.
     *
     * @param mixed string|array(mixed int|string|User) $manageGroups
     * 		If a single string is given, it contains a comma-separated list of
     * 		group names.
     * 		If an array is given, it can contain IDs (int), names (string) or
     *      objects (HACLGroup) for the group
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 			...if a user does not exist.
     */
    public function setManageGroups($manageGroups) {
        if (!empty($manageGroups) && is_string($manageGroups)) {
        // Managing groups are given as comma separated string
        // Split into an array
            $manageGroups = explode(',', $manageGroups);
        }
        if (is_array($manageGroups)) {
            $this->mManageGroups = $manageGroups;
            for ($i = 0; $i < count($manageGroups); ++$i) {
                $mg = $manageGroups[$i];
                if (is_string($mg)) {
                    $mg = trim($mg);
                }
                $gid = self::idForGroup($mg);
                if (!$gid) {
                    throw new HACLGroupException(HACLGroupException::UNKNOWN_GROUP, $mg);
                }
                $this->mManageGroups[$i] = (int) $gid;
            }
        } else {
            $this->mManageGroups = array();
        }

    }

    /**
     * Adds the user $user to this group. The new user is immediately added
     * to the group's definition in the database.
     *
     * @param User/string/int $user
     * 		This can be a User-object, name of a user or ID of a user. This user
     * 		is added to the group.
     * @param User/string/int $mgUser
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     *
     */
    public function addUser($user, $mgUser=null) {
    // Check if $mgUser can modifiy this group.
        $this->userCanModify($mgUser, true);
        list($userID, $userName) = haclfGetUserID($user);
        HACLStorage::getDatabase()->addUserToGroup($this->mGroupID, $userID);

    }

    /**
     * Adds the group $group to this group. The new group is immediately added
     * to the group's definition in the database.
     *
     * @param mixed(HACLGroup/string/id) $group
     * 		Group object, name or ID of the group that is added to $this group.
     * @param User/string/int $mgUser
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     * 		HACLGroupException(HACLGroupException::INVALID_GROUP_ID)
     *
     */
    public function addGroup($group, $mgUser=null) {
    	// Check if $mgUser can modifiy this group.
        $this->userCanModify($mgUser, true);

        $groupID = self::idForGroup($group);
        if ($groupID == 0) {
            throw new HACLGroupException(HACLGroupException::INVALID_GROUP_ID,
            							 $groupID);
        }

        HACLStorage::getDatabase()->addGroupToGroup($this->mGroupID, $groupID);
    }
    
    /**
     * Adds the queries for dynamic members $dmq to this group. The new queries
     * are immediately added to the group's definition in the database.
     * 
     * @param array(string) $dmq
     * 		Array of ask or sparql queries.
     * @param User/string/int $mgUser
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     * 
     * @throws
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     */
	public function addDynamicMemberQueries($dmq, $mgUser=null) {
    	// Check if $mgUser can modifiy this group.
        $this->userCanModify($mgUser, true);
        
        HACLStorage::getDatabase()->addDynamicMemberQueriesToGroup($this->mGroupID, $dmq);
        $this->mDynamicMemberQueries = array_merge($this->mDynamicMemberQueries, $dmq);
        HACLDynamicMemberCache::getInstance()->clearCache($this->mGroupID);
        
	}    

    /**
     * Removes all members (groups and users) from this group. They are
     * immediately removed from the group's definition in the database.
     *
     * @param User/string/int $mgUser
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @throws
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     *
     */
    public function removeAllMembers($mgUser=null) {
	    // Check if $mgUser can modifiy this group.
        $this->userCanModify($mgUser, true);
        HACLStorage::getDatabase()->removeAllMembersFromGroup($this->mGroupID);
    }

    /**
     * Removes the user $user from this group. The user is immediately removed
     * from the group's definition in the database.
     *
     * @param User/string/int $user
     * 		This can be a User-object, name of a user or ID of a user. This user
     * 		is removed from the group.
     * @param User/string/int $mgUser
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     *
     */
    public function removeUser($user, $mgUser=null) {
    // Check if $mgUser can modifiy this group.
        $this->userCanModify($mgUser, true);
        list($userID, $userName) = haclfGetUserID($user);
        HACLStorage::getDatabase()->removeUserFromGroup($this->mGroupID, $userID);

    }

    /**
     * Removes the group $group from this group. The group is immediately removed
     * from the group's definition in the database.
     *
     * @param mixed(HACLGroup/string/id) $group
     * 		Group object, name or ID of the group that is removed from $this group.
     * @param User/string/int $mgUser
     * 		User-object, name of a user or ID of a user who wants to modify this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 		HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     * 		HACLGroupException(HACLGroupException::INVALID_GROUP_ID)
     *
     */
    public function removeGroup($group, $mgUser=null) {
    // Check if $mgUser can modifiy this group.
        $this->userCanModify($mgUser, true);

        $groupID = self::idForGroup($group);
        if ($groupID == 0) {
            throw new HACLGroupException(HACLGroupException::INVALID_GROUP_ID,
            $groupID);
        }

        HACLStorage::getDatabase()->removeGroupFromGroup($this->mGroupID, $groupID);
    }

    /**
     * Returns all users who are member of this group.
     *
     * @param int $mode
     * 		HACLGroup::NAME:   The names of all users are returned.
     * 		HACLGroup::ID:     The IDs of all users are returned.
     * 		HACLGroup::OBJECT: User-objects for all users are returned.
     *
     * @return array(string/int/User)
     * 		List of all direct users in this group.
     *
     */
    public function getUsers($mode) {
    // retrieve the IDs of all users in this group
        $users = HACLStorage::getDatabase()->getMembersOfGroup($this->mGroupID, self::USER);
		$dynamicUsers = $this->queryDynamicMembers(self::ID);
		$dynamicUsers = $dynamicUsers['users'];
		$users = array_merge($users, $dynamicUsers);
		$users = array_merge(array_unique($users));
		
        if ($mode === self::ID) {
            return $users;
        }
        for ($i = 0; $i < count($users); ++$i) {
            if ($mode === self::NAME) {
                $users[$i] = User::whoIs($users[$i]);
            } else if ($mode === self::OBJECT) {
                    $users[$i] = User::newFromId($users[$i]);
                }
        }
        return $users;
    }

    /**
     * Returns all groups the user or the group with the ID $memberId is member of.
     *
     *
	 * @return array<array<"id" => int, "name" => string>>
	 * 		List of IDs of all direct users or groups in this group.
     * 
     */
    public static function getGroupsOfMember($memberId, $type = self::USER, 
    										 $recursive = false) {
		$db = HACLStorage::getDatabase();									 	
    	$groups = $db->getGroupsOfMember($memberId, $type);
    	if (!$recursive || empty($groups)) {
    		return $groups;
    	}
    	$result = array();
    	$parents = array();
    	$processedParents = array();
    	do {
    		// copy new parents
    		foreach ($groups as $g) {
    			$id = $g['id'];
    			if (!in_array($id, $processedParents)
    				&& !in_array($id, $parents)) {
    				$parents[] = $id;
    				$result[] = $g;
    			}
    		}
    		$groups = null;
    		$id = array_shift($parents);
    		if (!is_null($id)) {
    			$groups = $db->getGroupsOfMember($id, self::GROUP);
    			$processedParents[] = $id;
    		}
    		
    	} while (!empty($parents) || !empty($groups));
    	
    	return $result;
    }

    /**
     * Returns all groups who are member of this group.
     *
     * @param int $mode
     * 		HACLGroup::NAME:   The names of all groups are returned.
     * 		HACLGroup::ID:     The IDs of all groups are returned.
     * 		HACLGroup::OBJECT: HACLGroup-objects for all groups are returned.
     *
     * @return array(string/int/HACLGroup)
     * 		List of all direct groups in this group.
     *
     */
    public function getGroups($mode) {
    	// retrieve the IDs of all groups in this group
        $groups = HACLStorage::getDatabase()->getMembersOfGroup($this->mGroupID, self::GROUP);

        // retrieve all dynamic member groups
		$dynamicGroups = $this->queryDynamicMembers(self::ID);
		$dynamicGroups = $dynamicGroups['groups'];
		$groups = array_merge($groups, $dynamicGroups);
        $groups = array_merge(array_unique($groups));
        
        if ($mode === self::ID) {
            return $groups;
        }
        for ($i = 0; $i < count($groups); ++$i) {
            if ($mode === self::NAME) {
                $groups[$i] = self::nameForID($groups[$i]);
            } else if ($mode === self::OBJECT) {
                    $groups[$i] = self::newFromID($groups[$i]);
                }
        }
        return $groups;

    }
    
	/**
	 * Executes the queries for dynamic members and returns them in an array.
	 * 
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
	 */
	public function queryDynamicMembers($mode = HACLGroup::ID) {
		
		$memberCache = HACLDynamicMemberCache::getInstance();
		if ($memberCache->isCached($this->mGroupID)) {
			return $memberCache->getMembers($this->mGroupID, $mode);
		}
		
    	// Members are not cached 
    	// => Iterate over all queries and fill the cache 
    	if (empty($this->mDynamicMemberQueries)) {
    		// There are no dynamic member queries
    		// => cache an empty array for faster access
			$memberCache->addMembers($this->mGroupID, array());
    	} else {
			foreach ($this->mDynamicMemberQueries as $dmq) {
				$members = self::executeDMQuery($dmq);
				$memberCache->addMembers($this->mGroupID, $members);
			}
    	}
		
		return $memberCache->getMembers($this->mGroupID, $mode);
	}	
	
    

    /**
     * Checks if this group has the given group as member.
     *
     * @param mixed (int/string/HACLGroup) $group
     * 		ID, name or object for the group that is checked for membership.
     *
     * @param bool recursive
     * 		<true>, checks recursively among all children of this group if
     * 				$group is a member
     * 		<false>, checks only if $group is an immediate member of this group
     *
     * @return bool
     * 		<true>, if $group is a member of this group
     * 		<false>, if not
     * @throws
     * 		HACLGroupException(HACLGroupException::NO_GROUP_ID)
     * 			...if the name of the group is invalid
     * 		HACLGroupException(HACLGroupException::INVALID_GROUP_ID)
     * 			...if the ID of the group is invalid
     *
     */
    public function hasGroupMember($group, $recursive) {
        $groupID = self::idForGroup($group);

        if ($groupID === 0) {
            throw new HACLGroupException(HACLGroupException::INVALID_GROUP_ID,
            $groupID);
        }

        if (HACLStorage::getDatabase()
        		->hasGroupMember($this->mGroupID, $groupID, self::GROUP, $recursive)) {
			return true;
		}
        	
		return $this->hasDynamicMember($groupID, self::GROUP, $recursive);
        	
    }

    /**
     * Checks if this group has the given user as member.
     *
     * @param User/string/int $user
     * 		ID, name or object for the user that is checked for membership.
     *
     * @param bool recursive
     * 		<true>, checks recursively among all children of this group if
     * 				$group is a member
     * 		<false>, checks only if $group is an immediate member of this group
     *
     * @return bool
     * 		<true>, if $group is a member of this group
     * 		<false>, if not
     * @throws
     * 		HACLException(HACLException::UNKOWN_USER)
     * 			...if the user does not exist.
     *
     */
    public function hasUserMember($user, $recursive) {
        $userID = haclfGetUserID($user);
        $userID= $userID[0];
		// Do a quick check in the database
        if (HACLStorage::getDatabase()
        	   ->hasGroupMember($this->mGroupID, $userID, 
        	                    self::USER, $recursive)) {
			return true;        	                    	
		}
        
		return $this->hasDynamicMember($userID, self::USER, $recursive);
        	
    }

    /**
     * Checks if this group has the given dynamic member with the ID $id.
     * @param int $id
     * 		Group or user ID
     * @param string $mode
     * 		HACLGroup::USER - search for a user
     * 		HACLGroup::GROUP - search for a group
     * @param bool $recursive
     * 		If <true>, perform a recursive search
     * @return bool
     * 		<true>, if $id is a member of this group
     * 		<false> otherwise 
     */
    public  function hasDynamicMember($id, $mode, $recursive) {
        // Search for dynamic members
        $processedGroups = array();
        $pendingGroups = array($this->mGroupID);
        while (!empty($pendingGroups)) {
        	$gid = array_shift($pendingGroups);
        	try {
        		$group = ($gid == $this->mGroupID) ? $this : HACLGroup::newFromID($gid);
        	} catch (HACLGroupException $e) {
        		// The group does not exist
        		continue;
        	}
        	
        	// Get all static and dynamic member users
        	if ($mode === self::USER) {
	        	$users = $group->getUsers(self::ID);
	        	// Check if user is present 
	        	if (in_array($id, $users)) {
	        		return true;
	        	}
	        	if (!$recursive) {
	        		// No recursion => don't examine other groups
	        		return false;
	        	}
        	}
        	
        	// mark the current group as processed
        	$processedGroups[$gid] = true;
        	
        	// Get all static and dynamic group members and add them to the
        	// list of pending groups.
        	$groups = $group->getGroups(self::ID);
        	if ($mode === self::GROUP) {
        		if (in_array($id, $groups)) {
        			return true;
        		}
        	}
        	if (!$recursive) {
        		return false;
        	}
        	foreach ($groups as $g) {
        		if (!array_key_exists($g, $processedGroups) 
        		    && !in_array($g, $pendingGroups)) {
        		    // group is not yet processed and not pending
        		    $pendingGroups[] = $g;
				}
        	}
        }
        
        // user or group not found
        return false;
    }
    

    /**
     * Deletes this group from the database. All references to this group in the
     * hierarchy of groups are deleted as well.
     *
     * @param User/string/int $user
     * 		User-object, name of a user or ID of a user who wants to delete this
     * 		group. If <null>, the currently logged in user is assumed.
     *
     * @throws
     * 	HACLGroupException(HACLGroupException::USER_CANT_MODIFY_GROUP)
     *
     */
    public function delete($user = null) {
        $this->userCanModify($user, true);
        return HACLStorage::getDatabase()->deleteGroup($this->mGroupID);
    }

    /**
     * returns group description
     * containing users and groups
     * @return <string>
     */
    public function getGroupDescription() {
    	$result = "";
    	foreach($this->getUsers(HACLGroup::NAME) as $i) {
    		if ($result == "") {
    			$result = "U:$i";
    		} else {
    			$result .= ", U:$i";
    		}
    	}

    	global $haclgContLang;
    	$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP)."/";

    	foreach($this->getGroups(HACLGroup::NAME) as $groupName) {
    		if (strpos($groupName, $prefix) === 0) {
    			// Remove the prefix of the naming convention e.g. "Group/"
    			$groupName = substr($groupName, strlen($prefix));
    		}
    		if ($result == "") {
    			$result = "G:$groupName";
    		} else {
    			$result .= ", G:$groupName";
    		}
    	}
    	return $result;
    }
    
    /**
     * Saves the definition of this group as article. If the article already exists
     * it is overwritten.
     * 
     * @return bool success
     * 		<true>, if the article was saved successfully and
     * 		<false> otherwise.
     */
    public function saveArticle() {
    	global $haclgContLang;
    	$articleName = $haclgContLang->getNamespaces();
    	$articleName = $articleName[HACL_NS_ACL];
    	$articleName .= ':'.$this->mGroupName;
    	
    	// serialize members
    	global $wgContLang;
    	$userNS = $wgContLang->getNsText(NS_USER);
    	$members = array();
    	
    	$users = $this->getUsers(self::NAME);
    	foreach ($users as $u) {
    		$members[] = "$userNS:$u";
    	}
    	
    	$groups = $this->getGroups(self::NAME);
    	foreach ($groups as $g) {
    		$members[] = $g;
    	}
    	
    	$members = implode(', ', $members);
    	
    	// serialize managers
    	$managers = array();
    	
    	$users = $this->getManageUsers();
		foreach ($users as $uid) {
			if ($uid > 0) {
				$u = User::whoIs($uid);
				if ($u !== false) {
					$managers[] = "$userNS:$u";
				}
			} 
		}
			    	
    	$groups = $this->getManageGroups();
    	foreach ($groups as $gid) {
    		$g = self::nameForID($gid);
    		if (!is_null($g)) {
    			$managers[] = $g;
    		}
    	}
    	
    	$managers = implode(', ', $managers);
    	

    	// Serialization as wiki text
    	$wikitext = <<<GROUP
{{#{$haclgContLang->getParserFunction(HACLLanguage::PF_MEMBER)}:
{$haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_MEMBERS)}=$members
}}

{{#{$haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_GROUP)}:
{$haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_ASSIGNED_TO)}=$managers
}}

[[{$haclgContLang->getCategory(HACLLanguage::CAT_GROUP)}]]
GROUP
;

		// save the article
		$title = Title::newFromText($articleName);
		$article = new Article($title);
		// Set the article's content
		
		$status = $article->doEdit($wikitext, 'Group serialization', 
		                            $article->exists() ? EDIT_UPDATE : EDIT_NEW);

		return $status->isOK();
    	
    }
    
    /**
     * Reads all dynamic member queries of this group from the database. The
     * current array of queries is overwritten. 
     */
    public function initDynamicMemberQueriesFromDB() {
        $this->mDynamicMemberQueries = 
        	HACLStorage::getDatabase()->getDynamicMemberQueriesForGroup($this->mGroupID);
    }
    
	/**
	 * Executes a query for dynamic members and returns the resulting user and
	 * group names.
	 * 
	 * @param string $query
	 * 		The query for members.
	 * @return array<string>
	 * 		An array of group and user names. May be empty.
	 */
	public static function executeDMQuery($query) {
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
		
		
		$res = explode(',', $res);
		foreach ($res as $k => $v) {
			$res[$k] = trim($v);
		}
		return $res;
		
	}
	
    //--- Private methods ---

	
}