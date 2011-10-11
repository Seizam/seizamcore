<?php
/**
 * @file
 * @ingroup HaloACL_Exception
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
 * This file defines the class HACLGroupPermissionException
 * 
 * @author Thomas Schweitzer
 * Date: 08.10.2010
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

/**
 * Exceptions for the operations on group permissions of HaloACL.
 *
 */
class HACLGroupPermissionsException extends HACLException {

	//--- Constants ---
	
	// A parameter is missing or empty in a definition of a $haclgFeature
	// Parameters:
	// 1 - name of the group permission feature
	// 2 - name of missing parameter
	const MISSING_PARAMETER = 1;	

	// An entry in $haclgFeature has an unexpected value
	// Parameters:
	// 1 - name of the group permission feature
	// 2 - name of the parameter
	// 3 - value of the parameter
	// 4 - expected value
	const INVALID_PARAMETER_VALUE = 2;
	
	// The database contains a feature that is not defined in $haclgFeature
	// Parameters:
	// 1 - name of the group permission feature
	const UNKNOWN_FEATURE = 3;
	
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
    		case self::MISSING_PARAMETER:
    			global $haclgFeature;
    			$feature = print_r($haclgFeature[$args[1]], true);
    			$msg = "A parameter is missing or empty in the group permission feature '$args[1]'\n"
    				 . "Parameter: $args[2]\n"
    				 . "The feature is defined as follows:\n$feature";
    			break;
    		case self::INVALID_PARAMETER_VALUE:
    			global $haclgFeature;
    			$feature = print_r($haclgFeature[$args[1]], true);
    			$msg = "The definition of the group permission feature '$args[1]' has an invalid value.\n"
    				 . "Parameter: $args[2]\n"
    				 . "Value: $args[3]\n"
    				 . "Expected value: $args[4]\n"
    				 . "The feature is defined as follows:\n$feature";
    			break;
    		case self::UNKNOWN_FEATURE:
    			$msg = "The database contains a feature ('$args[1]') that is not defined in \$haclgFeature.\n".
    					"This feature has probably been removed from \$haclgFeature.\n".
    					"You can ignore undefined features by setting \$haclgThrowExceptionForMissingFeatures = false; in HACL_Initialize.php.\n";
    			break;
    	}
    	return $msg;
    }
}
