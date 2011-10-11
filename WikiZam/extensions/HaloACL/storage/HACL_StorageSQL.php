<?php
/**
 * @file
 * @ingroup HaloACL_Storage
 */

/*  Copyright 2009, ontoprise GmbH
 *   This file is part of the HaloACL-Extension.
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
 * This file provides the access to the SQL database tables that are
 * used by HaloACL.
 *
 * @author Thomas Schweitzer
 *
 */

global $haclgIP;
require_once $haclgIP . '/storage/HACL_DBHelper.php';

/**
 * This class encapsulates all methods that care about the database tables of
 * the HaloACL extension. This is the implementation for the SQL database.
 *
 */
class HACLStorageSQL {
	
	const GROUP_TYPE_HACL = 'HaloACL';
	

	/**
	 * Initializes the database tables of the HaloACL extensions.
	 * These are:
	 * - halo_acl_pe_rights:
	 * 		table of materialized inline rights for each protected element
	 * - halo_acl_rights:
	 * 		description of each inline right
	 * - halo_acl_rights_hierarchy:
	 * 		hierarchy of predefined rights
	 * - halo_acl_security_descriptors:
	 * 		table for security descriptors and predefined rights
	 * - halo_acl_groups:
	 * 		stores the ACL groups
	 * - halo_acl_group_members:
	 * 		stores the hierarchy of groups and their users
	 *
	 */
	public function initDatabaseTables($verbose = true) {

		$db =& wfGetDB( DB_MASTER );

		HACLDBHelper::reportProgress("Setting up HaloACL ...\n",$verbose);

		// halo_acl_rights:
		//		description of each inline right
		$table = $db->tableName('halo_acl_rights');

		HACLDBHelper::setupTable($table, array(
            'right_id' 		=> 'INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'actions' 		=> 'INT(8) NOT NULL',
            'groups' 		=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'users' 		=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'dynamic_assignees' => 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'description' 	=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'name' 			=> 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'origin_id' 	=> 'INT(8) UNSIGNED NOT NULL'),
		$db, $verbose);
		HACLDBHelper::reportProgress("   ... done!\n",$verbose);

		// halo_acl_pe_rights:
		// 		table of materialized inline rights for each protected element
		$table = $db->tableName('halo_acl_pe_rights');

		HACLDBHelper::setupTable($table, array(
            'pe_id' 	=> 'INT(8) NOT NULL',
            'type' 		=> 'ENUM(\'category\', \'page\', \'namespace\', \'property\', \'whitelist\') DEFAULT \'page\' NOT NULL',
            'right_id' 	=> 'INT(8) UNSIGNED NOT NULL'),
		$db, $verbose, "pe_id,type,right_id");
		HACLDBHelper::reportProgress("   ... done!\n",$verbose);

		// halo_acl_rights_hierarchy:
		//		hierarchy of predefined rights
		$table = $db->tableName('halo_acl_rights_hierarchy');

		HACLDBHelper::setupTable($table, array(
            'parent_right_id' 	=> 'INT(8) UNSIGNED NOT NULL',
            'child_id'			=> 'INT(8) UNSIGNED NOT NULL'),
		$db, $verbose, "parent_right_id,child_id");
		HACLDBHelper::reportProgress("   ... done!\n",$verbose, "parent_right_id, child_id");

		// halo_acl_security_descriptors:
		//		table for security descriptors and predefined rights
		$table = $db->tableName('halo_acl_security_descriptors');

		HACLDBHelper::setupTable($table, array(
            'sd_id' 	=> 'INT(8) UNSIGNED NOT NULL PRIMARY KEY',
            'pe_id' 	=> 'INT(8)',
            'type' 		=> 'ENUM(\'category\', \'page\', \'namespace\', \'property\', \'right\') DEFAULT \'page\' NOT NULL',
            'mr_groups' => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin',
            'mr_users' 	=> 'TEXT CHARACTER SET utf8 COLLATE utf8_bin'),
		$db, $verbose);
		HACLDBHelper::reportProgress("   ... done!\n",$verbose);

		// halo_acl_groups:
		//		stores the ACL groups
		$table = $db->tableName('halo_acl_groups');

		HACLDBHelper::setupTable($table, array(
            'group_id'   => 'INT(8) UNSIGNED NOT NULL PRIMARY KEY',
            'group_name' => 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL',
            'mg_groups'  => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin',
            'mg_users'   => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin'),
		$db, $verbose);
		HACLDBHelper::reportProgress("   ... done!\n",$verbose);

		// halo_acl_group_members:
		//		stores the hierarchy of groups and their users
		$table = $db->tableName('halo_acl_group_members');

		HACLDBHelper::setupTable($table, array(
            'parent_group_id' 	=> 'INT(8) UNSIGNED NOT NULL',
            'child_type' 		=> 'ENUM(\'group\', \'user\', \'dynamic\') DEFAULT \'user\' NOT NULL',
            'child_id' 			=> 'INT(8) NOT NULL',
			'dynamic_member_query'  => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin DEFAULT \'\' NOT NULL'),
		$db, $verbose, "parent_group_id,child_type,child_id,dynamic_member_query(32)");
		HACLDBHelper::reportProgress("   ... done!\n",$verbose, "parent_group_id, child_type, child_id, dynamic_member_query");

		// halo_acl_special_pages:
		//		stores the IDs of special pages that have no article ID
		$table = $db->tableName('halo_acl_special_pages');

		HACLDBHelper::setupTable($table, array(
            'id' 	=> 'INT(8) NOT NULL AUTO_INCREMENT',
            'name' 	=> 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL'),
		$db, $verbose, "id,name");
		HACLDBHelper::reportProgress("   ... done!\n",$verbose, "id,name");


		// setup quickacl-table
		$table = $db->tableName('halo_acl_quickacl');

		HACLDBHelper::setupTable($table, array(
            'sd_id' 	=> 'INT(8) NOT NULL',
            'user_id' 	=> 'INT(10) NOT NULL'),
		$db, $verbose, "sd_id,user_id");
		HACLDBHelper::reportProgress("   ... done!\n",$verbose, "sd_id,user_id");
		
		// halo_acl_group_permissions:
		//		description of each group permission for mediawiki features
		$table = $db->tableName('halo_acl_group_permissions');

		HACLDBHelper::setupTable($table, array(
            'group_id' 		=> 'INT(8) NOT NULL',
            'feature' 		=> 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL',
            'permission'	=> 'BOOL'),
		$db, $verbose, "group_id,feature");
		HACLDBHelper::reportProgress("   ... done!\n",$verbose);
		
		return true;

	}
	
	public function dropDatabaseTables($verbose = true) {
		global $wgDBtype;
		
		HACLDBHelper::reportProgress("Deleting all database content and tables generated by HaloACL ...\n\n",$verbose);
		$db =& wfGetDB( DB_MASTER );
		$tables = array(
			'halo_acl_rights',
			'halo_acl_pe_rights',
			'halo_acl_rights_hierarchy',
			'halo_acl_security_descriptors',
			'halo_acl_groups',
			'halo_acl_group_members',
			'halo_acl_special_pages',
			'halo_acl_quickacl',
			'halo_acl_group_permissions');
		foreach ($tables as $table) {
			$name = $db->tableName($table);
			$db->query('DROP TABLE' . ($wgDBtype=='postgres'?'':' IF EXISTS'). $name, 'SMWSemanticStoreSQL2::drop');
			HACLDBHelper::reportProgress(" ... dropped table $name.\n", $verbose);
		}
		HACLDBHelper::reportProgress("All data removed successfully.\n",$verbose);
	}

	/***************************************************************************
	 *
	 * Functions for groups
	 *
	 **************************************************************************/

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
	public function groupNameForID($groupID) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_groups');
		$sql = "SELECT group_name FROM $gt ".
            "WHERE group_id = {$db->addQuotes($groupID)};";
		$groupName = null;

		$res = $db->query($sql);

		if ($db->numRows($res) == 1) {
			$row = $db->fetchObject($res);
			$groupName = $row->group_name;
		}
		$db->freeResult($res);

		return $groupName;
	}

	/**
	 * Saves the given group in the database.
	 *
	 * @param HACLGroup $group
	 * 		This object defines the group that wil be saved.
	 *
	 * @throws
	 * 		Exception
	 *
	 */
	public function saveGroup(HACLGroup $group) {
		$db =& wfGetDB( DB_MASTER );

		$mgGroups = implode(',', $group->getManageGroups());
		$mgUsers  = implode(',', $group->getManageUsers());
		$db->replace($db->tableName('halo_acl_groups'), null, array(
            'group_id'    =>  $group->getGroupID() ,
            'group_name'	=>  $group->getGroupName() ,
            'mg_groups'   =>  $mgGroups,
            'mg_users'    =>  $mgUsers));

	}

	/**
	 * Retrieves all top level groups from the database.
	 *
	 * @return Array
	 * 		Array of Group Objects
	 *
	 */
	public function getGroups() {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_groups');
		$gmt = $db->tableName('halo_acl_group_members');
	/*
        	$sql = "SELECT * FROM $gt
		WHERE NOT EXISTS (

		SELECT child_id
		FROM $gmt
		WHERE $gmt.child_id = halo_acl_groups.group_id 
                AND $gmt.parent_group_id != $gmt.child_id
		) order by $gt.group_name

            ";
         *
         */
        	$sql = "SELECT * FROM $gt
		LEFT JOIN $gmt on $gt.group_id = $gmt.child_id
                WHERE $gmt.parent_group_id is null OR $gmt.parent_group_id = $gmt.child_id

            ";



		$groups = array();

		$res = $db->query($sql);

		while ($row = $db->fetchObject($res)) {

			$groupID = $row->group_id;
			$groupName = $row->group_name;
			$mgGroups = self::strToIntArray($row->mg_groups);
			$mgUsers  = self::strToIntArray($row->mg_users);

			$groups[] = new HACLGroup($groupID, $groupName, $mgGroups, $mgUsers, 
									  true, self::GROUP_TYPE_HACL);
		}

		$db->freeResult($res);

		return $groups;
	}


	/**
	 * Retrieves the description of the group with the name $groupName from
	 * the database.
	 *
	 * @param string $groupName
	 * 		Name of the requested group.
	 *
	 * @return HACLGroup
	 * 		A new group object or <null> if there is no such group in the
	 * 		database.
	 *
	 */
	public function getGroupByName($groupName) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_groups');
		$sql = "SELECT * FROM $gt ".
            "WHERE group_name = {$db->addQuotes($groupName)};";
		$group = null;

		$res = $db->query($sql);

		if ($db->numRows($res) == 1) {
			$row = $db->fetchObject($res);
			$groupID = $row->group_id;
			$mgGroups = self::strToIntArray($row->mg_groups);
			$mgUsers  = self::strToIntArray($row->mg_users);

			$group = new HACLGroup($groupID, $groupName, $mgGroups, $mgUsers, 
								   true, self::GROUP_TYPE_HACL);
		}
		$db->freeResult($res);

		return $group;
	}
	
