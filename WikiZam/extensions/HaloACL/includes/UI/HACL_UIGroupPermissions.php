<?php
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
 * This file defines the class HACLUIGroupPermissions
 * 
 * @author Thomas Schweitzer
 * Date: 12.11.2010
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---
 global $lodgIP;
//require_once("$lodgIP/...");

global $HACL_GP_GLOBAL_PERMISSIONS_PANEL;
$HACL_GP_GLOBAL_PERMISSIONS_PANEL = <<<HTML
        <div class="yui-skin-sam">
        	<div class="haclGroupPermission">
        		<div class="haclGPGeneralDiv">
        			{{hacl_gp_intro}}<br />
        			{{hacl_gp_lgr_intro}}***list_group_rights***
        		</div>
        		<div class="haclGPGeneralDiv" id="haclGPPermissions">
        			<div id="haclGPPermissionsSelectorDiv">
	        			<div id="haclGPPermissionsTitle">{{hacl_gp_permission}}</div>
	        			<select name="group_permissions" size="5" id="haclGPPermissionSelector">
	        				***all_permissions***
					    </select>
					</div>
				    <div id="haclGEPermissionExplanation">
				    	***permission_explanation***
				    </div>
        		</div>
        		<div id="haclgGPCheckmarkHint" class="haclGPGeneralDiv" style="display:none">
        			{{hacl_gp_set_permission}}
        			<span id="haclGESelectedPermission"></span><br />
        			<div>
	        			{{hacl_gp_hint}}
	        			<img src="***imgPath***/check0.gif" alt="" align="top"> = {{hacl_gp_check_default}}&nbsp;
	        			<img src="***imgPath***/check2.gif" alt="" align="top"> = {{hacl_gp_check_permit}}&nbsp;
	        			<img src="***imgPath***/check3.gif" alt="" align="top"> = {{hacl_gp_check_deny}}
	        		</div>
        		</div>
        		<div class="haclGPGeneralDiv haclGroupEditorDiv">
        			<div class="haclGPGeneralDiv haclGroupEditorFilter">
		        		{{hacl_gp_group_filter}}
		        		<input id="haclGroupTreeFilter" 
		        				name="gp_group_filter" 
		        				type="text" size="30" 
		        				maxlength="30" />
		        	</div>
	        		<div class="haclGPGeneralDiv haclGroupEditorTitle">
	        			<span id="haclGETGroup">{{hacl_gp_ge_group}}</span>
	        			<span id="haclGETInfo">{{hacl_gp_ge_info}}</span>
	        			<span id="haclGETPermission">{{hacl_gp_ge_permission}}</span>
	        		</div>
		            <div id="haclGroupTreeContainer" class="haclGroupEditorTree">
		            </div>
				</div>
				<div class="haclGPButtons" style="display:none">
			        <input type="button" id="haclGPDiscard" value="{{hacl_gp_discard}}" /> 
			        <input type="button" id="haclGPSave" value="{{hacl_gp_save}}" />
				</div>
            </div>
        </div>
        
        <script type="text/javascript">
            HACL.classes.GroupPermission();
        </script>
	
HTML;

global $HACL_GP_GLOBAL_PERMISSIONS_PANEL_ERROR;
$HACL_GP_GLOBAL_PERMISSIONS_PANEL_ERROR = <<<HTML
        <div class="yui-skin-sam">
        	<div class="haclGroupPermission">
        		<div class="haclGPGeneralDiv">
        			{{hacl_gp_intro}}
        		</div>
        		<div class="haclGPGeneralDiv" id="haclGPPermissions">
        			<b>***error_message***</b>
        		</div>
			</div>
		</div>
HTML;
		
 
/**
 * This class defines the UI for managing group permissions. It generates the
 * HTML for the UI and implements the backend for ajax calls.
 * 
 * @author Thomas Schweitzer
 * 
 */
class HACLUIGroupPermissions {
	
	//--- Constants ---

	//--- Private fields ---
	
	/**
	 * Constructor for  HACLUIGroupPermissions
	 *
	 */		
	function __construct() {
	}
	

