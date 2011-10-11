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
 * This file contains the class HACLWhitelist for managing the whitelist in the
 * database.
 * 
 * @author Thomas Schweitzer
 * Date: 11.05.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * The class HACLWhitelist manages the whitelist in the database. The whitelist
 * is a set of pages that can be read by everyone.
 * 
 * @author Thomas Schweitzer
 * 
 */
class  HACLWhitelist  {
	
	//--- Constants ---
//	const XY= 0;		// the result has been added since the last time
		
	//--- Private fields ---
	private $mPages = array();    		//array(string): The names of all pages
										//  (with namespace) that define the
										//  whitelist
	
	/**
	 * Constructor for HACLWhitelist. The new object has to be saved to store the
	 * whitelist in the database.
	 *
	 * @param array(string) $pages
	 * 		An array of pagenames (with namespace) that define the whitelist.
	 * 		For an empty whitelist, the array may be empty or the parameter 
	 * 		may be completely missing.
	 */		
	function __construct($pages = array()) {
		$this->mPages = $pages;
	}
	

	//--- getter/setter ---
	public function getPages()           {return $this->mPages;}

//	public function setXY($xy)               {$this->mXY = $xy;}
	
	//--- Public methods ---
	
	
	/**
	 * Creates a HACLWhitelist-object based on the content of the database.
	 *
	 * @return HACLWhitelist
	 * 		A whitelist object that contains all whitelist pages that are stored
	 * 		in the database.
	 */
	public static function newFromDB() {
		// Read the IDS of all pages that are part of the whitelist
		$pageIDs = HACLStorage::getDatabase()->getWhitelist();
		$pages = array();
		// Transform page-IDs to page names
		$etc = haclfDisableTitlePatch();
		foreach ($pageIDs as $pid) {
			$t = Title::newFromID($pid);
			if ($t) {
				$pages[] = $t->getFullText();
			}
		}
		haclfRestoreTitlePatch($etc);
		
		return new HACLWhitelist($pages);
	}
	
	/**
	 * Saves the pages of this object as whitelist in the database. It is not
	 * possible to add names of pages that do no exist. In this case an 
	 * exception is thrown. However, all existing articles are stored in the
	 * database.
	 *
	 * @throws HACLWhitelistException
	 * 		HACLWhitelistException(HACLWhitelistException::PAGE_DOES_NOT_EXIST)
	 * 		... if an article given in the whitelist does not exist.
	 */
	public function save() {
		$nonExistent = array();
		$ids = array();
		// Get the IDs of all pages
		foreach ($this->mPages as $name) {
			$id = haclfArticleID($name);
			if ($id == 0) {
				$nonExistent[] = $name;
			} else {
				$ids[] = $id;
			}
		}
		HACLStorage::getDatabase()->saveWhitelist($ids);
		if (!empty($nonExistent)) {
			throw new HACLWhitelistException(HACLWhitelistException::PAGE_DOES_NOT_EXIST,
											 $nonExistent);		
		}
	}

	/**
	 * Checks if the article with the ID or name $page is a member of the 
	 * whitelist.
	 *
	 * @param mixed int|string $page
	 * 		ID or name of the page
	 * 
	 * @return bool
	 * 		true, if the article is part of the whitelist
	 * 		false, otherwise
	 */
	public static function isInWhitelist($page) {
		if (!is_int($page)) {
			$page = haclfArticleID($page);
		}
		return HACLStorage::getDatabase()->isInWhitelist($page);
	}
	
	/**
	 * Checks if the given user can modify the whitelist. Only sysops and 
	 * bureaucrats can do this.
	 *
	 * @param User/string/int $user
	 * 		User-object, name of a user or ID of a user who wants to modify the 
	 * 		whitelist. If <null>, the currently logged in user is assumed.
	 * 
	 * @param boolean $throwException
	 * 		If <true>, the exception 
	 * 		HACLWhitelistException(HACLWhitelistException::USER_CANT_MODIFY_WHITELIST)
	 * 		is thrown, if the user can't modify the group.
	 * 
	 * @return boolean
	 * 		One of these values is returned if no exception is thrown:
	 * 		<true>, if the user can modify the Whitelist and
	 * 		<false>, if not
	 * 
	 * @throws 
	 * 		HACLException(HACLException::UNKOWN_USER)
	 * 		If requested: HACLWhitelistException(HACLWhitelistException::USER_CANT_MODIFY_WHITELIST) 
	 * 
	 */
	public static function userCanModify($user, $throwException = false) {
		if (!is_a($user, "User")) {
			// Get the ID of the user who wants to add/modify the group
			list($userID, $userName) = haclfGetUserID($user);
			if ($userID == 0) {
				// anonymous users can't modify the whitelist
				if ($throwException) {
					throw new HACLWhitelistException(HACLWhitelistException::USER_CANT_MODIFY_WHITELIST, 
			                                         'anonymous');
				} else {
					return false;
				}
			}
			$user = User::newFromId($userID);
		}
		$groups = $user->getGroups();
		return (in_array('sysop', $groups) || in_array('bureaucrat', $groups));
	}
	
	//--- Private methods ---
}