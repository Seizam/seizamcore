<?php
/**
 * @file
 * @ingroup HaloACL_Exception
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
 * Insert description here
 * 
 * @author Thomas Schweitzer
 * Date: 02.04.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

/**
 * Exceptions for the operations on groups of HaloACL.
 *
 */
class HACLGroupException extends HACLException {

	//--- Constants ---
	
	// There is no article for the specified group. 
	// Parameters:
	// 1 - name of the group
	const NO_GROUP_ID = 1;	
	
	// An unauthorized user tries to modify the definition of a group. 
	// Parameters:
	// 1 - name of the group
	// 2 - name of the user
	const USER_CANT_MODIFY_GROUP = 2;
	
	// An unknown group is given. It has no group ID. 
	// Parameters:
	// 1 - name of the group
	const UNKNOWN_GROUP = 3;

	// There is no group for the given group ID. 
	// Parameters:
	// 1 - ID of the group
	const INVALID_GROUP_ID = 4;	
	
	/**
	 * Constructor of the group exception.
	 *
	 * @param int $code
	 * 		A user defined error code.
	 */
    public function __construct($code = 0) {
    	$args = func_get_args();
    	// initialize super class
        parent::__construct($args);
    }
    
    protected function createMessage($args) {
    	$msg = "";
    	switch ($args[0]) {
    		case self::NO_GROUP_ID:
    			$msg = "The article for group $args[1] is not yet created.";
    			break;
    		case self::INVALID_GROUP_ID:
    			$msg = "The group ID <$args[1]> is invalid.";
    			break;
    		case self::USER_CANT_MODIFY_GROUP:
    			$msg = "The user $args[2] is not authorized to add or change the group $args[1].";
    			break;
    		case self::UNKNOWN_GROUP:
    			$msg = "The group $args[1] does not exist. There is no article that defines this group.";
    			break;
    	}
    	return $msg;
    }
}