	//--- getter/setter ---
//	public function getXY()           {return $this->mXY;}

//	public function setXY($xy)               {$this->mXY = $xy;}
	
	//--- Public methods ---
	
	/**
	 * Returns the HTML of the Global Permissions panel.
	 */
	public static function getPermissionsPanel() {
		global $HACL_GP_GLOBAL_PERMISSIONS_PANEL;
		$html = $HACL_GP_GLOBAL_PERMISSIONS_PANEL;
		
		// Create the link to Special:ListGroupRights
		$linker = new Linker();
		$t = SpecialPage::getTitleFor('Listgrouprights');
		$listGroupRights = $linker->link($t, '{{hacl_gp_listgrouprights}}');
		$html = str_replace("***list_group_rights***", $listGroupRights, $html);
		
		$html = self::insertPermissions($html);
		$html = self::replaceLanguageStrings($html);
		
		// Set correct path for images
		global $haclgHaloScriptPath;
		$imgPath = $haclgHaloScriptPath . "/skins/images";
		$html = str_replace("***imgPath***", $imgPath, $html);
		
		return $html;
	}
	
	/**
	 * Returns the children of the given group in JSON format for jQuery.tree
	 *
	 * @param string $groupID
	 * 		ID of the parent group or "---ROOT---" for the root level
	 * @param string $feature
	 * 		Name of the feature that should be evaluated concerning the check
	 * 		boxes
	 * @return string
	 * 		Children of the requested group
	 */
	public static function getGroupChildren($groupID, $feature) {
		$groups = array();
		if ($groupID == "---ROOT---") {
			// Add special groups
			$alu = wfMsg('hacl_gp_all_users');
			$ru = wfMsg('hacl_gp_registered_users');
			$groups[] = new HACLGroup(HACLGroupPermissions::ALL_USERS, "* $alu *", null, null);
			$groups[] = new HACLGroup(HACLGroupPermissions::REGISTERED_USERS, "* $ru *", null, null);
			// Get all top level groups
			$groups = array_merge($groups, HACLStorage::getDatabase()->getGroups());
		} else {
			// Get all children of the given group
			$group = HACLGroup::newFromID($groupID);
			$groups = $group->getGroups(HACLGroup::OBJECT);
		}

		global $haclgFeature;
		$tttext = wfMsg('hacl_gp_has_permissions');
		
		// Encode all children in JSON
		$json = "[";
		for ($i = 0, $n = count($groups); $i < $n; ++$i) {
			$g = $groups[$i];
			$name = $g->getGroupNameWithoutPrefix();
			$id   = $g->getGroupID();
			$hasChildren = count($g->getGroups(HACLGroup::ID));
			$state = $hasChildren
			? ',state: "closed"'
			: '';
			$comma = $i < $n-1 ? ',' : '';

			// Get the permissions for features of the group
			$permissions = HACLGroupPermissions::getPermissionsForGroup($id);
			$pf = array(); // permitted features
			$permissionState = " normal";
			foreach ($permissions as $f => $permitted) {
				if ($permitted) {
					if (array_key_exists($f, $haclgFeature)) {
						$pf[] = $haclgFeature[$f]['name'];
					} else {
						$pf[] = $f;
					}
				}
				if ($f === $feature) {
					$permissionState = $permitted ? " checked" : " crossed";
				}
			}
			$pf = implode(',', $pf);
			if (!empty($pf)) {
				$pf = <<<HTML
 <span class=\"tree-haloacl-permitted-features\" title=\"$tttext $pf\"></span>
HTML;
			}
			 
			$json .= <<<JSON
			{
			attributes: { 
				id : "haclgt-$id" 
			}, 
			data: "$name$pf<span class=\"tree-haloacl-check$permissionState\"></span>"
			$state
			}$comma
JSON;
		}
		$json .= "]";

		return $json;

	}

