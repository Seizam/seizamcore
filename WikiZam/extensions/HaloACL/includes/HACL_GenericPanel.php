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
 * This file contains the generic panel, which is used as container
 * in the whole gui
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */


class HACL_GenericPanel {

    private $saved = false;
    private $header;
    private $footer;
    private $content;
    private $panelid;

/**
 * expand mode: expand to expand panel and show content; replace to replace panel through content with back button
 *
 */
    function __construct($panelid, $name="", $title, $description = "", $showStatus = true,$showClose = true,$customState=null,$expandMode="expand",$showSaved=false) {
        $saved = wfMsg('hacl_saved');
        $notsaved = wfMsg('hacl_notsaved');
        $default_text = wfMsg('hacl_default');

        $this->panelid = $panelid;

        $this->header = <<<HTML
	<!-- start of panel div-->
	<div id="$panelid" class="panel haloacl_panel">
		<!-- panel's top bar -->
		<div id="title_$panelid" class="panel haloacl_panel_title">
			<span class="haloacl_panel_expand-collapse">
                            <a id="exp_col_link_$panelid" href="javascript:YAHOO.haloacl.viewGenericPanelContent_$panelid();">&nbsp;<div id="exp-collapse-button_$panelid" class="haloacl_panel_button_collapse"></div></a>
			</span>
                        <div class="haloacl_panel_nameDescr">
                            <span id="haloacl_panel_name_$panelid" class="panel haloacl_panel_name">$title</span>
                            <span id="haloacl_panel_descr_$panelid" class="panel haloacl_panel_descr">$description</span>
                        </div>
HTML;
        if($showStatus && $customState == null) {
            $this->header .= <<<HTML
                        <div class="haloacl_panel_statusContainer">
                            <span id="haloacl_panel_status_$panelid" class="haloacl_panel_status_notsaved">$notsaved</span>
                        </div>

HTML;
        }else if($showStatus && $customState != null){
             $this->header .= <<<HTML
                        <div class="haloacl_panel_statusContainer">
                            <span id="haloacl_panel_status_$panelid" class="haloacl_panel_status_saved">$customState</span>
                        </div>

HTML;
        }




        if($showClose) {
            $this->header .= <<<HTML
			<span id="closebutton_$panelid" class="button haloacl_panel_close">
				<a href="javascript:YAHOO.haloacl.removePanel('$panelid');"><div id="close-button_$panelid" class="haloacl_panel_button_close"></div></a>
			</span>
HTML;
        }
        $this->header .="</div>";

        $this->footer = <<<HTML
        </div> <!-- end of panel div -->
        <script type="javascript>
            YAHOO.haloacl.addTooltip("tooltip_$panelid","closebutton_$panelid","Click here to delete the right");

            //array keeping previous contents in case of replace expand mode
            YAHOO.haloacl.genericPanelParentContents_$panelid = new Array();

            YAHOO.haloacl.viewGenericPanelContent_$panelid = function() {
                switch ('$expandMode') {
                    case 'expand':
                        YAHOO.haloacl.togglePanel('$panelid');
                        //YAHOO.haloacl.removeoutside('$panelid');
                        break;
                    case 'replace':
                        YAHOO.haloacl.togglePanel('$panelid');

                        //YAHOO.haloacl.genericPanelParentContents_$panelid.push({element:$('$panelid').parentNode.id, content:$('$panelid').parentNode.innerHTML});

//                        var element = $('content_$panelid');
//                        if(element.visible()){
//                           // element.hide();
//                        }else{
//                            element.show();
//                        }
                        //replace parent content of $(panelid) with content and back button, add back link to array
                        break;
                }
            }

            YAHOO.haloacl.resumeGenericPanelContent_$panelid = function() {
                resomeTo = YAHOO.haloacl.genericPanelParentContents_$panelid.pop();
                $(resomeTo['element']).innerHTML = resomeTo['content'];

            }


            YAHOO.haloacl.removeoutside = function(panelid) {
                console.log("removeoutside called for panelid:"+panelid);

                console.log("using parent:");
                console.log($(panelid).parentNode);
                var elements = $(panelid).parentNode.children;
                    console.log($(panelid).parentNode.children);
                 //   console.log($(panelid).parentNode.childNodes);


                for(i=0;i<elements.length;i++){
                    var item = elements[i];
                    if(item.hasClassName("haloacl_panel")){
                        console.log("trying to close");
                        console.log(item);
                        if (item.id != $(panelid)) item.hide();
                    }
                }
                

                
                
            }


            //status handling
            genericPanelSetSaved_$panelid = function(saved) {
                try{
                    if(saved == "default"){
                        $('haloacl_panel_status_$panelid').innerHTML = '$default_text';
                        $('haloacl_panel_status_$panelid').setAttribute("class", "haloacl_panel_status_saved");

                    }else if (saved == true) {
                        $('haloacl_panel_status_$panelid').innerHTML = '$saved';
                        $('haloacl_panel_status_$panelid').setAttribute("class", "haloacl_panel_status_saved");
                    } else {
                        $('haloacl_panel_status_$panelid').innerHTML = '$notsaved';
                        $('haloacl_panel_status_$panelid').setAttribute("class", "haloacl_panel_status_notsaved");

                       //genericPanelSetSaved_hacl_panel_container(false);
                        $('haloacl_panel_status_hacl_panel_container').innerHTML = '$notsaved';
                        $('haloacl_panel_status_hacl_panel_container').setAttribute("class", "haloacl_panel_status_notsaved");
                    }
                }catch(e){}
            }


            genericPanelSetName_$panelid = function(name) {
                $('haloacl_panel_name_$panelid').innerHTML = name;
            }

            genericPanelSetDescr_$panelid = function(descr, descrLong) {
                if(descrLong == null){
                    descrLong = descr;
                }
                $('haloacl_panel_descr_$panelid').innerHTML = descr;

                if(YAHOO.haloacl.descr_tooltip_$panelid){
// TODO check whats going on here - this bug is ie-reated
//                    YAHOO.haloacl.descr_tooltip_$panelid.destroy();
                }

                var now = new Date();
                YAHOO.haloacl.descr_tooltip_$panelid = new YAHOO.widget.Tooltip("createACLPanel_tooltip", {
                context:"haloacl_panel_descr_$panelid",
                text:descrLong,
                zIndex :10
                });
            }
            


        </script>

HTML;
        if($showSaved){
            $this->footer .= <<<HTML
            <script>
try{
                genericPanelSetSaved_$panelid(true);
}catch(e){}
            </script>
HTML;
        }


    }

    function extendFooter($extension) {
        $this->footer .= $extension;
    }

    function getFooter() {
        return $this->footer;
    }

    function getHeader() {
        return $this->header;
    }

    function setContent($newContent) {
//        $this->content = '<div id="haloacl_generic_panel_content_'.$this->panelid.'" class="haloacl_generic_panel_content">'.$newContent.'</div>';
        $this->content = '<div id="content_'.$this->panelid.'" class="haloacl_generic_panel_content">'.$newContent.'</div>';
    }

    function getPanel() {
        return $this->header . $this->content . $this->footer;
    }

    function getContentWithFooter() {
        return $this->content . $this->footer;
    }

    function getSaved() {
        return $this->saved;
    }

    function setSaved($newSavedStatus) {
        $this->saved = $newSavedStatus;

    }
}