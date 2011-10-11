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
 * Exceptions for security descriptors
 * 
 * @author Thomas Schweitzer
 * Date: 16.04.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

/**
 * Exceptions for the operations on security descriptors (SD) of HaloACL.
 *
 */
class HACLSDException extends HACLException {

	//--- Constants ---
	
	// There is no article for the specified SD. 
	// Parameters:
	// 1 - name of the SD
	const NO_SD_ID = 1;	
	
	// A unauthorized user tries to modify the definition of a SD. 
	// Parameters:
	// 1 - name of the SD
	// 2 - name of the user
	const USER_CANT_MODIFY_SD = 2;
	
	// An unknown group is given for an SD. 
	// Parameters:
	// 1 - name of the SD
	// 2 - name of the group
	const UNKOWN_GROUP = 3;
	
	// There is no article or namespace for the specified protected element. 
	// Parameters:
	// 1 - name of the protected element
	// 2 - type of the protected element
	const NO_PE_ID = 4;

	// There is no security descriptor with the given name or ID
	// Parameters:
	// 1 - name or ID of the SD
	const UNKNOWN_SD = 5;

	// An SD is added as child to an SD.
	// Parameters:
	// 1 - Name of the SD to which the other SD is added
	// 2 - Name of the SD that is added to the other SD
	const CANNOT_ADD_SD = 6;
	
	// A rule for dynamic SDs is incomplete
	// Parameters:
	// 1 - The incomplete rule (which is an array of properties)
	const INCOMPLETE_DYNAMIC_SD_RULE = 7;
	
	// A rule for dynamic groups is incomplete
	// Parameters:
	// 1 - The incomplete rule (which is an array of properties)
	const INCOMPLETE_DYNAMIC_GROUP_RULE = 8;
	
	/**
	 * Constructor of the SD exception.
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
    		case self::NO_SD_ID:
    			$msg = "The article for the security descriptor $args[1] is not yet created.";
    			break;
    		case self::USER_CANT_MODIFY_SD:
    			$msg = "The user $args[2] is not authorized to change the security descriptor $args[1].";
    			break;
    		case self::UNKOWN_GROUP:
    			$msg = "The group $args[2] is unknown. It can not be used for security descriptor $args[1].";
    			break;
    		case self::NO_PE_ID:
    			$msg = "The element \"$args[1]\" that shall be protected does not exist. It's requested type is \"$args[2]\"";
    			break;
    		case self::UNKNOWN_SD:
    			$msg = "There is no security descriptor with the name or ID \"$args[1]\".";
    			break;
    		case self::CANNOT_ADD_SD:
    			$msg = "You can not add the security descriptor \"$args[2]\" as right to the security descriptor \"$args[1]\"";
    			break;
    		case self::INCOMPLETE_DYNAMIC_SD_RULE:
    			$rule = print_r($args[1], true);
    			$msg = <<<MSG
The following rule for dynamic security descriptors which was specified in "\$haclgDynamicSD" is incomplete:
$rule
At least the properties "user", "category" and "sd" must be specified!\n
MSG;
    			break;
    		case self::INCOMPLETE_DYNAMIC_GROUP_RULE:
    			$rule = print_r($args[1], true);
    			$msg = <<<MSG
The following rule for dynamic groups which was specified in "\$haclgDynamicGroup" is incomplete:
$rule
At least the properties "user", "category", "name" and "groupTemplate" must be specified!\n
MSG;
    			break;
    			
    	}
    	return $msg;
    }
}
