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
 * This file contains functions for client/server communication with Ajax.
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

/**
 *
 * @param <string> javascript-escaped string
 * @return <string> unescaped string
 */
function unescape($source) {
	$decodedStr = '';
	$pos = 0;
	$len = strlen ($source);

	while ($pos < $len) {
		$charAt = substr ($source, $pos, 1);
		if ($charAt == '%') {
			$pos++;
			$charAt = substr ($source, $pos, 1);
			if ($charAt == 'u') {
				// we got a unicode character
				$pos++;
				$unicodeHexVal = substr ($source, $pos, 4);
				$unicode = hexdec ($unicodeHexVal);
				$decodedStr .= code2utf($unicode);
				$pos += 4;
			} else {
				// we have an escaped ascii character
				$hexVal = substr ($source, $pos, 2);
				$decodedStr .= code2utf (hexdec ($hexVal));
				$pos += 2;
			}
		} else {
			$decodedStr .= $charAt;
			$pos++;
		}
	}
	return $decodedStr;
}


/*
 * defining ajax-callable functions
 */
$wgAjaxExportList[] = "haclGlobalPermissionsPanel";
$wgAjaxExportList[] = "haclGetGroupChildren";
$wgAjaxExportList[] = "haclFilterGroups";
$wgAjaxExportList[] = "haclSaveGroupPermissions";

$wgAjaxExportList[] = "haclAjaxTestFunction";
$wgAjaxExportList[] = "haclCreateACLPanels";
$wgAjaxExportList[] = "haclCreateManageACLPanels";
$wgAjaxExportList[] = "haclCreateAclContent";
$wgAjaxExportList[] = "haclCreateAclTemplateContent";
$wgAjaxExportList[] = "haclCreateRightContent";
$wgAjaxExportList[] = "haclCreateModificationRightsContent";
$wgAjaxExportList[] = "haclCreateSaveContent";
$wgAjaxExportList[] = "haclCreateManageExistingACLContent";
$wgAjaxExportList[] = "haclRightList";
$wgAjaxExportList[] = "haclManageAclsContent";
$wgAjaxExportList[] = "haclManageUserContent";
$wgAjaxExportList[] = "haclWhitelistsContent";
$wgAjaxExportList[] = "haclGetRightsPanel";
$wgAjaxExportList[] = "haclRightPanelSelectDeselectTab";
$wgAjaxExportList[] = "haclRightPanelAssignedTab";
$wgAjaxExportList[] = "haclGetGroupsForRightPanel";
$wgAjaxExportList[] = "haclGetUsersForUserTable";
$wgAjaxExportList[] = "haclSaveTempRightToSession";
$wgAjaxExportList[] = "haclGetModificationRightsPanel";
$wgAjaxExportList[] = "haclSaveSecurityDescriptor";
$wgAjaxExportList[] = "haclGetWhitelistPages";
$wgAjaxExportList[] = "haclGetUsersForGroups";
$wgAjaxExportList[] = "haclGetGroupsForManageUser";
$wgAjaxExportList[] = "haclGetACLs";
$wgAjaxExportList[] = "haclGetSDRightsPanel";
$wgAjaxExportList[] = "haclGetManageUserGroupPanel";
$wgAjaxExportList[] = "haclSaveTempGroupToSession";
$wgAjaxExportList[] = "haclSaveGroup";
$wgAjaxExportList[] = "haclGetSDRightsPanelContainer";
$wgAjaxExportList[] = "haclDeleteSecurityDescriptor";
$wgAjaxExportList[] = "haclCreateAclUserTemplateContent";
$wgAjaxExportList[] = "haclGetRightsContainer";
$wgAjaxExportList[] = "haclSaveWhitelist";
$wgAjaxExportList[] = "haclGetAutocompleteDocuments";
$wgAjaxExportList[] = "haclCreateManageUserTemplateContent";
$wgAjaxExportList[] = "haclDeleteGroups";
$wgAjaxExportList[] = "haclDeleteWhitelist";
$wgAjaxExportList[] = "haclGetGroupDetails";
$wgAjaxExportList[] = "haclCreateQuickAclTab";
$wgAjaxExportList[] = "haclGetQuickACLData";
$wgAjaxExportList[] = "haclSaveQuickacl";
$wgAjaxExportList[] = "haclDoesArticleExists";
$wgAjaxExportList[] = "haclSDpopupByName";
$wgAjaxExportList[] = "haclRemovePanelForTemparray";


/*
 * testfunction, that just returns "testtext"
 */
function haclAjaxTestFunction() {
    $temp = new AjaxResponse();
    $temp->addText("testtext");

    return $temp;
}

/**
 *
 * @return <string> content for createacl-tab
 */
function haclCreateACLPanels() {

// clear temp-right-sessions
    clearTempSessionRights();

    $response = new AjaxResponse();

    $html = <<<HTML
        <div class="yui-skin-sam">
            <input id="processType" type="hidden" value="init" />
            <div id="haloaclsubView" class="yui-navset"></div>
        </div>
        <script type="text/javascript">
            YAHOO.haloacl.buildSubTabView('haloaclsubView');
        </script>
HTML;

    $response->addText($html);
    return $response;

}

/**
 *
 * @return <string> content for manage-acl-tab
 */
function haclCreateManageACLPanels() {

// clear temp-right-sessions
    clearTempSessionRights();

    $response = new AjaxResponse();

    $html = <<<HTML
        <div class="yui-skin-sam">
            <div id="haloaclsubViewManageACL" class="yui-navset"></div>
        </div>
        <script type="text/javascript">
            YAHOO.haloacl.buildSubTabView('haloaclsubViewManageACL');
        </script>
HTML;

    $response->addText($html);
    return $response;

}


/**
 *
 * @param <string> predfine-type | DEPRECATED as predfine has been moved into rightspanel
 * @return <ajaxRepsonse> content for rights-section in create-acl-tab
 */
function haclCreateRightContent($predefine) {

    $hacl_createRightContent_help = wfMsg('hacl_createRightContent_help');
    $hacl_haloacl_tab_section_header_title = wfMsg('hacl_haloacl_tab_section_header_title');
    $create_right = wfMsg('hacl_create_right');
    $add_template = wfMsg('hacl_add_template');
    $next_step = wfMsg('hacl_general_nextStep');

    $response = new AjaxResponse();
    $helpItem = new HACL_helpPopup("Right", $hacl_createRightContent_help);

    $html = <<<HTML
        <!-- section start -->
        <!-- rights section -->
        <div class="haloacl_tab_section_container">
            <div class="haloacl_tab_section_header">
                <div class="haloacl_tab_section_header_count">2.</div>
                <div class="haloacl_tab_section_header_title">
        $hacl_haloacl_tab_section_header_title
                </div>
HTML;

    $html .= $helpItem->getPanel();
    $html .= <<<HTML
        </div>
            <div id="haloacl_tab_createacl_rightsection" class="haloacl_tab_section_content">
                <div class="haloacl_tab_section_content_row">
                    <div id="step2_button2">
                     <input id="haloacl_create_right_$predefine" type="button" value="$create_right"
                        onclick="javascript:YAHOO.haloacl.createacl_addRightPanel('$predefine');"/>
                    &nbsp;
                    <input  id="haloacl_add_right_$predefine" type="button" value="$add_template"
                        onclick="javascript:YAHOO.haloacl.createacl_addRightTemplate('$predefine');"/>
                    </div>
               </div>
            </div>
        </div>

        <div id="step3" style="display:none;width:600px;">
            <input id="haloacl_createacl_nextstep_$predefine" type="button" name="gotoStep3" value="$next_step"
            onclick="javascript:YAHOO.haloacl.createacl_gotoStep3();" />
        </div>

        <!-- section end -->

        <script type="javascript">

            YAHOO.haloacl.createacl_gotoStep3 = function() {
                $('step2_button2').hide();
                YAHOO.haloacl.loadContentToDiv('step3','haclCreateModificationRightsContent',{panelid:1});
            }

            YAHOO.haloacl.createacl_addRightPanel = function(predefine){
                  var panelid = 'create_acl_right_'+ YAHOO.haloacl.panelcouner;
                  var divhtml = '<div id="create_acl_rights_row'+YAHOO.haloacl.panelcouner+'" class="haloacl_tab_section_content_row"></div>';
                  var containerWhereDivsAreInserted = $('haloacl_tab_createacl_rightsection');
                  $('haloacl_tab_createacl_rightsection').insert(divhtml,containerWhereDivsAreInserted);

                  YAHOO.haloacl.loadContentToDiv('create_acl_rights_row'+YAHOO.haloacl.panelcouner,'haclGetRightsPanel',{panelid:panelid, predefine:predefine});
                  YAHOO.haloacl.panelcouner++;
            };

            YAHOO.haloacl.createacl_addRightTemplate = function(predefine){
                  var panelid = 'create_acl_right_'+ YAHOO.haloacl.panelcouner;
                  var divhtml = '<div id="create_acl_rights_row'+YAHOO.haloacl.panelcouner+'" class="haloacl_tab_section_content_row"></div>';
                  var containerWhereDivsAreInserted = $('haloacl_tab_createacl_rightsection');
                  $('haloacl_tab_createacl_rightsection').insert(divhtml,containerWhereDivsAreInserted);

                  YAHOO.haloacl.loadContentToDiv('create_acl_rights_row'+YAHOO.haloacl.panelcouner,'haclGetRightsContainer',{panelid:panelid});
                  YAHOO.haloacl.panelcouner++;
            };

        </script>

HTML;

    //create default panels
    switch ($predefine) {
        case "private":
            $html .= <<<HTML
                <script type="javascript">
                    YAHOO.haloacl.createacl_addRightPanel("private");
                </script>
HTML;
            break;
        case "all":
            $html .= <<<HTML
                <script type="javascript">
                    YAHOO.haloacl.createacl_addRightPanel("all");
                </script>
HTML;
            break;
    }


    $response->addText($html);
    return $response;
}

/**
 *
 * @global <User> $wgUser
 * @return <ajaxRepsonse> content for modification-rights-step in create-acl-tab
 */
function haclCreateModificationRightsContent() {


    $hacl_createModificationRightContent_help = wfMsg('hacl_createModificationRightContent_help');
    $hacl_haloacl_tab_section_header_mod_title = wfMsg('hacl_haloacl_tab_section_header_mod_title');
    $hacl_haloacl_mod_1 = wfMsg('hacl_haloacl_mod_1');
    $hacl_haloacl_mod_2 = wfMsg('hacl_haloacl_mod_2');
    $hacl_haloacl_mod_3 = wfMsg('hacl_haloacl_mod_3');
    $next_step = wfMsg('hacl_general_nextStep');


    global $wgUser;
    $currentUser = $wgUser->getName();

    $response = new AjaxResponse();

    $helpItem = new HACL_helpPopup("ModificationRight", $hacl_createModificationRightContent_help);

    $html = <<<HTML
        <!-- section start -->
        <!-- modificationrights section -->
        <div class="haloacl_tab_section_container">
            <div class="haloacl_tab_section_header">
                <div class="haloacl_tab_section_header_count">3.</div>
                <div class="haloacl_tab_section_header_title">
        $hacl_haloacl_tab_section_header_mod_title
                </div>
HTML;

    $html .= $helpItem->getPanel();
    $html .= <<<HTML

            </div>

            <div id="haloacl_tab_createacl_modificationrightsection" class="haloacl_tab_section_content">
                <div class="haloacl_tab_section_content_row">
        $hacl_haloacl_mod_1
                </div>
                <div class="haloacl_tab_section_content_row">
                    <div id="create_acl_rights_row_modificationrights" class="haloacl_tab_section_content_row"></div>
                </div>

            <div class="haloacl_tab_section_content_row">
                <div id="step3_buttons">
                    <input id="haloacl_save_modificationrights" type="button" name="gotoStep4" value="$next_step"
                    onclick="javascript:YAHOO.haloacl.create_acl_gotoStep4();"/>
                </div>
            </div>

        </div>
        <div id="step4"></div>
        <!-- section end -->
        <script type="javascript">

            YAHOO.haloacl.create_acl_gotoStep4 = function() {
                /*
                //checks and warnings
                var goAhead = false;
                if (YAHOO.haloacl.hasGroupsOrUsers('right_tabview_create_acl_modificationrights')) {
                    goAhead=true; 
                    if (!YAHOO.haloacl.isNameInUserArray('right_tabview_create_acl_modificationrights', '$currentUser') && !YAHOO.haloacl.isNameInUsersGroupsArray('right_tabview_create_acl_modificationrights', '$currentUser')) {alert('$hacl_haloacl_mod_2'); }
                } else {
                    alert("$hacl_haloacl_mod_3");
                }
                if (goAhead)
                */

                $('step3_buttons').hide();
                YAHOO.haloacl.loadContentToDiv('step4','haclCreateSaveContent',{panelid:1});
            }
        </script>
        <!-- section end -->
        <script type="javascript">
            // retrieving modification rights panel
            var panelid = 'create_acl_modificationrights';
            YAHOO.haloacl.loadContentToDiv('create_acl_rights_row_modificationrights','haclGetModificationRightsPanel',{panelid:panelid});
        </script>

HTML;


    $response->addText($html);
    return $response;
}


/**
 *
 * @global <User> $wgUser
 * @return <AjaxRepsonse> content for last step in createacl-tab / save-area
 */
function haclCreateSaveContent() {
    global $haclgContLang;
    $template = $haclgContLang->getSDTemplateName();
    $predefinedRightName = $haclgContLang->getPredefinedRightName();
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    $protectPage = $haclgContLang->getPetPrefix(HACLSecurityDescriptor::PET_PAGE);
    $protectCategory = $haclgContLang->getPetPrefix(HACLSecurityDescriptor::PET_CATEGORY);
    $protectNamespace = $haclgContLang->getPetPrefix(HACLSecurityDescriptor::PET_NAMESPACE);
    $protectProperty = $haclgContLang->getPetPrefix(HACLSecurityDescriptor::PET_PROPERTY);
        
    $hacl_createSaveContent_1 = wfMsg('hacl_createSaveContent_1');
    $hacl_createSaveContent_2 = wfMsg('hacl_createSaveContent_2');
    $hacl_createSaveContent_3 = wfMsg('hacl_createSaveContent_3');
    $hacl_createSaveContent_4 = wfMsg('hacl_createSaveContent_4');

    global $wgUser;
    $userName = $wgUser->getName();

    $response = new AjaxResponse();

    $helpItem = new HACL_helpPopup("hacl_savecreateaclhelp", wfMsg("hacl_createSavehelpopup1"));

    $html = <<<HTML
        <!-- section start -->
        <div class="haloacl_tab_section_container">
            <div class="haloacl_tab_section_header">
                <div class="haloacl_tab_section_header_count">4.</div>

                <div class="haloacl_tab_section_header_title">
        $hacl_createSaveContent_1
                </div>
HTML;
    $html .= $helpItem->getPanel();

    $jump_to_article = wfMsg('hacl_jumptoarticle');

    $html .= <<<HTML
            </div>

            <div class="haloacl_tab_section_content">
                <div class="haloacl_tab_section_content_row">
                    <div class="haloacl_tab_section_content_row_descr">
        $hacl_createSaveContent_2
                    </div>
                    <div class="haloacl_tab_section_content_row_content">
                        <div class="haloacl_tab_section_content_row_content_element">
                            <input type="text" disabled="true" id="create_acl_autogenerated_acl_name" value=""/>
                        </div>
                    </div>
                </div>


            </div>
        </div>
        <!-- section end -->
        <script type="javascript">
            $('haloacl_saveacl_button').enable();


            ////////ACL Name
            var ACLName = "";
            var protectedItem = "";
            $$('.create_acl_general_protect').each(function(item){
                if (item.checked){
                    protectedItem = item.value;
                }
            });
            switch (protectedItem) {
                case "page":ACLName = '$protectPage'; break; 
                case "property":ACLName = '$protectProperty'; break;
                case "namespace":ACLName = '$protectNamespace'; break;
                case "category":ACLName = '$protectCategory'; break;
            }

            switch ($('processType').value) {
                case "createACL":ACLName = "$ns:"+ACLName+'/'+$('create_acl_general_name').value;break;
                case "createAclTemplate":ACLName = '$ns:$predefinedRightName/'+$('create_acl_general_name').value;break;
                case "createAclUserTemplate":ACLName = '$ns:$template/$userName'; break;
                case "all_edited":ACLName = $('create_acl_general_name').value; break; //Name already existing - reuse
            }

            $('create_acl_autogenerated_acl_name').value = ACLName;


            var callback2 = function(result){
                if (result.status == '200'){
                    YAHOO.haloacl.notification.createDialogYesNo("content","$hacl_createSaveContent_3",result.responseText,{
                        yes:function(){window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=createACL';},
                        no:function(){window.location.href=YAHOO.haloacl.specialPageUrl+'/../'+result.responseText;},
                    },"Ok","$jump_to_article");
                } else {
                    YAHOO.haloacl.notification.createDialogOk("content","$hacl_createSaveContent_4",result.responseText,{
                    yes:function(){}
                    });
                }
            };

            YAHOO.haloacl.buildCreateAcl_SecDesc = function(){
                var groups = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstanceright_tabview_create_acl_modificationrights);

                // building xml
                var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                xml+="<secdesc>";
                xml+="<panelid>create_acl</panelid>";

                xml+="<users>";
                $$('.datatableDiv_right_tabview_create_acl_modificationrights_users').each(function(item){
                    if (item.checked){
                        xml+="<user>"+escape(item.name)+"</user>";
                    }
                });
                xml+="</users>";

                xml+="<groups>";
                groups.each(function(group){
                    xml+="<group>"+escape(group)+"</group>";
                });
                xml+="</groups>";
                xml+="<name>"+escape($('create_acl_autogenerated_acl_name').value)+"</name>";
                xml+="<definefor>"+""+"</definefor>";
                xml+="<ACLType>"+$('processType').value+"</ACLType>";


                $$('.create_acl_general_protect').each(function(item){
                    if (item.checked){
                        xml+="<protect>"+item.value+"</protect>";
                    }
                });

                xml+="</secdesc>";

                YAHOO.haloacl.sendXmlToAction(xml,'haclSaveSecurityDescriptor',callback2);
            };
        </script>

HTML;


    $response->addText($html);
    return $response;
}


/**
 *
 * @return <string> content for manage-acl-tab
 */
function haclCreateManageExistingACLContent() {

    $hacl_createManageACLContent_1 = wfMsg('hacl_createManageACLContent_1');
    $hacl_createManageACLContent_2 = wfMsg('hacl_createManageACLContent_2');

    $myGenericPanel = new HACL_GenericPanel("ManageExistingACLPanel", "[ ACL Explorer ]", "[ ACL Explorer ]", false, false,false);

    $tempContent = <<<HTML
        <div id="content_ManageExistingACLPanel">
            <div id="manageExistingACLRightList"></div>
        </div>
        <script>
            YAHOO.haloacl.loadContentToDiv('manageExistingACLRightList','haclRightList',{panelid:'manageExistingACLRightList', type:'edit'});
        </script>
HTML;

    $myGenericPanel->setContent($tempContent);

    $html = <<<HTML
        <div class="haloacl_tab_content">
            <div class="haloacl_tab_content_description">
                <div class="haloacl_manageusers_title">$hacl_createManageACLContent_1</div>
        $hacl_createManageACLContent_2
                </div>

HTML;
    $html.= $myGenericPanel->getPanel();
    $html.= <<<HTML

            <div class="haloacl_greyline">&nbsp;</div>
            <div id="ManageACLDetail"></div>
        </div>
HTML;

    return $html;
}


/**
 *
 * @global <User> $wgUser
 * @return <string> content for default user template creation | create-acl-tab
 */
function haclCreateManageUserTemplateContent() {
    global $haclgContLang;
    $template = $haclgContLang->getSDTemplateName();


    $hacl_createManageUserTemplateContent_1 = wfMsg('hacl_createManageUserTemplateContent_1');

    global $wgUser;
    $myGenericPanel = new HACL_GenericPanel("ManageExistingACLPanel", "[ $hacl_createManageUserTemplateContent_1 ]", "[ $hacl_createManageUserTemplateContent_1 ]", false, false,false);
    try {
        $SDName = "$template/".$wgUser->getName();
        $SD = HACLSecurityDescriptor::newFromName($SDName);
        $SDId = $SD->getSDID();

        $tempContent = <<<HTML
        <div id="ManageACLDetail2"></div>
        <script>
            YAHOO.haloacl.loadContentToDiv('ManageACLDetail2','haclGetSDRightsPanelContainer',{sdId:'$SDId',sdName:'$SDName',readOnly:'false'});
        </script>
HTML;
    }
    catch(Exception $e ) {
        $spt = SpecialPage::getTitleFor("HaloACL");
        $url = $spt->getFullURL();
        $url .= "?activetab=createACL&activesubtab=manageDefaultTemplate";


        $tempContent = "<div style='padding:8px;'>".wfMsg('hacl_nodefusertpl')." &raquo; <a href='$url'>".wfMsg('hacl_nodefusertpl_link')."</a></div>";
    }

    $myGenericPanel->setContent($tempContent);

    return $myGenericPanel->getPanel();
}


/**
 *
 * @param <string> $panelId
 * @param <bool> show name-input?
 * @param <GenericPanel> $helpItem
 * @param <string> createAclUserTemplate | createAclTemplate | createStdAcl (type based on subtab
 * @return <string> content for step 1 of acl-creation
 */