	/**
	 * Searches for all groups that contain the string $filter. For each matching
	 * group its path to the root group is generated and returned as a comma
	 * separated list of node IDs in the group tree.
	 * @param string $filter
	 * 		The filter that must be part of the group's name. 
	 * @return string
	 * 		A comma separated list of group IDs that leads from a root node to
	 * 		the matching group 
	 */
	public static function searchMatchingGroups($filter) {
		$result = "";
		$matchingGroups = HACLGroup::searchGroups($filter);
		foreach ($matchingGroups as $name => $id) {
			
			$parents = HACLGroup::getGroupsOfMember($id, HACLGroup::GROUP, true);
			$parents = array_reverse($parents);
			foreach ($parents as $g) {
				$gid = $g['id'];
				$result .= ",haclgt-$gid";
			}
		}
		
		return empty($result) ? "" : substr($result, 1);
	}
	
	//--- Private methods ---
	
	/**
	 * Inserts the options for all permissions into the listbox of the given
	 * $html. The placeholder "***all_permissions***" is replaced. An explanatory
	 * text is added for each permission at the position of the placeholder
	 * "***permission_explanation***".
	 * 
	 * @param string $html
	 * 		The HTML contains the placeholder "***all_permissions***" in a listbox
	 * 		that has to be replaced by options. Explanations replace the placeholder
	 * 		"***permission_explanation***"
	 * @return string
	 * 		HTML with permission options and explanations
	 */
	private static function insertPermissions($html) {
		global $haclgFeature, $wgUser;
		$groups = $wgUser->getEffectiveGroups();
		
		// Some permissions for features can only be changed by sysops or
		// bureaucrats 
		$isAdmin = in_array('bureaucrat', $groups) || in_array('sysop', $groups);
		$options = "";
		$explanations = <<<HTML
<div id="hacfFeatureDescr_0">
	{{hacl_gp_select_permission}}
</div>
HTML;
		$i = 1;
		foreach ($haclgFeature as $featureID => $feature) {
			if ($feature['permissibleBy'] == 'all' || $isAdmin) {
				$default = $feature['default'] === "permit" 
							? "{{hacl_gp_permit}}"
							: "{{hacl_gp_deny}}";
				$sf = str_replace('|', ', ', $feature['systemfeatures']);
				$options .= <<<HTML
<option descriptionID="hacfFeatureDescr_$i" 
		permissionID="$featureID">
	{$feature['name']}
</option>
HTML;
				$explanations .= <<<HTML
<div id="hacfFeatureDescr_$i" style="display:none">
{$feature['description']}<br />
{{hacl_gp_default}}<b>$default</b><br />
{{hacl_gp_comprises_features}}<br />
$sf<br />
</div>
HTML;
			}
			$i++;
		}
		
		if (empty($options)) {
			global $HACL_GP_GLOBAL_PERMISSIONS_PANEL_ERROR;
			$html = $HACL_GP_GLOBAL_PERMISSIONS_PANEL_ERROR;
			if ($isAdmin) {
				$html = str_replace("***error_message***", '{{hacl_gp_no_features_defined}}', $html);
			} else {
				$html = str_replace("***error_message***", '{{hacl_gp_no_features_for_user}}', $html);
			}
			return $html;			
		}
		
		$html = str_replace("***all_permissions***", $options, $html);
		$html = str_replace("***permission_explanation***", $explanations, $html);
		
		return $html;
	}
	
	/**
	 * Language dependent identifiers in $text that have the format {{identifier}}
	 * are replaced by the string that corresponds to the identifier.
	 * 
	 * @param string $text
	 * 		Text with language identifiers
	 * @return string
	 * 		Text with replaced language identifiers.
	 */
	private static function replaceLanguageStrings($text) {
		// Find all identifiers
		$numMatches = preg_match_all("/(\{\{(.*?)\}\})/", $text, $identifiers);
		if ($numMatches === 0) {
			return $text;
		}

		// Get all language strings
		$langStrings = array();
		foreach ($identifiers[2] as $id) {
			$langStrings[] = wfMsg($id);
		}
		
		// Replace all language identifiers
		$text = str_replace($identifiers[1], $langStrings, $text);
		return $text;
	}
	
}
