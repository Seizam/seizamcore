<?php
/**
 * @file
 * @ingroup HaloACL
 */

/*  Copyright 2010, ontoprise GmbH
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
 * This file contains the class HACLGroupPermissions.
 * 
 * @author Thomas Schweitzer
 * Date: 18.08.2010
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * The class HACLGroupPermissions takes care that HaloACL groups and their 
 * members can be used together with $wgGroupPermissions i.e. that Mediawiki
 * permissions can be defined for HaloACL groups.
 * 
 * @author Thomas Schweitzer
 * 
 */
class HACLGroupPermissions  {
	
	//--- Constants ---
	const ALL_USERS = -1;
	const REGISTERED_USERS = -2;
		
	//--- Private fields ---
	
	/**
	 * Constructor for  HACLGroupPermissions
	 *
	 * @param type $param
	 * 		Name of the notification
	 */		
	function __construct() {
	}
	

	//--- getter/setter ---
	
	//--- Public methods ---
	
	/**
	 * This function is a callback for the hook 'UserEffectiveGroups' which is
	 * used in User::getEffectiveGroups(). The list of groups stored in the
	 * Mediawiki database is enhanced by the groups managed by HaloACL.
	 * 
	 * @param User $user
	 * 		The user whose groups are retrieved.
	 * @param array<string> $userGroups
	 * 		This list of groups will be modified by this method.
	 * 
	 * @return
	 * 		Returns true.
	 */
	public static function onUserEffectiveGroups(&$user, &$userGroups) {
		$groups = HACLGroup::getGroupsOfMember($user->getId(), HACLGroup::USER, true);
		foreach ($groups as $g) {
			$userGroups[] = HACLGroup::removeNamePrefix($g['name']);
		}
		return true;
	}
	
	/**
	 * Upon startup of the wiki the default permissions in all $haclgFeature
	 * elements have to be translated into values of $wgGroupPermissions.
	 * This happens in this function. If the default permission is "permit"
	 * the group permission for '*' and 'user' (all anonymous and registered 
	 * users) is set to "true", otherwise in case of "deny" it is "false".
	 * 
	 * @throws HACLGroupPermissionsException
	 * 		MISSING_PARAMETER: if the entry 'systemfeatures' or 'default' of a 
	 * 			defined	feature is missing or empty.
	 * 		INVALID_PARAMETER_VALUE: if the default value is invalid
	 */
	public static function initDefaultPermissions() {
		global $haclgFeature, $wgGroupPermissions;
		
		foreach ($haclgFeature as $fname => $feature) {
			$sfs = $feature['systemfeatures'];
			$sysFeatures = explode('|', $sfs);
			if (empty($sfs) || count($sysFeatures) == 0) {
				throw new HACLGroupPermissionsException(
					HACLGroupPermissionsException::MISSING_PARAMETER,
					$fname, 'systemfeatures');
			}
			$default = @$feature['default'];
			if (empty($default)) {
				throw new HACLGroupPermissionsException(
					HACLGroupPermissionsException::MISSING_PARAMETER,
					$fname, 'default');
			}
			if ($default !== 'permit' && $default !== 'deny') {
				throw new HACLGroupPermissionsException(
					HACLGroupPermissionsException::INVALID_PARAMETER_VALUE,
					$fname, 'default', $default, "'permit' or 'deny'");
			}
			$permit = $default == 'permit' ? true : false;
			foreach ($sysFeatures as $sf) {
				$wgGroupPermissions['*'][$sf] = $permit;
				$wgGroupPermissions['user'][$sf] = $permit;
			}
		}
	}
	