function createGeneralContent($panelId, $nameBlock, $helpItem, $processType) {
    $html = "";
    if ($processType == "createAclTemplate") {
        $html .= '<script>$("createStdAclTab_content").innerHTML = "";</script>';
    }

    #echo "$processType";
    $hacl_createGeneralContent_1 = wfMsg('hacl_createGeneralContent_1');
    $hacl_createGeneralContent_2 = wfMsg('hacl_createGeneralContent_2');
    $hacl_createGeneralContent_3 = wfMsg('hacl_createGeneralContent_3');
    $hacl_createGeneralContent_4 = wfMsg('hacl_createGeneralContent_4');
    $hacl_createGeneralContent_5 = wfMsg('hacl_createGeneralContent_5');
    $hacl_createGeneralContent_6 = wfMsg('hacl_createGeneralContent_6');
    $hacl_createGeneralContent_7 = wfMsg('hacl_createGeneralContent_7');
    $hacl_createGeneralContent_8 = wfMsg('hacl_createGeneralContent_8');

    $hacl_general_nextStep = wfMsg('hacl_general_nextStep');

    $hacl_createGeneralContent_message1 = wfMsg('hacl_createGeneralContent_message1');
    $hacl_createGeneralContent_message2 = wfMsg('hacl_createGeneralContent_message2');
    $hacl_createGeneralContent_message3 = wfMsg('hacl_createGeneralContent_message3');

    $html .= <<<HTML
        <div class="haloacl_tab_content">

        <div class="haloacl_tab_content_description">
        $hacl_createGeneralContent_1
        </div>

        <!-- section start -->
        <div class="haloacl_tab_section_container">
            <div class="haloacl_tab_section_header">
                <div class="haloacl_tab_section_header_count">1.</div>
                <div class="haloacl_tab_section_header_title">
        $hacl_createGeneralContent_2
                </div>
HTML;

    $html .= $helpItem->getPanel();
    $html .= <<<HTML
        </div>
            <div class="haloacl_tab_section_content">

            <script>
                resetName = function(){
                    try{
                    if ($('create_acl_general_name') != null){
                        $('create_acl_general_name').value = "";
                    }
                    }catch(e){}
                };
            </script>
HTML;
    if ($processType <> "createAclTemplate") {
        $html .= <<<HTML
            <div class="haloacl_tab_section_content_row">
                    <div class="haloacl_tab_section_content_row_descr">
            $hacl_createGeneralContent_3
                    </div>
                    <div class="haloacl_tab_section_content_row_content">
                        <div class="haloacl_tab_section_content_row_content_element">
                            <input type="radio"  checked class="create_acl_general_protect" name="create_acl_general_protect" value="page" onClick="resetName();" />&nbsp;$hacl_createGeneralContent_4
                        </div>
HTML;
        if ($processType <> "createAclUserTemplate")
            $html .= <<<HTML
                        <div class="haloacl_tab_section_content_row_content_element">
                            <input type="radio" class="create_acl_general_protect" name="create_acl_general_protect" id="hacl_general_protect_property" value="property" onClick="resetName();"/>&nbsp;$hacl_createGeneralContent_5
                        </div>
                        <div class="haloacl_tab_section_content_row_content_element">
                            <input type="radio" class="create_acl_general_protect" name="create_acl_general_protect" id="hacl_general_protect_namespace" value="namespace" onClick="resetName();" />&nbsp;$hacl_createGeneralContent_6
                        </div>
                        <div class="haloacl_tab_section_content_row_content_element">
                            <input type="radio" class="create_acl_general_protect" name="create_acl_general_protect" id="hacl_general_protect_category" value="category" onClick="resetName();" />&nbsp;$hacl_createGeneralContent_7
                        </div>
                  
HTML;
        $html .= <<<HTML
            </div>
                </div>
HTML;
    }

    if ($nameBlock) {
        $html .= <<<HTML

                <div class="haloacl_tab_section_content_row">
                    <div class="haloacl_tab_section_content_row_descr">
            $hacl_createGeneralContent_8
                    </div>
                    <div class="haloacl_tab_section_content_row_content">
                        <div class="haloacl_tab_section_content_row_content_element">
 
                            <input type="text" name="create_acl_general_name2" id="create_acl_general_name" value="" />
                            <div id="create_acl_general_name_container"></div>
HTML;
        if ($processType == "createACL") {
            $html .= <<<HTML
                <script type="javascript">
                                YAHOO.haloacl.AutoCompleter('create_acl_general_name', 'create_acl_general_name_container');
                            </script>
HTML;
        }
        $html .= <<<HTML
            </div>
                    </div>
                </div>
                <script>
HTML;


        if ($processType == "createACL") {
            $html .= 'YAHOO.haloacl.addTooltip("tooltip_create_acl_general_name","create_acl_general_name","'.wfMsg('hacl_tooltip_enternameforexisting').'");';
        } else if ($processType == "createAclTemplate") {
                $html .= 'YAHOO.haloacl.addTooltip("tooltip_create_acl_general_name","create_acl_general_name","'.wfMsg('hacl_tooltip_eneternamefortemplate').'");';
            }
        $html .= <<<HTML
            // set title when comming form request to specialpage
                    if (YAHOO.haloacl.requestedTitle != ""){
                        try{
                            $("create_acl_general_name").value = YAHOO.haloacl.requestedTitle;
                        }
                        catch(e){};
                    }
                </script>
HTML;
    }

    $discard = wfMsg('hacl_discard_changes');
    $saveacl = wfMsg('hacl_save_acl');
    $tplexists = wfMsg('hacl_tpl_already_exists');
    $setexistingname = wfMsg('hacl_setexisting_name');
    $alreadyprotected = wfMsg('hacl_already_protected');
    $alreadyprotectedNSCat = wfMsg('hacl_already_protected_by_ns_or_cat');
    
    $html .= <<<HTML
        </div>

        <!-- section end -->

        <div id="step2_$panelId">
            <input id="step2_button_$panelId" type="button" name="gotoStep2" value="$hacl_general_nextStep"/>
        </div>

        <script type="javascript">

            // load autocompleter
            //YAHOO.haloacl.AutoCompleter();
            // processtype-values: createACL, createAclTemplate, createAclUserTemplate
            $('processType').value = '$processType';
            var processType = '$processType';

            // create listener for next-button
            var step2callbackSecondCheck = function(){
                var message = "";
                // check for protected
                if (processType != "createAclTemplate"){
                    var nextOk = false;
                    $$('.create_acl_general_protect').each(function(item){
                        if (item.checked) nextOk = true;
                    });
                    if (!nextOk)message+="$hacl_createGeneralContent_message1";
                }
                // check for name
                if (processType != "createAclUserTemplate"){
                    if ($('create_acl_general_name').value=="")message+="$hacl_createGeneralContent_message2";
                }

                if (message == "") {
 
                    // loading individual user panel
                    YAHOO.haloacl.loadContentToDiv('step2_$panelId','haclCreateRightContent',{predefine:YAHOO.haloacl.createAclStdDefine});

                    // disabeling controlls
                    $$('.create_acl_general_protect').each(function(item){
                        item.disabled = true;
                    });
                    if ($('create_acl_general_name') != null){
                        $('create_acl_general_name').disabled = true;
                    }
                    // ------------
                } else {
                    YAHOO.haloacl.notification.createDialogOk("content","",message,{
                        yes:function(){}
                    });
                }
            };

           var step2callback = function(){
                if (processType == "createAclTemplate"){
                    var protect = "Right";
                    YAHOO.haloacl.callAction("haclDoesArticleExists", {articletitle:$('create_acl_general_name').value,type:protect}, function(result){
                        if (result.responseText != "true"){
                            step2callbackSecondCheck();
                        } else {
                            YAHOO.haloacl.notification.createDialogOk("content","","$tplexists",{
                                yes:function(){}
                            });
                        }
                    });
                } else if (processType == "createACL"){
                    // getting protect
                    var protect = "";
                    $$('.create_acl_general_protect').each(function(item){
                        if (item.checked){
                            protect = item.value;
                        }
                    });

                    YAHOO.haloacl.callAction("haclDoesArticleExists", {articletitle:$('create_acl_general_name').value,type:protect}, function(result){
                        if (result.responseText == "true"){
                            step2callbackSecondCheck();
                        } else if (result.responseText == "sdexisting") {
                            YAHOO.haloacl.notification.createDialogOk("content","","$alreadyprotected",
                                                                      {yes:function(){} } );
                        } else if (result.responseText == "articleIsProtected") {
                            YAHOO.haloacl.notification.createDialogOk("content","","$alreadyprotectedNSCat",
                                                                      {yes:function(){} } );
                        } else {
                            YAHOO.haloacl.notification.createDialogOk("content","","$setexistingname",{
                                yes:function(){}
                            });
                        }
                    });
                } else {
                    step2callbackSecondCheck();
                }

            };

            YAHOO.haloacl.notification.subscribeToElement('step2_button_$panelId','click',step2callback);
 
        </script>
    </div>

            <div class="haloacl_tab_section_content">
                <div class="haloacl_tab_section_content_row">
                   <div class="haloacl_button_box">
                    <form>
                     <input type="button" class="haloacl_discard_button" id="haloacl_discardacl_button" value="$discard"
                         onclick="javascript:YAHOO.haloacl.discardChanges_createacl();"/>
                    &nbsp;<input disabled="true" type="button" id="haloacl_saveacl_button" name="safeACL" value="$saveacl"
                        onclick="javascript:YAHOO.haloacl.buildCreateAcl_SecDesc();"/>
                    </form>
                    </div>
                </div>

            </div>
HTML;

    return $html;

}


/**
 *
 * returns content of "create ACL" panel
 *
 * @return <AjaxRepsonse>
 *  returns content for createacl-tab
 */
function haclCreateAclContent() {

    $hacl_createACLContent_1 = wfMsg('hacl_createACLContent_1');
    $hacl_createACLContent_2 = wfMsg('hacl_createACLContent_2');

    // clear rights saved in session
    clearTempSessionRights();

    $response = new AjaxResponse();

    $helpText = $hacl_createACLContent_1;
    $helpItem = new HACL_helpPopup($hacl_createACLContent_2, $helpText);

    $html = createGeneralContent("createAcl", true, $helpItem, "createACL");

    $response->addText($html);
    return $response;
}


/**
 *
 * returns content of "manage ACL" panel
 *
 * @return <AjaxRepsonse>   returns tab-content for managel acls-tab
 */
function haclManageAclsContent() {

// clear rights saved in session
    clearTempSessionRights();

    $response = new AjaxResponse();

    $html = createGeneralContent("createAcl", true, $helpItem, "ManageACLTemplate");

    $response->addText($html);
    return $response;
}


/**
 *
 * returns content of "create ACL Template" panel
 *
 * @return <AjaxRepsonse>
 *  returns content for createacl-tab
 */
function haclCreateAclTemplateContent() {

    $hacl_createACLTemplateContent_1 = wfMsg('hacl_createACLTemplateContent_1');
    $hacl_createACLTemplateContent_2 = wfMsg('hacl_createACLTemplateContent_2');

    // clear temp-right-sessions
    clearTempSessionRights();

    $response = new AjaxResponse();

    $helpText = $hacl_createACLTemplateContent_1;
    $helpItem = new HACL_helpPopup($hacl_createACLTemplateContent_2, $helpText);

    $html = createGeneralContent("createAclTemplate", true, $helpItem, "createAclTemplate");

    $response->addText($html);
    return $response;
}


/**
 *
 * returns content of "create ACL Template" panel
 *
 * @return <AjaxRepsonse>
 *  returns content for createacl-tab
 */
function haclCreateAclUserTemplateContent() {
    global $wgUser,$haclgContLang;
    $hacl_createUserTemplateContent_1 = wfMsg('hacl_createUserTemplateContent_1');
    $hacl_createUserTemplateContent_2 = wfMsg('hacl_createUserTemplateContent_2');
    $template = $haclgContLang->getSDTemplateName();

    // clear temp-right-sessions
    clearTempSessionRights();

    $response = new AjaxResponse();

    $alreadyExisting = false;
    try {
        $SDName = "$template/".$wgUser->getName();
        $SD = HACLSecurityDescriptor::newFromName($SDName);
        $alreadyExisting = true;
    }
    catch(Exception $e ) {

    }

    $spt = SpecialPage::getTitleFor("HaloACL");
    $url = $spt->getFullURL();
    $url .= "?activetab=manageACLs&activesubtab=manageDefaultTemplate";

    if ($alreadyExisting) {
        $html = <<<HTML
        <div style="padding:5px;">
            You already created a default user template.
            &nbsp;
            <a href='$url'> &raquo; click here to edit your default user template</a>
        </div>
HTML;
        $response->addText($html);

        return $response;
    }



    $helpText = $hacl_createUserTemplateContent_1;
    $helpItem = new HACL_helpPopup($hacl_createUserTemplateContent_2, $helpText);

    $html = createGeneralContent("createAclTemplate", false, $helpItem, "createAclUserTemplate");

    $response->addText($html);
    return $response;
}



/**
 *  builds content for group panel in manageGroup-tab
 *
 * @global <User> $wgUser
 * @param <string> $panelid
 * @param <string> $name
 * @param <string> $description
 * @param <string> Users included in Group
 * @param <string> Groups included in Group
 * @param <string> Modification-Users
 * @param <string> Modification-Groups
 * @return <string> content of grouppanel
 */
function haclGetManageUserGroupPanel($panelid, $name="", $description="", $users=null, $groups=null, $manageUsers=null, $manageGroups=null) {
    global $wgUser;
    $hacl_rightsPanel_3 = wfMsg('hacl_groupdescription');

    clearTempSessionGroup();
    clearTempSessionRights();

    $newGroup = "false";
    #$groupname = "Group settings";
    $groupname = wfMsg('hacl_groupsettings');

    if ($users == null && $groups == null && $manageUsers == null && $manageGroups == null) {
        $newGroup = "true";
    }

    $hacl_manageUserGroupPanel_1 = wfMsg('hacl_manageUserGroupPanel_1');

    if ($newGroup == "true") {
        $myGenericPanel = new HACL_GenericPanel($panelid, "Group",$groupname,"",true,false);
    } else {
        $myGenericPanel = new HACL_GenericPanel($panelid, "Group",$groupname,$description,true,false,null,"expand",true);
    }
    $content = <<<HTML

		<div id="content_$panelid" class="panel haloacl_panel_content">
                    <div id="rightTypes_$panelid">
                    <div class="halocal_panel_content_row">
                        <div class="haloacl_panel_content_row_descr">
        $hacl_manageUserGroupPanel_1
                        </div>
                        <div class="haloacl_panel_content_row_content">
HTML;
    // when there is already a name set, we set it and disable changing of the name
    if ($name == "") {
        $content .='<input class="haloacl_manageuser_name" type="text"  id="right_name_'.$panelid.'" value=""/>';
    } else {
        $name = preg_replace("/Group\//is", "", $name);
        $content .='<input type="text" class="haloacl_manageuser_name" id="right_name_'.$panelid.'" value="'.$name.'" disabled="true"/>';
    }
    $discard = wfMsg('hacl_reset_groupsettings');
    $saveGroupsettings = wfMsg('hacl_save_group_settings');

    $content .= <<<HTML
        </div>
            </div>

        <div class="haloacl_greyline">&nbsp;</div>
        <div class="halocal_panel_content_row">
            <div class="haloacl_panel_content_row_descr" style="width:145px">
        $hacl_rightsPanel_3
            </div>
            <div class="haloacl_panel_content_row_content">
                <input type="text" disabled="true" id="right_description_$panelid" value="$description" />
            </div>
        </div>

        </div>

        <div id="right_tabview_$panelid" class="yui-navset"></div>
        <script type="text/javascript">
        
        // resetting previously selected items
        YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'] = new Array();
        YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'] = new Array();
        YAHOO.haloacl.clickedArrayUsersGroups['right_tabview_$panelid'] = new Array();

        YAHOO.haloaclrights.clickedArrayGroups['right_tabview_$panelid'] = new Array();

        YAHOO.haloacl.buildGroupPanelTabView('right_tabview_$panelid', '','' , '', '');
</script>

        <div class="haloacl_greyline">&nbsp;</div>
        <div class="haloacl_two_buttons">
        <div>
        <input type="button" id="haloacl_discardacl_users" value="$discard"
        onclick="javascript:YAHOO.haloacl.discardChanges_users();"/>
        </div>
        <div>
        <input id="haloacl_save_$panelid" type="button" name="safeRight" value="$saveGroupsettings" onclick="YAHOO.haloacl.buildGroupPanelXML_$panelid();" />
        </div>
        </div>
        </div>
        </div>
HTML;



    $myGenericPanel->setContent($content);

    $emptyGroupErr = wfMsg('hacl_popup_invalid_no_group_members');
    
    $footerextension = <<<HTML
    <script type="javascript>

            YAHOO.haloacl.panelcouner++;

            // rightpanel handling

            YAHOO.haloacl.refreshPanel_$panelid = function(){
                ////////saved state
                genericPanelSetSaved_$panelid(false);
                ////////autogenerated description

                if (true) {

                    var description = "";

                    var rightdesc = "";
                    
                    var users = "";
                    var groups = "";
                    for(i=0;i<YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'].length;i++){
                        if (YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'][i] != ""){
                            users = users+", U:"+YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'][i];
                        }
                    }

                    //var groupsarray = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstanceright_tabview_$panelid);
                    for(i=0;i<YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'].length;i++){
                        if (YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'][i] != ""){
                            groups = groups+", G:"+YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'][i];
                        }
                    }

                    if ((users != "") || (groups != "")) {
                        if ((users != "")) description = description+users.substr(2);
                        if ((users != "") && (groups != "")) description = description+", ";
                        if ((groups != "")) description = description+groups.substr(2);
                    }

                    $('right_description_$panelid').value = description;
                    if ($('right_name_$panelid') != null){
                        genericPanelSetName_$panelid("[ "+$('right_name_$panelid').value+" ] - ");
                    }

                    var descrLong = description;
                    if (description.length > 80) description = description.substr(0,80)+"...";
                    genericPanelSetDescr_$panelid(description,descrLong);

                } else {
                    var description = $('right_description_$panelid').value;
                    var descrLong = description;
                    if (description.length > 80) description = description.substr(0,80)+"...";
                    genericPanelSetDescr_$panelid(description,descrLong);
                }

            };

            YAHOO.haloacl.buildGroupPanelXML_$panelid = function(){


                var panelid = '$panelid';
                var groups = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstanceright_tabview_$panelid);
                if (groups.length == 0 
                	&& YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'] == 0) {
					YAHOO.haloacl.notification.createDialogOk("content", "Groups", "$emptyGroupErr", {
									yes: function () {}
								});
                	return;
                }
                	 
                genericPanelSetSaved_$panelid(true);
                if (YAHOO.haloacl.debug) console.log("panelid of grouppanel:"+panelid)

                // building xml
                var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                xml+="<inlineright>";
                xml+="<newgroup>"+escape('$newGroup')+"</newgroup>";
                xml+="<panelid>$panelid</panelid>";
                if ($('right_name_$panelid') != null){
                    xml+="<name>"+escape($('right_name_$panelid').value)+"</name>";
                }

                xml+="<users>";
/*
                $$('.datatableDiv_right_tabview_'+panelid+'_users').each(function(item){
                    if (item.checked){
                        xml+="<user>"+escape(item.name)+"</user>";
                    }
                });
*/
                YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'].each(function(item){
                    xml+="<user>"+escape(item)+"</user>";
                });
                xml+="</users>";

                xml+="<groups>";
                groups.each(function(group){
                    xml+="<group>"+escape(group)+"</group>";
                });
                xml+="</groups>";
                xml+="</inlineright>";


                var callback = function(result){
                    if (result.status == '200'){

                        //parse result
                        //YAHOO.lang.JSON.parse(result.responseText);
                        //genericPanelSetSaved_$panelid(true);
                        //genericPanelSetName_$panelid("[ "+$('right_name_$panelid').value+" ] - ");
                        //genericPanelSetDescr_$panelid(result.responseText);

                        YAHOO.haloacl.closePanel('$panelid');
                        $('manageUserGroupSettingsModificationRight').show();
                        $('manageUserGroupFinishButtons').show();
                        YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsModificationRight','haclGetRightsPanel',{panelid:'manageUserGroupSettingsModificationRight',predefine:'modificationGroup'});

                    } else {
                   YAHOO.haloacl.notification.createDialogOk("content","Something went wrong",result.responseText,{
                        yes:function(){
                            }
                    });
                    }
                };
                if (YAHOO.haloacl.debug) console.log(xml);
                if (YAHOO.haloacl.debug) console.log("sending xml to saveTempGroupToSession");
                YAHOO.haloacl.sendXmlToAction(xml,'haclSaveTempGroupToSession',callback);

            };

            YAHOO.util.Event.addListener("right_name_manageUserGroupSettingsRight","keyup",function(){
                $('haloacl_panel_name_manageUserGroupsettings').innerHTML = "[ Editing Group:"+$('right_name_manageUserGroupSettingsRight').value+" ]";
            });


            resetPanel_$panelid = function(){
                $('filterSelectGroup_$panelid').value = "";
            };

        </script>
HTML;

    // if we have got users or groups, we set them to the specific array
    $footerextension .= "<script>";
    foreach (explode(",",$users) as $item) {
        $footerextension .= "YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'].push('$item');";
    }
    foreach (explode(",",$groups) as $item) {
        $footerextension .= "YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'].push('$item');";
    }

    $footerextension .= "YAHOO.haloacl.clickedArrayUsers['right_tabview_manageUserGroupSettingsModificationRight'] = new Array();";
    $footerextension .= "YAHOO.haloacl.clickedArrayGroups['right_tabview_manageUserGroupSettingsModificationRight'] = new Array();";

    foreach (explode(",",$manageUsers) as $item) {
        $footerextension .= "YAHOO.haloacl.clickedArrayUsers['right_tabview_manageUserGroupSettingsModificationRight'].push('$item');";
    }
    foreach (explode(",",$manageGroups) as $item) {
        $footerextension .= "YAHOO.haloacl.clickedArrayGroups['right_tabview_manageUserGroupSettingsModificationRight'].push('$item');";
    }

    if (sizeof($manageUsers) == 0) {
        $currentUser = $wgUser->getName();
        $footerextension .= "YAHOO.haloacl.clickedArrayUsers['right_tabview_manageUserGroupSettingsModificationRight'].push('$currentUser');";
    }


    if (sizeof($manageUsers) == 0) {
        $footerextension .= "try{genericPanelSetSaved_manageUserGroupSettingsModificationRight('default');}catch(e){}";
        $footerextension .= "try{genericPanelSetDescr_manageUserGroupSettingsModificationRight('Modification rights for U:$currentUser');}catch(e){}";
    } else {
        $footerextension .= "try{YAHOO.haloacl.refreshPanel_manageUserGroupSettingsModificationRight();}catch(e){}";
    }

    if ($newGroup == "false") {
        $footerextension .="try{genericPanelSetSaved_manageUserGroupSettingsModificationRight(true);}catch(e){}";
    } else {
        $footerextension .="try{genericPanelSetSaved_manageUserGroupSettingsModificationRight('defaut');}catch(e){}";
    }

    $footerextension .= "</script>";

    /* autosaving exisiting groups */
    if ($newGroup == "false") {
    // saving modrights
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "<inlineright>";
        $xml .= "<panelid>manageUserGroupSettingsModificationRight</panelid>";
        $xml .= "<name>$name</name>";
        $xml .= "<description>modification rights</description>";
        $xml .= "<users>";

        foreach (explode(",",$manageUsers) as $u) {
            $xml .= "<user>$u</user>";
        }
        $xml .= "</users>";
        $xml .= "<groups>";
        foreach (explode(",",$manageGroups) as $g) {
            $xml .= "<group>$g</group>";
        }
        $xml .= "</groups>";
        $xml .= "</inlineright>";
        haclSaveTempRightToSession($xml);
        // -----

        // saving group
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "<inlineright>";
        $xml .= "<newgroup>false</newgroup>";
        $xml .= "<panelid>manageUserGroupSettingsRight</panelid>";
        $xml .= "<name>$name</name>";
        $xml .= "<description>$description</description>";
        $xml .= "<users>";

        foreach (explode(",",$users) as $u) {
            $xml .= "<user>$u</user>";
        }
        $xml .= "</users>";
        $xml .= "<groups>";
        foreach (explode(",",$groups) as $g) {
            $xml .= "<group>$g</group>";
        }
        $xml .= "</groups>";
        $xml .= "</inlineright>";
        haclSaveTempGroupToSession($xml);
    // -----

    }

    /* ------ */

    if ($newGroup == "true" || $newGroup === true) {
        $footerextension .="<script>genericPanelSetSaved_manageUserGroupsettings(false);</script>";
    } else {
        $footerextension .="<script>genericPanelSetSaved_manageUserGroupsettings(true);</script>";
    }

    // end of processing of old data
    $myGenericPanel->extendFooter($footerextension);

    return $myGenericPanel->getPanel();
}


/**
 *  flexibly used rights panel for creating new / editing existing / viewing existing inline rights
 *
 * @global <User> $wgUser
 * @param <string> $panelid
 * @param <string> predefine type, e.g. privateuse, individual, ...
 * @param <boolean> is panel readonly?
 * @param <boolean> do preloading of existing data
 * @param <string> right-id to preload
 * @param <string> panel's name
 * @param <string> panel's description
 * @return <string>
 */
