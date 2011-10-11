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
 * @class GroupPermission
 * This class handles the interaction in the Group Permission tab.
 * 
 */
HACL.classes.GroupPermission = function () {
	var $ = jQuery;
	
	//--- Private members ---

	// The instance of this object
	var that = {};
	
	// string - ID of the currently selected permission
	var mPermissionID;
	
	// HACL.classes.GroupTree - The group tree for the current permission
	var mGroupTree;
	
	/**
	 * Constructor for the GroupPermission class. Sets up the group permission
	 * panel and events.
	 */
	function construct() {
		addEventHandlers();

	};
	that.construct = construct;
	
	/**
	 * Adds all event handlers for the group permission panel
	 */
	function addEventHandlers() {
		// Change event handler for the permission selector.
		$('#haclGPPermissionSelector').change(onPermissionSelected);
		
		// Discard and Save buttons
		$("#haclGPDiscard").click(onDiscardChanges);
		$("#haclGPSave").click(onSave);
	};
	
	/**
	 * This function is called when the user clicks a permission in the permission
	 * selector. It changes the explanation text of the permission and the current
	 * permission in the group editor.
	 */
	function onPermissionSelected() {
		var val = $(this).val();
		
		// Show the description of the permission
		var option = $("option:selected", $(this));
		var descrElem = option.attr('descriptionID');
		$('#haclGEPermissionExplanation').children().hide();
		$('#'+descrElem).show();
		
		// Show the permission to edit
		$('#haclGESelectedPermission').text(val);
		
		mPermissionID = option.attr('permissionID');
		
		// Open the group editor
		$('.haclGroupEditorDiv').show();
		// If the group tree is present if has to be refreshed for the new permission
		if (typeof mGroupTree !== 'undefined') {
			mGroupTree.destroy();
		}
		// Create the group tree element for the currently select permission
        mGroupTree = HACL.classes.GroupTree("#haclGroupTreeContainer", mPermissionID);
		// Show hint for checkboxes
		$('#haclgGPCheckmarkHint').show();
		
		$('.haclGPButtons').show();
		
	};
	
	/**
	 * This function is called when the discard button is clicked. It closes all
	 * panel section but the permission selector.
	 * 
	 */
	function onDiscardChanges() {
		// Hide the hint for checkboxes, the group editor, the discard and save buttons
		$('#haclgGPCheckmarkHint, .haclGroupEditorDiv, .haclGPButtons').hide();
		
		// Deselect the permission in the permission selector and show the help
		// text
		$('#haclGPPermissionSelector option:selected').removeAttr("selected");
		$('#haclGEPermissionExplanation').children().hide();
		$('#hacfFeatureDescr_0').show();
		
	};
	
	/**
	 * This function is called when the  Save button is clicked. It saves the
	 * changed permissions.
	 */
	function onSave() {
		var permissions = mGroupTree.getChangedPermissions();
		var permissionsJSON = JSON.stringify(permissions);
		var url = wgServer + wgScriptPath + "/index.php?action=ajax";
		// save permissions via ajax
		$.ajax({  url:  url, 
				  data: "rs=haclSaveGroupPermissions&rsargs[]="
					  	+ encodeURIComponent(mPermissionID)
					  	+ "&rsargs[]="
					  	+ encodeURIComponent(permissionsJSON),
				  success: onPermissionsSaved,
				  error: onPermissionsSaveError,
				  type: 'POST'
				});
		
	};
	
	/**
	 * This function is called when changed permissions were saved successfully.
	 */
	function onPermissionsSaved(data, textStatus, XMLHttpRequest) {
		var title = gHACLLanguage.getMessage('globalPermissions');
		YAHOO.haloacl.notification.createDialogOk("content", title, data,
				{
					yes: function(){}
				});		
		onDiscardChanges();
	};
	
	/**
	 * This function is called when changed permissions were not saved successfully.
	 */
	function onPermissionsSaveError(xhr, textStatus, errorThrown) {
		var title = gHACLLanguage.getMessage('globalPermissions');
		YAHOO.haloacl.notification.createDialogOk("content", title, xhr.statusText,
				{
					yes: function(){}
				});		
	};
	
	construct();
	return that;
};
