<?php
/**
 * @file
 * @ingroup HaloACL_Storage
 */

/*  Copyright 2009, ontoprise GmbH
* 
*   This file is part of the HaloACL-Extension.
*
*   The HaloACL-Extension is free software; you can redistribute 
*   it and/or modify it under the terms of the GNU General Public License as 
*   published by the Free Software Foundation; either version 3 of the License, 
*   or (at your option) any later version.
*
*   The HaloACL-Extension is distributed in the hope that it will 
*   be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * This file provides the access to the database tables that are
 * used by the HaloACL extension.
 * 
 * @author Thomas Schweitzer
 * 
 */


/**
 * This class encapsulates all methods that care about the database tables of 
 * the HaloACL extension. It is a singleton that contains an instance 
 * of the actual database access object e.g. the Mediawiki SQL database.
 *
 */
class HACLStorage {

	//--- Private fields---
	
	private static $mInstance; // HACLStorage: the only instance of this singleton
	private static $mDatabase; // The actual database object
	
	//--- Constructor ---
	
	/**
	 * Constructor.
	 * Creates the object that handles the concrete database access.
	 *
	 */
	private function __construct() {
        global $haclgIP;
        if (self::$mDatabase == NULL) {
            global $haclgBaseStore;
            switch ($haclgBaseStore) {
                case (HACL_STORE_SQL):
                    require_once("$haclgIP/storage/HACL_StorageSQL.php");
                    self::$mDatabase = new HACLStorageSQL();
                    break;
                case (HACL_STORE_LDAP):
                    require_once("$haclgIP/storage/HACL_StorageLDAP.php");
                    self::$mDatabase = new HACLStorageLDAP();
                    break;
            }
        }
		
	}
	
	//--- Public methods ---
	
	/**
	 * Returns the single instance of this class.
	 *
	 * @return HACLStorage
	 * 		The single instance of this class.
	 */
	public static function getInstance() {
        if (!isset(self::$mInstance)) {
            $c = __CLASS__;
            self::$mInstance = new $c;
        }

        return self::$mInstance;
	}
	
	/**
	 * Returns the actual database. 
	 *
	 * @return object
	 * 		The object to access the database.
	 */
	public static function getDatabase() {
        self::getInstance(); // Make sure, singleton is initialized
        return self::$mDatabase;
	}
	
	/**
	 * Resets the storage for testing purposes. The global variable
	 * $haclgBaseStore will be set to $storeID and the next time getDatabase()
	 * will return a corresponding database.
	 * 
	 * @param $storeID
	 * 		ID of the store that will be used the next time when getDatabase()
	 * 		is called.
	 */
	public static function reset($storeID) {
		self::$mInstance = null;
		self::$mDatabase = null;
		global $haclgBaseStore;
		$haclgBaseStore = $storeID;
		
	}
	
	 
}