function haclGetRightsPanel($panelid, $predefine, $readOnly = false, $preload = false, $preloadRightId = 0, $panelName = "Right", $rightDescription = "",$showSaved=false) {
    $updatedFromOutside=false;
    if ($predefine == "modificationGroup") {
        $updatedFromOutside=true;
        $predefine = "modification";
    }

    if ($preload == "false" && $preload !== true) {
        $preload = false;
    }
    if ($preload == "true") {
        $preload = true;
    }



    /*  define for part */
    $hacl_createGeneralContent_9 = wfMsg('hacl_createGeneralContent_9');
    $hacl_createGeneralContent_10 = wfMsg('hacl_createGeneralContent_10');
    $hacl_createGeneralContent_11 = wfMsg('hacl_createGeneralContent_11');
    $hacl_createGeneralContent_12 = wfMsg('hacl_createGeneralContent_12');
    $hacl_createGeneralContent_13 = wfMsg('hacl_createGeneralContent_13');
    $hacl_createGeneralContent_14 = wfMsg('hacl_createGeneralContent_14');
    /* ---- */

    $hacl_rightsPanel_1 = wfMsg('hacl_rightsPanel_1');
    $hacl_rightsPanel_2 = wfMsg('hacl_rightsPanel_2');
    $hacl_rightsPanel_3 = wfMsg('hacl_rightsPanel_3');
    $hacl_rightsPanel_4 = wfMsg('hacl_rightsPanel_4');
    $hacl_rightsPanel_5 = wfMsg('hacl_rightsPanel_5');
    $hacl_rightsPanel_6 = wfMsg('hacl_rightsPanel_6');
    $hacl_rightsPanel_7 = wfMsg('hacl_rightsPanel_7');
    $hacl_rightsPanel_8 = wfMsg('hacl_rightsPanel_8');
    $hacl_rightsPanel_9 = wfMsg('hacl_rightsPanel_9');
    $hacl_rightsPanel_10 = wfMsg('hacl_rightsPanel_10');
    $hacl_rightsPanel_11 = wfMsg('hacl_rightsPanel_11');
    $hacl_rightsPanel_12 = wfMsg('hacl_rightsPanel_12');
    $hacl_rightsPanel_13 = wfMsg('hacl_rightsPanel_13');


    $hacl_rightsPanel_allUsersRegistered = wfMsg('hacl_rightsPanel_allUsersRegistered');
    $hacl_rightsPanel_allAnonymousUsers = wfMsg('hacl_rightsPanel_allAnonymousUsers');
    $hacl_rightsPanel_allUsers = wfMsg('hacl_rightsPanel_allUsers');

    $hacl_rightsPanel_right_fullaccess = wfMsg('hacl_rightsPanel_right_fullaccess');
    $hacl_rightsPanel_right_read = wfMsg('hacl_rightsPanel_right_read');
    $hacl_rightsPanel_right_edit = wfMsg('hacl_rightsPanel_right_edit');
    $hacl_rightsPanel_right_editfromform = wfMsg('hacl_rightsPanel_right_editfromform');
    $hacl_rightsPanel_right_WYSIWYG = wfMsg('hacl_rightsPanel_right_WYSIWYG');
    $hacl_rightsPanel_right_create = wfMsg('hacl_rightsPanel_right_create');
    $hacl_rightsPanel_right_move = wfMsg('hacl_rightsPanel_right_move');
    $hacl_rightsPanel_right_delete = wfMsg('hacl_rightsPanel_right_delete');
    $hacl_rightsPanel_right_annotate = wfMsg('hacl_rightsPanel_right_annotate');

    global $wgUser;
    $currentUser = $wgUser->getName();

    if ($readOnly === "true") $readOnly = true;
    if ($readOnly === "false") $readOnly = false;

    if ($predefine !== "modification" && $preload) {
	    $right = HACLRight::newFromID($preloadRightId);
	    if ($right->hasDynamicAssignees()) {
	    	$readOnly = true;
	    }
    }
    
    if ($readOnly) {
        $expandMode = "expand";
    } else {
        $expandMode = "expand";
    }

    if ($predefine == "modification") {
        $myGenericPanel = new HACL_GenericPanel($panelid, "Right", $panelName, $rightDescription, !$readOnly, false, "Default", $expandMode,$showSaved);
    } else {
        $myGenericPanel = new HACL_GenericPanel($panelid, "Right", $panelName, $rightDescription, !$readOnly, !$readOnly, null, $expandMode,$showSaved);
    }
    if (($readOnly === true) or ($readOnly == "true")) $disabled = "disabled"; else $disabled = "";

    if ($readOnly) $ro = "ja"; else $ro = "nein";

    $content = <<<HTML

		<div id="content_$panelid" class="yui-skin-sam panel haloacl_panel_content">
                    <div id="rightTypes_$panelid">
HTML;

    if (!$readOnly) {
        $content .= <<<HTML
                        <div class="halocal_panel_content_row">
                            <div class="haloacl_panel_content_row_descr">
            $hacl_rightsPanel_1
                            </div>
                            <div class="haloacl_panel_content_row_content">
                                <input type="text" id="right_name_$panelid" class="haloacl_right_name" value="$panelName" $disabled />
                            </div>
                        </div>
HTML;
    }
    $content .= <<<HTML
                        <div class="halocal_panel_content_row">
                            <div class="haloacl_panel_content_row_descr">
        $hacl_rightsPanel_2
                            </div>
                            <div class="haloacl_panel_rights">

                                <div class="right_fullaccess"><input id = "checkbox_right_fullaccess_$panelid" actioncode="255" type="checkbox" class="right_rights_$panelid" name="fullaccess" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_fullaccess</div>
                                <div class="right_read"><input id = "checkbox_right_read_$panelid" type="checkbox" actioncode="128" actioncode="128" class="right_rights_$panelid" name="read" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_read</div>
                                <div class="right_edit"><input id = "checkbox_right_edit_$panelid" type="checkbox" actioncode="8" class="right_rights_$panelid" name="edit" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_edit</div>
                                <div class="right_formedit"><input id = "checkbox_right_formedit_$panelid" actioncode="64" type="checkbox" class="right_rights_$panelid" name="formedit" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_editfromform</div>
                                <div class="right_wysiwyg"><input id = "checkbox_right_wysiwyg_$panelid" type="checkbox" actioncode="32" class="right_rights_$panelid" name="wysiwyg" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_WYSIWYG</div>
                                <div class="right_create"><input id = "checkbox_right_create_$panelid" type="checkbox" actioncode="4" class="right_rights_$panelid" name="create" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_create</div>
                                <div class="right_move"><input id = "checkbox_right_move_$panelid" type="checkbox" actioncode="2" class="right_rights_$panelid" name="move" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_move</div>
                                <div class="right_delete"><input id = "checkbox_right_delete_$panelid" type="checkbox" actioncode="1" class="right_rights_$panelid" name="delete" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_delete</div>
                                <div class="right_annotate"><input id = "checkbox_right_annotate_$panelid" type="checkbox" actioncode="16" class="right_rights_$panelid" name="annotate" onClick="updateRights$panelid(this)" $disabled/>&nbsp;$hacl_rightsPanel_right_annotate</div>

                            </div>
                        </div>
                        <script type="javascript>
HTML;

    // ----------------------------
    // --- preloading right to array ---
    if ($preload) {
        if ($predefine <> "modification") {
            $right = HACLRight::newFromID($preloadRightId);
            $users = $right->getUsers(true);
            #print_r($users);
            $groups = $right->getGroups(true);
        } else {
            $SD = HACLSecurityDescriptor::newFromID($preloadRightId);
            $users = $SD->getManageUsers();
            $groups = $SD->getManageGroups();
        }
        // preload users
        $forAnon = false;
        $forReg = false;
        foreach ($users as $user) {
            if ($user == "0") {
                $forAnon = true;
            } elseif ($user == "-1") {
                $forReg = true;
            } else {
                $hUser = User::newFromId($user)->getName();
                $content .= <<<HTML
                    YAHOO.haloacl.addUserToUserArray('right_tabview_$panelid', '$hUser');
HTML;
            }
        }
        // if something down here is true, we have to change the predefine
        if ($forAnon && $forReg) {
            $predefine = "allusers";
        } elseif ($forReg) {
            $predefine = "allusersregistered";
        } elseif ($forAnon) {
            $predefine = "allusersanonymous";
        }

        // preload usgroupsers
        foreach ($groups as $group) {
            try {
                $hGroup = HACLGroup::newFromId($group);
                $hGroup = haclRemoveGroupPrefix($hGroup->getGroupName());
            } catch (HACLGroupException $e) {
            // The group may no longer exist
                if ($e->getCode() == HACLGroupException::INVALID_GROUP_ID) {
                    continue;
                }
            }
            $content .= <<<HTML
                    YAHOO.haloacl.addGroupToGroupArray('right_tabview_$panelid', '$hGroup');
HTML;
        }
    } else {
        $content .="
        YAHOO.haloacl.addUserToUserArray('right_tabview_$panelid', '$currentUser')";
    }
    // --------------------------------

    $content .= <<<HTML
        // tickbox handling
        updateRights$panelid = function(element) {
            //console.log(element);

            var name = $(element).readAttribute("name");
            //element = $(element.id);
            //console.log(element);

                var includedrights = "";
                if (name == "fullaccess"){
                    includedrights = ",create,read,edit,annotate,wysiwyg,formedit,delete,move";
                } else if (name == "read"){
                    includedrights = "";
                } else if (name == "formedit"){
                    includedrights = ",read";
                } else if (name == "annotate"){
                    includedrights = ",read";
                } else if (name == "wysiwyg"){
                    includedrights = "read";
                } else if (name == "edit"){
                    includedrights = ",read,formedit,annotate,wysiwyg";
                } else if (name == "create"){
                    includedrights = ",read,edit,formedit,annotate,wysiwyg";
                } else if (name == "move"){
                    includedrights = ",read,edit,formedit,annotate,wysiwyg";
                } else if (name == "delete"){
                    includedrights = ",read,edit,formedit,annotate,wysiwyg";
                }

                var excluderight = "";
                if (name == "fullaccess"){
                    excluderight = ",fullaccess,create,read,edit,annotate,wysiwyg,formedit,delete,move";
                } else if (name == "read"){
                    excluderight = ",fullaccess,create,read,edit,annotate,wysiwyg,formedit,delete,move";
                } else if (name == "formedit"){
                    excluderight = ",fullaccess,create,edit,formedit,delete,move";
                } else if (name == "annotate"){
                    excluderight = ",fullaccess,create,edit,annotate,delete,move";
                } else if (name == "wysiwyg"){
                excluderight = ",fullaccess,create,edit,wysiwyg,delete,move";
                } else if (name == "edit"){
                    excluderight = ",fullaccess,edit,create,delete,move";
                } else if (name == "create"){
                    excluderight = ",fullaccess,create";
                } else if (name == "move"){
                    excluderight = ",fullaccess,move";
                } else if (name == "delete"){
                    excluderight = ",fullaccess,delete";
                }
            if (element.checked){

                $$('.right_rights_$panelid').each(function(item){
                    if (includedrights.indexOf(','+$(item).readAttribute("name")) >= 0){
                        item.checked = true;
                    }
                });
                
            } else {
                // remove all lower rights when this right is removed
                $$('.right_rights_$panelid').each(function(item){
                if (excluderight.indexOf(','+$(item).readAttribute("name")) >= 0){
                        item.checked = false;
                    }
                });
            }
        }
    </script>
</div>
HTML;

    /*
     * starting NOT readonly part of rightpanel,
     * so this part will be only displayed if this panel
     * is not for information-only purpose
     */
    if (!$readOnly) {
        $content .= <<<HTML
                    <div class="haloacl_greyline">&nbsp;</div>
                    <div class="halocal_panel_content_row">
                        <div class="haloacl_panel_content_row_descr" style="width:145px">
            $hacl_rightsPanel_3
                        </div>
                        <div class="haloacl_panel_content_row_content">
                            <input type="text" disabled="true" id="right_description_$panelid" value="$rightDescription" />
                        </div>
                    </div>
                    <div class="halocal_panel_content_row">
                        <div class="haloacl_panel_content_row_descr" style="width:145px">
                            &nbsp;
                        </div>
                        <div class="haloacl_panel_content_row_content">
            $hacl_rightsPanel_4
                            <input id="right_autodescron_$panelid" type="radio" value="on" name="right_descriptiontext_$panelid" class="right_descriptiontext_$panelid" checked $disabled />&nbsp;$hacl_rightsPanel_5
                            <input id="right_autodescroff_$panelid" type="radio" value="off" name="right_descriptiontext_$panelid" class="right_descriptiontext_$panelid" $disabled />&nbsp;$hacl_rightsPanel_6
                        </div>
                    </div>
                    <script>
                        YAHOO.haloacl.right_descriptiontext_$panelid = function(element){

                            var autoGenerate;
                            $$('.right_descriptiontext_$panelid').each(function(item){
                                if (item.checked){
                                    autoGenerate = item.value;
                                }
                            });

                            if (autoGenerate == "on") {
                               $('right_description_$panelid').disabled = true;
                               YAHOO.haloacl.refreshPanel_$panelid();
                            } else {
                               $('right_description_$panelid').disabled = false;
                               $('right_description_$panelid').value = "";
                            }
                        }

                        YAHOO.util.Event.addListener("right_autodescron_$panelid", "click", YAHOO.haloacl.right_descriptiontext_$panelid);
                        YAHOO.util.Event.addListener("right_autodescroff_$panelid", "click", YAHOO.haloacl.right_descriptiontext_$panelid);

                    </script>
HTML;
    }


    if ($predefine != "modification") {
        $content .= <<<HTML
        <div class="haloacl_greyline">&nbsp;</div>

        <!-- define for start -->
           <div style="width:800px!important" class="halocal_panel_content_row">
                <div class="haloacl_panel_content_row_descr">
            $hacl_createGeneralContent_9
                </div>
                <form>
                <div class="haloacl_panel_content_row_content">
                    <div class="haloacl_panel_define_element">
                        <input type="radio" class="create_acl_general_definefor create_acl_general_definefor_$panelid" name="create_acl_general_definefor" value="privateuse" />&nbsp;$hacl_createGeneralContent_10
                    </div>
                    <div style="width:400px!important" class="haloacl_panel_define_element">
                        <input type="radio" class="create_acl_general_definefor create_acl_general_definefor_$panelid" name="create_acl_general_definefor" value="individual" />&nbsp;$hacl_createGeneralContent_11
                    </div>
                </div>
            <div class="haloacl_panel_content_row_content"  style="padding-left:80px;clear:left">
                <div class="haloacl_panel_define_element">
                    <input type="radio" class="create_acl_general_definefor create_acl_general_definefor_$panelid" name="create_acl_general_definefor" value="allusers" />&nbsp;$hacl_createGeneralContent_12
                </div>
                <div style="width:170px" class="haloacl_panel_define_element">
                    <input type="radio" class="create_acl_general_definefor create_acl_general_definefor_$panelid" name="create_acl_general_definefor" value="allusersregistered" />&nbsp;$hacl_createGeneralContent_13
                </div>
                <div style="width:170px" class="haloacl_panel_define_element">
                    <input type="radio" class="create_acl_general_definefor create_acl_general_definefor_$panelid" name="create_acl_general_definefor" value="allusersanonymous" />&nbsp;$hacl_createGeneralContent_14
                </div>
            </div>
          </div>
          </form>
        <!-- define for end -->
HTML;
    }

    $content .= <<<HTML

            <div class="haloacl_greyline">&nbsp;</div>

        <!-- tabview container -->
            <div id="haloacl_inline_notification_$panelid" class="haloacl_inline_notification">&nbsp;</div>
            <div id="right_tabview_$panelid" class="yui-navset"></div>
        <!-- tabview container end -->



        <script>
	
	        YAHOO.haloaclrights.clickedArrayGroups['right_tabview_$panelid'] = new Array();
	        
            /* define-for-javascript-handling */
            YAHOO.haloacl.panelDefinePanel_$panelid = "";
   

            function loadUserTabIfNeeded(){
                if (YAHOO.haloacl.panelDefinePanel_$panelid == 'modification' ||YAHOO.haloacl.panelDefinePanel_$panelid == 'individual'){
                    $('right_tabview_$panelid').innerHTML = "";
                    YAHOO.haloacl.buildRightPanelTabView('right_tabview_$panelid',YAHOO.haloacl.panelDefinePanel_$panelid , '$readOnly', '$preload', '$preloadRightId');
                } else {
                    $('right_tabview_$panelid').innerHTML = "";
                }
            }

            function init(){
                //console.log("running rightspanel init method for predfined: $predefine");
                YAHOO.haloacl.panelDefinePanel_$panelid = '$predefine';
                $$('.create_acl_general_definefor_$panelid').each(function(item){
                    if (YAHOO.haloacl.panelDefinePanel_$panelid == item.value){
                        item.checked = true;
                    }
                });
                loadUserTabIfNeeded();
            }

            init();

            YAHOO.haloacl.reset_modification_$panelid = function(){
               $('right_tabview_$panelid').innerHTML = "";
               YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'] = new Array();
               YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'] = new Array();
               YAHOO.haloacl.clickedArrayUsersGroups['right_tabview_$panelid'] = new Array();

               YAHOO.haloacl.buildRightPanelTabView('right_tabview_$panelid',YAHOO.haloacl.panelDefinePanel_$panelid , '$readOnly', '$preload', '$preloadRightId');

            };

            YAHOO.haloacl.defineForChange_$panelid = function(){

                // on change reset selected users
                if (YAHOO.haloacl.debug){console.log("clearing arrays - called from define for change - YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid']");};
                YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'] = new Array();
                YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'] = new Array();
                YAHOO.haloacl.clickedArrayUsersGroups['right_tabview_$panelid'] = new Array();

                var element = null;
                $$('.create_acl_general_definefor_$panelid').each(function(item){
                    if (item.checked){
                        element = item;
                    }
                });
                if (element == null){alert("failure");}
                YAHOO.haloacl.panelDefinePanel_$panelid = element.value;
                YAHOO.haloacl.refreshPanel_$panelid();
                loadUserTabIfNeeded();
            };

            YAHOO.util.Event.addListener($$('.create_acl_general_definefor_$panelid'), "click", function(item){YAHOO.haloacl.defineForChange_$panelid();});
        </script>

HTML;
    //    }

    if ($readOnly) {
        $content .= <<<HTML
        <script>
            $$(".create_acl_general_definefor_$panelid").each(function(item){
                if (!item.checked){
                    item.disabled = true;
                }
            });
        </script>
HTML;

    }



    if (!$readOnly) {
        $content .= <<<HTML
            <br/>
HTML;

        $tt_deleteright = wfMsg('hacl_tooltip_clickto_delete_right');
        $tt_resetright = wfMsg('hacl_tooltip_clickto_reset_right');
        $tt_saveright = wfMsg('hacl_tooltip_clickto_save_right');
        $tt_savemodright = wfMsg('hacl_tooltip_clickto_save_modright');

        if ($predefine != "modification") {
            $content .= <<<HTML
                     <div class="haloacl_three_buttons">
                        <form>
                        <div><input type="button" id="haloacl_delete_$panelid" value="$hacl_rightsPanel_8" onclick="javascript:YAHOO.haloacl.removePanel('$panelid');" /></div>
                        <div><input id="haloacl_reset_$panelid" type="button" value="$hacl_rightsPanel_9" onclick="javascript:YAHOO.haloacl.removePanel('$panelid',function(){YAHOO.haloacl.createacl_addRightPanel('$predefine');});" /></div>
                        <div><input id="haloacl_save_$panelid" type="button" name="saveRightStd" value="$hacl_rightsPanel_10" onclick="YAHOO.haloacl.buildRightPanelXML_$panelid();" /></div>
                        </form>
                        <script>
                            YAHOO.haloacl.addTooltip("tooltip_delete_$panelid", "haloacl_delete_$panelid", "$tt_deleteright");
                            YAHOO.haloacl.addTooltip("tooltip_reset_$panelid", "haloacl_reset_$panelid", "$tt_resetright");
                            YAHOO.haloacl.addTooltip("tooltip_save_$panelid", "haloacl_save_$panelid", "$tt_saveright");
                        </script>
HTML;
        } else {
            $content .= <<<HTML
                     <div class="haloacl_two_buttons">
                        <div><input id="haloacl_reset_$panelid" type="button" value="$hacl_rightsPanel_9" onclick="javascript:YAHOO.haloacl.reset_modification_$panelid();" /></div>
                        <div>&nbsp;<input id="haloacl_save_$panelid" type="button" name="safeRight" value="$hacl_rightsPanel_10" onclick="YAHOO.haloacl.buildRightPanelXML_$panelid();" /></div>
                        <script>
                        YAHOO.haloacl.addTooltip("tooltip_save_$panelid", "haloacl_save_$panelid", "$tt_savemodright");
                        </script>
HTML;
        }
        $content .= <<<HTML
            </div>
HTML;
    }
    $content .= <<<HTML
        </div>
HTML;

    $myGenericPanel->setContent($content);
    $currentUserName = "  ".$wgUser->getName();

    $footerextension = <<<HTML
    <script type="javascript>

            // recreates save status and autogenerated fields
            // to be called whenever an element in right panel changes
            YAHOO.haloacl.refreshPanel_$panelid = function(){

                ////////saved state
                genericPanelSetSaved_$panelid(false);

                ////////autogenerated description
                var autoGenerate;
                $$('.right_descriptiontext_$panelid').each(function(item){
                    if (item.checked){
                        autoGenerate = item.value;
                    }
                });

                if (autoGenerate == "on") {

                    var description = "";

                    var rightdesc = "";
                    var isFullaccess = false;;
                    $$('.right_rights_$panelid').each(function(item){
                        if (item.checked && item.name != "fullaccess"){
                            if ((rightdesc) != "") rightdesc = rightdesc+", ";
                            rightdesc = rightdesc+item.name;
                        }
                        if (item.checked && item.name == "fullaccess"){
                            isFullaccess = true;
                        }
                    });
                    if (isFullaccess){
                        description = "fullaccess";
                    } else {
                        description = rightdesc;
                    }

                    var users = "";
                    var groups = "";

                    switch (YAHOO.haloacl.panelDefinePanel_$panelid) {
                        case "privateuse":
                        case "private":
                                users = "$currentUserName";


                            //var groupsarray = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstanceright_tabview_$panelid);
                            for(i=0;i<YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'].length;i++){
                                if (YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'][i] != ""){
                                    groups = groups+", G:"+YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'][i];
                                }
                            }
                            /*
                            groupsarray.each(function(group){
                                groups = groups+", G:"+group;
                            });
                            */
                            break;
                        case "modification":
                            //description = "Modification rights";

                        case "individual":
                            for(i=0;i<YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'].length;i++){
                                if (YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'][i] != ""){
                                    users = users+", U:"+YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'][i];
                                }
                            }
                            
                            //var groupsarray = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstanceright_tabview_$panelid);
                            for(i=0;i<YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'].length;i++){
                                if (YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'][i] != ""){
                                    groups = groups+", G:"+YAHOO.haloacl.clickedArrayGroups['right_tabview_$panelid'][i];
                                }
                            }
                            /*
                            groupsarray.each(function(group){
                                groups = groups+", G:"+group;
                            });
                            */
                            break;
                        case "allusersregistered":
                            users = "  $hacl_rightsPanel_allUsersRegistered";
                            break;
                        case "allusersanonymous":
                            users = "  $hacl_rightsPanel_allAnonymousUsers";
                            break;
                        case "allusers":
                            users = "  $hacl_rightsPanel_allUsers";
                            break;
                    }

                    if ((users != "") || (groups != "")) {
                        description = description+" for ";
                        if ((users != "")) description = description+users.substr(2);
                        if ((users != "") && (groups != "")) description = description+", ";
                        if ((groups != "")) description = description+groups.substr(2);
                    }

                    $('right_description_$panelid').value = description;
                    if (YAHOO.haloacl.panelDefinePanel_$panelid == 'modification'){
                        genericPanelSetName_$panelid("[ Modification Rights ] - ");
                    } else if ($('right_name_$panelid') != null){
                        genericPanelSetName_$panelid("[ "+$('right_name_$panelid').value+" ] - ");
                    }

                    var descrLong = description;
                    if (description.length > 80) description = description.substr(0,80)+"...";
                    genericPanelSetDescr_$panelid(description,descrLong);

                } else {
                    var description = $('right_description_$panelid').value;
                    var descrLong = description;
                    if (description.length > 80) description = description.substr(0,80)+"...";
                    genericPanelSetDescr_$panelid(description,descrLong);    

                }

            };

            // rightpanel handling
            YAHOO.haloacl.buildRightPanelXML_$panelid = function(onlyReturnXML){
                var panelid = '$panelid';

                if (YAHOO.haloacl.panelDefinePanel_$panelid == "individual" || YAHOO.haloacl.panelDefinePanel_$panelid == "modification"){
                    var somedatawasentered = false;
                } else {
                    var somedatawasentered = true;
                }

                if (YAHOO.haloacl.panelDefinePanel_$panelid == "modification"){
                    var rightswereset = true;
                } else {
                    var rightswereset = false;
                }

                var currentUserIncludedInRight = false;

                // building xml
                var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                xml+="<inlineright>";
                xml+="<panelid>$panelid</panelid>";
                xml+="<type>"+YAHOO.haloacl.panelDefinePanel_$panelid+"</type>";
                if ($('right_name_$panelid') != null){
                   // xml+="<name>"+escape($('right_name_$panelid').value)+"</name>";
                    xml+="<name>"+escape($('right_name_$panelid').value)+"</name>";
                }
                if ($('right_description_$panelid') != null){
                    xml+="<description>"+escape($('right_description_$panelid').value)+"</description>";
                }
                $$('.create_acl_general_protect').each(function(item){
                    if (item.checked){
                        xml+="<protect>"+escape(item.value)+"</protect>";
                    }
                });

                xml+="<rights>";
                $$('.right_rights_$panelid').each(function(item){
                    if (item.checked){
                        rightswereset = true;
                        xml+="<right>"+escape(item.name)+"</right>";
                    }
                });
                xml+="</rights>";


                 if (YAHOO.haloacl.panelDefinePanel_$panelid == "individual"  || YAHOO.haloacl.panelDefinePanel_$panelid == "modification"){

                    var groups = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstanceright_tabview_$panelid);

                    xml+="<users>";
/*
                    $$('.datatableDiv_right_tabview_'+panelid+'_users').each(function(item){
                        if (item.checked){
                            somedatawasentered= true;
                            xml+="<user>"+item.name+"</user>";

                            if (item.name == '$currentUser'){
                                currentUserIncludedInRight = true;
                            }
                        }
                    });

*/
                    var usersarray = YAHOO.haloacl.clickedArrayUsers['right_tabview_$panelid'];
                    if (usersarray != null){
                        usersarray.each(function(element){
                            somedatawasentered= true;
                            xml+="<user>"+escape(element)+"</user>";
                            if (element == "$currentUser"){
                                currentUserIncludedInRight = true;
                            }
                        });
                    }
                    xml+="</users>";


                    xml+="<groups>";
                    groups.each(function(group){
                        somedatawasentered= true;
                        xml+="<group>"+escape(group)+"</group>";
                    });
                    xml+="</groups>";
                }

        
                xml+="</inlineright>";

                if (onlyReturnXML == true){
                    return xml;
                } else {
                    if (somedatawasentered == false || rightswereset == false){
                        YAHOO.haloacl.notification.createDialogOk("content","Something went wrong","You can't create an empty right. Please select an user or a group and actions.",{
                            yes:function(){
                                }
                        });
                    } else {
                        if ('$predefine' == 'modification' && currentUserIncludedInRight == false){
                            YAHOO.haloacl.notification.createDialogYesNo("content","Warning","You are not included in that right.",{
                                yes:function(){

                                    var callback = function(result){
                                        if (result.status == '200'){
                                            //parse result
                                            //YAHOO.lang.JSON.parse(result.responseText);
                                            genericPanelSetSaved_$panelid(true);
                                            YAHOO.haloacl.closePanel('$panelid');
                                            if ($('step3') != null){
                                                $('step3').show();
                                            }
                                        } else {
                                            alert(result.responseText);
                                        }
                                    };
                                    YAHOO.haloacl.sendXmlToAction(xml,'haclSaveTempRightToSession',callback);
                                },
                                no:function(){}
                            },"Ok","Cancel");
                        } else {
                            var callback = function(result){
                                if (result.status == '200'){
                                    //parse result
                                    //YAHOO.lang.JSON.parse(result.responseText);
                                    genericPanelSetSaved_$panelid(true);
                                    YAHOO.haloacl.closePanel('$panelid');
                                    if ($('step3') != null){
                                        $('step3').show();
                                    }
                                } else {
                                    alert(result.responseText);
                                }
                            };
                            YAHOO.haloacl.sendXmlToAction(xml,'haclSaveTempRightToSession',callback);
                        }
                   }
                }
            };

            $$('.right_rights_$panelid').each(function(item){
                YAHOO.util.Event.addListener(item, "click", YAHOO.haloacl.refreshPanel_$panelid);

            });

            YAHOO.util.Event.addListener("right_name_$panelid", "click", YAHOO.haloacl.refreshPanel_$panelid);
            YAHOO.util.Event.addListener("right_description_$panelid", "keyup", YAHOO.haloacl.refreshPanel_$panelid);



            checkAll_$panelid = function () {
                $('checkbox_right_read').checked = false;
                genericPanelSetSaved_$panelid(false);
            };

            resetPanel_$panelid = function(){
                $('filterSelectGroup_$panelid').value = "";
            };


        </script>
