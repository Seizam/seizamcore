/**
 * @file
 * @ingroup HaloACL_LanguageScript
 */

/*  Copyright 2009, ontoprise GmbH
*  This file is part of the halo-Extension.
*
*   The halo-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The halo-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
/**
* HaloACL_Language.js
* 
* A class that stores all language strings for the user interface.
* 
* @author Thomas Schweitzer
*
*/

var HACLLanguage = Class.create();

/**
 * This class provides language dependent strings for an identifier.
 * 
 */
HACLLanguage.prototype = {

	/**
	 * @public
	 * 
	 * Constructor.
	 */
	initialize: function() {
	},

	/*
	 * @public
	 * 
	 * Returns a language dependent message for an ID, or the ID, if there is 
	 * no message for it.
	 * 
	 * @param string id
	 * 			ID of the message to be retrieved.
	 * @return string
	 * 			The language dependent message for the given ID.
	 */
	getMessage: function(id, type) {
		switch (type) {
			case "user":
				var msg = gHACLUserLanguageStrings[id];
				if (!msg) {
					msg = id;
				} 
				break;
				
			case "cont":
				var msg = gHACLContLanguageStrings[id];
				if (!msg) {
					msg = id;
				} 
				break;
			default: 
				var msg = gHACLUserLanguageStrings[id];
				if (!msg) {
					var msg = gHACLContLanguageStrings[id];
					if (!msg) {
						msg = id;
					}
				}
		} 
			
		// Replace variables
		msg = msg.replace(/\$n/g,wgCanonicalNamespace); 
		msg = msg.replace(/\$p/g,wgPageName);
		msg = msg.replace(/\$t/g,wgTitle);
		msg = msg.replace(/\$u/g,wgUserName);
		msg = msg.replace(/\$s/g,wgServer);
		return msg;
	}
	
}

// Singleton of this class

var gHACLLanguage = new HACLLanguage();