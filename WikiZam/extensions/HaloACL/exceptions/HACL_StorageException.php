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
 * Exceptions for the storage layer of HaloACL.
 *
 */
class HACLStorageException extends HACLException {

	//--- Constants ---
	
	// LDAP groups can not be saved 
	// Parameters:
	// 1 - name of the group
	const CANT_SAVE_LDAP_GROUP = 1;	
	
	// It is not possible to save a group that already exists in LDAP 
	// Parameters:
	// 1 - name of the group
	const SAME_GROUP_IN_LDAP = 2;
	
	// LDAP groups can not be modified
	// Parameters:
	// 1 - ID of the group
	const CANT_MODIFY_LDAP_GROUP = 3;
	
	
	/**
	 * Constructor of the storage exception.
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
    		case self::CANT_SAVE_LDAP_GROUP:
    			$msg = "The LDAP group <$args[1]> can not be saved.";
    			break;
    		case self::SAME_GROUP_IN_LDAP:
    			$msg = "A group with the name <$args[1]> already exists in LDAP.";
    			break;
    		case self::CANT_MODIFY_LDAP_GROUP:
    			$msg = "The LDAP group with the ID <$args[1]> can not be modified.";
    			break;
    			
    	}
    	return $msg;
    }
}
