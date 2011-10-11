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
 * this file provides the notificationhandling system
 * for the haloacl-extension
 *
 * notificationhandling includes:
 * -creation of dialogs with given callback
 * -subscribing to elements in dom
 * *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

YAHOO.namespace("haloacl.notification");
YAHOO.haloacl.notification.counter = 0;


/**
 *  creates popup with one button
 *  @param target-div
 *  @param title
 *  @param message / content
 *  @param callback (on yes:)
 *
 */
YAHOO.haloacl.notification.createDialogOk = function (renderedTo,title,content,callback){
    if(title == null){
        title = "Info";
    }
    title = "HaloACL: "+title;
    YAHOO.haloacl.notification.counter++;

    new Insertion.Bottom(renderedTo,"<div id='haloacl_notification"+YAHOO.haloacl.notification.counter+"' class='yui-skin-sam'>&nbsp;</div>");

    if(YAHOO.haloacl.debug)console.log("create dialog called");
    var handleYes = function() {
        try{
            callback.yes();
        }catch(e){}
        this.hide();
    };


    var dialog = 	new YAHOO.widget.SimpleDialog("dialog"+YAHOO.haloacl.notification.counter,
    {
        width: "300px",
        fixedcenter: true,
        visible: false,
        draggable: false,
        close: true,
        text: content,
        icon: YAHOO.widget.SimpleDialog.ICON_INFO,
        constraintoviewport: true,
        buttons: [ {
            text:"Ok",
            handler:handleYes,
            isDefault:true
        }]
    } );

    dialog.setHeader(title);

    // Render the Dialog
    dialog.render('haloacl_notification'+YAHOO.haloacl.notification.counter);
    dialog.show();
    
    if(YAHOO.haloacl.debug)console.log("create dialog finished");

};
/**
 *  creates popup with 2 buttons
 *  @param target-div
 *  @param title
 *  @param message / content
 *  @param callback (on yes:, no:)
 *  @param label for yes-button
 *  @param label for no-button
 *
 */
YAHOO.haloacl.notification.createDialogYesNo = function (renderedTo,title,content,callback,yestext,notext){
    if(title == null){
        title = "Info";
    }
    title = "HaloACL: "+title;

    YAHOO.haloacl.notification.counter++;
    if(yestext == null){
        yestext = gHACLLanguage.getMessage('ok');
    };
    if(notext == null){
        notext = gHACLLanguage.getMessage('cancel');
    };

    new Insertion.Bottom(renderedTo,"<div id='haloacl_notification"+YAHOO.haloacl.notification.counter+"' class='yui-skin-sam'>&nbsp;</div>");

    if(YAHOO.haloacl.debug)console.log("create dialog called");
    var handleYes = function(content) {
        callback.yes(content);
        this.hide();
    };
    var handleNo = function(content) {
        callback.no(content);
        this.hide();
    };

    var dialog = 	new YAHOO.widget.SimpleDialog("dialog"+YAHOO.haloacl.notification.counter,
    {
        width: "300px",
        fixedcenter: true,
        visible: false,
        draggable: false,
        close: true,
        text: content,
        icon: YAHOO.widget.SimpleDialog.ICON_INFO,
        constraintoviewport: true,
        buttons: [ {
            text:yestext,
            handler:handleYes,
            isDefault:true
        },

        {
            text:notext,
            handler:handleNo
        } ]
    } );

    dialog.setHeader(title);

    // Render the Dialog
    dialog.render('haloacl_notification'+YAHOO.haloacl.notification.counter);
    dialog.show();

    if(YAHOO.haloacl.debug)console.log("create dialog finished");

};

/**
 *  add listener to element
 *  @param element to append to
 *  @param event (e.g. click)
 *  @param callback
 *
 */
YAHOO.haloacl.notification.subscribeToElement = function(elementId, event, callback){
    YAHOO.util.Event.addListener($(elementId), event, callback);
};

/**
 *  displays inline notification
 *  @param element to append to
 *  @param event (e.g. click)
 *  @param callback
 *
 */
YAHOO.haloacl.notification.showInlineNotification = function(content, targetdiv){
    if(YAHOO.haloacl.debug)console.log("trying to add notification to targetdiv:"+targetdiv);
    // DISABLED
    //$(targetdiv).innerHTML = content;
}
/**
 *  hides inline notification
 *  @param notification-div
 *
 */
YAHOO.haloacl.notification.hideInlineNotification = function(targetdiv){
    $(targetdiv).innerHTML = "";
}

/**
 *  hides all inline notification
 *
 */
YAHOO.haloacl.notification.clearAllNotification = function(){
    $$('.haloacl_inline_notification').each(function(item){
       item.innerHTML = "";
    });
}