HTML;


    /*
     * manageUsers loads this panel once and then modifies its content via js !!!
     */
    if ($predefine == "modification" && !$updatedFromOutside) {
        $footerextension .= <<<HTML
        <script>
            genericPanelSetDescr_$panelid("for U: $currentUser","for U: $currentUser");
        </script>
HTML;

        $footerextension .= <<<HTML
    <script>
        try{
        //YAHOO.haloacl.refreshPanel_$panelid();
        }catch(e){}
    </script>
HTML;
    }




    if ($preload == true) {

    // preload rights
        if ($predefine <> "modification") {
            $right = HACLRight::newFromID($preloadRightId);
            $actions = $right->getActions();

            $footerextension .= <<<HTML
                <script type="javascript>
HTML;
            if ($actions & HACLRight::EDIT)  $footerextension .= "$('checkbox_right_edit_$panelid').checked = true;";
            if ($actions & HACLRight::CREATE)  $footerextension .= "$('checkbox_right_create_$panelid').checked = true;";
            if ($actions & HACLRight::MOVE)  $footerextension .= "$('checkbox_right_move_$panelid').checked = true;";
            if ($actions & HACLRight::DELETE)  $footerextension .= "$('checkbox_right_delete_$panelid').checked = true;";
            if ($actions & HACLRight::READ)  $footerextension .= "$('checkbox_right_read_$panelid').checked = true;";
            if ($actions & HACLRight::FORMEDIT)  $footerextension .= "$('checkbox_right_formedit_$panelid').checked = true;";
            if ($actions & HACLRight::ANNOTATE)  $footerextension .= "$('checkbox_right_annotate_$panelid').checked = true;";
            if ($actions & HACLRight::WYSIWYG)  $footerextension .= "$('checkbox_right_wysiwyg_$panelid').checked = true;";
            if ($actions & HACLRight::EDIT && $actions & HACLRight::CREATE && $actions & HACLRight::MOVE && $actions & HACLRight::DELETE && $actions & HACLRight::READ && $actions & HACLRight::FORMEDIT && $actions & HACLRight::ANNOTATE && $actions & HACLRight::WYSIWYG) $footerextension .= "$('checkbox_right_fullaccess_$panelid').checked = true;";
            $footerextension .= "</script>";

        } else {
            $footerextension .= <<<HTML
            <script>
                var description="Modification rights for U:$currentUser";
                genericPanelSetDescr_$panelid(description,description);
            </script>
HTML;
        }



        $footerextension .= <<<HTML
        <script type="javascript>
            YAHOO.haloacl.closePanel('$panelid');

            // preload = true ==> already save this right in session.
            //YAHOO.haloacl.buildRightPanelXML_$panelid();

        </script>
HTML;

    }

    switch ($predefine) {

        case "modification":
            $footerextension .= <<<HTML
            <script type="javascript>
                $('rightTypes_$panelid').style.display = 'none';
                if ($('rigth_name_$panelid') != null){
                    $('right_name_$panelid').value ="$hacl_rightsPanel_11";
                }
                if ($('right_description_$panelid') != null){
                    $('right_description_$panelid').value ="$hacl_rightsPanel_12";
                }
                //$('close-button_$panelid').style.display = 'none';
                genericPanelSetName_$panelid('$hacl_rightsPanel_11');
                YAHOO.haloacl.closePanel('$panelid');
            </script>
HTML;
            break;
        case "individual":break;
        case "all":break;
        case "private":
            $footerextension .= <<<HTML
            <script type="javascript>
                //$('rightTypes_$panelid').style.display = 'none';
                genericPanelSetName_$panelid("$hacl_rightsPanel_13 $currentUser");
                //YAHOO.haloacl.buildRightPanelXML_$panelid();
            </script>
HTML;
            break;
    }

    $myGenericPanel->extendFooter($footerextension);

    return $myGenericPanel->getPanel();
}


/**
 *
 * @param <String>  panelid of parents-div to have an unique identifier for each modification-right-panel
 * @return <string>   modification-right-panel html
 */
function haclGetModificationRightsPanel($panelid) {

    $html = <<<HTML
	<!-- start of panel div-->
	<div id="modificationRights" >
		
	</div> <!-- end of panel div -->
        <script type="javascript>
            YAHOO.haloacl.loadContentToDiv('modificationRights','haclGetRightsPanel',{panelid:'$panelid', predefine:'modification', readOnly:false, preload:false, preloadRightId:0});
        </script>
HTML;

    return $html;
}

/**
 * select-deselect user/group editor
 *
 * @global <User> $wgUser
 * @param <string> $panelid
 * @param <string> predfine-type
 * @param <boolean> is readonly?
 * @param <boolean> preload right?
 * @param <string> right-id to preload
 * @param <string> $context 
 * 		The context of the tab: "RightPanel" or "GroupPanel"
 * @return <string> select-deselect tab-content
 */
function haclRightPanelSelectDeselectTab($panelid, $predefine, $readOnly, $preload, $preloadRightId, $context) {
    if ($preload == "false") {
        $preload=false;
    };

    $hacl_rightPanelSelectDeselectTab_1 = wfMsg('hacl_rightPanelSelectDeselectTab_1');
    $hacl_rightPanelSelectDeselectTab_2 = wfMsg('hacl_rightPanelSelectDeselectTab_2');
    $hacl_rightPanelSelectDeselectTab_3 = wfMsg('hacl_rightPanelSelectDeselectTab_3');
    $hacl_rightPanelSelectDeselectTab_4 = wfMsg('hacl_rightPanelSelectDeselectTab_4');
    $hacl_rightPanelSelectDeselectTab_5 = wfMsg('hacl_rightPanelSelectDeselectTab_5');
    $select_text = wfMsg('hacl_select');

    global $wgUser;
    $currentUser = $wgUser->getName();

    $parentsPanelid = substr($panelid, 14);
    $select_text = wfMsg('hacl_select');
    $html = <<<HTML
        <!-- leftpart -->
        <div class="haloacl_rightpanel_selecttab_container">
            <div class="haloacl_rightpanel_selecttab_leftpart">
                <div class="haloacl_rightpanel_selecttab_leftpart_filter">
                    <span class="haloacl_rightpanel_selecttab_leftpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_1
                    </span>
                    <span>$select_text</span>
                </div>
                <div class="haloacl_rightpanel_selecttab_leftpart_filter">
                    <span class="haloacl_rightpanel_selecttab_leftpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_2
                    </span>
                    <input id="filterSelectGroup_$panelid" class="haloacl_filter_input" type="text" />
                </div>
                <div id="treeDiv_$panelid" class="haloacl_rightpanel_selecttab_leftpart_treeview">&nbsp;</div>
                <div class="haloacl_rightpanel_selecttab_leftpart_treeview_userlink">
                    <a class="highlighted datatable_user_link" onClick="YAHOO.haloacl.removeHighlighting();$(this).addClassName('highlighted');$('datatablepaging_groupinfo_$panelid').innerHTML='<span style=\'font-weight:normal\'>in</span>&nbsp;Users';" href="javascript:YAHOO.haloacl.datatableInstance$panelid.executeQuery('all');">$hacl_rightPanelSelectDeselectTab_3</a>
                </div>
            </div>

            <!-- end of left part -->


            <!-- starting right part -->

            <div class="haloacl_rightpanel_selecttab_rightpart">
                <div class="haloacl_rightpanel_selecttab_rightpart_filter">

                    <span class="haloacl_rightpanel_selecttab_rightpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_4:&nbsp;<span id="datatablepaging_count_$panelid"></span> <span id="datatablepaging_groupinfo_$panelid"><span style="font-weight:normal">in</span>&nbsp;Users</span>
                    </span>
                    <span>$select_text</span>

                </div>
                <div class="haloacl_rightpanel_selecttab_rightpart_filter">
                    <span class="haloacl_rightpanel_selecttab_rightpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_5
                    </span>
                    <input id="datatable_filter_$panelid" class="haloacl_filter_input" type="text" />

                </div>
                <div id="datatableDiv_$panelid" class="haloacl_rightpanel_selecttab_rightpart_datatable">&nbsp;</div>
                <div id="datatablepaging_datatableDiv_$panelid"></div>
                </div>
            </div>

            <!-- end of right part -->

        </div>
    <script type="text/javascript">
        YAHOO.haloacl.notification.clearAllNotification();

 

        // user list on the right

        YAHOO.haloacl.datatableInstance$panelid = YAHOO.haloacl.userDataTable("datatableDiv_$panelid","$panelid");


        YAHOO.haloacl.treeInstance$panelid = YAHOO.haloacl.getNewTreeview("treeDiv_$panelid",'$panelid', 'editGroups');

        YAHOO.haloacl.labelClickAction_$panelid = function(query,element){
            if (YAHOO.haloacl.debug) console.log("element"+element);
            if (YAHOO.haloacl.debug) console.log("query"+query);
            var element = $(element);

            YAHOO.haloacl.removeHighlighting();

            $(element.parentNode.parentNode).addClassName("highlighted");
            if (YAHOO.haloacl.debug) console.log(element);
            $('datatablepaging_groupinfo_$panelid').innerHTML = "<span style='font-weight:normal'>in</span> "+query;

            YAHOO.haloacl.datatableInstance$panelid.executeQuery(query);

        };

        YAHOO.haloacl.treeInstance$panelid.labelClickAction = 'YAHOO.haloacl.labelClickAction_$panelid';
        YAHOO.haloacl.buildTreeFirstLevelFromJson(YAHOO.haloacl.treeInstance$panelid, '$context');

       
        //filter event
        YAHOO.util.Event.addListener("filterSelectGroup_$panelid", "keyup", function(e){
            var filtervalue = document.getElementById("filterSelectGroup_$panelid").value;
            YAHOO.haloacl.applyFilterOnTree (YAHOO.haloacl.treeInstance$panelid.getRoot(), filtervalue);
        });


        YAHOO.util.Event.addListener("datatable_filter_$panelid", "keyup", function(){
            if (YAHOO.haloacl.debug) console.log("filterevent fired");

            if (YAHOO.haloacl.lastFilterExecSelect_$panelid == null || YAHOO.haloacl.lastFilterExecSelect_$panelid == "undefined"){
                YAHOO.haloacl.lastFilterExecSelect_$panelid = 0;
            }

            var filtervalue = $('datatable_filter_$panelid').value;
            var now = new Date();
            now = now.getTime();
            if (filtervalue == "" || YAHOO.haloacl.lastFilterExecSelect_$panelid + YAHOO.haloacl.filterQueryDelay <= now){
                YAHOO.haloacl.lastFilterExecSelect_$panelid  = now;
                YAHOO.haloacl.datatableInstance$panelid.executeQuery('');
            }

            if (YAHOO.haloacl.debug) console.log("datatable_filter_$panelid");
        });


        /* this function is called from onClick-attribute in userTable.js
         * select formater
         */
        YAHOO.haloacl.handleDatatableClick_$panelid = function(item){
           var panelid = '$panelid';



           if (YAHOO.haloacl.clickedArrayUsers[panelid] == null){
                YAHOO.haloacl.clickedArrayUsers[panelid] = new Array();
           }
           if (YAHOO.haloacl.clickedArrayUsersGroups[panelid] == undefined){
                YAHOO.haloacl.clickedArrayUsersGroups[panelid] = new Array();
            }
            var element = item;
            if (element.checked){
                //console.log("adding item" + element.name);
                if (YAHOO.haloacl.debug) console.log("adding "+item.name+" to list of checked users - panelid:$panelid");
                YAHOO.haloacl.addUserToUserArray('$panelid',element.name);
            } else {
                //console.log("remov item" + element.name);
                if (YAHOO.haloacl.debug) console.log("removing "+item.name+" from list of checked users - panelid:$panelid");
                YAHOO.haloacl.removeUserFromUserArray('$panelid',element.name);
            }
            YAHOO.haloacl.clickedArrayUsersGroups['$panelid'][element.name] = $(element).readAttribute("groups");

            /*
           if (YAHOO.haloacl.debug) console.log("restet array for panelid:"+panelid);
           $$('.datatableDiv_'+panelid+'_users').each(function(item){
                if (item.checked){
                   YAHOO.haloacl.clickedArrayUsers[panelid].push(item.name);

                }
                YAHOO.haloacl.clickedArrayUsersGroups[panelid][item.name] = $(item).readAttribute("groups");
           });
           */
           // recreate desc
           try{
            var fncname = "YAHOO.haloacl.refreshPanel_"+panelid.substr(14)+"();";
            eval(fncname);
            }catch(e){}
            try{
            YAHOO.haloacl.highlightAlreadySelectedUsersInDatatable(panelid);
            }catch(e){}
        };
        
        //YAHOO.util.Event.addListener("datatableDiv_$panelid", "click", handleDatatableClick);
        </script>
HTML;


    return $html;

}


/**
 *  assigned tab
 *
 * @param <string> $panelid
 * @param <string> predfine-type
 * @param <boolean> is readonly? (hide delete-icons)
 * @param <boolean> do preload?
 * @param <string> preload-right-id
 * @return <string> content of assigned-tab
 */
function haclRightPanelAssignedTab($panelid, $predefine, $readOnly, $preload=false, $preloadRightId=8) {
    if ($preload == "false") {
        $preload = false;
    }

    $hacl_rightPanelSelectDeselectTab_1 = wfMsg('hacl_rightPanelSelectDeselectTab_1');
    $hacl_rightPanelSelectDeselectTab_2 = wfMsg('hacl_rightPanelSelectDeselectTab_2');
    $hacl_rightPanelSelectDeselectTab_3 = wfMsg('hacl_rightPanelSelectDeselectTab_3');
    $hacl_rightPanelSelectDeselectTab_4 = wfMsg('hacl_rightPanelSelectDeselectTab_4');
    $hacl_rightPanelSelectDeselectTab_5 = wfMsg('hacl_rightPanelSelectDeselectTab_5');

    $hacl_no_groups_or_users = wfMsg('hacl_no_groups_or_users');

    // FIXME: "Remove" via Internationalization/wfMsg
    $html = <<<HTML
        <!-- leftpart -->
        <div id="haloacl_rightpanel_selectab_info_$panelid" style="width:875px;height:320px;display:none" class="haloacl_rightpanel_selecttab_container">
        </div>
        <div id="haloacl_rightpanel_selectab_$panelid" class="haloacl_rightpanel_selecttab_container">
            <div class="haloacl_rightpanel_selecttab_leftpart">
                <div class="haloacl_rightpanel_selecttab_leftpart_filter">
                    <span class="haloacl_rightpanel_selecttab_leftpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_1
                    </span>
                    <span>Remove</span>
                </div>
                <div class="haloacl_rightpanel_selecttab_leftpart_filter">
                    <span class="haloacl_rightpanel_selecttab_leftpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_2
                    </span>
                    <input class="haloacl_filter_input" id="filterAssignedGroup_$panelid" type="text" />
                </div>
                <div id="treeDivRO_$panelid" class="haloacl_rightpanel_selecttab_leftpart_treeview">&nbsp;</div>
                <!--
                <div class="haloacl_rightpanel_selecttab_leftpart_treeview_userlink">
        $hacl_rightPanelSelectDeselectTab_3
                </div>
                -->
            </div>
            <!-- end of left part -->

            <!-- starting right part -->

            <div class="haloacl_rightpanel_selecttab_rightpart">
                <div class="haloacl_rightpanel_selecttab_rightpart_filter">
                    <span class="haloacl_rightpanel_selecttab_rightpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_4
                    </span>
                    <span>Remove</span>
                </div>
                <div class="haloacl_rightpanel_selecttab_rightpart_filter">
                    <span class="haloacl_rightpanel_selecttab_rightpart_filter_title">
        $hacl_rightPanelSelectDeselectTab_5
                    </span>
                    <input class="haloacl_filter_input type="text" onKeyup="YAHOO.haloacl.refilterUsersAssigned_$panelid(this);"/>
                </div>
                <div id="ROdatatableDiv_$panelid" class="haloacl_rightpanel_selecttab_rightpart_datatable">&nbsp;</div>
                <div id="ROdatatablepaging_ROdatatableDiv_$panelid"></div>
                </div>
            </div>
            <!-- end of right part -->

        </div>
    <script type="text/javascript">
       
        YAHOO.haloacl.notification.clearAllNotification();

        if (YAHOO.haloacl.hasGroupsOrUsers('$panelid') == true){
            $('haloacl_rightpanel_selectab_info_$panelid').hide();
            $('haloacl_rightpanel_selectab_$panelid').show();

            // user list on the right
            YAHOO.haloacl.RODatatableInstace$panelid = YAHOO.haloacl.ROuserDataTableV2("ROdatatableDiv_$panelid","$panelid",'$readOnly');

            // treeview part - so the left part of the select/deselct-view

           YAHOO.haloacl.ROtreeInstance$panelid = YAHOO.haloacl.getNewTreeview("treeDivRO_$panelid",'$panelid');

            YAHOO.haloacl.labelClickASSIGNED$panelid = function(name,element){
//                console.log(element);
//                console.log(element.parentNode.parentNode);
//                element.parentNode.parentNode.remove();
                element.parentNode.parentNode.setAttribute("style","display:none");

                YAHOO.haloacl.removeGroupFromGroupArray('$panelid', name);
                $('ROdatatableDiv_$panelid').innerHTML = "";
                YAHOO.haloacl.RODatatableInstace$panelid = YAHOO.haloacl.ROuserDataTableV2("ROdatatableDiv_$panelid","$panelid");
            };
            YAHOO.haloacl.ROtreeInstance$panelid.labelClickAction = "YAHOO.haloacl.labelClickASSIGNED$panelid";

        } else {
            $('haloacl_rightpanel_selectab_$panelid').hide();
            $('haloacl_rightpanel_selectab_info_$panelid').innerHTML = "$hacl_no_groups_or_users";
            $('haloacl_rightpanel_selectab_info_$panelid').show();


        }

        YAHOO.haloacl.buildUserTreeRO(YAHOO.haloacl.treeInstance$panelid, YAHOO.haloacl.ROtreeInstance$panelid);

        refilterGroup = function() {
            YAHOO.haloacl.filterNodesGroupUser (YAHOO.haloacl.ROtreeInstance$panelid.getRoot(), document.getElementById("filterAssignedGroup_$panelid").value);
        }
        YAHOO.util.Event.addListener("filterAssignedGroup_$panelid", "keyup", refilterGroup);

        YAHOO.haloacl.refilterUsersAssigned_$panelid = function(element){
            YAHOO.haloacl.filterUserDatatableJS("userdatatable_name_$panelid",element.value);
        }


    </script>
HTML;

    return $html;

}


/**
 *  element to show existing rights
 *  also used in template-chooser
 * @param <string> $panelid
 * @param <string> "readonly" or null
 * @param <string> don't filter (true for template-choosing-purpose)
 * @return <string> right-listing
 */
function haclRightList($panelid, $type = "readOnly",$nofilter = "") {

    $hacl_rightList_All = wfMsg('hacl_rightList_All');
    $hacl_rightList_StandardACLs = wfMsg('hacl_rightList_StandardACLs');
    $hacl_rightList_Page = wfMsg('hacl_rightList_Page');
    $hacl_rightList_Category = wfMsg('hacl_rightList_Category');
    $hacl_rightList_Property = wfMsg('hacl_rightList_Property');
    $hacl_rightList_Namespace = wfMsg('hacl_rightList_Namespace');
    $hacl_rightList_ACLtemplates = wfMsg('hacl_rightList_ACLtemplates');
    $hacl_rightList_Defaultusertemplates = wfMsg('hacl_rightList_Defaultusertemplates');
    $hacl_RightsContainer_2 = wfMsg('hacl_RightsContainer_2');

    $hacl_rightList_1 = wfMsg('hacl_rightList_1');
    $hacl_manageUser_7 = wfMsg('hacl_manageUser_7');

    $delete_text = wfMsg('hacl_delete_link_header');
    $edit_text = wfMsg('hacl_rightsPanel_right_edit');
    $delete_selected = wfMsg('hacl_delete_selected');
    $selected_text = wfMsg('hacl_selected');
    $select_text = wfMsg('hacl_select');
    $showacls_text = wfMsg('hacl_showacls');


    $response = new AjaxResponse();

    $html = <<<HTML

    <div class="haloacl_manageacl_selector_content">
HTML;
    if ($type != "readOnly") {
        $html .= <<<HTML
            <script>
            YAHOO.haloacl.filter_handleFilterChangeEvent = function(e){
                if (e.name == "all"){
                    if (e.checked){
                        $$('.haloacl_manageacl_filter').each(function(i){i.checked=true;});
                    } else {
                        $$('.haloacl_manageacl_filter').each(function(i){i.checked=false;});
                    }
                } else if (e.name == "standardacl"){
                    if (e.checked){
                        $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "page" || i.name == "category" || i.name == "property" || i.name == "namespace"){
                                i.checked=true;
                            }
                        });
                    } else {
                        $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all" ||i.name == "page" || i.name == "category" || i.name == "property" || i.name == "namespace"){
                                i.checked=false;
                            }
                        });
                    }
                } else if (e.name == "page"){
                    if (e.checked){
                    } else {
                       $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all" || i.name == "standardacl"){
                                i.checked=false;
                            }
                        });
                    }
                } else if (e.name == "category"){
                    if (e.checked){
                    } else {
                       $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all"|| i.name == "standardacl"){
                                i.checked=false;
                            }
                        });
                    }
                } else if (e.name == "property"){
                    if (e.checked){
                    } else {
                       $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all"|| i.name == "standardacl"){
                                i.checked=false;
                            }
                        });
                    }
                } else if (e.name == "namespace"){
                    if (e.checked){
                    } else {
                       $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all"|| i.name == "standardacl"){
                                i.checked=false;
                            }
                        });
                    }
                } else if (e.name == "acltemplate"){
                    if (e.checked){
                    } else {
                       $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all"){
                                i.checked=false;
                            }
                        });
                    }
                } else if (e.name == "defusertemplate"){
                    if (e.checked){
                    } else {
                       $$('.haloacl_manageacl_filter').each(function(i){
                            if (i.name == "all"){
                                i.checked=false;
                            }
                        });
                    }
                }
            };
            </script>


            <div id="haloacl_manageuser_contentmenu">
            <div id="haloacl_manageacl_contentmenu_title">
            $showacls_text
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="all"/>&nbsp;$hacl_rightList_All
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="standardacl"/>&nbsp;$hacl_rightList_StandardACLs
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter sub" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);"  type="checkbox" checked="" name="page"/>&nbsp;$hacl_rightList_Page
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter sub" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="category"/>&nbsp;$hacl_rightList_Category
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter sub" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="property"/>&nbsp;$hacl_rightList_Property
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter  sub" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="namespace"/>&nbsp;$hacl_rightList_Namespace
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="acltemplate"/>&nbsp;$hacl_rightList_ACLtemplates
            </div>
            <div class="haloacl_manageacl_contentmenu_element">
                <input class="haloacl_manageacl_filter" onClick="YAHOO.haloacl.filter_handleFilterChangeEvent(this);" type="checkbox" checked="" name="defusertemplate"/>&nbsp;Default user
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;templates
            </div>


        </div>
HTML;
    }

    $html .= <<<HTML

        <div id="haloacl_manageuser_contentlist">

        <div id="manageuser_grouplisting">
                <div class="haloacl_manageacl_contenttitle">
        $hacl_rightList_1
HTML;


    if ($type != "readOnly") {
        $html .= <<<HTML
            <span>$delete_text</span><span>Edit</span><span>Info</span>
HTML;
    } else {
        $html .= <<<HTML
            <span>$select_text</span><span>Info</span>
HTML;

    }

    $html .= <<<HTML
        </div>
