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
 * @file
 * @ingroup HaloACLScripts
 * @author: Thomas Schweitzer
 */

if (typeof HACL == "undefined") {
// Define the HACL module	
	var HACL = { 
		classes : {}
	};
}

/**
 * This is the class of the group tree.
 */
HACL.classes.GroupTree = function (container, feature) {
	var $ = jQuery;
	
	//--- Private members ---

	// The instance of this object
	var that = {};
	
	
	// The tree container
	var mTreeContainer = container;
	
	// The permissions for this feature are set in the group tree 
	var mFeature = feature;
	
	// The actual group tree
	var mGroupTree;
	
	// <true> if an ajax call for the filter function is currently running
	var mFilterRunning = false;
	
	// <true>, if a request for filtering is still waiting to be executed.
	var mFilterRequestWaiting = false;
	
	// Current value of the filter
	var mFilter = "";
	
	// Print debug messages if <true>
	var mDebug = false;
	
	// This object contains all permissions that were changed by the user
	// in form of an associative array
	var mChangedPermissions = {};
	
	// Timeout for collecting several keystrokes in the filter imput field
	var mFilterTimeout;
	
	//--- Public methods ---
	
	//--- Getter/setter ---
		
	/**
	 * Constructor for the group tree. Sets up the tree and its events.
	 */
	that.construct = function () {
		var url = wgServer + wgScriptPath + "/index.php?action=ajax";

		var treeConfig = {
			data : { 
				type  : "json",
				async : true,
				opts  : {
					method : "POST",
					url : url
				}
			},
			ui : {
				theme_name : "haloacl"
			},
			types : {
				"default" : {
	  				draggable : false
				}
			},
			callback : {
				beforedata: that.onBeforeData,
				onsearch: function(NODES, TREE_OBJ) {
					// Check if the main text of the tree nodes matches the filter
					var filterLC = mFilter.toLowerCase();
					NODES.each(function () {
						var text = $(this).text();
						if (text.toLowerCase().indexOf(filterLC) >= 0) {
							$(this).addClass("search");
						}
					});
				}
			}
		};
		// Create the tree widget		
		$(mTreeContainer).tree(treeConfig);
		
		// Clear the filter
		$('#haclGroupTreeFilter').val("");
		
		mGroupTree = $.tree.reference(mTreeContainer);
		addEventHandlers();

	};

	/**
	 * Destroys this group tree
	 */
	that.destroy = function () {
		removeEventHandlers();
		
		mGroupTree.destroy();
		// jsTree forgets to delete these live events:
		$("#" + mTreeContainer + " li").die();
		$("#" + mTreeContainer + " li a").die();
	};
	
	/**
	 * Returns the object of changed permissions.
	 * @return {Object}
	 * 		The fields of this object are the IDs of changed permissions and
	 * 		their value is the new value of the permission.
	 */
	that.getChangedPermissions = function () {
		return mChangedPermissions;
	};
	
	/**
	 * Adds all event handlers for the group tree
	 */
	function addEventHandlers() {
		// Add a live handler for clicking the checkboxes in the group tree
		$('.tree-haloacl-check').live('click', that.onCheckboxClicked);
		
		// Keyup handler for the filter
		$('#haclGroupTreeFilter').keyup(that.onFilterKeyup);
		
		// We want to know when ajax calls are finished for filtering the group
		// tree
		$(mTreeContainer).ajaxStop(that.ajaxStop);
		
		// Unfortunately jsTree doesn't offer a possibility to configure the
		// ajax URL for searched. We have to replace jQuery's ajax() method
		// to change it,
		var jQueryAjaxFnc = $.ajax;
		$.ajax = function (s) {
			// Create an appropriate request with the search data.
			if (typeof s.data.search !== 'string') {
				// Not a search request => call the original method
				jQueryAjaxFnc(s);
				return;
			}
			var search = s.data.search;
			s.data = "rs=haclFilterGroups&rsargs[]="+encodeURIComponent(search);
			// Call jQuery's method
			jQueryAjaxFnc(s);
		};
		
		// Add a jQuery selector for case insensitive content matches
		$.extend($.expr[":"], {
			"containsNC" : function (elem, i, match, array) {
				return (elem.textContent || elem.innerText || "")
						.toLowerCase()
						.indexOf((match[3] || "").toLowerCase()) >= 0;
			}
		});
	};
	
	/**
	 * Removes the previously attached event handlers.
	 */
	function removeEventHandlers() {
		// Click handler for checks in tree
		$('.tree-haloacl-check').die('click');
		
		// Keyup handler for the filter
		$('#haclGroupTreeFilter').unbind('keyup');
		
		// Ajax stop event
		$(mTreeContainer).unbind('ajaxStop');
		
	};
	
	/**
	 * This function is called by the tree before data is sent to the server for
	 * retrieving deeper branches.
	 * It creates the correct URI parameter for the request.
	 * 
	 * @param {Object} NODE
	 * @param {Object} TREE_OBJ
	 */
	that.onBeforeData = function (NODE, TREE_OBJ) {
		return "rs=haclGetGroupChildren&rsargs[]="
			  	+ encodeURIComponent($(NODE).attr("id") || "---ROOT---")
				+ "&rsargs[]="
				+ encodeURIComponent(mFeature); 
	}
	
	/**
	 * Event handler for clicking the checkboxes in the tree.
	 */
	that.onCheckboxClicked = function (event) {
		var newState;
		if ($(this).hasClass('normal')) {
			$(this).removeClass('normal');
			$(this).addClass('checked');
			newState = 'permit';
		} else if ($(this).hasClass('checked')) {
			$(this).removeClass('checked');
			$(this).addClass('crossed');
			newState = 'deny';
		} else if ($(this).hasClass('crossed')) {
			$(this).removeClass('crossed');
			$(this).addClass('normal');
			newState = 'default';
		}
		
		var groupID = $(this).parents('li:first').attr('id');
		mChangedPermissions[groupID] = newState;
		return false;
	};
	
	/**
	 * Keyup event handler for the group tree filter.
	 */
	that.onFilterKeyup = function () {
		mFilter = $('#haclGroupTreeFilter').val();
		if (mDebug) { console.log("Filter: "+mFilter+"\n"); }
		if (typeof mFilterTimeout !== 'undefined') {
			if (mDebug) { console.log("Clearing timeout.\n"); }
			clearTimeout(mFilterTimeout);
		}
		mFilterTimeout = setTimeout(that.filterGroupTree, 300, mFilter);
		return false;
	};
	
	/**
	 * Filter the group tree for all groups that contain the given filter string.
	 * @param {string} filter
	 * 		The filter to be matched in group names
	 */
	that.filterGroupTree = function (filter) {
		if (filter === '') {
			// If the filter is empty, the tree is reset
			mGroupTree.search(null);
			return;
		}
		
		if (mFilterRunning) {
			// We are still waiting for the result of the ajax function
			// => Remember the filter request
			mFilterRequestWaiting = true;
			if (mDebug) { console.log("Request waiting\n"); }
			return;
		}

		mFilterRunning = true;
		if (mDebug) { console.log("Request running\n"); }

		mGroupTree.search(filter, "containsNC");
		
	}
	
	/**
	 * This function is called when all ajax request are finished. We need to find out
	 * when requests for filtering the group tree finish so that pending filter
	 * requests can be started.
	 */
	that.ajaxStop = function () {
		// A filter request finished. 
		mFilterRunning = false;
		if (mDebug) { console.log("Requests completed.\n"); }

		if (mFilterRequestWaiting) {
			// A filter request is still waiting => start it.
			if (mDebug) {
				console.log("Starting new request\n");
			}

			mFilterRequestWaiting = false;
			that.onFilterKeyup();
		}
	};
	
	that.construct();
	return that;

}