	/**
	 * All group permissions that are stored in the database are transferred
	 * to $wgGroupPermissions. There are some special group IDs:
	 * ALL_USERS => all users (anonymous and registered) (*)
	 * REGISTERED_USERS => registered users (user)
	 * 
	 * @throws HACLGroupPermissionsException
	 * 		UNKNOWN_FEATURE, if the DB contains an unknown feature
	 */
	public static function initPermissionsFromDB() {
		
		if (defined( 'DO_MAINTENANCE' ) && !defined('UNIT_TEST_RUNNING')) {
			return;
		}
		// Get all group permissions from the DB
		$db = HACLStorage::getDatabase();
		$permissions = $db->getAllGroupPermissions();
		
		// Set $wgGroupPermissions for all permissions from the DB
		global $wgGroupPermissions, $haclgFeature;
		foreach ($permissions as $p) {
			switch ($p['groupID']) {
			case self::ALL_USERS:
				$group = '*';
				break;
			case self::REGISTERED_USERS:
				$group = 'user';
				break;
			default:
				$group = HACLGroup::nameForID($p['groupID']);
				$group = HACLGroup::removeNamePrefix($group);
				break;
			}
			if (!array_key_exists($p['feature'], $haclgFeature)) {
				global $haclgThrowExceptionForMissingFeatures;
				if ($haclgThrowExceptionForMissingFeatures) {
					// Unknown feature found
					throw new HACLGroupPermissionsException(
						HACLGroupPermissionsException::UNKNOWN_FEATURE,
						$p['feature']);
				} else {
					// Suppress exception and continue with next feature
					continue;
				}
			}
			$feature = $haclgFeature[$p['feature']]['systemfeatures'];
			$sysFeatures = explode('|', $feature);
			$permitted = $p['permission'];
			
			foreach ($sysFeatures as $sf) {
				$wgGroupPermissions[$group][$sf] = $permitted;
			}
		}
		
		// Special rights for sysop and bureaucrat: They are always able to 
		// read pages. Otherwise they could lock themselves out.
		$wgGroupPermissions['sysop']['read'] = true;
		$wgGroupPermissions['bureaucrat']['read'] = true;
		
	}
	
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
	public static function storePermission($groupID, $feature, $permission) {
		$db = HACLStorage::getDatabase();
		$db->storeGroupPermission($groupID, $feature, $permission);
	}

	/**
	 * Deletes the group permission of a group for a feature.
	 * 
	 * @param int $groupID
	 * 		ID of the group whose permission is to be deleted.
	 * @param string $feature
	 * 		Name of the feature whose permission is deleted.
	 */
	public static function deletePermission($groupID, $feature) {
		$db = HACLStorage::getDatabase();
		$db->deleteGroupPermission($groupID, $feature);
	}

	/**
	 * Retrieves the permission of the group with ID $groupID for the feature
	 * $feature.
	 * 
	 * @param int $groupID
	 * 		The ID of the group whose permission is retrieved.
	 * @param string $feature
	 * 		Name of the feature whose permission is retrieved.
	 * @return boolean
	 * 		true, if the feature is permitted for the group
	 * 		false, otherwise
	 */
	public static function getPermission($groupID, $feature) {
		$db = HACLStorage::getDatabase();
		return $db->getGroupPermission($groupID, $feature);
	}
	
	/**
	 * Returns all permissions of features that are explicitly specified (i.e.
	 * stored in the database) for the group with the ID $groupID. 
	 * 
	 * @param $groupID
	 * 		ID of the group
	 * @return array(string feature => boolean permission)
	 */
	public static function getPermissionsForGroup($groupID) {
		$db = HACLStorage::getDatabase();
		return $db->getPermissionsForGroup($groupID);
	}
	
	/**
	 * Deletes all group permissions in the database.
	 */
	public static function deleteAllPermissions() {
		$db = HACLStorage::getDatabase();
		$db->deleteAllGroupPermissions();
	}
	
	/**
	 * Saves the group permissions of the given $feature.
	 * @param string $feature
	 * 		ID of the feature
	 * @param array(string groupID => string permission) $permissions
	 * 		Permissions for groups. Permission must be one of 'permit', 'deny'
	 * 		or 'default'.
	 * @return bool
	 * 		<true> if saving permissions was successful
	 * 		<false> otherwise
	 */
	public static function saveGroupPermissions($feature, $permissions) {
		$db = HACLStorage::getDatabase();
		foreach ($permissions as $groupID => $p) {
			if ($p === 'default' || ($p !== 'permit' && $p !== 'deny')) {
				// Delete explicit group permission
				$db->deleteGroupPermission($groupID, $feature);
			} else {
				// Store explicit permission
				$perm = $p === 'permit';
				$db->storeGroupPermission($groupID, $feature, $perm);
			}
		}
		return true;
	}

	
	//--- Private methods ---
}