HTML;

    if ($type != "readOnly" || true) {
        $html .= <<<HTML
            <div class="haloacl_manageacl_contenttitle">
            Filter:&nbsp;<input id="haloacl_rightlist_filterinput_$panelid" class="haloacl_filter_input"/>
        </div>
HTML;
    }

    if ($type != "readOnly") {
        $html .= <<<HTML

            <div id="haloacl_manageacl_acltree">
                <div id="treeDiv_$panelid" class="haloacl_rightpanel_selecttab_leftpart_treeview">&nbsp;</div>
            </div>
        </div>
HTML;
    } else {
        $html .= <<<HTML
            <div id="haloacl_manageacl_acltree">
                <div id="treeDiv_$panelid" style="height:277px" class="haloacl_rightpanel_selecttab_leftpart_treeview">&nbsp;</div>
            </div>
        </div>
HTML;
    }

    $showing1 = wfMsg('hacl_showing_text');
    $showing2 = wfMsg('hacl_showing_elements_text');

    if ($type != "readOnly") {
        $html .= <<<HTML
        <div id="haloacl_manageuser_contentlist_footer">
            <span class="haloacl_cont_under_trees">
            $showing1 <span id="haloacl_rightstree_count">0</span> $showing2
            </span>
            <input type="button" onClick="YAHOO.haloacl.manageACLdeleteCheckedGroups();" value="$hacl_manageUser_7" />
        </div>
HTML;

    } else {
        $html .= <<<HTML
        <div id="haloacl_manageuser_contentlist_footer" style="float:right">
            <input type="button" id="hacl_use_selected_tpl" name="useTemplate" value="$hacl_RightsContainer_2" onclick="YAHOO.haloacl.buildTemplatePanelXML_$panelid();" />
        </div>
HTML;

    }

    $html .= <<<HTML
    </div>

    

<script type="text/javascript">




    YAHOO.haloacl.manageACLdeleteCheckedGroups = function(){
        var checkedSDs = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloaclrights.treeInstance$panelid, null);

        var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
        xml += "<sdstodelete>";
        for(i=0;i<checkedSDs.length;i++){
            xml += "<sd>"+escape(checkedSDs[i])+"</sd>";
        }
        xml += "</sdstodelete>";
        if (YAHOO.haloacl.debug) console.log(xml);

        var callback5 = function(result){
            YAHOO.haloacl.notification.createDialogOk("content","Manage ACLs",result.responseText,{
                yes:function(){
                    window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=manageACLs';
                    }
            });
        };

        YAHOO.haloacl.sendXmlToAction(xml,'haclDeleteSecurityDescriptor',callback5);

    };


    // treeview part - so the left part of the select/deselct-view

    YAHOO.haloaclrights.treeInstance$panelid = YAHOO.haloaclrights.getNewRightsTreeview("treeDiv_$panelid",'$panelid', '$type');
    YAHOO.haloaclrights.treeInstance$panelid.labelClickAction = 'YAHOO.haloacl.manageACL_handleClick';
    YAHOO.haloaclrights.buildTreeFirstLevelFromJson(YAHOO.haloaclrights.treeInstance$panelid,"template","$nofilter");


    // adding filtering
    YAHOO.util.Event.addListener("haloacl_rightlist_filterinput_$panelid", "keyup", function(e){
        var filtervalue = document.getElementById("haloacl_rightlist_filterinput_$panelid").value;
        YAHOO.haloaclrights.applyFilterOnTree (YAHOO.haloaclrights.treeInstance$panelid.getRoot(), filtervalue,"$nofilter");
    });


    YAHOO.haloacl.manageACL_handleClick = function(groupname){
        if (YAHOO.haloacl.debug) console.log(groupname);
        $$('.manageUser_highlighted').each(function(item){
            item.removeClassName("manageUser_highlighted");
        });
        try{
        var element = $('manageUserRow_'+groupname);
        if (YAHOO.haloacl.debug) console.log(element);
        var temp = element.parentNode.parentNode.parentNode.parentNode;
        $(temp).addClassName("manageUser_highlighted");
        }catch(e){}
        YAHOO.haloacl.manageUser_selectedGroup = groupname;

    };



HTML;
    if ($type != "readOnly") {
        $html .=<<<HTML

        var reloadaction = function(){
            YAHOO.haloaclrights.treeInstance$panelid = YAHOO.haloaclrights.getNewRightsTreeview("treeDiv_$panelid",'$panelid', '$type');
            YAHOO.haloaclrights.buildTreeFirstLevelFromJson(YAHOO.haloaclrights.treeInstance$panelid,"template","$nofilter");
        }
        $$('.haloacl_manageacl_filter').each(function(item){
            YAHOO.util.Event.addListener(item, "click", reloadaction);
        });
        
HTML;
    }

    $html .= <<<HTML


/*
    refilter = function() {
        YAHOO.haloaclrights.filterNodes (YAHOO.haloaclrights.treeInstance$panelid.getRoot(), document.getElementById("filterSelectGroup_$panelid").value);
    }
    //filter event
    YAHOO.util.Event.addListener("filterSelectGroup_$panelid", "keyup", refilter);
    YAHOO.util.Event.addListener("datatable_filter_$panelid", "keyup", function(){
        if (YAHOO.haloacl.debug) console.log("filterevent fired");
        YAHOO.haloaclrights.datatableInstance$panelid.executeQuery('');
    });
*/

</script>

HTML;

    $html = "<div id=\"content_".$panelid."\">".$html."</div>";

    $response->addText($html);
    return $response;

}


/**
 *
 * returns panel listing all existing rights, embedded in container for editing (Manage ACL Panel)
 *
 * @param <string>  rightid
 * @param <string>  right's name
 * @param <bool>    is readonly?
 * @return <string>   returns the user/group-select tabview; e.g. contained in right panel
 */
function haclGetSDRightsPanelContainer($sdId, $sdName, $readOnly=false) {
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];

    $hacl_SDRightsPanelContainer_1 = wfMsg('hacl_SDRightsPanelContainer_1');
    $hacl_SDRightsPanelContainer_2 = wfMsg('hacl_SDRightsPanelContainer_2');
    $hacl_SDRightsPanelContainer_3 = wfMsg('hacl_SDRightsPanelContainer_3');
    $hacl_SDRightsPanelContainer_4 = wfMsg('hacl_SDRightsPanelContainer_4');

    $create_right = wfMsg('hacl_create_right');
    $add_template = wfMsg('hacl_add_template');

    if ($readOnly == "true") $readOnly = true;
    if ($readOnly == "false") $readOnly = false;
    
    $isDynamicSD = false;
    $sd = HACLSecurityDescriptor::newFromID($sdId);
    if ($sd->hasDynamicInlineRights()) {
    	$readOnly = true;
    	$isDynamicSD = true;
    }

    $sdName = "$ns:".$sdName;
    $panelid = "SDRightsPanel_$sdId";
    $response = new AjaxResponse();

    //$myGenericPanel = new HACL_GenericPanel($panelid, "[ $hacl_SDRightsPanelContainer_1 $sdName ]", "[ $hacl_SDRightsPanelContainer_1 $sdName ]","", true, true, null, "expand",true);
    $myGenericPanel = new HACL_GenericPanel("hacl_panel_container", "[ $hacl_SDRightsPanelContainer_1 $sdName ]", "[ $hacl_SDRightsPanelContainer_1 $sdName ]","", true, true, null, "expand",true);

    $predefine = "individual";
    $html = "";
    if (!$readOnly) {
        $html = <<<HTML
        <!-- add right part -->
                <div class="haloacl_existing_right_add_buttons">
                    <input id="haloacl_create_right_$predefine" type="button" value="$create_right"
                        onclick="javascript:YAHOO.haloacl.createacl_addRightPanel('$predefine');"/>
                    &nbsp;
                    <input id="haloacl_add_right_$predefine" type="button" value="$add_template"
                        onclick="javascript:YAHOO.haloacl.createacl_addRightTemplate('$predefine');"/>
                </div>
            <script>
            YAHOO.haloacl.createacl_addRightPanel = function(predefine){
                  var panelid = 'create_acl_right_'+ YAHOO.haloacl.panelcouner;
                  var divhtml = '<div id="create_acl_rights_row'+YAHOO.haloacl.panelcouner+'" class="haloacl_tab_section_content_row"></div>';
                  var containerWhereDivsAreInserted = $('haloacl_tab_createacl_rightsection');
                  $('haloacl_tab_createacl_rightsection').insert(divhtml,containerWhereDivsAreInserted);

                  YAHOO.haloacl.loadContentToDiv('create_acl_rights_row'+YAHOO.haloacl.panelcouner,'haclGetRightsPanel',{panelid:panelid, predefine:predefine});
                  YAHOO.haloacl.panelcouner++;
            };

            YAHOO.haloacl.createacl_addRightTemplate = function(predefine){
                  var panelid = 'create_acl_right_'+ YAHOO.haloacl.panelcouner;
                  var divhtml = '<div id="create_acl_rights_row'+YAHOO.haloacl.panelcouner+'" class="haloacl_tab_section_content_row"></div>';
                  var containerWhereDivsAreInserted = $('haloacl_tab_createacl_rightsection');
                  $('haloacl_tab_createacl_rightsection').insert(divhtml,containerWhereDivsAreInserted);

                  YAHOO.haloacl.loadContentToDiv('create_acl_rights_row'+YAHOO.haloacl.panelcouner,'haclGetRightsContainer',{panelid:panelid});
                  YAHOO.haloacl.panelcouner++;
            };
            </script>

        <div id="haloacl_tab_createacl_rightsection">&nbsp;</div>

        <!-- end of add right part -->
HTML;
    }

    $aclsavedmsg = wfMsg('hacl_createSaveContent_3');
    
    if ($readOnly) {
    	$buttonBox = "";
    	if ($isDynamicSD) {
    		$linker = new Linker();
    		$sdLink = $linker->link(Title::newFromText($sdName));
			$msg = wfMsg('hacl_dynamic_right_not_editable', $sdLink);
			$buttonBox = <<<HTML
        <div class="haloacl_button_box haloacl_dynamic_right_msg" style="margin-bottom: 10px;">
        	$msg
        </div>
HTML;
    	}
    					
    } else {
    	$buttonBox = <<<HTML
        <div class="haloacl_button_box haloacl_three_buttons" style="height: 18px; margin-bottom: 10px;">
            <div><input type="button" style="margin-left:10px" value="$hacl_SDRightsPanelContainer_2" onclick="javascript:YAHOO.haloacl.deleteSD('$sdId');" /></div>
            <div><input type="button" class="haloacl_discard_button" value="$hacl_SDRightsPanelContainer_3" onclick="javascript:YAHOO.haloacl.removePanel('$panelid');YAHOO.haloacl.createacl_addRightPanel();" /></div>
            <div><input id="haloacl_save_right" type="button" name="safeRight" value="$hacl_SDRightsPanelContainer_4" onclick="YAHOO.haloacl.buildCreateAcl_SecDesc();" /></div>
        </div>
HTML;
    }
    $html .= <<<HTML
        <div class="haloacl_sd_container_$readOnly" id="SDRightsPanelContainer_$sdId">
        </div>
        <script>
            YAHOO.haloacl.loadContentToDiv('SDRightsPanelContainer_$sdId','haclGetSDRightsPanel',{sdId:'$sdId', readOnly:'$readOnly',autosave:'true'});
        </script>
        <div class="haloacl_greyline">&nbsp;</div>
 		$buttonBox 
        <script type="javascript">

            var callback2 = function(result){
                    if (result.status == '200'){
                        try{
                        $('create_acl_autogenerated_acl_name').value = result.responseText;
                        }catch(e){};
                        genericPanelSetSaved_$panelid(true);

                        YAHOO.haloacl.notification.createDialogOk("content","ACL","Right has been saved",{
                            yes:function(){
                                window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=manageACLs';
                                }
                        });
                    } else {
                        YAHOO.haloacl.notification.createDialogOk("content","Something went wrong",result.responseText,{
                            yes:function(){
                                }
                        });
                    }
                };

            YAHOO.haloacl.buildCreateAcl_SecDesc = function(){

                // building xml
                var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                xml+="<secdesc>";
                xml+="<panelid>create_acl</panelid>";
                xml+="<name>"+escape('$sdName')+"</name>";
                xml+="<ACLType>all_edited</ACLType>";

                var callback2 = function(result){
                    if (result.status == '200'){

                        YAHOO.haloacl.notification.createDialogOk("content","Right","$aclsavedmsg",{
                            yes:function(){
                                }
                        });
                    } else {
                        YAHOO.haloacl.notification.createDialogOk("content","Something went wrong",result.responseText,{
                            yes:function(){
                                }
                        });
                    }
                };

                $$('.create_acl_general_protect').each(function(item){
                    if (item.checked){
                        xml+="<protect>"+item.value+"</protect>";
                    }
                });

                xml+="</secdesc>";

                YAHOO.haloacl.sendXmlToAction(xml,'haclSaveSecurityDescriptor',callback2);
            };
        </script>
HTML;

    $html = "<div id=\"content_".$panelid."\">".$html."</div>";

    $myGenericPanel->setContent($html);

    $response->addText($myGenericPanel->getPanel());
    return $response;

}


/**
 *
 * returns panel listing all existing rights, embedded in container for selection (Create ACL Panel)
 *
 * @param <string>  unique identifier
 * @return <string>   returns the user/group-select tabview; e.g. contained in right panel
 */
function haclGetRightsContainer($panelid, $type = "readOnly") {

    $hacl_RightsContainer_1 = wfMsg('hacl_RightsContainer_1');
    $hacl_RightsContainer_2 = wfMsg('hacl_RightsContainer_2');

    $response = new AjaxResponse();

    $myGenericPanel = new HACL_GenericPanel($panelid, "$hacl_RightsContainer_1", "$hacl_RightsContainer_1");

    $html = <<<HTML
        <div style="margin:10px 0;float:left" id="SDRightsPanelContainer_$panelid"></div>
        <script>
            YAHOO.haloacl.loadContentToDiv('SDRightsPanelContainer_$panelid','haclRightList',{panelid:'$panelid', type:'$type',nofilter:'true'});
        </script>
        

        <script type="javascript">

            YAHOO.haloacl.buildTemplatePanelXML_$panelid = function(onlyReturnXML){

                var panelid = '$panelid';
                var checkedgroups = YAHOO.haloaclrights.getCheckedNodesFromRightsTree(YAHOO.haloaclrights.treeInstance$panelid, null);
              
                    var counter = 0;
                    checkedgroups.each(function(actualtemplate){

                        // building xml
                        var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                        xml+="<inlineright>";
                        xml+="<panelid>$panelid"+"_templatecount_"+counter+"</panelid>";
                        xml+="<type>template</type>";

                        xml+="<name>"+escape(actualtemplate)+"</name>";

                        xml+="</inlineright>";

                        var callback3 = function(result){
                            console.log(result);
                        };
                        YAHOO.haloacl.sendXmlToAction(xml,'haclSaveTempRightToSession',callback3);
                        counter++;
                    });
                    if (checkedgroups.length > 0){
                        genericPanelSetSaved_$panelid(true);
                        YAHOO.haloacl.closePanel('$panelid');
                        if ($('step3') != null){
                            $('step3').show();
                        }
                    } else {
                         YAHOO.haloacl.notification.createDialogOk("content","Groups: Something went wrong","No templates have been selected",{
                            yes:function(callback){
                            }});
                    }
            };

        </script>
HTML;

    $html = "<div id=\"content_".$panelid."\">".$html."</div>";

    $myGenericPanel->setContent($html);

    $response->addText($myGenericPanel->getPanel());
    return $response;

}


/**
 *
 * returns panel listing all existing rights
 *
 * @param <string> right's id
 * @param <bool>   is readonly?
 * @param <bool>   autosave right after loading?
 * @return <string>   returns the user/group-select tabview; e.g. contained in right panel
 */
function haclGetSDRightsPanel($sdId, $readOnly = false,$autosave = true) {


    $alreadyLoadedTpls = array();
    $alreadyLoadedTpls[] = $sdId;

    if ($autosave == "true" || $autosave == "") {
        $autosave = true;
    } elseif ($autosave == "false") {
        $autosave = false;
    }

    if ($autosave)clearTempSessionRights();

    if ($readOnly === "true") $readOnly = true;
    if ($readOnly === "false") $readOnly = false;

    $html = "";
    $response = new AjaxResponse();

    $SD = HACLSecurityDescriptor::newFromId($sdId);

    $tempRights = array();

    if ($readOnly) {
        $expandMode = "replace";
        $autosave = false;
    #$expandMode = "expand";
    } else {
        $expandMode = "expand";
    }

    //attach inline right texts
    foreach ($SD->getInlineRights(false) as $rightId) {
    //echo "----$rightId---";
        $tempRight = HACLRight::newFromId($rightId);
        $html .= haclGetRightsPanel("SDDetails_".$sdId."_".$rightId, 'individual', $readOnly, "true", $rightId, $tempRight->getName(), HACLRight::newFromID($rightId)->getDescription(),true, true, null, "expand",true);

        /* if autosave is active, save that inline right to session */
        if ($autosave) {
            $rightToSave = HACLRight::newFromID($rightId);
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= "<inlineright>";
            $xml .= "<panelid>SDDetails_{$sdId}_{$rightId}</panelid>";
            $xml .= "<name>{$rightToSave->getName()}</name>";
            $xml .= "<description>{$rightToSave->getDescription()}</description>";
            $xml .= "<users>";
            $defineFor = "individual";
            if (array_intersect($rightToSave->getUsers(), array("0","-1"))) {
                $defineFor ="allusers";
            } elseif (array_intersect($rightToSave->getUsers(), array("0"))) {
                $defineFor ="allusersanonymous";
            } elseif (array_intersect($rightToSave->getUsers(), array("-1"))) {
                $defineFor ="allusersregistered";
            } else {
                foreach ($rightToSave->getUsersEx(HACLRight::NAME) as $u) {
                    $xml .= "<user>$u</user>";
                }
            }
            $xml .= "</users>";
            $xml .= "<type>$defineFor</type>";
            $xml .= "<groups>";
            foreach ($rightToSave->getGroupsEx(HACLRight::NAME) as $g) {
                $xml .= "<group>$g</group>";
            }
            $xml .= "</groups>";
            $xml .= "<rights>";
            $actions = $rightToSave->getActions();
            if ($actions & HACLRight::EDIT)  $xml .= "<right>edit</right>";
            if ($actions & HACLRight::CREATE)  $xml .= "<right>create</right>";
            if ($actions & HACLRight::MOVE)  $xml .= "<right>move</right>";
            if ($actions & HACLRight::DELETE)  $xml .= "<right>delete</right>";
            if ($actions & HACLRight::READ)  $xml .= "<right>read</right>";
            if ($actions & HACLRight::FORMEDIT)  $xml .= "<right>formedit</right>";
            if ($actions & HACLRight::ANNOTATE)  $xml .= "<right>annotate</right>";
            if ($actions & HACLRight::WYSIWYG)  $xml .= "<right>wysiwyg</right>";
            $xml .= "</rights>";
            $xml .= "</inlineright>";
            haclSaveTempRightToSession($xml);

        }

    }

    //attach predefined right texts
    // only templates down here
    foreach ($SD->getPredefinedRights(false) as $subSdId) {
        $sdName = HACLSecurityDescriptor::nameForID($subSdId);
        if ($autosave) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= "<inlineright>";
            $xml .= "<panelid>subRight_$subSdId</panelid>";
            $xml .= "<name>$sdName</name>";
            $xml .= "<type>template</type>";
            $xml .= "</inlineright>";
            haclSaveTempRightToSession($xml);
        }


        //                                              ($panelid, $name="", $title, $description = "", $showStatus = true,$showClose = true,$customState=null,$expandMode="expand") {
        if (!$readOnly) {
            $myGenericPanel = new HACL_GenericPanel("subRight_$subSdId", "[ Template: $sdName ]", "[ Template: $sdName ]", "", true, true, null, $expandMode,true);
        } else {
            $myGenericPanel = new HACL_GenericPanel("subRight_$subSdId", "[ Template: $sdName ]", "[ Template: $sdName ]", "", false, false, null, $expandMode);
        }

        $temphtml = <<<HTML
        <div id="content_subRight_$subSdId">
        <div id="subPredefinedRight_$subSdId"></div>
HTML;
        if (!$readOnly) {

            $deletetpltext = wfMsg('hacl_deletetplfromacl');
            $addtpltext = wfMsg('hacl_addtpltoacl');

            $temphtml .= <<<HTML
        <div class="haloacl_buttons_under_panel">
            <div><input type="button" id="haloacl_delete_$sdName" value="$deletetpltext" onclick="javascript:YAHOO.haloacl.removePanel('subRight_$subSdId');" /></div>
            <div><input id="haloacl_save_$sdName" type="button" name="safeRight" value="$addtpltext" onclick="YAHOO.haloacl.buildRightPanelXML_$subSdId();" /></div>
        </div>
HTML;
        }
        $temphtml .= <<<HTML
        <script>

            YAHOO.haloacl.buildRightPanelXML_$subSdId = function(onlyReturnXML){

                // building xml
                var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                xml+="<inlineright>";
                xml+="<panelid>subRight_$subSdId</panelid>";
                xml+="<type>template</type>";
              
                xml+="<name>"+escape('$sdName')+"</name>";

                xml+="</inlineright>";
                var callback = function(result){
                    genericPanelSetSaved_subRight_$subSdId(true);
                    YAHOO.haloacl.togglePanel('subRight_$subSdId');

                    return null;
                }

                YAHOO.haloacl.sendXmlToAction(xml,'haclSaveTempRightToSession',callback);
                          
            };
HTML;
        // preventing deathlock
        if (!in_array($subSdId, $alreadyLoadedTpls)) {
            $temphtml .= <<<HTML

                YAHOO.haloacl.loadContentToDiv('subPredefinedRight_$subSdId','haclGetSDRightsPanel',{sdId:'$subSdId', readOnly:'true', autosave:'false'});

HTML;
        }
        $alreadyLoadedTpls[] = $subSdId;

        $temphtml .= <<<HTML
            YAHOO.haloacl.togglePanel('subRight_$subSdId');

        </script>
        </div>
HTML;
        $myGenericPanel->setContent($temphtml);
        $html .= $myGenericPanel->getPanel();

    }


    $html .= '<div class="haloacl_greyline">&nbsp;</div>';

    // saving modificationrights
    if ($autosave) {

        $users = $SD->getManageUsers();
        $groups = $SD->getManageGroups();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "<inlineright>";
        $xml .= "<panelid>SDDetails_".$sdId."_modification</panelid>";
        $xml .= "<name>Modification Right</name>";
        if (isset($rightToSave)) {
            $xml .= "<description>{$rightToSave->getDescription()}</description>";
        }
        $xml .= "<users>";

        foreach ($users as $u) {
            $xml .= "<user>".User::newFromId($u)->getName()."</user>";
        }
        $xml .= "</users>";
        $xml .= "<type>modification</type>";
        $xml .= "<groups>";
        foreach ($groups as $g) {
            $xml .= "<group>".HACLGroup::nameForID($g)."</group>";
        }
        $xml .= "</groups>";
        $xml .= "</inlineright>";
        haclSaveTempRightToSession($xml);

        $html .= haclGetRightsPanel("SDDetails_".$sdId."_modification", 'modification', $readOnly, true, $sdId, "Modification Right","",true);

    } else {
        $html .= haclGetRightsPanel("SDDetails_".$sdId."_modification", 'modification', $readOnly, true, $sdId, "Modification Right");
    }

    $response->addText($html);
    return $response;

}


/**
 * function called intern (not ajax) to clear right that have been saved to session
 */
function clearTempSessionRights() {
    unset($_SESSION['temprights']);
}

/**
 * function called intern to clear groups that have been saved to session
 */
function clearTempSessionGroup() {
    unset($_SESSION['tempgroups']);
}

/**
 *  saves groupsettings to session
 *  finaly (if group will be saved) all temp-saved group will be added to the group
 * @global <type> $haclgContLang
 * @param <string> xml containing groupsettings
 * @return <AjaxResponse> save-indicator / error message
 */
function haclSaveTempGroupToSession($groupxml) {
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    $groupPrefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP);
    
    // checking if action is valid
    $xml = new SimpleXMLElement($groupxml);
    $groupname = urldecode((String)$xml->name);
    if ($groupname == "") {
        $response = new AjaxResponse();
        $response->setResponseCode(400);
        $msg = wfMsgForContent('hacl_group_no_name');
        $response->addText($msg);
        return $response;
    }
    $newGroup = (String)$xml->newgroup;
    if ($newGroup == "true") {
    	$groupID = HACLGroup::idForGroup($groupname);
    	if ($groupID == null) {
    		$groupID = HACLGroup::idForGroup("$groupPrefix/$groupname");
    	}
        if ($groupID) {
        	$group = HACLGroup::newFromID($groupID);
        	$type = $group->getType();
            $response = new AjaxResponse();
            $response->setResponseCode(400);
            $msg = wfMsgForContent('hacl_group_exists', $groupname, $type);
            $response->addText($msg);
            return $response;
        }

    }
    $_SESSION['tempgroups'] = $groupxml;
    $response = new AjaxResponse();
    $response->setResponseCode(200);
    $response->addText(wfMsg('hacl_saveTempGroup_1'));
    return $response;
}


/**
 *
 * @param <string>  right serialized as xml
 * @return <AjaxResponse>     200: ok / right saved to session
 *                      400: failure / rihght not saved to session (exception's message will be returned also)
 */
