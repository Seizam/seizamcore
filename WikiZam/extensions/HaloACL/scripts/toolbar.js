/**
 * @file
 * @ingroup HaloACL_UI_Script
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
 * This file contains the class HACLGroup.
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 03.04.2009
 *
 */

/**
 * delivers js-functionality to toolbar
 *
 * @author hipath
 */

// general ajax stuff
YAHOO.namespace("haloacl");
YAHOO.namespace("haloacl.toolbar");

/**
 *  renders result of an ajax-call to a div
 *  @param target-container
 *  @param actionname
 *  @param parameterlist (json)
 *
 */
YAHOO.haloacl.toolbar.loadContentToDiv = function(targetdiv, action, parameterlist){
    /*   var queryparameterlist = {
        rs:action
    };
     */


    //    console.log($(targetdiv));
    
    var querystring = "rs="+action;

    if(parameterlist != null){
        for(param in parameterlist){
            // temparray.push(parameterlist[param]);
            querystring = querystring + "&rsargs[]="+parameterlist[param];
        }
    }

    new Ajax.Request("?action=ajax", {
        //method:tab.get('loadMethod'),
        method:'post',
        // parameters: queryparameterlist,
        parameters: querystring,
        asynchronous:true,
        evalScripts:true,
        //  insertion:before,
        onSuccess: function(o) {
            //            console.log(o);
            $(targetdiv).insert({
                top:o.responseText
            })
        },
        onFailure: function(o) {
        }
    });
};
/**
 *  calls remoteaction with parameters and executes callback if given
 *  @param actionname
 *  @param paramterlist (json)
 *  @param callback
 *
 */
YAHOO.haloacl.toolbar.callAction = function(action, parameterlist, callback){
    if(callback == null){
        callback = function(result){
        //            console.log("stdcallback:"+result);
        }
    }

    var querystring = "rs="+action;

    if(parameterlist != null){
        for(param in parameterlist){
            querystring = querystring + "&rsargs[]="+parameterlist[param];
        }
    }
    new Ajax.Request("?action=ajax",{
        method:'post',
        onSuccess:function(result){
            try{
                $('wpSave').writeAttribute("type","submit");
                $('wpSave').removeAttribute("disabled");
                $('wpSave').writeAttribute("onClick","");

            }catch(e){}
            $('wpSave').click();
        },
        onFailure:function(result){
            try{
                $('wpSave').writeAttribute("type","submit");
                $('wpSave').writeAttribute("onClick","");
            }catch(e){}
            $('wpSave').click();
        },
        parameters:querystring
    });
};

/**
 *  provides hook for save-article
 *  @param not used anymore, due to toolbar-changes
 */
YAHOO.haloacl.toolbar_handleSaveClick = function(element){

    //var textbox = $('wpTextbox1');
    var tps = $('haloacl_toolbar_pagestate');
    var state  = tps[tps.selectedIndex].value;

    if (state == "protected"){
        var tpw = $('haloacl_template_protectedwith');
        var tmpvalue  = tpw[tpw.selectedIndex].text;
        //textbox.value = textbox.value + "{{#protectwith:"+$('haloacl_template_protectedwith').value+"}}";
        YAHOO.haloacl.toolbar.callAction('haclSetToolbarChoose',{tpl:tmpvalue});

    }else{
        //textbox.value = textbox.value + "{{#protectwith:unprotected}}";
        YAHOO.haloacl.toolbar.callAction('haclSetToolbarChoose',{tpl:'unprotected'},function(result){
           
        });
    }


};
/**
 * initializes toolbar
 *  so changes save button
 */

YAHOO.haloacl.toolbar_initToolbar = function(){
	var value = $('wpSave').readAttribute('value');
	var title = $('wpSave').readAttribute('title');
	var accesskey = $('wpSave').readAttribute('accesskey');
	var tabindex = $('wpSave').readAttribute('tabindex');
	var name = $('wpSave').readAttribute('name');
	$('wpSave').hide();
	new Insertion.After('wpSave', '<input id="wpSaveReplacement" type="button" value="'+value+'" title="'+title+'" accesskey="'+accesskey+'" tabindex="'+tabindex+'" name="'+name+'" />');
//        $('wpSave').writeAttribute("type","button");
    $('wpSaveReplacement').writeAttribute("onClick","YAHOO.haloacl.toolbar_handleSaveClick(this);return false;");
    YAHOO.haloacl.toolbar_updateToolbar();
    YAHOO.haloacl.toolbar_templateChanged();

}

/**
 * updates toolbar
 * triggered via change from unportected to protected and pro -> unprotected
 */