	/**
	 * Searches for all groups whose name contains the search string $search.
	 * 
	 * @param string $search
	 * 		The group name must contain the string. Comparison is case insensitive.
	 * 
	 * @return array(string => int)
	 * 		A map from group names to group IDs of groups that match the search 
	 * 		string. Matches in the prefix of a group name (e.g. "Group/someName")
	 * 		are not removed.
	 */
	public function searchMatchingGroups($search) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_groups');
		$search = $db->addQuotes("%$search%");
		$sql = "SELECT group_name, group_id FROM $gt ".
               "WHERE lower(group_name) LIKE lower(CONVERT($search USING utf8));";
		$group = null;

		$res = $db->query($sql);

		$matches = array();
		while ($row = $db->fetchObject($res)) {
			$matches[$row->group_name] = (int) $row->group_id;
		}
		$db->freeResult($res);

		return $matches;
		
	}

	/**
	 * Retrieves the description of the group with the ID $groupID from
	 * the database.
	 *
	 * @param int $groupID
	 * 		ID of the requested group.
	 *
	 * @return HACLGroup
	 * 		A new group object or <null> if there is no such group in the
	 * 		database.
	 *
	 */
	public function getGroupByID($groupID) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_groups');
		$sql = "SELECT * FROM $gt ".
            "WHERE group_id = {$db->addQuotes($groupID)};";
		$group = null;

		$res = $db->query($sql);

		if ($db->numRows($res) == 1) {
			$row = $db->fetchObject($res);
			$groupID = $row->group_id;
			$groupName = $row->group_name;
			$mgGroups = self::strToIntArray($row->mg_groups);
			$mgUsers  = self::strToIntArray($row->mg_users);

			$group = new HACLGroup($groupID, $groupName, $mgGroups, $mgUsers, 
								   true, self::GROUP_TYPE_HACL);
		}
		$db->freeResult($res);

		return $group;
	}

	/**
	 * Adds the user with the ID $userID to the group with the ID $groupID.
	 *
	 * @param int $groupID
	 * 		The ID of the group to which the user is added.
	 * @param int $userID
	 * 		The ID of the user who is added to the group.
	 *
	 */
	public function addUserToGroup($groupID, $userID) {
		$db =& wfGetDB( DB_MASTER );

		$db->replace($db->tableName('halo_acl_group_members'), null, array(
            'parent_group_id'    =>  $groupID ,
            'child_type'	=>  'user' ,
            'child_id '   =>  $userID));

	}

	/**
	 * Adds the group with the ID $childGroupID to the group with the ID
	 * $parentGroupID.
	 *
	 * @param $parentGroupID
	 * 		The group with this ID gets the new child with the ID $childGroupID.
	 * @param $childGroupID
	 * 		The group with this ID is added as child to the group with the ID
	 *      $parentGroup.
	 *
	 */
	public function addGroupToGroup($parentGroupID, $childGroupID) {
		$db =& wfGetDB( DB_MASTER );

		$db->replace($db->tableName('halo_acl_group_members'), null, array(
            'parent_group_id'    =>  $parentGroupID ,
            'child_type'	=>  'group' ,
            'child_id '   =>  $childGroupID));

	}

	/**
     * Adds the queries for dynamic members $dmq to the group with the ID
     * $parentGroupID.
	 *
	 * @param $parentGroupID
	 * 		The group with this ID gets the new child with the ID $childGroupID.
     * @param array(string) $dmq
     * 		Array of ask or sparql queries.
	 *
	 */
	public function addDynamicMemberQueriesToGroup($parentGroupID, $dmq) {
		$db =& wfGetDB( DB_MASTER );
		
		foreach ($dmq as $q) {
			$db->replace($db->tableName('halo_acl_group_members'), null, array(
	            'parent_group_id'       => $parentGroupID ,
	            'child_type'	        => 'dynamic' ,
			    'child_id '             => 0,
	            'dynamic_member_query ' => $q));
		}

	}
	
	/**
	 * Removes the user with the ID $userID from the group with the ID $groupID.
	 *
	 * @param $groupID
	 * 		The ID of the group from which the user is removed.
	 * @param int $userID
	 * 		The ID of the user who is removed from the group.
	 *
	 */
	public function removeUserFromGroup($groupID, $userID) {
		$db =& wfGetDB( DB_MASTER );

		$db->delete($db->tableName('halo_acl_group_members'), array(
            'parent_group_id'    => $groupID ,
            'child_type'	=>  'user' ,
            'child_id '   =>  $userID));

	}

	/**
	 * Removes all members from the group with the ID $groupID.
	 *
	 * @param $groupID
	 * 		The ID of the group from which the user is removed.
	 *
	 */
	public function removeAllMembersFromGroup($groupID) {
		$db =& wfGetDB( DB_MASTER );

		$db->delete($db->tableName('halo_acl_group_members'),
		array('parent_group_id' => $groupID));

	}


	/**
	 * Removes the group with the ID $childGroupID from the group with the ID
	 * $parentGroupID.
	 *
	 * @param $parentGroupID
	 * 		This group loses its child $childGroupID.
	 * @param $childGroupID
	 * 		This group is removed from $parentGroupID.
	 *
	 */
	public function removeGroupFromGroup($parentGroupID, $childGroupID) {
		$db =& wfGetDB( DB_MASTER );

		$db->delete($db->tableName('halo_acl_group_members'), array(
            'parent_group_id'    =>  $parentGroupID,
            'child_type'	=>  'group' ,
            'child_id '   =>  $childGroupID));

	}

	/**
	 * Returns the IDs of all users or groups that are a member of the group
	 * with the ID $groupID.
	 *
	 * @param string $memberType
	 * 		'user' => ask for all user IDs
	 *      'group' => ask for all group IDs
	 * @return array(int)
	 * 		List of IDs of all direct users or groups in this group.
	 *
	 */
	public function getMembersOfGroup($groupID, $memberType) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_group_members');
		$sql = "SELECT child_id FROM $gt ".
            "WHERE parent_group_id = {$db->addQuotes($groupID)} AND ".
            "child_type={$db->addQuotes($memberType)};";

		$res = $db->query($sql);

		$members = array();
		while ($row = $db->fetchObject($res)) {
			$members[] = (int) $row->child_id;
		}

		$db->freeResult($res);

		return $members;

	}

	/**
	 * Returns all direct groups the user is member of.
	 *
	 * @param string $memberID
	 * 		ID of the user or group who's direct groups are retrieved.
	 * @param string $type
	 * 		HACLGroup::USER: retrieve parent groups of a user
	 * 		HACLGroup::GROUP: retrieve parent groups of a group
	 * @return array<array<"id" => int, "name" => string>>
	 * 		List of IDs of all direct groups of the given user.
	 *
	 */
	public function getGroupsOfMember($memberID, $type = HACLGroup::USER) {

		$db =& wfGetDB( DB_SLAVE );
		$ut = $db->tableName('user');
		$gt = $db->tableName('halo_acl_groups');
		$gmt = $db->tableName('halo_acl_group_members');
		if ($type == HACLGroup::USER) {
			$sql = "SELECT DISTINCT user_id, group_id, group_name
					FROM $ut
					LEFT JOIN  $gmt ON  $gmt.child_id = $ut.user_id
					LEFT JOIN $gt ON $gt.group_id = $gmt.parent_group_id
					WHERE $ut.user_id = $memberID AND $gmt.child_type = 'user'
	            ";
		} else {
			$sql = "SELECT DISTINCT group_id, group_name
					FROM $gmt gmt
         			LEFT JOIN $gt ON $gt.group_id = gmt.parent_group_id
					WHERE gmt.child_id = $memberID AND gmt.child_type = 'group'";
		}
		$res = $db->query($sql);

		$curGroupArray = array();

		while ($row = $db->fetchObject($res)) {
			if (isset($row->group_id) && isset($row->group_name)) {
				$curGroupArray[] = array("id"   => $row->group_id, 
				                         "name" => $row->group_name);
			}
		}

		$db->freeResult($res);

		return $curGroupArray;


	}

	/**
	 * Checks if the given user or group with the ID $childID belongs to the
	 * group with the ID $parentID.
	 *
	 * @param int $parentID
	 * 		ID of the group that is checked for a member.
	 *
	 * @param int $childID
	 * 		ID of the group or user that is checked for membership.
	 *
	 * @param string $memberType
	 * 		HACLGroup::USER  : Checks for membership of a user
	 * 		HACLGroup::GROUP : Checks for membership of a group
	 *
	 * @param bool recursive
	 * 		<true>, checks recursively among all children of this $parentID if
	 * 				$childID is a member
	 * 		<false>, checks only if $childID is an immediate member of $parentID
	 *
	 * @return bool
	 * 		<true>, if $childID is a member of $parentID
	 * 		<false>, if not
	 *
	 */
	public function hasGroupMember($parentID, $childID, $memberType, $recursive) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_group_members');

		// Ask for the immediate parents of $childID
		$sql = "SELECT parent_group_id FROM $gt ".
            "WHERE child_id = '$childID' AND ".
            "child_type='$memberType';";

		$res = $db->query($sql);

		$parents = array();
		while ($row = $db->fetchObject($res)) {
			if ($parentID == (int) $row->parent_group_id) {
				$db->freeResult($res);
				return true;
			}
			$parents[] = (int) $row->parent_group_id;
		}
		$db->freeResult($res);

		// $childID is not an immediate child of $parentID
		if (!$recursive || empty($parents)) {
			return false;
		}

		// Check recursively, if one of the parent groups of $childID is $parentID

		$ancestors = array();
		while (true) {
			// Check if one of the parent's parent is $parentID
			$sql = "SELECT parent_group_id FROM $gt ".
                "WHERE parent_group_id='$parentID' AND ".
                "child_id in (".implode(',', $parents).") AND ".
                "child_type='group';";

			$res = $db->query($sql);
			if ($db->numRows($res) == 1) {
				// The request parent was found
				$db->freeResult($res);
				return true;
			}

			// Parent was not found => retrieve all parents of the current set of
			// parents.
			$sql = "SELECT DISTINCT parent_group_id FROM $gt WHERE ".
			(empty($ancestors) ? ""
			: "parent_group_id not in (".implode(',', $ancestors).") AND ").
                "child_id in (".implode(',', $parents).") AND ".
                "child_type='group';";

			$res = $db->query($sql);
			if ($db->numRows($res) == 0) {
				// The request parent was found
				$db->freeResult($res);
				return false;
			}

			$ancestors = array_merge($ancestors, $parents);
			$parents = array();
			while ($row = $db->fetchObject($res)) {
				if ($parentID == (int) $row->parent_group_id) {
					$db->freeResult($res);
					return true;
				}
				$parents[] = (int) $row->parent_group_id;
			}
			$db->freeResult($res);
		}

	}
	
	/**
	 * Return an array of dynamic member queries for the group with the ID
	 * $groupID.
	 * @param int $groupID
	 * 		ID of the group
	 * @return array(string)
	 * 		The member queries
	 */
	public function getDynamicMemberQueriesForGroup($groupID) {
		$db =& wfGetDB( DB_SLAVE );
		$gt = $db->tableName('halo_acl_group_members');

		// Ask for the immediate parents of $childID
		$sql = "SELECT dynamic_member_query FROM $gt ".
            "WHERE parent_group_id = '$groupID' AND ".
            "child_type='dynamic';";

		$res = $db->query($sql);

		$dmq = array();
		while ($row = $db->fetchObject($res)) {
			$dmq[] = $row->dynamic_member_query;
		}
		$db->freeResult($res);
		
		return $dmq;
	}

	/**
	 * Deletes the group with the ID $groupID from the database. All references
	 * to the group in the hierarchy of groups are deleted as well.
	 *
	 * However, the group is not removed from any rights, security descriptors etc.
	 * as this would mean that articles will have to be changed.
	 *
	 *
	 * @param int $groupID
	 * 		ID of the group that is removed from the database.
	 *
	 */
	public function deleteGroup($groupID) {
		$db =& wfGetDB( DB_MASTER );

		// Delete the group from the hierarchy of groups (as parent and as child)
		$table = $db->tableName('halo_acl_group_members');
		$db->delete($table, array('parent_group_id' => $groupID));
		$db->delete($table, array('child_type'	=>  'group',
            'child_id '   =>  $groupID));

		// Delete the group's definition
		$table = $db->tableName('halo_acl_groups');
		$db->delete($table, array('group_id' => $groupID));

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
	public function groupExists($groupID) {
		$db =& wfGetDB( DB_SLAVE );

		$obj = $db->selectRow($db->tableName('halo_acl_groups'),
								array("group_id"), array("group_id" => $groupID));
		return ($obj !== false);
	}
	
	/**
	 * Checks if there are several definitions for the group with the specified
	 * $groupName. This can happen if another source of groups is attached.
	 * 
	 * @param string $groupName
	 * 		The name of the group that is checked.
	 * @return bool
	 * 		false
	 * 		In the SQL storage layer there can be no overloaded groups. 
	 */
	public function isOverloaded($groupName) {
		return false;
	}


	/***************************************************************************
	 *
	 * Functions for security descriptors (SD)
	 *
	 **************************************************************************/



	/**
	 * Retrieves all SDs from
	 * the database.
	 *
	 *
	 * @return Array
	 * 		Array of SD Objects
	 *
	 */
	public function getSDs($types) {
		global $haclgContLang;
		$defTemplatePrefix = $haclgContLang->getSDTemplateName();
		
		$db =& wfGetDB( DB_SLAVE );
		$sdt = $db->tableName('halo_acl_security_descriptors');
		$p = $db->tableName('page');
		$u = $db->tableName('user');
		$sql = "SELECT * FROM $sdt
		LEFT JOIN $p ON $p.page_id = $sdt.sd_id
		WHERE 1=0";

		foreach ($types as $type) {
			switch($type) {
				case "all": 
					$sql .= " OR 1" ; 
					break;
				case "page": 
					$sql .= " OR type='page'" ; 
					break;
				case "category": 
					$sql .= " OR type='category'" ; 
					break;
				case "property": 
					$sql .= " OR type='property'" ; 
					break;
				case "namespace": 
					$sql .= " OR type='namespace'" ; 
					break;
				case "standardacl": 
					$sql .= " OR (type='namespace' OR type='property' OR type='category' OR type='page')" ; 
					break;
				case "acltemplate": 
					$sql .= " OR (pe_id='0' AND NOT ($p.page_title IN (SELECT CONCAT(\"$defTemplatePrefix/\", user_name) FROM $u)))" ; 
					break;
				case "defusertemplate": 
					$sql .= " OR (pe_id='0' AND ($p.page_title IN (SELECT CONCAT(\"$defTemplatePrefix/\", user_name) FROM $u)))" ; 
					break;
			}
		}

                $sql .= " ORDER BY page_title";
		$sds = array();
		$res = $db->query($sql);
		while ($row = $db->fetchObject($res)) {
			$sds[] = HACLSecurityDescriptor::newFromID($row->sd_id);
		}
		$db->freeResult($res);

		return $sds;
	}

	/**
	 * Saves the given SD in the database.
	 *
	 * @param HACLSecurityDescriptor $sd
	 * 		This object defines the SD that wil be saved.
	 *
	 * @throws
	 * 		Exception
	 *
	 */
	public function saveSD(HACLSecurityDescriptor $sd) {
		$db =& wfGetDB( DB_MASTER );

		$mgGroups = implode(',', $sd->getManageGroups());
		$mgUsers  = implode(',', $sd->getManageUsers());
		$db->replace($db->tableName('halo_acl_security_descriptors'), null, array(
            'sd_id'       =>  $sd->getSDID() ,
            'pe_id'	    =>  $sd->getPEID(),
            'type'	    =>  $sd->getPEType(),
            'mr_groups'   =>  $mgGroups,
            'mr_users'    =>  $mgUsers));

	}

	/**
	 * Adds a predefined right to a security descriptor or a predefined right.
	 *
	 * The table "halo_acl_rights_hierarchy" stores the hierarchy of rights. There
	 * is a tuple for each parent-child relationship.
	 *
	 * @param int $parentRightID
	 * 		ID of the parent right or security descriptor
	 * @param int $childRightID
	 * 		ID of the right that is added as child
	 * @throws
	 * 		Exception
	 * 		... on database failure
	 */
	public function addRightToSD($parentRightID, $childRightID) {
		$db =& wfGetDB( DB_MASTER );

		$db->replace($db->tableName('halo_acl_rights_hierarchy'), null, array(
            'parent_right_id' => $parentRightID,
            'child_id'	    => $childRightID));
	}

	/**
	 * Adds the given inline rights to the protected elements of the given
	 * security descriptors.
	 *
	 * The table "halo_acl_pe_rights" stores for each protected element (e.g. a
	 * page) its type of protection and the IDs of all inline rights that are
	 * assigned.
	 *
	 * @param array<int> $inlineRights
	 * 		This is an array of IDs of inline rights. All these rights are
	 * 		assigned to all given protected elements.
	 * @param array<int> $securityDescriptors
	 * 		This is an array of IDs of security descriptors that protect elements.
	 * @throws
	 * 		Exception
	 * 		... on database failure
	 */
	public function setInlineRightsForProtectedElements($inlineRights,
	$securityDescriptors) {
		$db =& wfGetDB( DB_MASTER );

		foreach ($securityDescriptors as $sd) {
			// retrieve the protected element and its type
			$obj = $db->selectRow($db->tableName('halo_acl_security_descriptors'),
									array("pe_id","type"), array("sd_id" => $sd));
			if (!$obj) {
				continue;
			}
			foreach ($inlineRights as $ir) {
				$db->replace($db->tableName('halo_acl_pe_rights'), null, array(
                    'pe_id'	 =>  $obj->pe_id,
                    'type'	 =>  $obj->type,
                    'right_id' =>  $ir));
			}
		}
	}

	/**
	 * Returns the IDs of all direct inline rights of all given security
	 * descriptor IDs.
	 *
	 * @param array<int> $sdIDs
	 * 		Array of security descriptor IDs.
	 *
	 * @return array<int>
	 * 		An array of inline right IDs without duplicates.
	 */
	public function getInlineRightsOfSDs($sdIDs) {
		if (empty($sdIDs)) {
			return array();
		}
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_rights');

		$sql = "SELECT DISTINCT right_id FROM $t WHERE ".
            "origin_id in (".implode(',', $sdIDs).");";
		$res = $db->query($sql);

		$irs = array();
		while ($row = $db->fetchObject($res)) {
			$irs[] = (int) $row->right_id;
		}
		$db->freeResult($res);
		return $irs;
	}

	/**
	 * Returns the IDs of all predefined rights of the given security
	 * descriptor ID.
	 *
	 * @param int $sdID
	 * 		ID of the security descriptor.
	 * @param bool $recursively
	 * 		<true>: The whole hierarchy of rights is returned.
	 * 		<false>: Only the direct rights of this SD are returned.
	 *
	 * @return array<int>
	 * 		An array of predefined right IDs without duplicates.
	 */
	public function getPredefinedRightsOfSD($sdID, $recursively) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_rights_hierarchy');

		$parentIDs = array($sdID);
		$childIDs = array();
		$exclude = array();
		while (true) {
			if (empty($parentIDs)) {
				break;
			}
			$sql = "SELECT DISTINCT child_id FROM $t WHERE ".
                "parent_right_id in (".implode(',', $parentIDs).");";
			$res = $db->query($sql);

			$exclude = array_merge($exclude, $parentIDs);
			$parentIDs = array();

			while ($row = $db->fetchObject($res)) {
				$cid = (int) $row->child_id;
				if (!in_array($cid, $childIDs)) {
					$childIDs[] = $cid;
				}
				if (!in_array($cid, $exclude)) {
					// Add a new parent for the next level in the hierarchy
					$parentIDs[] = $cid;
				}
			}
			$numRows = $db->numRows($res);
			$db->freeResult($res);
			if ($numRows == 0 || !$recursively) {
				// No further children found
				break;
			}
		}
		return $childIDs;
	}

	/**
	 * Finds all (real) security descriptors that are related to the given
	 * predefined right. The IDs of all SDs that include this right (via the
	 * hierarchy of rights) are returned.
	 *
	 * @param int $prID
	 * 		IDs of the protected right
	 *
	 * @return array<int>
	 * 		An array of IDs of all SD that include the PR via the hierarchy
	 *      of PRs.
	 */
	public function getSDsIncludingPR($prID) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_rights_hierarchy');

		$parentIDs = array();
		$childIDs = array($prID);
		$exclude = array();
		while (true) {
			$sql = "SELECT DISTINCT parent_right_id FROM $t WHERE ".
                "child_id in (".implode(',', $childIDs).");";
			$res = $db->query($sql);

			$exclude = array_merge($exclude, $childIDs);
			$childIDs = array();

			while ($row = $db->fetchObject($res)) {
				$prid = (int) $row->parent_right_id;
				if (!in_array($prid, $parentIDs)) {
					$parentIDs[] = $prid;
				}
				if (!in_array($prid, $exclude)) {
					// Add a new child for the next level in the hierarchy
					$childIDs[] = $prid;
				}
			}
			$db->freeResult($res);
			if (empty($childIDs)) {
				// No further children found
				break;
			}
		}

		// $parentIDs now contains all SDs/PRs that include $prID
		// => select only the SDs

		$sdIDs = array();
		if (empty($parentIDs)) {
			return $sdIDs;
		}
		$t = $db->tableName('halo_acl_security_descriptors');
		$sql = "SELECT sd_id FROM $t ".
            "WHERE pe_id != 0 AND sd_id in (".implode(',', $parentIDs).");";
		$res = $db->query($sql);

		while ($row = $db->fetchObject($res)) {
			$sdIDs[] = (int) $row->sd_id;
		}
		$db->freeResult($res);

		return $sdIDs;

	}

	/**
	 * Retrieves the description of the SD with the ID $SDID from
	 * the database.
	 *
	 * @param int $SDID
	 * 		ID of the requested SD.
	 *
	 * @return HACLSecurityDescriptor
	 * 		A new SD object or <null> if there is no such SD in the
	 * 		database.
	 *
	 */
	public function getSDByID($SDID) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_security_descriptors');
		$sql = "SELECT * FROM $t ".
            "WHERE sd_id = '$SDID';";
		$sd = null;

		$res = $db->query($sql);

		if ($db->numRows($res) == 1) {
			$row = $db->fetchObject($res);
			$sdID = (int)$row->sd_id;
			$peID = (int)$row->pe_id;
			$type   = $row->type;
			$mgGroups = self::strToIntArray($row->mr_groups);
			$mgUsers  = self::strToIntArray($row->mr_users);

			$name = HACLSecurityDescriptor::nameForID($sdID);
			$sd = new HACLSecurityDescriptor($sdID, $name, $peID, $type, $mgGroups, $mgUsers);
		}
		$db->freeResult($res);

		return $sd;
	}

	/**
	 * Deletes the SD with the ID $SDID from the database. The right remains as
	 * child in the hierarchy of rights, as it is still defined as child in the
	 * articles that define its parents.
	 *
	 * @param int $SDID
	 * 		ID of the SD that is removed from the database.
	 * @param bool $rightsOnly
	 * 		If <true>, only the rights that $SDID contains are deleted from
	 * 		the hierarchy of rights, but $SDID is not removed.
	 * 		If <false>, the complete $SDID is removed (but remains as child
	 * 		in the hierarchy of rights).
	 *
	 */
	public function deleteSD($SDID, $rightsOnly = false) {
		$db =& wfGetDB( DB_MASTER );

		// Delete all inline rights that are defined by the SD (and the
		// references to them)
		$t = $db->tableName('halo_acl_rights');
		$sql = "SELECT right_id FROM $t ".
            "WHERE origin_id = '$SDID';";

		$res = $db->query($sql);

		while ($row = $db->fetchObject($res)) {
			$this->deleteRight($row->right_id);
		}
		$db->freeResult($res);

		// Remove all inline rights from the hierarchy below $SDID from their
		// protected elements. This may remove too many rights => the parents
		// of $SDID must materialize their rights again
		$prs = $this->getPredefinedRightsOfSD($SDID, true);
		$irs = $this->getInlineRightsOfSDs($prs);

		$peRights = $db->tableName('halo_acl_pe_rights');
		$secDesc = $db->tableName('halo_acl_security_descriptors');
		if (!empty($irs)) {
			$sds = $this->getSDsIncludingPR($SDID);
			$sds[] = $SDID;
			foreach ($sds as $sd) {
				// retrieve the protected element and its type
				$obj = $db->selectRow($secDesc,
				array("pe_id","type"),
				array("sd_id" => $sd));
				if (!$obj) {
					continue;
				}

				foreach ($irs as $ir) {
					$db->delete($peRights, array('right_id' => $ir,
                        'pe_id' => $obj->pe_id,
                        'type' => $obj->type));
				}
			}
		}

		// Get all direct parents of $SDID
		$res = $db->select('halo_acl_rights_hierarchy', 'parent_right_id', "child_id = $SDID");
		$parents = array();
		while ($row = $db->fetchObject($res)) {
			$parents[] = $row->parent_right_id;
		}
		$db->freeResult($res);

		// Delete the SD from the hierarchy of rights in halo_acl_rights_hierarchy
		$table = $db->tableName('halo_acl_rights_hierarchy');
		//		if (!$rightsOnly) {
		//			$db->delete($table, array('child_id' => $SDID));
		//		}
		$db->delete($table, array('parent_right_id' => $SDID));

		// Rematerialize the rights of the parents of $SDID
		foreach ($parents as $p) {
			$sd = HACLSecurityDescriptor::newFromID($p);
			$sd->materializeRightsHierarchy();
		}

		// Delete the SD from the definition of SDs in halo_acl_security_descriptors
		if (!$rightsOnly) {
			$table = $db->tableName('halo_acl_security_descriptors');
			$db->delete($table, array('sd_id' => $SDID));
		}

	}

	/***************************************************************************
	 *
	 * Functions for inline rights
	 *
	 **************************************************************************/

	/**
	 * Saves the given inline right in the database.
	 *
	 * @param HACLRight $right
	 * 		This object defines the inline right that wil be saved.
	 *
	 * @return int
	 * 		The ID of an inline right is determined by the database (AUTO INCREMENT).
	 * 		The new ID is returned.
	 *
	 * @throws
	 * 		Exception
	 *
	 */
	public function saveRight(HACLRight $right) {
		$db =& wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_rights');

		$groups = implode(',', $right->getGroups());
		$users  = implode(',', $right->getUsers());
		$dynamicAssignees = implode('##daq##', $right->getDynamicAssigneeQueries());
		$rightID = $right->getRightID();
		$setValues = array(
            'actions'     => $right->getActions(),
            'groups'	  => $groups,
            'users'	      => $users,
            'dynamic_assignees' => $dynamicAssignees,
            'description' => $right->getDescription(),
            'name'        => $right->getName(),
            'origin_id'   => $right->getOriginID());
		if ($rightID == -1) {
			// right does not exist yet in the DB.
			$db->insert($t, $setValues);
			// retrieve the auto-incremented ID of the right
			$rightID = $db->insertId();
		} else {
			$setValues['right_id'] = $rightID;
			$db->replace($t, null, $setValues);
		}

		return $rightID;
	}

	/**
	 * Retrieves the description of the inline right with the ID $rightID from
	 * the database.
	 *
	 * @param int $rightID
	 * 		ID of the requested inline right.
	 *
	 * @return HACLRight
	 * 		A new inline right object or <null> if there is no such right in the
	 * 		database.
	 *
	 */
	public function getRightByID($rightID) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_rights');
		$sql = "SELECT * FROM $t ".
            "WHERE right_id = '$rightID';";
		$sd = null;

		$res = $db->query($sql);

		if ($db->numRows($res) == 1) {
			$row = $db->fetchObject($res);
			$rightID = $row->right_id;
			$actions = $row->actions;
			$groups = self::strToIntArray($row->groups);
			$users  = self::strToIntArray($row->users);
			$dynamicAssignees = $row->dynamic_assignees;
			$dynamicAssignees = empty($dynamicAssignees)
								? array()
								: explode('##daq##', $dynamicAssignees);
			$description = $row->description;
			$name        = $row->name;
			$originID    = $row->origin_id;

			$sd = new HACLRight($actions, $groups, $users, $dynamicAssignees,
			                    $description, $name, $originID);
			$sd->setRightID($rightID);
		}
		$db->freeResult($res);

		return $sd;
	}

	/**
	 * Returns the IDs of all inline rights for the protected element with the
	 * ID $peID that have the protection type $type and match the action $actionID.
	 *
	 * @param int $peID
	 * 		ID of the protected element
	 * @param strint $type
	 * 		Type of the protected element: One of
	 *		HACLSecurityDescriptor::PET_PAGE
	 * 		HACLSecurityDescriptor::PET_CATEGORY
	 * 		HACLSecurityDescriptor::PET_NAMESPACE
	 * 		HACLSecurityDescriptor::PET_PROPERTY
	 *
	 * @param int $actionID
	 * 		ID of the action. One of
	 * 		HACLRight::READ
	 * 		HACLRight::FORMEDIT
	 * 		HACLRight::WYSIWYG
	 * 		HACLRight::EDIT
	 * 		HACLRight::ANNOTATE
	 * 		HACLRight::CREATE
	 * 		HACLRight::MOVE
	 * 		HACLRight::DELETE;
	 *
	 * @return array<int>
	 * 		An array of IDs of rights that match the given constraints.
	 */
	public function getRights($peID, $type, $actionID) {
		$db =& wfGetDB( DB_SLAVE );
		$rt = $db->tableName('halo_acl_rights');
		$rpet = $db->tableName('halo_acl_pe_rights');

		$sql = "SELECT rights.right_id FROM $rt AS rights, $rpet AS pe ".
            "WHERE pe.pe_id = $peID AND pe.type = '$type' AND ".
            "rights.right_id = pe.right_id AND".
            "(rights.actions & $actionID) != 0;";
		$sd = null;

		$res = $db->query($sql);

		$rightIDs = array();
		while ($row = $db->fetchObject($res)) {
			$rightIDs[] = $row->right_id;
		}
		$db->freeResult($res);

		return $rightIDs;

	}

	/**
	 * Deletes the inline right with the ID $rightID from the database. All
	 * references to the right (from protected elements) are deleted as well.
	 *
	 * @param int $rightID
	 * 		ID of the right that is removed from the database.
	 *
	 */
	public function deleteRight($rightID) {
		$db =& wfGetDB( DB_MASTER );

		// Delete the right from the definition of rights in halo_acl_rights
		$table = $db->tableName('halo_acl_rights');
		$db->delete($table, array('right_id' => $rightID));

		// Delete all references to the right from protected elements
		$table = $db->tableName('halo_acl_pe_rights');
		$db->delete($table, array('right_id' => $rightID));

	}

	/**
	 * Checks if the SD with the ID $sdID exists in the database.
	 *
	 * @param int $sdID
	 * 		ID of the SD
	 *
	 * @return bool
	 * 		<true> if the SD exists
	 * 		<false> otherwise
	 */
	public function sdExists($sdID) {
		$db =& wfGetDB( DB_SLAVE );

		$obj = $db->selectRow($db->tableName('halo_acl_security_descriptors'),
		array("sd_id"), array("sd_id" => $sdID));
		return ($obj !== false);
	}

	/**
	 * Tries to find the ID of the security descriptor for the protected element
	 * with the ID $peID.
	 *
	 * @param int $peID
	 * 		ID of the protected element
	 * @param int $peType
	 * 		Type of the protected element
	 *
	 * @return mixed int|bool
	 * 		int: ID of the security descriptor
	 * 		<false>, if there is no SD for the protected element
	 */
	public static function getSDForPE($peID, $peType) {
		$db =& wfGetDB( DB_SLAVE );

		$obj = $db->selectRow($db->tableName('halo_acl_security_descriptors'),
		array("sd_id"),
		array("pe_id" => $peID,
            "type"  => $peType));
		return ($obj === false) ? false : $obj->sd_id;
	}


	/***************************************************************************
	 *
	 * Functions for the whitelist
	 *
	 **************************************************************************/

	/**
	 * Stores the whitelist that is given in an array of page IDs in the database.
	 * All previous whitelist entries are deleted before the new list is inserted.
	 *
	 * @param array(int) $pageIDs
	 * 		An array of page IDs of all articles that are part of the whitelist.
	 */
	public function saveWhitelist($pageIDs) {
		$db =& wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_pe_rights');

		// delete old whitelist entries
		$db->delete($t, array('type' => 'whitelist'));

		$setValues = array();
		foreach ($pageIDs as $pid) {
			$setValues[] = array(
                'pe_id'     => $pid,
                'type'	  => 'whitelist',
                'right_id'  => 0);
		}
		$db->insert($t, $setValues);

	}

	/**
	 * Returns the IDs of all pages that are in the whitelist.
	 *
	 * @return array(int)
	 * 		Article-IDs of all pages in the whitelist
	 *
	 */
	public function getWhitelist() {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_pe_rights');

		$res = $db->select($t, 'pe_id', "type='whitelist'");
		$pageIDs = array();
		while ($row = $db->fetchObject($res)) {
			$pageIDs[] = (int)$row->pe_id;
		}
		$db->freeResult($res);

		return $pageIDs;
	}

	/**
	 * Checks if the article with the ID <$pageID> is part of the whitelist.
	 *
	 * @param int $pageID
	 * 		IDs of the page which is checked for membership in the whitelist
	 *
	 * @return bool
	 * 		<true>, if the article is part of the whitelist
	 * 		<false>, otherwise
	 */
	public function isInWhitelist($pageID) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_pe_rights');

		$obj = $db->selectRow($t, array('pe_id'),
		array('type'  => 'whitelist',
            'pe_id' => $pageID));
		return $obj !== false;

	}

	/***************************************************************************
	 *
	 * Functions for special page IDs
	 *
	 **************************************************************************/

	/**
	 * Special pages do not have an article ID, however access control relies
	 * on IDs. This method assigns a (negative) ID to each Special Page whose ID
	 * is requested. If no ID is stored yet for a given name, a new one is created.
	 *
	 * @param string $name
	 * 		Full name of the special page
	 *
	 * @return int id
	 * 		The ID of the page. These IDs are negative, so they do not collide
	 * 		with normal page IDs.
	 */
	public static function idForSpecial($name) {
		$db =& wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_special_pages');

		$obj = $db->selectRow($t, array('id'),
		array('name'  => $name));
		if ($obj === false) {
			// ID not found => create a new one
			$db->insert($t, array('name' => $name));
			// retrieve the auto-incremented ID of the right
			return -$db->insertId();
		} else {
			return -$obj->id;
		}
	}

	/**
	 * Special pages do not have an article ID, however access control relies
	 * on IDs. This method retrieves the name of a special page for its ID.
	 *
	 * @param int $id
	 * 		ID of the special page
	 *
	 * @return string name
	 * 		The name of the page if the ID is valid. <0> otherwise
	 */
	public static function specialForID($id) {
		$db =& wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_special_pages');

		$obj = $db->selectRow($t, array('name'),
		array('id' => -$id));
		return ($obj === false) ? 0 : $obj->name;
	}

	/**
	 * Lists of users and groups are stored as comma separated string of IDs.
	 * This function converts the string to an array of integers. Non-numeric
	 * elements in the list are skipped.
	 *
	 * @param string $values
	 * 		comma separated string of integer values
	 * @return array(int)
	 * 		Array of integers or <null> if the string was empty.
	 */
	protected static function strToIntArray($values) {
		if (!is_string($values) || strlen($values) == 0) {
			return null;
		}
		$values = explode(',', $values);
		$intValues = array();
		foreach ($values as $v) {
			if (is_numeric($v)) {
				$intValues[] = (int) trim($v);
			}
		}
		return (count($intValues) > 0 ? $intValues : null);
	}


	/**
	 * Returns all Articles names and ids
	 *
	 * @param string $subName
	 * @return array(int, string)
	 * 		List of IDs of all direct users or groups in this group.
	 *
	 */
	public function getArticles($subName, $noACLs=false, $type =null) {

                $extendWhere = null;
                if($type == "property"){
                    $extendWhere = SMW_NS_PROPERTY;
                }elseif($type == "category"){
                    $extendWhere = NS_CATEGORY;
                }elseif($type == "page"){
                    $extendWhere = "0";
                }
                
		$db =& wfGetDB( DB_SLAVE );
		$ut = $db->tableName('page');
		$gt = $db->tableName('halo_acl_groups');
		$gmt = $db->tableName('halo_acl_group_members');

                if($extendWhere != null){
                    $sql = "SELECT DISTINCT page_id, page_title FROM $ut WHERE lower(CONVERT(page_title USING latin1)) LIKE lower('%$subName%') AND page_namespace = '$extendWhere'";
                }else{
                    $sql = "SELECT DISTINCT page_id, page_title FROM $ut WHERE lower(CONVERT(page_title USING latin1)) LIKE lower('%$subName%')";
                }
                if($noACLs){
                	global $haclgNamespaceIndex;
                    $sql .= " and page_namespace != '$haclgNamespaceIndex'";
                }
                $sql .= " ORDER BY page_title";


		$res = $db->query($sql);
		$articleArray = array();
		while ($row = $db->fetchObject($res)) {
			$articleArray[] = array("id"=>$row->page_id, "name"=>$row->page_title);
		}
		$db->freeResult($res);
		return $articleArray;
	}




    /***************************************************************************
	 *
	 * Functions for quickacls
	 *
	 **************************************************************************/


	public function saveQuickAcl($user_id, $sd_ids) {
		$db =& wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_quickacl');
		// delete old quickacl entries
		$db->delete($t, array('user_id' => $user_id));

		$setValues = array();
		foreach ($sd_ids as $sd_id) {
			$setValues[] = array(
                'sd_id'     => $sd_id,
                'user_id'  => $user_id);
		}
		$db->insert($t, $setValues);

	}


	public function getQuickacl($user_id) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_quickacl');

		$res = $db->select($t, 'sd_id', "user_id=".$user_id."");

		$sd_ids = array();
		while ($row = $db->fetchObject($res)) {
			$sd_ids[] = (int)$row->sd_id;
		}
		$db->freeResult($res);

		$quickacl = new HACLQuickacl($user_id,$sd_ids);
		return $quickacl;
	}

	public function deleteQuickaclForSD($sdid){
		$db =& wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_quickacl');
		// delete old quickacl entries
		$db->delete($t, array('sd_id' => $sdid));
		return true;
	}

	
	/***************************************************************************
	 *
	 * Functions for group permissions
	 *
	 **************************************************************************/
	
	/**
	 * Stores the group permission of a group for a feature.
	 * 
	 * @param int $groupID
	 * 		ID of the group whose permission is to be stored.
	 * @param string $feature
	 * 		Name of the feature whose permission is set.
	 * @param boolean $permission
	 * 		true, if the feature is permitted for the group
	 * 		false, otherwise
	 */
	public function storeGroupPermission($groupID, $feature, $permission) {
		$db =& wfGetDB( DB_MASTER );

		$db->replace($db->tableName('halo_acl_group_permissions'), null, array(
            'group_id'    	=>  $groupID ,
            'feature'		=>  $feature ,
            'permission '   =>  $permission));
	}

	/**
	 * Deletes the group permission of a group for a feature.
	 * 
	 * @param int $groupID
	 * 		ID of the group whose permission is to be deleted.
	 * @param string $feature
	 * 		Name of the feature whose permission is deleted.
	 */
	public function deleteGroupPermission($groupID, $feature) {
		$db =& wfGetDB( DB_MASTER );
		$db->delete($db->tableName('halo_acl_group_permissions'), 
					array('group_id' => $groupID,
						  'feature' =>  $feature));
	}

	/**
	 * Retrieves the permission of the group with ID $groupID for the feature
	 * $feature.
	 * 
	 * @param int $groupID
	 * 		The ID of the group whose permission is retrieved.
	 * @param string $feature
	 * 		Name of the feature whose permission is retrieved.
	 * @return boolean/NULL
	 * 		true, if the feature is permitted for the group
	 * 		false, if not
	 * 		NULL, if no permission is stored
	 */
	public function getGroupPermission($groupID, $feature) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_group_permissions');
		$res = $db->select($t, 
							array('permission'), 
							array('group_id' => $groupID,
								  'feature'  => $feature,
							));

		$permission = NULL;
		while ($row = $db->fetchObject($res)) {
			$permission = $row->permission == 1 ? true : false;
		}
		$db->freeResult($res);

		return $permission;
	}
	
	/**
	 * Returns all permissions of features that are explicitly specified (i.e.
	 * stored in the database) for the group with the ID $groupID. 
	 * 
	 * @param $groupID
	 * 		ID of the group
	 * @return array(string feature => boolean permission)
	 */
	public function getPermissionsForGroup($groupID) {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_group_permissions');
		$res = $db->select($t, 
							array('feature' ,'permission'), 
							array('group_id' => $groupID));

		$permissions = array();
		while ($row = $db->fetchObject($res)) {
			$permissions[$row->feature] = $row->permission == 1 ? true : false;
		}
		$db->freeResult($res);

		return $permissions;
	}
	
	/**
	 * Returns an array with all existing group permissions.
	 * 
	 * @return array(array('groupID' => int, 'feature' => string, 'permission' => bool))
	 */
	public function getAllGroupPermissions() {
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('halo_acl_group_permissions');
		$res = $db->select($t, 
							array('group_id', 'feature' ,'permission'), 
							array());

		$permissions = array();
		while ($row = $db->fetchObject($res)) {
			$permissions[] = array( 'groupID'   => $row->group_id, 
									'feature'    => $row->feature ,
									'permission' => $row->permission == 1 ? true : false);
		}
		$db->freeResult($res);

		return $permissions;
		
	}
	
	/**
	 * Deletes all group permissions in the database.
	 */
	public function deleteAllGroupPermissions() {
		$db = & wfGetDB( DB_MASTER );
		$t = $db->tableName('halo_acl_group_permissions');
		$db->delete($t,'*');
	}
	
	/**
	 * Returns the names of all pages in the ACL namespace.
	 * @return array<string>
	 * 		Names of pages without namespace
	 */
	public function getAllACLPages() {
		global $haclgNamespaceIndex;
		
		$db =& wfGetDB( DB_SLAVE );
		$t = $db->tableName('page');
		$res = $db->select($t, 
							array('page_title'), 
							array('page_namespace' => $haclgNamespaceIndex));

		$pages = array();
		while ($row = $db->fetchObject($res)) {
			$pages[] = $row->page_title;
		}
		$db->freeResult($res);

		return $pages;
		
	}
	
}