function haclSaveTempRightToSession($rightxml) {
    $ajaxResponse = new AjaxResponse();
    try {

        $xml = new SimpleXMLElement($rightxml);

        $panelid = (string)$xml->panelid;
        #  $_SESSION['temprights'][$panelid] = $tempright;

        $_SESSION['temprights'][$panelid] = $rightxml;

/*
 *      actually done on clientside / javascript
 *      $autoGeneratedRightName = $autoGeneratedRightNameRights.$autoGeneratedRightName;
        if (strlen($autoGeneratedRightName) > 50) $autoGeneratedRightName = substr($autoGeneratedRightName,0,50)."...";

        if ($autoDescription == "on") $description = $autoGeneratedRightName;
*/
        $ajaxResponse->setContentType("json");
        $ajaxResponse->setResponseCode(200);
    //$ajaxResponse->addText("");

    } catch (Exception  $e) {

        $ajaxResponse->setResponseCode(400);
        $ajaxResponse->addText($e->getMessage());
    }
    return $ajaxResponse;

}
/**
 *  removes a tempsaved-right from session
 *  this happens when a right is delted from an acl
 *
 * @param <string>  panelid of right
 * @return <AjaxResponse>     200: ok / right saved to session
 *                      400: failure / rihght not saved to session (exception's message will be returned also)
 */
function haclRemovePanelForTemparray($panelid) {
    $ajaxResponse = new AjaxResponse();
    try {
        unset($_SESSION['temprights'][$panelid]);

        $ajaxResponse->setResponseCode(200);
        $ajaxResponse->addText("success");

    } catch (Exception  $e) {

        $ajaxResponse->setResponseCode(400);
        $ajaxResponse->addText($e->getMessage());
    }
    return $ajaxResponse;

}


/**
 *  converts sd-name to sd-id
 * @param <string> SD-Name
 * @return <AjaxResponse> SD-Id
 */
function haclSDpopupByName($sdName) {
    $ajaxResponse = new AjaxResponse();
    try {


        $tempSD = HACLSecurityDescriptor::newFromName($sdName);
        $ajaxResponse->addText($tempSD->getSDID());

        $ajaxResponse->setResponseCode(200);
    //$ajaxResponse->addText("");

    } catch (Exception  $e) {

        $ajaxResponse->setResponseCode(400);
        $ajaxResponse->addText($e->getMessage());
    }
    return $ajaxResponse;

}



/**
 *  deletes a security descriptor
 *      -> aricle and so from db (via article-deletion)
 *
 * @param <string> $sdId
 * @return <AjaxResponse>200: ok | 400: failure with error-message
 */
function haclDeleteSecurityDescriptor($sdIds) {
	global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];

    $xml = new SimpleXMLElement($sdIds);
    $result = wfMsg("hacl_nothing_deleted");
    $success = true;
    foreach ($xml->xpath('//sd') as $sd) {
        $sd = unescape($sd);
        if ($sd != null) {
            try {
            	$sdObj = HACLSecurityDescriptor::newFromName("$ns:$sd");
            	// Check if the current user can modify the SD
            	if ($sdObj->userCanModify(null)) {
	                $sdarticle = new Article(Title::newFromText("$ns:$sd"));
	                $sdarticle->doDelete("gui-deletion");
            	} else {
	                $result .= wfMsg('hacl_user_cannot_delete_right',"$ns:$sd");
	                $success = false;
            	}
                if ($success) {
                	$result = wfMsg('hacl_deleteSecurityDescriptor_1');
                }
            } catch(Exception $e ) {
                $result .= "Error while deleting $ns:$sd. ";
                $success = false;
            }
        }
    }
    
    $ajaxResponse = new AjaxResponse();

	$ajaxResponse->setContentType("json");
	$ajaxResponse->setResponseCode($success ? 200 : 400);
	$ajaxResponse->addText($result);

	return $ajaxResponse;
}


/**
 *  saves a securitydescriptor while including all temp-saved-rights
 *
 * @global <User> $wgUser
 * @global <type> $haclgContLang
 * @param <string> xml containing sd-related information
 * @return <AjaxResponse> success message | error-message
 */
function haclSaveSecurityDescriptor($secDescXml) {
    global $wgUser;
    global $haclgContLang, $wgContLang;
    
    $userNS = $wgContLang->getNsText(NS_USER);
    $template = $haclgContLang->getSDTemplateName();
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    $secDescXmlInstance = new SimpleXMLElement($secDescXml);

	// Retrieve all language dependent strings for parser functions
	$predefRight    = $haclgContLang->getParserFunction(HACLLanguage::PF_PREDEFINED_RIGHT);
	$rights         = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_RIGHTS);
	$access         = $haclgContLang->getParserFunction(HACLLanguage::PF_ACCESS);
	$propertyAccess = $haclgContLang->getParserFunction(HACLLanguage::PF_PROPERTY_ACCESS);
	$member         = $haclgContLang->getParserFunction(HACLLanguage::PF_MEMBER);		
	$members        = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_MEMBERS);
	$manageRights   = $haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_RIGHTS);		
	$assignedTo     = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_ASSIGNED_TO);
	$actionsPFP     = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_ACTIONS);
	$descriptionPFP = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_DESCRIPTION);
	$name     		= $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_NAME);
	$categorySD     = "[[".$haclgContLang->getCategory(HACLLanguage::CAT_SECURITY_DESCRIPTOR)."]]";
	$categoryRight  = "[[".$haclgContLang->getCategory(HACLLanguage::CAT_RIGHT)."]]";
	
    try {
        $inline = "";
        $modificationSaved = false;
        // building rights
        foreach ($_SESSION['temprights'] as $tempright) {

            $xml = new SimpleXMLElement($tempright);
            $actions = 0;
            $groups = '';
            $users = '';
            $type = $xml->type;

            if ($type == "template") {
                $inline .= "\n{{#$predefRight:$rights=".unescape($xml->name).'}}';
            } else {

                $protect = null;
                foreach ($secDescXmlInstance->xpath('//protect') as $item) {
                    $protect = (String)$item;
                }

                $description = $xml->description ? unescape($xml->description) : '';
                $autoDescription = $xml->autoDescription ? unescape($xml->autoDescription) : '';
                $rightName = $xml->name ? unescape($xml->name) : '';

                switch ($type) {
                    case "privateuse":
                        $users = "$userNS:".$wgUser->getName();
                        break;
                    case "individual":
                    case "private":
                        foreach ($xml->xpath('//group') as $group) {
                            if ($groups == '') {
                                $groups = haclAddGroupPrefix(unescape((string)$group));
                            } else {
                                $groups = $groups.",".haclAddGroupPrefix(unescape((string)$group));
                            }
                        }
                        foreach ($xml->xpath('//user') as $user) {
                            if ($users == '') {
                                $users = "$userNS:".unescape((string)$user);
                            } else {
                                $users = $users.",$userNS:".unescape((string)$user);
                            }
                        }
                        break;
                    case "modification":
                        $foundModrights = false;
                        foreach ($xml->xpath('//group') as $group) {
                            $foundModrights = true;
                            if ($groups == '') {
                                $groups = haclAddGroupPrefix((string)$group);
                            } else {
                                $groups = $groups.",".unescape((string)haclAddGroupPrefix($group));
                            }
                        }
                        foreach ($xml->xpath('//user') as $user) {
                            $foundModrights = true;
                            if ($users == '') {
                                $users = "$userNS:".unescape((string)$user);
                            } else {
                                $users = $users.",$userNS:".unescape((string)$user);
                            }

                        }
                        if (!$foundModrights) {
                            $users = "$userNS:".$wgUser->getName();
                        }
                        break;
                    case "allusersregistered":
                        $users = "#";
                        break;
                    case "allusersanonymous":
                        $users = "*";
                        break;
                    case "allusers":
                        $users = "*,#";
                        break;
                }

                // Rights must be translated 
                $en = new HACLLanguageEn();
                $enActionNames = $en->getActionNames();
                $contentActionNames = $haclgContLang->getActionNames();
                $translation = array();
                foreach ($enActionNames as $k => $an) {
                	$translation[$an] = $contentActionNames[$k];
                }
                
                $actions = "";
                foreach ($xml->xpath('//right') as $right) {
                    if ((string)$right != "fullaccess") {
                        if ($actions == '') {
                            $actions = $translation[(string)$right];
                        } else {
                            $actions .= ",".$translation[(string)$right];
                        }
                    }
                }
 
                if ($type <> "modification") {
					//normal rights
                    if (!$protect == null && $protect == "property") {
                        $inline .= "\n{{#$propertyAccess: $assignedTo=";
                    } else {
                        $inline .= "\n{{#$access: $assignedTo=";
                    }
                    if ($groups <> '') $inline .= $groups;
                    if (($users <> '') && ($groups <> '')) $inline .= ','.$users;
                    if (($users <> '') && ($groups == '')) $inline .= $users;
                    $inline .= "\n |$actionsPFP=$actions\n |$descriptionPFP=$description\n |$name=$rightName}}";

                } else {
					//modification rights
                    $inline .= "\n{{#$manageRights: $assignedTo=";
                    if ($groups <> '') $inline .= $groups;
                    if (($users <> '') && ($groups <> '')) $inline .= ','.$users;
                    if (($users <> '') && ($groups == '')) $inline .= $users;

                    $inline .='}}';
                    $modificationSaved = true;
                }

                //line break
                $inline .= "\n";

            }

        } // end of foreach
        if ($modificationSaved == false) {
            $inline .= "\n{{#$manageRights: $assignedTo=$userNS:".$wgUser->getName()."}}";
        }


        $aclType = (String)$secDescXmlInstance->ACLType;

        if ($aclType == "createACL") {
        	$inline .= "\n$categorySD";
        } else if ($aclType == "createAclUserTemplate") {
        	$inline .= "\n$categorySD";
        } else {
        	$inline .= "\n$categoryRight";
        }

        $SDName = $secDescXmlInstance->name;

        $aclName = unescape((string)$SDName);

        // create article for security descriptor

        $sdarticle = new Article(Title::newFromText($aclName));


        $sdarticle->doEdit($inline, "");
        $SDID = $sdarticle->getID();

        $articlename = preg_replace("/$ns:Page\//is", "", $aclName);

        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->setContentType("json");
        $ajaxResponse->setResponseCode(200);
        $ajaxResponse->addText($articlename);

    } catch (Exception  $e) {
        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->setResponseCode(400);
        $ajaxResponse->addText($e->getMessage());
    }
    return $ajaxResponse;

}

/**
 *  saves a group while including groupsettings from temp-saved groupsetting in session
 *
 * @global <type> $haclgContLang
 * @global <User> $wgUser
 * @param <string> xml formated group info (also includes modification-rights)
 * @param <string> parentgroup of that group (for root-level groups: null)
 * @return <AjaxResponse> success message | error-message
 */
function haclSaveGroup($manageRightsXml,$parentgroup = null) {
	global $haclgContLang, $wgContLang;
	global $wgUser;

	$userNS = $wgContLang->getNsText(NS_USER);
	$template = $haclgContLang->getSDTemplateName();
	$predefinedRightName = $haclgContLang->getPredefinedRightName();
	$ns = $haclgContLang->getNamespaces();
	$ns = $ns[HACL_NS_ACL];
	$groupPrefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP);

	if ($parentgroup == wfMsg('hacl_root_group') || $parentgroup == "undefined") {
		$parentgroup = null;
	}

	if (!array_key_exists("tempgroups", $_SESSION)) {
		$ajaxResponse = new AjaxResponse();
		$ajaxResponse->setResponseCode(400);
		$ajaxResponse->addText(wfMsg('hacl_save_group_settings_first'));
		return $ajaxResponse;
	}

	$groupXml = $_SESSION['tempgroups'];
	$groups = "";
	$users = "";
	$mrgroups = "";
	$mrusers = "";

	try {
		/* create group inline from 2 xmls */
		//get group members
		$groupXml = new SimpleXMLElement($groupXml);
		// securitydescriptor-part
		foreach ($groupXml->xpath('//group') as $group) {
			if (trim($group) != "") {
				if ($groups == '') {
					$groups = haclAddGroupPrefix(unescape((string)$group));
				} else {
					$groups .= ",".haclAddGroupPrefix(unescape((string)$group));
				}
			}
		}
		foreach ($groupXml->xpath('//user') as $user) {
			if (trim($user)!="") {
				if ($users == '') {
					$users = $userNS.':'.unescape((string)$user);
				} else {
					$users .= ",".$userNS.':'.unescape((string)$user);
				}
			}
		}

		//get manage rights
		$manageRightsXml = new SimpleXMLElement($manageRightsXml);
		// securitydescriptor-part
		foreach ($manageRightsXml->xpath('//group') as $group) {
			if (trim($group)) {
				if ($mrgroups == '') {
					$mrgroups = haclAddGroupPrefix(unescape((string)$group));
				} else {
					$mrgroups .= ",".haclAddGroupPrefix(unescape((string)$group));
				}
			}
		}
		foreach ($manageRightsXml->xpath('//user') as $user) {
			if (trim($user) !="") {
				if ($mrusers == '') {
					$mrusers = $userNS.':'.unescape((string)$user);
				} else {
					$mrusers .= ",".$userNS.':'.unescape((string)$user);
				}
			}
		}

		$groupName = unescape($groupXml->name);
		
		// Retrieve all language dependent strings for parser functions
		$member  = $haclgContLang->getParserFunction(HACLLanguage::PF_MEMBER);		
		$members = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_MEMBERS);
		$manageGroup  = $haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_GROUP);		
		$assignedTo   = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_ASSIGNED_TO);
		$categoryGroup = "[[".$haclgContLang->getCategory(HACLLanguage::CAT_GROUP)."]]";
				
		// create article for security descriptor
		$sdarticle = new Article(Title::newFromText("$ns:".$groupPrefix.'/'.$groupName));

		if ($users == "") {
			$inline = "\n{{#$member:$members=$groups}}";
		} elseif ($groups == "") {
			$inline = "\n{{#$member:$members=$users}}";
		} else {
			$inline = "\n{{#$member:$members=$users,$groups}}";
		}

		if ($mrgroups == "") {
			$inline .= "\n{{#$manageGroup:$assignedTo=$mrusers}}".
        			   "\n$categoryGroup";
		} elseif ($mrusers == "") {
			$inline .= "\n{{#$manageGroup:$assignedTo=$mrgroups}}".
        			   "\n$categoryGroup";
		} elseif ($mrgroups != "" && $mrusers != "") {
			$inline .= "\n{{#$manageGroup:$assignedTo=$mrgroups,$mrusers}}".
        			   "\n$categoryGroup";
		} else {
			$inline .= "\n{{#$manageGroup:$assignedTo=$userNS:".$wgUser->getName()."}}".
        			   "\n$categoryGroup";
		}

		$sdarticle->doEdit($inline, "");
		$SDID = $sdarticle->getID();

		// as a new article starts we have to reset the parser
		HACLParserFunctions::getInstance()->reset();

		// new group saved
		// if new group is a subgroup we have to attach it to that
		if ($parentgroup) {
			// now we have to edit the parentgroup's definition
			$parentgroup = haclAddGroupPrefix($parentgroup);

			$parentGroupArray = readGroupDefinition($parentgroup);
			$parentgrouparticle = new Article(Title::newFromText("$ns:".$parentgroup));
			#echo ("opening article with title:ACL:".$parentgroup);

			// building new arent inline
			$parent_memuser = "";
			// setting the new group as first member
			$parent_memgroup = "$groupPrefix/$groupName";
			$parent_user = "";
			$parent_group = "";
			if (isset($parentGroupArray['members']['group'])) {
				foreach ($parentGroupArray['members']['group'] as $group) {
					if (trim($group)) {
						if ($parent_memgroup == '') {
							$parent_memgroup = (string)$group;
						} else {
							$parent_memgroup .= ",".(string)$group;
						}
					}
				}
			}
			if (isset($parentGroupArray['members']['user'])) {
				foreach ($parentGroupArray['members']['user'] as $user) {
					if (trim($user) !="") {
						if ($parent_memuser == '') {
							$parent_memuser = $userNS.':'.(string)$user;
						} else {
							$parent_memuser .= ",".$userNS.':'.(string)$user;
						}
					}
				}
			}
			if (isset($parentGroupArray['manage']['user'])) {

				foreach ($parentGroupArray['manage']['user'] as $user) {
					if (trim($user)) {
						if ($parent_user == '') {
							$parent_user = $userNS.':'.(string)$user;
						} else {
							$parent_user .= ",".$userNS.':'.(string)$user;
						}
					}
				}
			}
			if (isset($parentGroupArray['manage']['group'])) {
				foreach ($parentGroupArray['manage']['group'] as $group) {
					if (trim($group) !="") {
						if ($parent_group == '') {
							$parent_group = (string)$group;
						} else {
							$parent_group .= (string)$group;
						}
					}
				}
			}
			if ($parent_memuser == "") {
				$newparentinline = "\n{{#$member:$members=$parent_memgroup}}";
			} elseif ($parent_memgroup == "") {
				$newparentinline = "\n{{#$member:$members=$parent_memuser}}";
			} else {
				$newparentinline = "\n{{#$member:$members=$parent_memuser,$parent_memgroup}}";
			}

			if ($parent_group == "") {
				$newparentinline .= "\n{{#$manageGroup:$assignedTo=$parent_user}}".
        			   				"\n$categoryGroup";
			} elseif ($parent_user == "") {
				$newparentinline .= "\n{{#$manageGroup:$assignedTo=$parent_group}}".
        			   				"\n$categoryGroup";
			} else {
				$newparentinline .= "\n{{#$manageGroup:$assignedTo=$parent_group,$parent_user}}".
        			   				"\n$categoryGroup";
			}

			//echo ("trying to insert following inline:".$newparentinline);
			$parentgrouparticle->doEdit($newparentinline,"");


		}
		$ajaxResponse = new AjaxResponse();
		$ajaxResponse->setContentType("json");
		$ajaxResponse->setResponseCode(200);
		$ajaxResponse->addText("descriptor saved".$SDID );

	} catch (Exception  $e) {
		$ajaxResponse = new AjaxResponse();
		$ajaxResponse->setResponseCode(400);
		$ajaxResponse->addText($e->getMessage());
	}
	return $ajaxResponse;

}

function readGroupDefinition($groupName) {
    $result = array();
    $group = HACLGroup::newFromName($groupName);
    $temp = $group->getGroups(HACLGroup::OBJECT);
    foreach ($temp as $item) {
        $result['members']['group'][] = $item->getGroupName();
    }
    $temp = null;
    $temp = $group->getUsers(HACLGroup::OBJECT);
    foreach ($temp as $item) {
        $result['members']['user'][] = $item->getName();
    }
    $temp = null;
    $temp = $group->getManageGroups();

    foreach ($temp as $groupID) {
        $result['manage']['group'][] = HACLGroup::nameForID($groupID);
    }
    $temp = null;
    $temp = $group->getManageUsers();
    foreach ($temp as $userID) {
        $db =& wfGetDB( DB_SLAVE );
        $gt = $db->tableName('user');
        $sql = "SELECT * FROM $gt where user_id = ".$userID;
        $res = $db->query($sql);
        $row = $db->fetchObject($res);
        $result['manage']['user'][] = $row->user_name;
    }

    return $result;
}

/**
 * saves complete whitelist for wiki
 *
 * @global <type> $haclgContLang
 * @param <string> xml-formed whitelist
 * @return <AjaxResponse> success message | error-message
 */
function haclSaveWhitelist($whitelistXml) {
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    
    $whitelistPF  = $haclgContLang->getParserFunction(HACLLanguage::PF_WHITELIST);
	$pagesPFP = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_PAGES);
    
    try {
    //get group members
        $oldWhitelists = HACLWhitelist::newFromDB();
        $pages = "";

        foreach ($oldWhitelists->getPages() as $item) {
            if ($pages == '') {
                $pages = unescape((string)$item);
            } else {
                $pages .= ",".unescape((string)$item);
            }
        }

        $whitelistXml = new SimpleXMLElement($whitelistXml);
        foreach ($whitelistXml->xpath('//page') as $page) {
            if ($pages == '') {
                $pages = unescape((string)$page);
            } else {
                $pages .= ",".unescape((string)$page);
            }
        }


        // create article
        $sdarticle = new Article(Title::newFromText($haclgContLang->getWhitelist()));
        $inline = "{{#$whitelistPF:$pagesPFP=$pages}}";

        $sdarticle->doEdit($inline, "");
        $SDID = $sdarticle->getID();

        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->setContentType("json");
        $ajaxResponse->setResponseCode(200);
        $ajaxResponse->addText($inline );

    } catch (Exception   $e) {
        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->setResponseCode(400);
        $ajaxResponse->addText($e->getMessage());
    }
    return $ajaxResponse;

}

/**
 * delivers data for autocompleter
 *
 * @global <type> $haclgContLang
 * @global <type> $wgExtraNamespaces
 * @global <User> $wgUser
 * @param <string> search-string
 * @param <string> type of object (e.g. page, category, ...)
 * @return <string> json-formed source for datatable
 */
function haclGetAutocompleteDocuments($subName,$type) {
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $nsPrefix = $ns[HACL_NS_ACL];
    $petPrefix = $haclgContLang->getPetPrefix(HACLSecurityDescriptor::PET_NAMESPACE);
    global $wgCanonicalNamespaceNames,$wgUser;


    $realnametype = "";
    if ($type == "page") {
        $realnametype = "Page";
    } elseif ($type == "category") {
        $realnametype = "Category";
    } elseif ($type == "property") {
        $realnametype = "Proptery";
    }

    $a = array();
    if ($type == "namespace") {
    	$namespaces = $wgCanonicalNamespaceNames;
    	$namespaces[] = $nsMain = $haclgContLang->getLabelOfNSMain();
    	global $haclgUnprotectableNamespaces;
        foreach ($namespaces as $ns) {
        	if (in_array($ns, $haclgUnprotectableNamespaces) 
        	    || ($ns == $nsMain && in_array("Main", $haclgUnprotectableNamespaces))) {
        		// The namespace can not be protected.
        		continue;
        	} 
            $addThatItem = true;
            $SDName = "$nsPrefix:$petPrefix/$ns";
            try {
                $sd = HACLSecurityDescriptor::newFromName($SDName);
                if (!$sd->userCanModify($wgUser->getName())) {
                    $addThatItem = false;
                }
            }
            catch(Exception $e ) {}

            if ($addThatItem && preg_match("/$subName/is",$ns)) {
                $temp = array("name"=>$ns);
                $a['records'][] = $temp;
            }
        }
    } else {
        foreach (HACLStorage::getDatabase()->getArticles($subName,true,$type) as $item) {
            $addThatItem = true;
            $itemname = $item["name"];
            $SDName = "$ns:$realnametype/$itemname";
            try {
                $sd = HACLSecurityDescriptor::newFromName($SDName);
                if (!$sd->userCanModify($wgUser->getName())) {
                    $addThatItem = false;
                }
            }
            catch(Exception $e ) {}
            if ($addThatItem) {
                if (preg_match('/Property\//is',$itemname) ) {
                    $item["name"] = substr($itemname,9);
                    $a['records'][] = $item;
                } elseif ($type == "category" && preg_match('/Category\//is',$itemname)) {
                    $item["name"] = substr($itemname,9);
                    $a['records'][] = $item;
                } else {
                    $a['records'][] = $item;
                }
            }
        }
    }

    return(json_encode($a));

}


/**
 *
 * @param <String>  selected group in tree
 * @param <String>  column to sort by
 * @param <String>  sort-direction
 * @param <Int>     first index of resultlist (paging)
 * @param <Int>     total results (paging)
 * @return <JSON>   return array of users
 */
function haclGetUsersForUserTable($selectedGroup,$sort,$dir,$startIndex,$results,$filter) {

    global $wgUser;
    global $wgTitle;
    $a = array();
    $a['recordsReturned'] = 10;
    #$a['totalrecords'] = 0;
    $a['startIndex'] = $startIndex;
    $a['sort'] = $sort;
    $a['dir'] = $dir;
    $a['pageSize'] = 10;
    $a['records'] = array();

    $tmpstring = "";

    if ($selectedGroup == 'all' || $selectedGroup == '') {

        $db =& wfGetDB( DB_SLAVE );
        $gt = $db->tableName('user');
        $sql = "SELECT * FROM $gt order by user_name";

        $res = $db->query($sql);
        while ($row = $db->fetchObject($res)) {
            $tmpstring = "";
            $tmlGroups = HACLGroup::getGroupsOfMember($row->user_id);
            foreach ($tmlGroups as $key => $val) {
                if (!strpos($tmpstring, $val["name"])) {
                    $tmpstring .= haclRemoveGroupPrefix($val["name"]).",";
                }
            }
            //$tmpstring = '<br /><span style="font-size:8px;">'.$tmpstring."</span>";

            $a['records'][] = array('name'    => $row->user_name,
                                    'groups'  => $tmpstring,
                                    'id'      => $row->user_id,
                                    'checked' => 'false');
        }

        $db->freeResult($res);

    } else {
        $group = HACLGroup::newFromName(haclAddGroupPrefix($selectedGroup));
        $groupUsers = $group->getUsers(HACLGroup::OBJECT);
        foreach ($groupUsers as $key => $val) {
            $tempgroup = array();
            foreach (HACLGroup::getGroupsOfMember($val->getId()) as $blubb) {
                $tempgroup[] = haclRemoveGroupPrefix($blubb['name']);
            }
            $a['records'][] = array('name'    => $val->getName(),
                                    'id'      => $val->getId(),
                                    'checked' => 'false', 
                                    'groups'  => $tempgroup); 

        }
    }

    // doing filtering php-based
    if ($filter !="" && $filter != null) {
        $filteredResults = array();
        $pattern = "/".$filter."/is";
        foreach ($a['records'] as $record) {
            if (preg_match($pattern, $record["name"])) {
                $filteredResults[] = $record;
            }
        }
        $a['records'] = $filteredResults;
    }
    #print_r($a['records']);

    // generating paging-stuff
    $a['totalRecords'] = sizeof($a['records']);
    $a['records'] = array_slice($a['records'],$startIndex,$a['pageSize']);
    $a['recordsReturned'] = sizeof($a['records']);

    return(json_encode($a));

}


