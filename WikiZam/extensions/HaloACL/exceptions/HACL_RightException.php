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
 * Exceptions for inline rights
 * 
 * @author Thomas Schweitzer
 * Date: 20.04.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

/**
 * Exceptions for the operations on inline rights of HaloACL.
 *
 */
class HACLRightException extends HACLException {

	//--- Constants ---
	
	// There is no inline right with the given ID
	// Parameters:
	// 1 - ID of the inline right
	const UNKNOWN_RIGHT = 1;
	
	
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
    		case self::UNKNOWN_RIGHT:
    			$msg = "There is no inline right with the name or ID \"$args[1]\".";
    			break;
    			
    	}
    	return $msg;
    }
}