YAHOO.haloacl.toolbar_updateToolbar = function(){
	var selection = $('haloacl_toolbar_pagestate');
	var state = selection[selection.selectedIndex].value;
    if (state == "protected") {
        try{
     	   $('haloacl_template_protectedwith').show();
        }catch(e){}
        try{
     	   $('haloacl_template_protectedwith_desc').show();
        }catch(e){}
        try{
      	  $('haloacl_toolbar_popuplink').show();
        }catch(e){}
		YAHOO.haloacl.toolbar_templateChanged();     
    } else {
        $('haloacl_template_protectedwith').hide();
        $('haloacl_template_protectedwith_desc').hide();
        $('haloacl_toolbar_popuplink').hide();
		$('hacl_toolbarcontainer').removeClassName('hacl_toolbar_validAcl');
		$('hacl_toolbarcontainer').removeClassName('hacl_toolbar_invalidAcl');
		$('hacl_toolbarcontainer').addClassName('hacl_toolbar_validAcl');
		
		$('hacl_page_state').removeClassName('hacl_toolbar_invalidAclText');
		$('hacl_page_state').removeClassName('hacl_toolbar_validAclText');
		$('hacl_page_state').addClassName('hacl_toolbar_validAclText');
	
		$('haloacl_template_protectedwith_desc').removeClassName('hacl_toolbar_invalidAclText');
		$('haloacl_template_protectedwith_desc').removeClassName('hacl_toolbar_validAclText');
		$('haloacl_template_protectedwith_desc').addClassName('hacl_toolbar_validAclText');

    }
};

var gHACLToolbarTooltip = null;

YAHOO.haloacl.toolbar_templateChanged = function(){
	var selection = $('haloacl_template_protectedwith');
	if (!selection || !selection.visible()) {
		return;
	}
	var option = selection.down('option', selection.selectedIndex);
	if (!option) {
		return;
	}
	var valid = option.readAttribute('valid');
	var addClass = (valid == "false") ? 'haloacl_warningbutton' : 'haloacl_infobutton';
	$('anchorPopup_toolbar').removeClassName('haloacl_warningbutton');
	$('anchorPopup_toolbar').removeClassName('haloacl_infobutton');
	$('anchorPopup_toolbar').addClassName(addClass);
	
	var tooltiptext = gHACLLanguage.getMessage(valid == "true" ? 'aclinfotooltip' : 'aclwarningtooltip');
	if (gHACLToolbarTooltip == null) {
    	gHACLToolbarTooltip = new YAHOO.widget.Tooltip("anchorPopup_toolbar_tooltip", {
									        context:"anchorPopup_toolbar",
									        text: tooltiptext,
									        zIndex :10
   										 });
	} else {
		gHACLToolbarTooltip.cfg.setProperty("text", tooltiptext);
	}
	
	addClass = (valid == "true") ? 'hacl_toolbar_validAcl' : 'hacl_toolbar_invalidAcl';
	$('hacl_toolbarcontainer').removeClassName('hacl_toolbar_invalidAcl');
	$('hacl_toolbarcontainer').removeClassName('hacl_toolbar_validAcl');
	$('hacl_toolbarcontainer').addClassName(addClass);
	
	addClass = (valid == "true") ? 'hacl_toolbar_validAclText' : 'hacl_toolbar_invalidAclText';
	$('hacl_page_state').removeClassName('hacl_toolbar_invalidAclText');
	$('hacl_page_state').removeClassName('hacl_toolbar_validAclText');
	$('hacl_page_state').addClassName(addClass);

	$('haloacl_template_protectedwith_desc').removeClassName('hacl_toolbar_invalidAclText');
	$('haloacl_template_protectedwith_desc').removeClassName('hacl_toolbar_validAclText');
	$('haloacl_template_protectedwith_desc').addClassName(addClass);

};

/**
 * callback for sDpopupByName
 * -> creates popup
 * @param result of request
 */
YAHOO.haloacl.callbackSDpopupByName = function(result){
	var tpw = $('haloacl_template_protectedwith');
	var protectedWith = tpw[tpw.selectedIndex].text;
    if(result.status == '200'){
        YAHOO.haloaclrights.popup(result.responseText, protectedWith, 'toolbar');
    }else{
        alert(result.responseText);
    }
};

/**
 *  loads sd-id for sd-name
 *  @param sdname
 */
YAHOO.haloacl.sDpopupByName = function(sdName){
    YAHOO.haloacl.callAction('haclSDpopupByName', {
        sdName:sdName
    }, YAHOO.haloacl.callbackSDpopupByName);

};