/**
 * return users, that are member of a specified group
 *
 * @global <User> $wgUser
 * @global <string> $wgTitle
 * @param <string> groupname
 * @return <string> json-formed list of users
 */
function haclGetUsersForGroups($groupsstring) {
    if ($groupsstring == "") {
        return json_encode(array());
    }
    global $wgUser;
    global $wgTitle;
    $a = array();

    $groupsarray = explode(",",$groupsstring);
    $result = array();

    foreach ($groupsarray as $group) {
        $group = HACLGroup::newFromName(haclAddGroupPrefix($group));
        $groupUsers = $group->getUsers(HACLGroup::OBJECT);
        $finalGroupusers = array();
        foreach ($groupUsers as $user) {
            $temp["id"] = $user->getId();
            $temp["name"] = $user->getName();
            $finalGroupuser[] = $temp;
        }

    }
    foreach ($finalGroupuser as $user) {
        $reallyAddToArray = true;
        foreach ($result as $test) {
            if ($test["name"] == $user["name"]) {
                $reallyAddToArray = false;
            }
        }
        if ($reallyAddToArray) {
            $tmpstring = "";
            $tmlGroups = HACLGroup::getGroupsOfMember($user["id"]);
            foreach ($tmlGroups as $key => $val) {
                $tmpstring .= haclRemoveGroupPrefix($val["name"]).",";
            }
            $temp = array('name'=>$user['name'],'groups'=>$tmpstring);
            $result[] = $temp;
        }
    }

    return(json_encode($result));

}


/**
 * delivers data to treeview in rightspanel
 *
 * @param <string> group, which will be expanded
 * @param <string> search-string from filter
 * @param <boolean> load all groups recursivly (no dynamic loading)
 * @param <integer> internaly used indicator for recursion-level
 * @param <array> internally used array for recursion
 * @return <string> json-formed list of groups
 */
function haclGetGroupsForRightPanel($clickedGroup, $search=null, $recursive=false, $level=0,$subgroupsToCall = null) {

    $array = array();
    if ($search) 
    	$recursive = true;

    // return first level
    global $haclgContLang;
	$rootGroup = wfMsg('hacl_root_group');   
    if ($clickedGroup == 'all' || $clickedGroup == $rootGroup) {
    //get level 0 groups
        if ($level == 0) {
            $groups = HACLStorage::getDatabase()->getGroups();
        } else {
            $groups = $subgroupsToCall;
        }
        foreach ( $groups as $key => $value) {
            if ($recursive) {
                $parent = HACLGroup::newFromName($value->getGroupName());
                $subgroupsToCall = $parent->getGroups(HACLGroup::OBJECT);
                if (sizeof($subgroupsToCall)> 0 || $level == 0) {
                    $subgroups = haclGetGroupsForRightPanel("all", $search, true, $level+1,$subgroupsToCall);
                }

                if (!$search 
                    || stripos(haclRemoveGroupPrefix($value->getGroupName()),$search) !== false 
                    || (isset($subgroups) 
                        && (sizeof($subgroups) > 0))) {
					$name = haclRemoveGroupPrefix($value->getGroupName());
					$g = in_array($name, $array) ? $array[$name] : null;
					if (is_null($g) || $g['type'] == 'HaloACL') {
						$tempgroup = array('name'   => $name,
						                   'id'     => $value->getGroupId(),
						                   'checked'=> 'false',
										   'canBeModified' => $value->canBeModified(),
										   'type' => $value->getType());
	                    if (isset($subgroups)) {
	                        $tempgroup['children'] = $subgroups;
	                    }
	                    $array[$name] = $tempgroup;
					}
                }

            // non recursive part
            } else {
                if (!$search 
                    || preg_match("/$search/is", haclRemoveGroupPrefix($value->getGroupName()))) {
                    try {
                        $subparent = HACLGroup::newFromName($value->getGroupName());
                        $subgroups = $subparent->getGroups(HACLGroup::OBJECT);
						$name = haclRemoveGroupPrefix($value->getGroupName());
						$g = in_array($name, $array) ? $array[$name] : null;
						if (is_null($g) || $g['type'] == 'HaloACL') {
							$tempgroup = array('name'    => $name,
											   'id'      => $value->getGroupId(),
											   'checked' => 'false',
											   'canBeModified' => $value->canBeModified(),
											   'type' => $value->getType());
	                        if (sizeof($subgroups) == 0 && !$search) {
	                            $tempgroup['children'] = '';
	                        }
	                        $array[$name] = $tempgroup;
						}
                    } catch (HACLGroupException $e) {}

                }
            }
        }

    } else {
        $parent = HACLGroup::newFromName(haclAddGroupPrefix($clickedGroup));
        //groups
        $groups = $parent->getGroups(HACLGroup::OBJECT);
        foreach ( $groups as $key => $value ) {

            $subparent = HACLGroup::newFromName($value->getGroupName());
            $subgroups = $subparent->getGroups(HACLGroup::OBJECT);
			$name = haclRemoveGroupPrefix($value->getGroupName());
			$g = in_array($name, $array) ? $array[$name] : null;
			if (is_null($g) || $g['type'] == 'HaloACL') {
				$tempgroup = array('name'    => $name,
	                               'id'      => $value->getGroupId(),
	                               'checked' => 'false',
								   'canBeModified' => $value->canBeModified(),
								   'type' => $value->getType());
	            if (sizeof($subgroups) == 0) {
	                $tempgroup['children'] = '';
	            }
	            $array[$name] = $tempgroup;
			}
        }
    }
    $array = array_values($array);
    //only json encode final result
    if ($level == 0) {
        $array = (json_encode($array));
    }

    return $array;

}
/**
 * delivers data to treeview in managegroups
 *
 * @param <string> group, which will be expanded
 * @param <string> search-string from filter
 * @param <boolean> load all groups recursivly (no dynamic loading)
 * @param <integer> internaly used indicator for recursion-level
 * @param <array> internally used array for recursion
 * @return <string> json-formed list of groups
 */
function haclGetGroupsForManageUser($clickedGroup, $search=null, 
									$recursive=false, $level=0,
									$subgroupsToCall=null) {
    global $wgUser,$haclCrossTemplateAccess;
    $array = array();
    if ($search) {
    	$recursive = true;
    }

    // return first level
    if ($clickedGroup == 'all' || $clickedGroup == wfMsg('hacl_root_group')) {
    //get level 0 groups
        if ($level == 0) {
            $groups = HACLStorage::getDatabase()->getGroups();
        } else {
            $groups = $subgroupsToCall;
        }
        foreach ( $groups as $key => $value) {
        	$valid = $value->checkIntegrity();
            if ($recursive) {

                $parent = HACLGroup::newFromName($value->getGroupName());
                $subgroupsToCall = $parent->getGroups(HACLGroup::OBJECT);
                if (sizeof($subgroupsToCall)> 0 || $level == 0) {
                    $subgroups = haclGetGroupsForManageUser("all", $search, true, $level+1,$subgroupsToCall);
                }
                if (($value->userCanModify($wgUser->getName()) 
                     || array_intersect_key($haclCrossTemplateAccess, $wgUser->getGroups()) != null)
                    && $value->canBeModified()) {
                   	if (!$search || stripos(haclRemoveGroupPrefix($value->getGroupName()),$search) !== false || (isset($subgroups) && (sizeof($subgroups) > 0))) {
                    	$tempgroup = array('name'=>haclRemoveGroupPrefix($value->getGroupName()),
                                           'id'=>$value->getGroupId(),
                                           'checked'=>'false', 
                                           'children'=>$subgroups,
                                           'description'=>$value->getGroupDescription(),
			                               'valid' => $valid);
                    	if (isset($subgroups)) {
                    		$tempgroup['children'] = $subgroups;
                        }
                        $array[] = $tempgroup;
                    }
                }

            // non recursive part
            } else {
                if (($value->userCanModify($wgUser->getName()) 
                     || array_intersect_key($haclCrossTemplateAccess, $wgUser->getGroups()) != null)
                    && $value->canBeModified()) {

                    if (!$search || preg_match("/$search/is",haclRemoveGroupPrefix($value->getGroupName()))) {

                        try {
                            $subparent = HACLGroup::newFromName($value->getGroupName());
                            $subgroups = $subparent->getGroups(HACLGroup::OBJECT);
							$tempgroup = array('name'=>haclRemoveGroupPrefix($value->getGroupName()),
                                			   'id'=>$value->getGroupId(),
                                			   'checked'=>'false',
                                			   'description'=>$value->getGroupDescription(),
                            			   	   'valid' => $valid);
                            if (sizeof($subgroups) == 0 && !$search) {
			                    $tempgroup['children'] = '';
                            }
                            $array[] = $tempgroup;
                        } catch (HACLGroupException $e) { }

                    }
                }
            }
        }

    } else {
        $parent = HACLGroup::newFromName(haclAddGroupPrefix($clickedGroup));
        //groups
        $groups = $parent->getGroups(HACLGroup::OBJECT);
        foreach ( $groups as $key => $value ) {
        	if (($value->userCanModify($wgUser->getName())
        	     || array_intersect_key($haclCrossTemplateAccess, $wgUser->getGroups()) != null)
        	    && $value->canBeModified()) {
        		$valid = $value->checkIntegrity();
            	
                $subparent = HACLGroup::newFromName($value->getGroupName());
                $subgroups = $subparent->getGroups(HACLGroup::OBJECT);
				$tempgroup = array('name'=>haclRemoveGroupPrefix($value->getGroupName()),
                    			   'id'=>$value->getGroupId(),
                    			   'checked'=>'false',
                    			   'description'=>$value->getGroupDescription(),
                            	   'valid' => $valid);
                if (sizeof($subgroups) == 0) {
                    $tempgroup['children'] = '';
                }
                $array[] = $tempgroup;
            }
        }
    }



    //only json encode final result
    if ($level == 0) {
        $array = (json_encode($array));
    }

    return $array;


}

/**
 *
 * Delivers per-type-filtered ACLs for the Manage ACLs view
 * for ACL management, user template views
 *
 * @param <XML>     selected types
 * @param <string>  search-string
 * @return <JSON>   json from first-level-childs of the query-group; not all childs!
 */
function haclGetACLs($typeXML, $filter = null) {
    global $wgUser;
    global $haclCrossTemplateAccess;
    global $haclgContLang;
    $username = $wgUser->getName();

    $template = $haclgContLang->getSDTemplateName();


    $types = array();

    if ($typeXML == "all") {
        $types[] = "all";
    } else {
        $typeXML = new SimpleXMLElement($typeXML);
        foreach ($typeXML->xpath('//type') as $type) {
            $types[] = $type;
        }
    }

    $dontCheckForCanMod = false;
    for($i=0;$i<sizeof($types);$i++) {
        if ($types[$i] == "acltemplate_nofilter") {
            $dontCheckForCanMod = true;
            $types[$i] = "acltemplate";
        }
    }

    $array = array();

    $SDs = HACLStorage::getDatabase()->getSDs($types);

    foreach ( $SDs as $key => $SD) {
 		// check integrity of SD
 		$valid = $SD->checkIntegrity();
 		   	
	    // processing default user templates
        if (preg_match("/$template\//is",$SD->getSDName())) {
            if (($SD->getSDName() == "$template/$username") ||$dontCheckForCanMod || (array_intersect($wgUser->getGroups(), $haclCrossTemplateAccess) != null)) {
                $tempRights = array();
                foreach ($SD->getInlineRights(false) as $rightId) {
                    try {
                        $tempright = HACLRight::newFromID($rightId);
                        $tempRights[] = array('id'=>$rightId, 'name'=>$tempright->getName(),'description'=>$tempright->getDescription());
                    }catch(Exception $e ) {}
                }
                foreach ($SD->getPredefinedRights(false) as $subSdId) {
                    try {
                        $tempright = HACLSecurityDescriptor::newFromID($subSdId);
                        $tempRights[] = array('id'=>$subSdId, 'description'=>"Template - ".$tempright->getSDName());
                    }catch(Exception $e ) {}
                }
                $tempSD = array('id'=>$SD->getSDID(), 
                				'name'=>$SD->getSDName(), 
                				'rights'=>$tempRights, 
                				'valid' => $valid);
                $array[] = $tempSD;
            }

        // processing other acls
        } elseif ($SD->userCanModify($wgUser->getName()) ||$dontCheckForCanMod || array_intersect($wgUser->getGroups(), $haclCrossTemplateAccess)) {
            $tempRights = array();
            foreach ($SD->getInlineRights(false) as $rightId) {
                try {
                    $tempright = HACLRight::newFromID($rightId);
                    $tempRights[] = array('id'=>$rightId, 'name'=>$tempright->getName(),'description'=>$tempright->getDescription());
                }catch(Exception $e ) {}
            }
            foreach ($SD->getPredefinedRights(false) as $subSdId) {
                try {
                    $tempright = HACLSecurityDescriptor::newFromID($subSdId);
                    $tempRights[] = array('id'=>$subSdId, 'description'=>"Template - ".$tempright->getSDName());
                }catch(Exception $e ) {}
            }
            $tempSD = array('id'=>$SD->getSDID(), 
            				'name'=>$SD->getSDName(), 
            				'rights'=>$tempRights, 
                			'valid' => $valid);
            $array[] = $tempSD;
        }
    }

    $result = array();
    if ($filter != null) {
        foreach ($array as $item) {
            if (preg_match("/$filter/is", $item["name"])) {
                $result[] = $item;
            }
        }
    } else {
        $result = $array;
    }

    return (json_encode($result));

}


/**
 *
 * Delivers an ACL incl. rights selected by name of descriptor article
 * for ACL management, user template views
 *
 * @param <String>  name of ACL Descriptor article
 * @return <JSON>   json from first-level-childs of the query-group; not all childs!
 */
function getACLByName($name) {

    $array = array();

    $SD = HACLSecurityDescriptor::newFromName($name);

    $tempRights = array();

    //attach inline right texts
    foreach (getInlineRightsOfSDs(array($SD->getSDID())) as $key2 => $rightId) {
        $tempright = HACLRight::newFromID($rightId);
        $tempRights[] = array('id'=>$rightId, 'description'=>$tempright->getDescription());
    }

    $tempSD = array('id'=>$SD->getSDID(), 'name'=>$SD->getSDName(), 'rights'=>$tempRights);
    $array[] = $tempSD;


    return (json_encode($array));
}


/**
 *
 * Delivers an ACL incl. rights selected by id of descriptor
 *
 * @param <int>  id of ACL Descriptor
 * @return <JSON>   json from first-level-childs of the query-group; not all childs!
 */
function getACLById($id) {

    $array = array();

    $SD = HACLSecurityDescriptor::newFromId($id);

    $tempRights = array();

    //attach inline right texts
    foreach (getInlineRightsOfSDs(array($SD->getSDID())) as $key2 => $rightId) {
        $tempright = HACLRight::newFromID($rightId);
        $tempRights[] = array('id'=>$rightId, 'description'=>$tempright->getDescription());
    }

    $tempSD = array('id'=>$SD->getSDID(), 'name'=>$SD->getSDName(), 'rights'=>$tempRights);
    $array[] = $tempSD;


    return (json_encode($array));
}



/**
 *
 * Delivers whitelist pages for Manage Whitelist view
 *
 * @return <JSON>   json from first-level-childs of the query-group; not all childs!
 */
function haclGetWhitelistPages($query,$sort,$dir,$startIndex,$results,$filter) {

    $a = array();
    $a['recordsReturned'] = 2;
    $a['totalRecords'] = 10;
    $a['startIndex'] = 0;
    $a['sort'] = null;
    $a['dir'] = "asc";
    $a['pageSize'] = 5;

    $test = HACLWhitelist::newFromDB();

    $a['records'] = array();
    foreach ($test->getPages() as $item) {
        if ($query == "all" || preg_match('/'.$query.'/is',$item)) {
            $a['records'][] = array('name'=>$item,'checked'=>'false');
        }
    }
    // generating paging-stuff
    $a['totalRecords'] = sizeof($a['records']);
    $a['records'] = array_slice($a['records'],$startIndex,$a['pageSize']);
    $a['recordsReturned'] = sizeof($a['records']);

    return(json_encode($a));

}




/**
 *
 * @return <string>   returns tab-content for manageUser-tab
 */
function haclManageUserContent() {
    clearTempSessionGroup();

    $response = new AjaxResponse();

    $hacl_manageUser_1 = wfMsg('hacl_manageUser_1');
    $hacl_manageUser_2 = wfMsg('hacl_manageUser_2');
    $hacl_manageUser_3 = wfMsg('hacl_manageUser_3');
    $hacl_manageUser_4 = wfMsg('hacl_manageUser_4');
    $hacl_manageUser_5 = wfMsg('hacl_manageUser_5');
    $hacl_manageUser_6 = wfMsg('hacl_manageUser_6');
    $hacl_manageUser_7 = wfMsg('hacl_manageUser_7');
    $hacl_manageUser_8 = wfMsg('hacl_manageUser_8');
    $hacl_manageUser_9 = wfMsg('hacl_manageUser_9');
    $hacl_manageUser_10 = wfMsg('hacl_manageUser_10');


    $dutHeadline = wfMsg('hacl_create_acl_dut_headline');
    $dutInfo = wfMsg('hacl_create_acl_dut_info');
    $dutGeneralHeader = wfMsg('hacl_create_acl_dut_general');
    $dutGeneralDefine = wfMsg('hacl_create_acl_dut_general_definefor');
    $dutGeneralDefinePrivate = wfMsg('hacl_create_acl_dut_general_private_use');
    $dutGeneralDefineAll = wfMsg('hacl_create_acl_dut_general_all');
    $dutGeneralDefineSpecific = wfMsg('hacl_create_acl_dut_general_specific');
    $dutRightsHeader = wfMsg('hacl_create_acl_dut_rights');

    $dutRightsButtonCreate = wfMsg('hacl_create_acl_dut_button_create_right');
    $dutRightsButtonAddTemplate = wfMsg('hacl_create_acl_dut_button_add_template');
    $dutRightsLegend = wfMsg('hacl_create_acl_dut_new_right_legend');
    $dutRightsLegendSaved = wfMsg('hacl_create_acl_dut_new_right_legend_status_saved');
    //$dutRightsLegendNotSaved = wfMsg('hacl_create_acl_dut_new_right_legend_status_notsaved');
    $dutRightsName = wfMsg('hacl_create_acl_dut_new_right_name');
    $dutRightsDefaultName = wfMsg('hacl_create_acl_dut_new_right_defaultname');
    $dutRights = wfMsg('hacl_create_acl_dut_new_right_rights');
    $dutRightsFullAccess = wfMsg('hacl_create_acl_dut_new_right_fullaccess');
    $dutRightsRead = wfMsg('hacl_create_acl_dut_new_right_read');
    $dutRightsEWF = wfMsg('hacl_create_acl_dut_new_right_ewf');
    $dutRightsEdit = wfMsg('hacl_create_acl_dut_new_right_edit');
    $dutRightsCreate = wfMsg('hacl_create_acl_dut_new_right_create');
    $dutRightsMove = wfMsg('hacl_create_acl_dut_new_right_move');
    $dutRightsDelete = wfMsg('hacl_create_acl_dut_new_right_delete');
    $dutRightsAnnotate = wfMsg('hacl_create_acl_dut_new_right_annotate');

    $html = <<<HTML
        <div class="haloacl_manageusers_container">
        <div class="haloacl_tab_content_description">
            <div class="haloacl_manageusers_title">
        $hacl_manageUser_1
            </div>
            <div class="haloacl_manageusers_subtitle">
        $hacl_manageUser_2
            </div>
        </div>
HTML;
    $showing1 = wfMsg('hacl_showing_text');
    $showing2 = wfMsg('hacl_showing_elements_text');

    $delete_text = wfMsg('hacl_delete_link_header');
    $edit_text = wfMsg('hacl_rightsPanel_right_edit');
    $delete_selected = wfMsg('hacl_delete_selected');
    $selected_text = wfMsg('hacl_selected');
    $select_text = wfMsg('hacl_select');

    //FIXME: "Edit" and "Info" via wfMsg()/internat.
    $panelContent = <<<HTML
        <div id="content_manageUsersPanel">
        <div id="haloacl_manageuser_contentmenu">
            <div id="haloacl_manageuser_contentmenu_title">
        $hacl_manageUser_3
            </div>
            <div id="haloacl_manageuser_contentmenu_element">
                <a id="haloacl_managegroups_newsubgroup" href="javascript:YAHOO.haloacl.manageUser.addNewSubgroup(YAHOO.haloacl.treeInstancemanageuser_grouplisting.getRoot(),YAHOO.haloacl.manageUser_selectedGroup);">$hacl_manageUser_4</a>
            </div>
            <div id="haloacl_manageuser_contentmenu_element">
                <a href="javascript:YAHOO.haloacl.manageUser.addNewSubgroupOnSameLevel(YAHOO.haloacl.treeInstancemanageuser_grouplisting.getRoot(),YAHOO.haloacl.manageUser_selectedGroup);">$hacl_manageUser_5</a>
            </div>
        </div>
        <div id="haloacl_manageuser_contentlist">
            <div id="manageuser_grouplisting">
                <div id="haloacl_manageuser_contentlist_title">
        $hacl_manageUser_6<span id="haloacl_manageuser_contentlist_title_delete">$delete_text</span>
                   <span id="haloacl_manageuser_contentlist_title_edit">Edit</span>
                   <span id="haloacl_manageuser_contentlist_title_info">Info</span>
                </div>
                <div id="haloacl_manageuser_contentlist_title">
                    Filter:&nbsp;<input id="haloacl_manageuser_filterinput" class="haloacl_filter_input" onKeyup="YAHOO.haloacl.manageUserRefilter(this);"/>
                </div>
                <div id="treeDiv_manageuser_grouplisting">
                </div>
                <div id="haloacl_manageuser_contentlist_footer">
                <span class="haloacl_cont_under_trees">
        $showing1 <span id="haloacl_manageuser_count">0</span> $showing2
                </span>
                    <input type="button" onClick="YAHOO.haloacl.manageACLdeleteCheckedGroups();" value="$hacl_manageUser_7" />
                </div>
            </div>
        </div>
    </div>
        <script>
            // treeview part - so the left part of the select/deselct-view
            YAHOO.haloacl.manageUser_selectedGroup = "";

            YAHOO.haloacl.treeInstancemanageuser_grouplisting = new YAHOO.widget.TreeView("treeDiv_manageuser_grouplisting");
            YAHOO.haloacl.treeInstancemanageuser_grouplisting.labelClickAction = 'YAHOO.haloacl.manageUser_handleGroupSelect';
            YAHOO.haloacl.manageUser.buildTreeFirstLevelFromJson(YAHOO.haloacl.treeInstancemanageuser_grouplisting);

            YAHOO.haloacl.manageUser_handleGroupSelect = function(groupname){
                if (YAHOO.haloacl.debug) console.log(groupname);
                $$('.manageUser_highlighted').each(function(item){
                    item.removeClassName("manageUser_highlighted");
                });
                try{
                var element = $('manageUserRow_'+groupname);
                if (YAHOO.haloacl.debug) console.log(element);
                var temp = element.parentNode.parentNode.parentNode.parentNode;
                $(temp).addClassName("manageUser_highlighted");
                }catch(e){}
                YAHOO.haloacl.manageUser_selectedGroup = groupname;
            };


            YAHOO.haloacl.manageACLdeleteCheckedGroups = function(){
                var checkedgroups = YAHOO.haloacl.getCheckedNodesFromTree(YAHOO.haloacl.treeInstancemanageuser_grouplisting, null);

                var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
                xml += "<groupstodelete>";
                for(i=0;i<checkedgroups.length;i++){
                    xml += "<group>"+escape(checkedgroups[i])+"</group>";
                }
                xml += "</groupstodelete>";
                if (YAHOO.haloacl.debug) console.log(xml);

                var callback5 = function(result){
                    YAHOO.haloacl.notification.createDialogOk("content","Manage Groups",result.responseText,{
                        yes:function(){
                            window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=manageUsers';
                            }
                    });
                };

                YAHOO.haloacl.sendXmlToAction(xml,'haclDeleteGroups',callback5);

            };

            YAHOO.haloacl.manageUserRefilter = function(element) {
                var filtervalue = document.getElementById("haloacl_manageuser_filterinput").value;
                YAHOO.haloacl.manageUser.applyFilterOnTree (YAHOO.haloacl.treeInstancemanageuser_grouplisting.getRoot(), filtervalue);
            }


        </script>
HTML;
    //$panelid, $name="", $title, $description = "", $showStatus = true,$showClose = true
    $myGenericPanel = new HACL_GenericPanel("manageUsersPanel","manageUsersPanel", "[ $hacl_manageUser_8 ]","",false,false);
    $myGenericPanel->setSaved(true);
    $myGenericPanel->setContent($panelContent);

    $html .= $myGenericPanel->getPanel();

    $html .= <<<HTML
        </div>
HTML;

    // ------ NOW STARTS THE EDITING PART ------
    $html .= <<<HTML
        <div id="haloacl_manageUser_editing_container" style="display:none">
            <div id="haloacl_manageUser_editing_title">
            </div>
HTML;
    $discard = wfMsg('hacl_discard_changes');
	$msgDynamicGroup = wfMsg('hacl_dynamic_group_not_editable');

    $groupPanelContent = <<<HTML
        <div id="content_manageUserGroupsettings">
            <div id="manageUserGroupSettingsRight">
            </div>
            <div id="manageUserGroupSettingsModificationRight">
                <script>
                    // now loaded via handleedit
                   // YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsRight','haclGetRightsPanel',{panelid:'manageUserGroupSettingsRight',predefine:'individual'});
                </script>

                <div id="manageUserGroupSettingsModificationRight">
                </div>
                <script>
                    // now loaded via handleedit
                 //   YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsModificationRight','haclGetRightsPanel',{panelid:'manageUserGroupSettingsModificationRight',predefine:'modification'});
                </script>
            </div>
            <div class="haloacl_tab_section_content">
                <div class="haloacl_tab_section_content_row">
                   <div class="haloacl_button_box">
                    <div id="haloacl_dynamic_group_msg" class="haloacl_dynamic_group_msg" style="display:none;">
                    	$msgDynamicGroup
                    </div>
                    <form id="haloacl_save_discard_form">
                     <input type="button" class="haloacl_discard_button" id="haloacl_discardacl_button" value="$discard"
                        onclick="javascript:YAHOO.haloacl.discardChanges_users();"/>
                    &nbsp;<input id="haloacl_managegroups_save" type="button" value="$hacl_manageUser_10" onClick="YAHOO.haloacl.manageUsers_saveGroup();" />

                    </form>
                    </div>
                </div>

            </div>
            <div id="manageUserGroupFinishButtons">
            </div>
        </div>


HTML;

    $groupPanel = new HACL_GenericPanel("manageUserGroupsettings","manageUserGroupsettings", "Group","",true,false);
    $groupPanel->setSaved(false);
    $groupPanel->setContent($groupPanelContent);
    $html .= $groupPanel->getPanel();


    $html .= <<<HTML
        </div>
HTML;

    $response->addText($html);
    return $response;
}


