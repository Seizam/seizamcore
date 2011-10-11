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
 * This class provides the help-popup which is primary used
 * in createacl
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

/**
 * Description of HACL_AjaxConnector
 *
 * @author hipath
 */
 
class HACL_helpPopup {

    private $saved = false;
    private $header;
    private $footer;
    private $content;


    function __construct($panelid, $helptext="") {

        $panelid = uniqid($panelid);
        $this->content = <<<HTML
            <div id="anchorHelpCreateRight_$panelid" class="haloacl_helpbutton" onclick="javascript:YAHOO.haloacl.popup_showHelpCreateRight_$panelid();return false;"></div>
            <div id="popup_HelpCreateRight_$panelid"></div>
HTML;

    $help_text = wfMsg('hacl_help_popup');
        $this->footer = <<<HTML
            <script type="javascript">

                YAHOO.haloacl.popup_showHelpCreateRight_$panelid = function(){
                    if(YAHOO.haloacl.popup_helpcreateright_$panelid == null){
                        YAHOO.haloacl.popup_helpcreateright_$panelid = new YAHOO.widget.Panel('popup_HelpCreateRight_$panelid',{
                                close:true,
                                visible:true,
                                draggable:true,
                                resizable:true,
                                width:"500px",
                              //  modal:true,
                                zIndex:15000,
                                context:  ["anchorHelpCreateRight_$panelid","tl","bl", ["beforeShow"]]
                        });
                        YAHOO.haloacl.popup_helpcreateright_$panelid.setHeader("$help_text");
                        YAHOO.haloacl.popup_helpcreateright_$panelid.setBody("$helptext");
                        YAHOO.haloacl.popup_helpcreateright_$panelid.render();
                        YAHOO.haloacl.popup_helpcreateright_$panelid.show();
                    }else{
                        YAHOO.haloacl.popup_helpcreateright_$panelid.render();
                        YAHOO.haloacl.popup_helpcreateright_$panelid.show();

                    }
               };

            </script>
HTML;


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
        $this->content = $newContent;
    }

    function getPanel() {


        return $this->header . $this->content . $this->footer;

    }

    function getSaved() {
        return $this->saved;
    }

    function setSaved($newSavedStatus) {
        $this->saved = $newSavedStatus;

    }
}