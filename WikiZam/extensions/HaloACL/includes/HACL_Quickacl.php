<?php
/**
 * @file
 * @ingroup HaloACL_UI_Backend
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
 * This file contains the quickacl-objekt
 * and provides quickacl-methods
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

//--- Includes ---
global $haclgIP;
class HACLQuickacl {

    private $userid = 0;
    private $sd_ids = array();


    public function getUserid() {
        return $this->userid;
    }

    public function setUserid($userid) {
        $this->userid = $userid;
    }


    function __construct($userid,$sd_ids) {
        $this->userid = $userid;
        $this->sd_ids = $sd_ids;
    }


    public function getSD_IDs() {
        return $this->sd_ids;
    }
    
    public function addSD_ID($sdID) {
    	if (!in_array($sdID, $this->sd_ids)) {
    		$this->sd_ids[] = $sdID;
    	}
    }
    
    public static function newForUserId($user_id){
        return HACLStorage::getDatabase()->getQuickacl($user_id);

    }

    public function save(){
        return HACLStorage::getDatabase()->saveQuickacl($this->userid, $this->sd_ids);
    }
    public static function removeQuickAclsForSD($sdid){
        return HACLStorage::getDatabase()->deleteQuickaclForSD($sdid);
    }
}