/**
 *
 * @return <string>   returns content for whitelist-tab
 */
function haclWhitelistsContent() {
	$hacl_whitelist_1 = wfMsg('hacl_whitelist_1');
	$hacl_whitelist_2 = wfMsg('hacl_whitelist_2');
	$hacl_whitelist_3 = wfMsg('hacl_whitelist_3');
	$hacl_whitelist_4 = wfMsg('hacl_whitelist_4');
	
	$response = new AjaxResponse();
	$html = <<<HTML
	    <div class="haloacl_manageusers_container">
	        <div class="haloacl_manageusers_title">
			    $hacl_whitelist_1
	        </div>
	        <div class="haloacl_manageusers_subtitle">
			    $hacl_whitelist_2
	        </div>
HTML;
	
	$showing1        = wfMsg('hacl_showing_text');
	$showing2        = wfMsg('hacl_showing_elements_text');
	$delete_text     = wfMsg('hacl_delete_link_header');
	$edit_text       = wfMsg('hacl_rightsPanel_right_edit');
	$delete_selected = wfMsg('hacl_delete_selected');
	$addPage         = wfMsg('hacl_whitelist_addbutton');
	$filter          = wfMsg('hacl_whitelist_filter');
	$pageRemoved     = wfMsg('hacl_whitelist_pageremoved');
	
	$myGenPanelContent = <<<HTML
	<div id="content_manageUsersPanel">
	    <div id="haloacl_whitelist_contentlist">
	        <div id="manageuser_grouplisting">
		        <div id="haloacl_manageuser_contentlist_title">
				    $hacl_whitelist_3
				    <span id="haloacl_manageuser_contentlist_title_delete">$delete_text</span>
	        	</div>
	            <div id="haloacl_manageuser_contentlist_title">
	                $filter:&nbsp;
	                <input id="haloacl_whitelist_filterinput" 
	                       class="haloacl_filter_input" 
	                       onKeyup="YAHOO.haloacl.whitelistDatatableInstance.executeQuery(this.value);"/>
	            </div>
	        	<div id="haloacl_whitelist_datatablecontainer">
	           		<div id="haloacl_whitelist_datatable" class="haloacl_whitelist_datatable yui-content">
	            	</div>
	       		</div>
	        	<div id="haloacl_whitelist_contentlist_footer">
	            	<span class="haloacl_cont_under_trees">
	    				$showing1 
	    				<span id="haloacl_whitelist_count">0</span> 
	    				$showing2
	            	</span>
	            	<input type="button" value="$delete_selected" 
	            	       onClick="YAHOO.haloacl.deleteWhitelist();" />
	        	</div>
	    	</div>
		</div>
		<div style="clear:both">&nbsp;</div>
	    $hacl_whitelist_4 &nbsp;
		<div style="clear:both">
	    	<input type="text" id="haloacl_whitelist_pagename" />
	    	<div id="whitelist_name_container"></div>
	    	<input style="margin-left:211px" 
	    	       type="button" value="$addPage" 
	    	       onClick="YAHOO.haloacl.saveWhitelist();" />
		</div>
	    
		<script type="javascript">
		    YAHOO.haloacl.AutoCompleter('haloacl_whitelist_pagename', 'whitelist_name_container');
		</script>
	</div>
HTML;
	$html .= $myGenPanelContent;
	$html .= <<<HTML
	
	<script>
	    YAHOO.haloacl.whitelistDatatableInstance = YAHOO.haloacl.whitelistTable('haloacl_whitelist_datatable','haloacl_whitelist_datatable');
	
	    YAHOO.haloacl.saveWhitelist = function(){
	
	        if (YAHOO.haloacl.debug) console.log("saveWhitelist called");
	        var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
	        xml += "<whitelistContent>";
	        xml += "<page>"+escape($('haloacl_whitelist_pagename').value)+"</page>";
	        xml += "</whitelistContent>";
	
	        var callback4 = function(result){
	            if (YAHOO.haloacl.debug) console.log("callback4 called");
	            if (YAHOO.haloacl.debug) console.log(YAHOO.haloacl.whitelistDatatableInstance);
	            YAHOO.haloacl.whitelistDatatableInstance.executeQuery("");
	
	            if (YAHOO.haloacl.debug) console.log(result);
	            $('haloacl_whitelist_pagename').value = "";
	            if (YAHOO.haloacl.debug) console.log("callback4 end");
	        };
	        
	        YAHOO.haloacl.sendXmlToAction(xml,'haclSaveWhitelist',callback4);
	
	    };
	
	    YAHOO.haloacl.deleteWhitelist = function(){
	
	        if (YAHOO.haloacl.debug) console.log("deleteWhitelist called");
	        var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
	        xml += "<whitelistContent>";
	        var itemFound = false;
	        $$('.haloacl_whitelist_datatable_users').each(function(item){
	            if (item.checked){
	                xml += "<page>"+escape(item.name)+"</page>";
	                itemFound = true;
	            }
	        });
	        xml += "</whitelistContent>";
	
	        var callback6 = function(result){
	            YAHOO.haloacl.notification.createDialogOk("content","Whitelist","$pageRemoved",{
	                yes:function(){}
	            });
	            YAHOO.haloacl.whitelistDatatableInstance.executeQuery("");
	
	        };
	        
	        if (itemFound) {
		        YAHOO.haloacl.sendXmlToAction(xml,'haclDeleteWhitelist',callback6);
			}
	
	    };
	
	    YAHOO.haloacl.whitelistClicks = new Array();
	    // appending checkbox-memory
	    YAHOO.haloacl.whitelistCheck = function(element){
	        if (element.checked){
	            if (YAHOO.haloacl.whitelistClicks.indexOf(element.name) == -1){
	                YAHOO.haloacl.whitelistClicks.push(element.name);
	            }
	        } else {
	            YAHOO.haloacl.whitelistClicks = YAHOO.haloacl.whitelistClicks.without(element.name);
	        }
	    };
	
	
	</script>
	</div>
HTML;
	
	$response->addText($html);
	return $response;
}


/**
 * delete groups
 *
 * @global <type> $haclgContLang
 * @param <string> xml containing groups to be delted
 * @param <type> not used
 * @return <type> string
 */
function haclDeleteGroups($grouspXML, $type) {
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    $result = "";
    $whitelistXml = new SimpleXMLElement($grouspXML);
    $result = wfMsg("hacl_nothing_deleted");
    foreach ($whitelistXml->xpath('//group') as $group) {
        $group = unescape($group);
        if ($group != null) {
            try {
            	$group = haclAddGroupPrefix($group);
                $sdarticle = new Article(Title::newFromText("$ns:".$group));
                $sdarticle->doDelete("gui-deletion");
                $result = wfMsg('hacl_deleteGroup_1');

            } catch(Exception $e ) {
                $result .= "Error while deleting $ns:".$group.". ";
            }
        }

    }


    // create article
    return $result;

}

/**
 *  deletes page from whiltelist
 * @global <type> $haclgContLang
 * @param <string> xml-list of pages that should be removed from whitelist
 * @return <string>
 */
function haclDeleteWhitelist($whitelistXml) {
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    $whitelistPF  = $haclgContLang->getParserFunction(HACLLanguage::PF_WHITELIST);
	$pagesPFP = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_PAGES);
    try {

        $whitelists = array();
        $whitelistXml = new SimpleXMLElement($whitelistXml);
        foreach ($whitelistXml->xpath('//page') as $page) {
            $whitelists[] = unescape((string)$page);
        }

        //get group members
        $oldWhitelists = HACLWhitelist::newFromDB();
        $pages = "";

        foreach ($oldWhitelists->getPages() as $item) {
            if (!in_array($item,$whitelists)) {
                if ($pages == '') {
                    $pages = (string)$item;
                } else {
                    $pages .= ",".(string)$item;
                }
            }
        }

        // create article
        $sdarticle = new Article(Title::newFromText($haclgContLang->getWhitelist()));
        if ($pages == "") {
            $inline = '';
        } else {
        	$inline = "{{#$whitelistPF:$pagesPFP=$pages}}";
        }
        $sdarticle->doEdit($inline, "");
        $SDID = $sdarticle->getID();

        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->setContentType("json");
        $ajaxResponse->setResponseCode(200);
        $ajaxResponse->addText($inline );

    } catch (Exception   $e ) {
        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->setResponseCode(400);
        $ajaxResponse->addText($e->getMessage());
    }
    return $ajaxResponse;

}

/**
 * returns group-details for a given groupname
 * @param <string> name of group
 * @return <string> json.-formed groupdetails
 */
function haclGetGroupDetails($groupname) {
    $g = HACLGroup::newFromName(haclAddGroupPrefix($groupname));
    // Remove the naming convention prefix from all group members
    $groupMembers = $g->getGroups(HACLGroup::NAME);
    foreach ($groupMembers as $k => $gm) {
    	$groupMembers[$k] = haclRemoveGroupPrefix($gm);
    }
    $result = array(
        'name'=>haclRemoveGroupPrefix($g->getGroupName()),
        'memberUsers'=>$g->getUsers(HACLGroup::NAME),
        'memberGroups'=>$groupMembers,
        'description'=>$g->getGroupDescription(),
        'manageUsers'=>array(),
        'manageGroups'=>array(),
    	'hasDynamicMembers' => $g->hasDynamicMembers()
    );
    foreach ($g->getManageUsers() as $id) {
        $db =& wfGetDB( DB_SLAVE );
        $gt = $db->tableName('user');
        $sql = "SELECT * FROM $gt ";
        $sql .= "WHERE user_id = ".$id;
        $res = $db->query($sql);
        while ($row = $db->fetchObject($res)) {
            $result['manageUsers'][] = $row->user_name;

        }
    }
    foreach ($g->getManageGroups() as $id) {
        $result['manageGroups'][] = haclRemoveGroupPrefix(HACLGroup::newFromID($id)->getGroupName());
    }
    return json_encode($result);
}

/**
 * @return <string> content for the quickacl-tab
 */
function haclCreateQuickAclTab() {

    $hacl_quickACL_1 = wfMsg('hacl_quickACL_1');
    $hacl_quickACL_2 = wfMsg('hacl_quickACL_2');
    $hacl_quickACL_3 = wfMsg('hacl_quickACL_3');

    $response = new AjaxResponse();
    $html = <<<HTML
        <div class="haloacl_manageusers_container">
            <div class="haloacl_manageusers_title">
        $hacl_quickACL_1
            </div>
            <div class="haloacl_manageusers_subtitle">
        $hacl_quickACL_2
            </div>
HTML;
    //$myGenPanel = new HACL_GenericPanel("haloacl_quicklist_panel", $hacl_quickACL_1, $hacl_quickACL_1, "", false, false);
    $showing1 = wfMsg('hacl_showing_text');
    $showing2 = wfMsg('hacl_showing_elements_text');
    $showing3 = wfMsg('hacl_selected');

    $select_text = wfMsg('hacl_select');
    //FIXME: "Info" also needs to be internationalized. That's an awfull CSS selector mix here.
    $myGenPanelContent = <<<HTML
   <div id="content_haloacl_quicklist_panel">
        <div id="haloacl_whitelist_contentlist">
            <div id="manageuser_grouplisting">
            <div id="haloacl_manageuser_contentlist_title">
        $hacl_quickACL_3<span style="margin-right:20px;float:right;">$select_text</span><span style="margin-right:25px;float:right;">Info</span>
            </div>
                <div id="haloacl_manageuser_contentlist_title">
                    Filter:&nbsp;<input class="haloacl_filter_input" onKeyup="YAHOO.haloacl.quickaclTableInstance.executeQuery(this.value);"/>
                </div>
            <div id="haloacl_whitelist_datatablecontainer">
                <div id="haloacl_quickacl_datatable" class="haloacl_whitelist_datatable yui-content">
                </div>
            </div>
            <div id="haloacl_whitelist_contentlist_footer">
                <span class="haloacl_cont_under_trees">
        $showing1 <span id="haloacl_quickacl_count">0</span> $showing2
                </span>
                <span class="haloacl_cont_under_trees">
                    &nbsp; | &nbsp;&nbsp;$showing3 <span id="haloacl_quickacl_selected">0</span> - 15
                </span>
                <input id="haloacl_save_quickacl_button" type="button" value="Save Quickacl" onClick="YAHOO.haloacl.saveQuickacl();" />
            </div>
           </div>
       </div>
       <div style="clear:both">&nbsp;</div>
  </div>

HTML;
    //$myGenPanel->setContent($myGenPanelContent);

    //$html .= $myGenPanel->getPanel();
    $html .= $myGenPanelContent;
    $html .= <<<HTML

  <script>

        YAHOO.haloacl.updateQuickaclCount = function(item){
            var counter = 0;
            $$('.haloacl_quickacl_datatable_template').each(function(item){
                if (item.checked){
                    counter++;
                }
            });
            if (counter > 15 && item){
                item.checked = false;
                YAHOO.haloacl.notification.createDialogOk("content","Quickacl","Only 15 Templates in quickacl are allowed");
                counter--;
            }


            $('haloacl_quickacl_selected').innerHTML = counter;
        };

        // clickhandling
        YAHOO.haloacl.quickAclClicks = new Array();
        // appending checkbox-memory
        YAHOO.haloacl.quickACLCheck = function(element){
            if (element.checked){
                if (YAHOO.haloacl.quickAclClicks.indexOf(element.name) == -1){
                    YAHOO.haloacl.quickAclClicks.push(element.name);
                }
            } else {
                YAHOO.haloacl.quickAclClicks = YAHOO.haloacl.quickAclClicks.without(element.name);
            }
        };
        // --------------

        YAHOO.haloacl.quickaclTableInstance = YAHOO.haloacl.quickaclTable('haloacl_quickacl_datatable','haloacl_quickacl_datatable');

        YAHOO.haloacl.saveQuickacl = function(){

            if (YAHOO.haloacl.debug) console.log("saveQuickacl called");
            var xml = "<?xml version=\"1.0\"  encoding=\"UTF-8\"?>";
            xml += "<quickaclContent>";
            $$('.haloacl_quickacl_datatable_template').each(function(item){
                if (item.checked){
                    xml += "<template>"+escape(item.name)+"</template>";
                }
            });
            xml += "</quickaclContent>";

            var callback4 = function(result){
                YAHOO.haloacl.quickaclTableInstance.executeQuery("");
                YAHOO.haloacl.notification.createDialogOk("content","QuickACL",result.responseText,{
                    yes:function(){}
                    });

            };

            YAHOO.haloacl.sendXmlToAction(xml,'haclSaveQuickacl',callback4);

        };



    </script>
</div>
HTML;

    $response->addText($html);
    return $response;

    return $html;
}


/**
 * returns quickacl-data for logged in user
 *
 * @global <User> $wgUser
 * @global <array> $haclCrossTemplateAccess
 * @param <string> search-term
 * @param <string> sort column
 * @param <string> sort-direction
 * @param <string> index to start
 * @param <string> numbers of results
 * @param <type> not used
 * @return <string> json-formed results
 */
function haclGetQuickACLData($query,$sort,$dir,$startIndex,$results,$filter) {
    global $wgUser,$haclCrossTemplateAccess;

    $a = array();
    $a['recordsReturned'] = 2;
    $a['totalRecords'] = 10;
    $a['startIndex'] = 0;
    $a['sort'] = null;
    $a['dir'] = "asc";
    $a['pageSize'] = 5;

    $types = array("acltemplate");
    $templates = HACLStorage::getDatabase()->getSDs($types);

    $quickacl = HACLQuickacl::newForUserId($wgUser->getId());

    $a['records'] = array();
    foreach ($templates as $sd) {
        if ($query == "all" || preg_match('/'.$query.'/is',$sd->getSDName())) {
            $checked = false;
            try {
                if (in_array($sd->getSDId(), $quickacl->getSD_IDs())) {
                    $checked = true;
                }
            }catch(Exception $e ) {}
            $a['records'][] = array('id'=>$sd->getSDId(), 'name'=>$sd->getSDName(),'checked'=>$checked);
        }
    }

    // generating paging-stuff
    $a['totalRecords'] = sizeof($a['records']);
    $a['recordsReturned'] = sizeof($a['records']);

    return(json_encode($a));

}

/**
 * saves quickacl-list for logged in user
 *
 * @global <User> $wgUser
 * @param <string> xml containing quickacl-entries
 * @return <AjaxResponse> indicates status
 */
function haclSaveQuickacl($xml) {
    global $wgUser;
    $templates = array();
    $xmlInstance = new SimpleXMLElement($xml);

    foreach ($xmlInstance->xpath('//template') as $template) {
        $templates[] = unescape((string)$template);
    }
    if (sizeof($templates) < 15) {
        $quickacl = new HACLQuickacl($wgUser->getId(), $templates);
        $quickacl->save();
        return wfMsg('hacl_quickACL_4');
    } else {
        return wfMsg('hacl_quickacl_limit');
    }
}

/**
 *
 * @param <string> articlename
 * @param <string> type of article
 * @return <AjaxResponse> indicating if article exists, and if article is secured
 */
function haclDoesArticleExists($articlename,$protect) {
    global $haclgContLang,
    $wgCanonicalNamespaceNames;


    $template = $haclgContLang->getSDTemplateName();
    $predefinedRightName = $haclgContLang->getPredefinedRightName();
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];

    if ($protect == "property") {
        $articlename = "Property:".$articlename;
    }
    if ($protect == "namespace") {
        $response = new AjaxResponse();
    	$namespaces = $wgCanonicalNamespaceNames;
    	$namespaces[] = $haclgContLang->getLabelOfNSMain();
        
        if (array_intersect($namespaces,array($articlename))) {
            $sd = new Article(Title::newFromText("$ns:$protect/$articlename"));
            if ($sd->exists()) {
                $response->addText("sdexisting");

            } else {
                $response->addText("true");
            }
        } else {
            $response->addText("false");
        }
        return $response;
    }
    if ($protect == "category") {
        $articlename = "Category:".$articlename;
    }
    if ($protect == "template") {
        $articlename = "$ns:$template/".$articlename;
    }
    if ($protect == "Right") {
        $articlename = "$ns:$predefinedRightName/".$articlename;
    }

    $response = new AjaxResponse();
    $article = new Article(Title::newFromText($articlename));
    if ($article->exists()) {
    	$sdtitle = Title::newFromText("$ns:$protect/$articlename");
        $sd = new Article($sdtitle);
        if ($sd->exists()) {
            $response->addText("sdexisting");
        } else {
        	// The article might not be protectable as it is already protected
        	// by a category or a namespace
        	global $wgUser;
        	if (HACLEvaluator::checkSDCreation($sdtitle, $wgUser) === false) {
        		$response->addText("articleIsProtected");
        	} else {
            	$response->addText("true");
        	}
        }
    } else {
        $response->addText("false");
    }
    return $response;
}

/**
 * This function removes the prefix from the name of a group. The prefix is a 
 * language dependent naming convention like "Group/" in "Group/MyGroup".
 *
 * @param string $groupName
 * 		A group name with or without prefix.
 * 
 * @return string
 * 		The group name without prefix.
 */
function haclRemoveGroupPrefix($groupName) {
	global $haclgContLang;
	$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP)."/";
	if (strpos($groupName, $prefix) === 0) {
		return substr($groupName, strlen($prefix));
	}
	return $groupName;
}

/**
 * This function adds the prefix to the name of a group. The prefix is a 
 * language dependent naming convention like "Group/" in "Group/MyGroup".
 *
 * @param string $groupName
 * 		A group name without prefix.
 * 
 * @return string
 * 		The group name with prefix.
 */
function haclAddGroupPrefix($groupName) {
	// If the group exists without the prefix, no prefix is added
	if (HACLStorage::getDatabase()->getGroupByName($groupName) !== null) {
		return $groupName;
	}
	
	global $haclgContLang;
	$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP)."/";
	return $prefix.$groupName;
}



/*******************************************************************************
 * 
 * Group permissions
 * 
 */ 

/**
 * Returns the HTML of the whole Global Permission panel.
 * 
 * @return <string> content for global permissions-tab
 */
function haclGlobalPermissionsPanel() {

	// clear temp-right-sessions
    clearTempSessionRights();

    $response = new AjaxResponse();
	
	$html = HACLUIGroupPermissions::getPermissionsPanel();	
	
    $response->addText($html);
    return $response;

}

/**
 * Returns the children of the given group in JSON format for jQuery.tree
 * 
 * @param string $groupID
 * 		ID of the parent group or "---ROOT---" for the root level
 * @return string
 * 		Children of the requested group
 */
function haclGetGroupChildren($groupID, $feature) {

	$response = new AjaxResponse();
	$response->setContentType("json");

	$groupID = urldecode($groupID);
	$feature = urldecode($feature);
	$groupID = str_replace("haclgt-", "", $groupID);

	$json = HACLUIGroupPermissions::getGroupChildren($groupID, $feature);
	$response->addText($json);
	return $response;

}

/**
 * Returns the paths to groups that match the given filter.
 * 
 * @param string $filter
 * 		Group names must contain this string
 * @return string
 * 		A comma separated list of group IDs that make up the paths to the groups
 * 		that match the filter
 */
function haclFilterGroups($filter) {
	$response = new AjaxResponse();
	$response->setContentType("text/plain");

	$filter = urldecode($filter);

	$result = HACLUIGroupPermissions::searchMatchingGroups($filter);
	if (empty($result)) {
		// Empty results are allowed but jsTree throws an exception
		// => return a non-existing ID
		$result = "non-existing-id";
	}
	$response->addText($result);
	return $response;
	
}

/**
 * Saves changed permissions of the given feature.
 * 
 * @param string $feature
 * 		ID of the feature
 * @param string $changedPermissions
 * 		JSON encoded array that contains the changed permissions
 */
function haclSaveGroupPermissions($feature, $changedPermissions) {
	$feature = urldecode($feature);
	$changedPermissions = urldecode($changedPermissions);
	$changedPermissions = json_decode($changedPermissions, true);
	
	$groupPermission = array();
	foreach ($changedPermissions as $group => $permission) {
		$group = str_replace('haclgt-', '', $group);
		$groupPermission[$group] = $permission;
	}
	$result = HACLGroupPermissions::saveGroupPermissions($feature, $groupPermission);
	
    $response = new AjaxResponse();
    $response->setContentType("html");
    
    $response->addText(wfMsg('hacl_gp_permissions_saved'));
	return $response;
	
}