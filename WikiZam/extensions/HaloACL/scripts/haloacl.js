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
 * This file contains javascript-basics
 * so used throughout the whole gui
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

// Globals
YAHOO.namespace("haloacl");
YAHOO.namespace("haloaclrights");
YAHOO.namespace ("haloacl.constants");
YAHOO.namespace ("haloacl.settings");
YAHOO.namespace ("haloacl.manageUser");

// log debug information to js-console
YAHOO.haloacl.debug = false;

if(YAHOO.haloacl.debug){
    console.log("======== DEBUG MODE ENABLED =========");
    console.log("-- visit haloacl.js to switch mode --");
    console.log("=====================================");
}


// set standard define-type for createacl panel
// must be one of: individual, privateuse, allusers, allusersregistered, allusersanonymous
YAHOO.haloacl.createAclStdDefine = "individual";

// delay between queries in treeview || MILLISECONDS
YAHOO.haloacl.filterQueryDelay = 200;


YAHOO.haloacl.panelcouner = 0;
// has all checked users from grouptree
YAHOO.haloacl.clickedArrayGroups = new Array();
// has all checked users form datatable
YAHOO.haloacl.clickedArrayUsers = new Array();
// has all selected ACL templates from template tree
YAHOO.haloacl.selectedTemplates = new Array();
// has groups for the checked users [panelid][username] = groupsstring
YAHOO.haloacl.clickedArrayUsersGroups = new Array();

// has all checked users from righttree
YAHOO.haloaclrights.clickedArrayGroups = new Array();

// knows, if the actual modificationrights have been saved
YAHOO.haloacl.modrightssaved = false;




/**
 *  builds main tab view
 *  @param target-container
 *  @param page-title (used for quickaccess-toolbar-access
 *  @param show whitelist-tab?
 *  @param active-tab e.g. createACL, manageACLs, manageUsers, whitelists (passed-thorugh get-parameter)
 *
 */
YAHOO.haloacl.buildMainTabView = function(containerName,requestedTitle,showWhitelistTab,activeTab){
    if(YAHOO.haloacl.debug) console.log("got requestedtitle:"+requestedTitle);
    if(requestedTitle != null){
        YAHOO.haloacl.requestedTitle = requestedTitle;
    }else{
        YAHOO.haloacl.requestedTitle = "";
    }
    
    var createACLActive = false;
    var manageUserActive = false;
    var manageACLActive = false;
    var whitelistActive = false;
    var globalPermissionsActive = false;
    if(activeTab == "createACL"){
        createACLActive = true;
    }else if(activeTab == "manageACLs"){
        manageACLActive = true;
    }else if (activeTab == "manageUsers"){
        manageUserActive = true;
    }else if(activeTab == "whitelists"){
        whitelistActive = true;
    }else if(activeTab == "globalPermissions"){
        globalPermissionsActive = true;
    }




    YAHOO.haloacl.haloaclTabs = new YAHOO.widget.TabView(containerName);
	
	//--- Global permissions tab ---
    var globalPermissionTab = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('globalPermissions'),
        dataSrc:'haclGlobalPermissionsPanel',
        cacheData:false,
        active:globalPermissionsActive,
        id:'globalPermissionsPanel_button'
    });
    globalPermissionTab._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclTabs.addTab(globalPermissionTab);
    globalPermissionTab.addListener('click', function(e){});
    $(globalPermissionTab.get('contentEl')).setAttribute('id','globalpermissionsTab');
    globalPermissionTab.addListener('click', function(e){
        try{
            $('manageaclmainTab').innerHTML = "";
            $('manageuserTab').innerHTML = "";
        }catch(e){}
    });
    new YAHOO.widget.Tooltip("globalPermissionsPanel_tooltip", {
        context:"globalPermissionsPanel_button",
        text:gHACLLanguage.getMessage('globalPermissionsTooltip'),
        zIndex :10
    });
	
	
	//--- Create ACL tab ---
    var tab1 = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('createACL'),
        dataSrc:'haclCreateACLPanels',
        cacheData:false,
        active:createACLActive,
        id:'createACLPanel_button'
    });
    tab1._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclTabs.addTab(tab1);
    tab1.addListener('click', function(e){});
    $(tab1.get('contentEl')).setAttribute('id','creataclTab');
    tab1.addListener('click', function(e){
        try{
            $('manageaclmainTab').innerHTML = "";
            $('manageuserTab').innerHTML = "";
        }catch(e){}
    });

    new YAHOO.widget.Tooltip("createACLPanel_tooltip", {
        context:"createACLPanel_button",
        text:gHACLLanguage.getMessage('createStdACLTooltip'),
        zIndex :10
    });


    // ------

    var tab2 = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('manageACLs'),
        dataSrc:'haclCreateManageACLPanels',
        cacheData:false,
        active:manageACLActive,
        id:"manageACLPanel_button"
    });
    tab2._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclTabs.addTab(tab2);
    tab2.addListener('click', function(e){});
    $(tab2.get('contentEl')).setAttribute('id','manageaclmainTab');
    tab1.addListener('click', function(e){
        try{
            $('creataclTab').innerHTML = "";
            $('manageuserTab').innerHTML = "";
        }catch(e){}

    });

    new YAHOO.widget.Tooltip("manageACLPanel_tooltip", {
        context:"manageACLPanel_button",
        text:gHACLLanguage.getMessage('manageACLTooltip'),
        zIndex :10
    });
    // ------

    var tab3 = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('manageUser'),
        dataSrc:'haclManageUserContent',
        cacheData:false,
        active:manageUserActive,
        id:"manageUserContent_button"
    });
    tab3._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclTabs.addTab(tab3);
    tab3.addListener('click', function(e){});
    $(tab3.get('contentEl')).setAttribute('id','manageuserTab');
    tab1.addListener('click', function(e){
        try{
            $('creataclTab').innerHTML = "";
            $('manageaclmainTab').innerHTML = "";
        }catch(e){}
    });

    new YAHOO.widget.Tooltip("manageUserContent_tooltip", {
        context:"manageUserContent_button",
        text:gHACLLanguage.getMessage('manageUserTooltip'),
        zIndex :10
    });
    // ------

    if(showWhitelistTab == "true"){
        var tab4 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('whitelists'),
            dataSrc:'haclWhitelistsContent',
            cacheData:false,
            active:whitelistActive,
            id:"whitelist_button"
        });
        tab4._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab4);
        tab4.addListener('click', function(e){});
        $(tab4.get('contentEl')).setAttribute('id','whitelistsTab');

        new YAHOO.widget.Tooltip("whitelist_tooltip", {
            context:"whitelist_button",
            text:gHACLLanguage.getMessage('manageWhitelistTooltip'),
            zIndex :10
        });
    }
// ------

};


/**
 *  builds subtab view
 *  @param target-container
 *
 */
YAHOO.haloacl.buildSubTabView = function(containerName){    
    YAHOO.haloacl.haloaclTabs = new YAHOO.widget.TabView(containerName);
    var manageAclActive = true;
    var manageDefaultTemplateActive = false;
    var createQuickActive = false;
    if(YAHOO.haloacl.activeSubTab == "manageDefaultTemplate"){
        manageAclActive = false;
        manageDefaultTemplateActive = true;
        createQuickActive = false;
    }else if(YAHOO.haloacl.activeSubTab == "quickacl"){
        manageAclActive = false;
        manageDefaultTemplateActive = false;
        createQuickActive = true;
    }

    if (containerName == "haloaclsubViewManageACL") {
        var tab1 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('manageExistingACLs'),
            dataSrc:'haclCreateManageExistingACLContent',
            cacheData:false,
            active:manageAclActive,
            id:"createStdAclTab"
        });
        tab1._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab1);
        tab1.addListener('click', function(e){});


        // ------

        var tab2 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('manageQuickAccess'),
            dataSrc:'haclCreateQuickAclTab',
            cacheData:false,
            active:createQuickActive,
            id:"createQuickAclTab"
        });
        tab2._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab2);
        tab2.addListener('click', function(e){});

        // ------

        var tab3 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('manageDefaultUserTemplate'),
            dataSrc:'haclCreateManageUserTemplateContent',
            cacheData:false,
            active:manageDefaultTemplateActive,
            id:"createTmpAclTab"
        });
        tab3._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab3);
        tab3.addListener('click', function(e){});



    } else if (containerName == "haloaclsubView") {
        var createAclACtive = true;
        var createTplActive = false;
        var createDefUserActive = false;
        if(YAHOO.haloacl.activeSubTab == "manageDefaultTemplate"){
            createAclACtive = false;
            createDefUserActive = true;
            createTplActive = false;
        }else if(YAHOO.haloacl.activeSubTab == "createTemplate"){
            createAclACtive = false;
            createDefUserActive = false;
            createTplActive = true;
        }


        var tab1 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('createStandardACL'),
            dataSrc:'haclCreateAclContent',
            cacheData:false,
            active:createAclACtive,
            id:"createStdAclTab"
        });
        tab1._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab1);
        tab1.addListener('click', function(e){
            $('createTmpAclTab_content').innerHTML = "";
            $('createUserAclTab_content').innerHTML = "";
        });
        $(tab1.get('contentEl')).setAttribute('id','createStdAclTab_content');


        // ------

        var tab2 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('createACLTemplate'),
            dataSrc:'haclCreateAclTemplateContent',
            cacheData:false,
            active:createTplActive,
            id:"createTmpAclTab"
        });
        tab2._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab2);
        tab2.addListener('click', function(e){
            $('createStdAclTab_content').innerHTML = "";
            $('createUserAclTab_content').innerHTML = "";
        });
        $(tab2.get('contentEl')).setAttribute('id','createTmpAclTab_content');



        // ------

        var tab3 = new YAHOO.widget.Tab({
            label: gHACLLanguage.getMessage('createDefaultUserTemplate'),
            dataSrc:'haclCreateAclUserTemplateContent',
            cacheData:false,
            active:createDefUserActive,
            id:"createUserAclTab"
        });
        tab3._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclTabs.addTab(tab3);
        tab3.addListener('click', function(e){
            $('createStdAclTab_content').innerHTML = "";
            $('createTmpAclTab_content').innerHTML = "";
        });
        $(tab3.get('contentEl')).setAttribute('id','createUserAclTab_content');

    }

};

/**
 *  datasource for tabview
 *  so tab-data is loaded via this tabDataConnect
 *
 */
YAHOO.haloacl.tabDataConnect = function() {

    // resetting checked nodes
    YAHOO.haloacl.checkedInRightstree = null;
    YAHOO.haloacl.checkedInGroupstree = null;

    var tab = this;

    var querystring = "rs="+tab.get('dataSrc');
    var postData = tab.get('postData');
    
    if(postData != null){
        for(param in postData){
            querystring = querystring + "&rsargs[]="+postData[param];
        }
    }
    YAHOO.util.Dom.addClass(tab.get('contentEl').parentNode, tab.LOADING_CLASSNAME);
    tab._loading = true;
    new Ajax.Updater(tab.get('contentEl'), "?action=ajax", {
        //method:tab.get('loadMethod'),
        method:'post',
        parameters:querystring,
        asynchronous:true,
        evalScripts:true,
        onSuccess: function(o) {
            YAHOO.util.Dom.removeClass(tab.get('contentEl').parentNode, tab.LOADING_CLASSNAME);
            tab.set('dataLoaded', true);
            tab._loading = false;
        },
        onFailure: function(o) {
            YAHOO.util.Dom.removeClass(tab.get('contentEl').parentNode, tab.LOADING_CLASSNAME);
            tab._loading = false;
        }
    });
};

/**
 *  renders result of an ajax-call to a div
 *  @param target-container
 *  @param actionname
 *  @param parameterlist (json)
 *
 */
YAHOO.haloacl.loadContentToDiv = function(targetdiv, action, parameterlist){
    /*   var queryparameterlist = {
        rs:action
    };
     */
    var querystring = "rs="+action;

    if(parameterlist != null){
        for(param in parameterlist){
            // temparray.push(parameterlist[param]);
            querystring = querystring + "&rsargs[]="+parameterlist[param];
        }
    }

    new Ajax.Updater(targetdiv, "?action=ajax", {
        //method:tab.get('loadMethod'),
        method:'post',
        // parameters: queryparameterlist,
        parameters: querystring,
        asynchronous:true,
        evalScripts:true,
        onSuccess: function(o) {
            tab._loading = false;
            $(targetdiv).scrollTo();
            $(targetdiv).visible();
        },
        onFailure: function(o) {
        }
    });
};

/**
 *  takes xml, and sends that to an action
 *  @param xml (string)
 *  @param actionname
 *  @param callback
 *  @param parentNode
 *
 */
YAHOO.haloacl.sendXmlToAction = function(xml, action,callback,parentNode){
    if(callback == null){
        callback = function(result){
            alert("stdcallback:"+result);
        }
    }
    var querystring = "rs="+action+"&rsargs[]="+escape(xml);
    if(parentNode != ""){
        querystring += "&rsargs[]="+escape(parentNode);
    }

    new Ajax.Request("?action=ajax",{
        method:'post',
        onSuccess:callback,
        onFailure:callback,
        parameters:querystring
    });


};

/**
 *  calls remoteaction with parameters and executes callback if given
 *  @param actionname
 *  @param paramterlist (json)
 *  @param callback
 *
 */
YAHOO.haloacl.callAction = function(action, parameterlist, callback){
    if(callback == null){
        callback = function(result){
            alert("stdcallback:"+result);
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
        onSuccess:callback,
        onFailure:callback,
        parameters:querystring
    });
};

/**
 *  toggles panel
 *  @param panelid
 *
 */
YAHOO.haloacl.togglePanel = function(panelid){
    var element = $('content_'+panelid);
    var button = $('exp-collapse-button_'+panelid);
    if(element.visible()){
        button.removeClassName('haloacl_panel_button_collapse');
        button.addClassName('haloacl_panel_button_expand');
        element.hide();
    }else{
        button.addClassName('haloacl_panel_button_collapse');
        button.removeClassName('haloacl_panel_button_expand');
        element.show();
    }
};

/**
 *  removes panel
 *  @param panelid
 *  @param callback
 *
 */
YAHOO.haloacl.removePanel = function(panelid,callback){
    YAHOO.haloacl.notification.createDialogYesNo("content",gHACLLanguage.getMessage('confirmDeleteReset'),gHACLLanguage.getMessage('confirmDeleteMessage'),{
        yes:function(){
            var element = $(panelid);
            element.remove();
            if(callback != null){
                callback();
            }
            YAHOO.haloacl.callAction("haclRemovePanelForTemparray",{
                panelid:panelid
            },function(){});

        },
        no:function(){}
    },"Ok","Cancel");

};
/**
 *  closes panel
 *  @param panelid
 *
 */
YAHOO.haloacl.closePanel = function(panelid){
    var element = $('content_'+panelid);
    var button = $('exp-collapse-button_'+panelid);
    button.removeClassName('haloacl_panel_button_collapse');
    button.addClassName('haloacl_panel_button_expand');
    element.hide();
};

/**
 *  builds rightpanel tabs
 *  @param targetdiv
 *  @param predfine-type
 *  @param is readonly? (only renders assigned tab with deleteicon disabled)
 *  @param do preload?
 *  @param rightId to preload
 *
 */
YAHOO.haloacl.buildRightPanelTabView = function(containerName, predefine, readOnly, preload, preloadRightId){

    
    YAHOO.haloacl.haloaclRightPanelTabs = new YAHOO.widget.TabView(containerName);
    var parameterlist = {
        panelid:containerName,
        predefine:predefine,
        readOnly:readOnly,
        preload:preload,
        preloadRightId:preloadRightId,
		context: 'RightPanel'
    };



    myLabel = gHACLLanguage.getMessage('selectDeselect');
    if (!readOnly) {
        selectActive = true;
        selectDisabled = false;
        assActive = false;
    } else {
        selectActive = false;
        selectDisabled = false;
        assActive = true;
    }

    if(!readOnly){
        var tab1 = new YAHOO.widget.Tab({
            label: myLabel,
            dataSrc:'haclRightPanelSelectDeselectTab',
            cacheData:false,
            active:selectActive,
            disabled:selectDisabled,
            postData:parameterlist
        });
        tab1._dataConnect = YAHOO.haloacl.tabDataConnect;
        YAHOO.haloacl.haloaclRightPanelTabs.addTab(tab1);
        tab1.addListener('click', function(e){
            $('rightPanelAssignedTab'+containerName).innerHTML = "";
        });
        //$(tab1.get('contentEl')).style.display = 'none';
        $(tab1.get('contentEl')).setAttribute('id','rightPanelSelectDeselectTab'+containerName);
        $(tab1.get('contentEl')).setAttribute('class','haloacl_rightPanelTab');
    }
    
    var tab2 = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('assigned'),
        dataSrc:'haclRightPanelAssignedTab',
        cacheData:false,
        active:assActive,
        //id:"rightPanelAssignedTab"+containerName,
        postData:parameterlist
    });

    tab2._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclRightPanelTabs.addTab(tab2);
    tab2.addListener('click', function(e){
        $('rightPanelSelectDeselectTab'+containerName).innerHTML = "";
    });
    $(tab2.get('contentEl')).setAttribute('id','rightPanelAssignedTab'+containerName);
    $(tab2.get('contentEl')).setAttribute('class','haloacl_rightPanelTab');


    

// ------

};

// --- handling global arrays for selection of users and groups

/**
 *  removes user from checked-users array
 *  @param panelid
 *  @param name
 *  @param deletetable-type (user, group, usergroup - dependencies=
 *
 */
YAHOO.haloacl.removeUserFromUserArray = function(panelid,name,deletable){
    if(YAHOO.haloacl.debug) console.log("deletable-type:"+deletable);
    if(YAHOO.haloacl.debug) console.log("array before deletion");
    if(YAHOO.haloacl.debug) console.log(YAHOO.haloacl.clickedArrayUsers[panelid]);
    var elementToRemove = 0;
    for(i=0;i<YAHOO.haloacl.clickedArrayUsers[panelid].length;i++){
        if(YAHOO.haloacl.clickedArrayUsers[panelid][i] == name){
            elementToRemove = i;
        }
    }
    YAHOO.haloacl.clickedArrayUsers[panelid].splice(elementToRemove,1);

    var element = $(panelid+"assigned"+name);
    if(deletable == "user"){
        try{
            element.parentNode.parentNode.parentNode.hide();
        }
        catch(e){
            if(YAHOO.haloacl.debug) console.log("hiding element failed");
            if(YAHOO.haloacl.debug) console.log(e);
        }
    }else{
        deletable == "groupuser";
    }{
        try{
            element.hide();
        //element.parentNode.parentNode.parentNode.hide();
        }
        catch(e){
            if(YAHOO.haloacl.debug) console.log(e);
        }
    }
    if(YAHOO.haloacl.debug) console.log("array after deletion");
    if(YAHOO.haloacl.debug) console.log(YAHOO.haloacl.clickedArrayUsers[panelid]);


    var fncname = "YAHOO.haloacl.refreshPanel_"+panelid.substr(14)+"();";
    eval(fncname);


};
/**
 *  adds user to checked-users array
 *  @param panelid
 *  @param name
 *
 */
YAHOO.haloacl.addUserToUserArray = function(panelid, name){
//    if(name.length > 2){

        if (!YAHOO.haloacl.clickedArrayUsers[panelid]){
            YAHOO.haloacl.clickedArrayUsers[panelid] = new Array();
        }
        if(YAHOO.haloacl.debug) console.log("adding user "+name+" to "+panelid+"-array");
        var alreadyContained = false;
        for(i=0;i<YAHOO.haloacl.clickedArrayUsers[panelid].length;i++){
            if(YAHOO.haloacl.clickedArrayUsers[panelid][i] == name){
                alreadyContained = true;
                if(YAHOO.haloacl.debug) console.log("found element - not creating new entry");
            }
        }
        if(!alreadyContained){
            YAHOO.haloacl.clickedArrayUsers[panelid].push(name);
        }
//    }else{
//        if(YAHOO.haloacl.debug) console.log("to short username added - skipping");
//    }

    if(YAHOO.haloacl.debug) console.log(":::"+YAHOO.haloacl.clickedArrayUsers[panelid]);


};

/**
 *  adds group to checked-groups array
 *  @param panelid
 *  @param name
 *
 */
YAHOO.haloacl.addGroupToGroupArray = function(panelid, name){
//    if(name.length > 2){
        if(!YAHOO.haloacl.clickedArrayGroups[panelid]){
            YAHOO.haloacl.clickedArrayGroups[panelid] = new Array();
        }
        if(YAHOO.haloacl.debug) console.log("adding group "+name+" to "+panelid+"-array");
        var alreadyContained = false;
        for(i=0;i<YAHOO.haloacl.clickedArrayGroups[panelid].length;i++){
            if(YAHOO.haloacl.clickedArrayGroups[panelid][i] == name){
                alreadyContained = true;
                if(YAHOO.haloacl.debug) console.log("found element - not creating new entry");
            }
        }
        if(!alreadyContained){
            YAHOO.haloacl.clickedArrayGroups[panelid].push(name);
        }
//    }else{
//        if(YAHOO.haloacl.debug) console.log("to short groupname added - skipping");
//    }
};

YAHOO.haloacl.getGroupsArray = function (panelid){
    return YAHOO.haloacl.clickedArrayGroups[panelid];
};

/**
 *  removes group from checked-groups array
 *  @param panelid
 *  @param name
 *
 */
YAHOO.haloacl.removeGroupFromGroupArray = function(panelid,name){
    if(YAHOO.haloacl.debug) console.log("trying to remove "+name+" from "+panelid+"-array");
    var elementToRemove = 0;
    for(i=0;i<YAHOO.haloacl.clickedArrayGroups[panelid].length;i++){
        if(YAHOO.haloacl.clickedArrayGroups[panelid][i] == name){
            elementToRemove = i;
            if(YAHOO.haloacl.debug) console.log("found element");
        }
    }
    YAHOO.haloacl.clickedArrayGroups[panelid].splice(elementToRemove,1);
};

/**
 *  checks if name is in group-array
 *  @param panelid
 *  @param name
 *  @return true/false
 *
 */
YAHOO.haloacl.isNameInGroupArray = function(panelid, name){
    /*
    for(i=0;i<YAHOO.haloacl.clickedArrayGroups[panelid].length;i++){
        if(YAHOO.haloacl.clickedArrayGroups[panelid][i] == name){
            return true;
        }
    }
    return false;
    */
    if(YAHOO.haloacl.clickedArrayGroups[panelid] && YAHOO.haloacl.clickedArrayGroups[panelid].indexOf(name) == -1){
        return false;
    }
    return true;
};

/**
 *  checks if name is in group-array
 *  @param panelid
 *  @param name
 *  @return true/false
 *
 */
YAHOO.haloacl.isNameInUsersGroupsArray = function(panelid, name){
    for(i=0;i<YAHOO.haloacl.clickedArrayGroups[panelid].length;i++){
        if(YAHOO.haloacl.clickedArrayGroups[panelid][i] == name){
            return true;
        }
    }
    return false;

};



/**
 *  checks if name is in group-array
 *  @param panelid
 *  @param name
 *  @return true/false
 *
 */
YAHOO.haloacl.isNameInUserArray = function(panelid, name){
    for(i=0;i<YAHOO.haloacl.clickedArrayUsers[panelid].length;i++){
        if(YAHOO.haloacl.clickedArrayUsers[panelid][i] == name){
            return true;
        }
    }
    return false;

};

/**
 *  checks if panel has groups or users
 *  @param panelid
 *
 */
YAHOO.haloacl.hasGroupsOrUsers = function(panelid){
    if(YAHOO.haloacl.debug) console.log("testing "+panelid);
    if (((YAHOO.haloacl.clickedArrayGroups[panelid]) && (YAHOO.haloacl.clickedArrayGroups[panelid].length > 0)) || (YAHOO.haloacl.clickedArrayUsers[panelid] && (YAHOO.haloacl.clickedArrayUsers[panelid].length > 0))) {
        if(YAHOO.haloacl.debug) console.log("available");
        return true;
    } else {
        if(YAHOO.haloacl.debug) console.log("not available");
        return false;
    }

};

/**
 *  builds grouppanel-tabview
 *  @param targetdiv/container
 *  @param predfine type (e.g. privateuse,...)
 *  @param is readonly?
 *  @param do preload?
 *  @param right id to preload
 *
 */
YAHOO.haloacl.buildGroupPanelTabView = function(containerName, predefine, readOnly, preload, preloadRightId){
    YAHOO.haloacl.haloaclRightPanelTabs = new YAHOO.widget.TabView(containerName);
    var parameterlist = {
        panelid:containerName,
        predefine:predefine,
        readOnly:readOnly,
        preload:preload,
        preloadRightId:preloadRightId,
		context: 'GroupPanel'
    };

    //if (!readOnly) {

    var tab1 = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('selectDeselect'),
        dataSrc:'haclRightPanelSelectDeselectTab',
        cacheData:false,
        active:true,
        postData:parameterlist
    });
    tab1._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclRightPanelTabs.addTab(tab1);
    tab1.addListener('click', function(e){});
    $(tab1.get('contentEl')).setAttribute('id','rightPanelSelectDeselectTab'+containerName);
    //}


    // ------

    var tab2 = new YAHOO.widget.Tab({
        label: gHACLLanguage.getMessage('assigned'),
        dataSrc:'haclRightPanelAssignedTab',
        cacheData:false,
        active:false,
        postData:parameterlist
    });

    tab2._dataConnect = YAHOO.haloacl.tabDataConnect;
    YAHOO.haloacl.haloaclRightPanelTabs.addTab(tab2);
    tab2.addListener('click', function(e){});
    $(tab2.get('contentEl')).setAttribute('id','rightPanelAssignedTab'+containerName);



// ------


};



/**
 *  deletes sd and creates a notification
 *  @param sdID
 *
 */
YAHOO.haloacl.deleteSD = function(sdId){
    YAHOO.haloacl.callAction('haclDeleteSecurityDescriptor', {
        sdId:sdId
    }, function(result){
        YAHOO.haloacl.notification.createDialogOk("content","ManageACL",gHACLLanguage.getMessage('rightHasBeenDeleted'),{
            yes:function(){
                window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=manageACLs';
            }
        });
    });

};

/**
 *  resets all hightlighted elements
 *
 */
YAHOO.haloacl.removeHighlighting = function(){
    $$('.highlighted').each(function(item){
        $(item).removeClassName("highlighted");
    });
};

/**
 *  creates popup
 *  @param right-id to be loaded
 *  @param popups-label
 *  @param anchor/container for popup
 *
 */
YAHOO.haloaclrights.popup = function(id, label, anchorId, valid){

    /*
    if(YAHOO.haloaclrights.popupPanel == null){

        YAHOO.haloaclrights.popupPanel = new YAHOO.widget.Panel('popup_'+id,{
            close:true,
            visible:true,
            draggable:true,
            resizable:true,
            context:  ["anchorPopup_"+id,"tl","bl", ["beforeShow"]]
        });
        popupClose = function(type, args) {
            //YAHOO.haloaclrights.popupPanel.destroy();
        }
        YAHOO.haloaclrights.popupPanel.subscribe("hide", popupClose);
    }

    YAHOO.haloaclrights.popupPanel.setHeader(label);
    YAHOO.haloaclrights.popupPanel.setBody('<div id="popup_content_'+id+'">');
    YAHOO.haloaclrights.popupPanel.render();
    YAHOO.haloaclrights.popupPanel.show();
*/

	if (typeof valid != 'undefined') {
		if (valid == false) {
			var uri = wgServer + wgScript + "/ACL:" + label;
			window.open(uri, "_blank");
			return;
		}
	}
    if (!anchorId) {
        anchorId = id;
    }
    var now = new Date();
    now = now.getTime();

    var myPopup = new YAHOO.widget.Panel('popup_'+anchorId,{
        close:true,
        visible:true,
        draggable:true,
        resizable:true,
        constraintoviewport:true,
        zIndex :10,
        width:"1000px",
        context:  ["anchorPopup_"+anchorId,"tl","bl", ["beforeShow"]]
    // context:  ["content","tl","bl", ["beforeShow"]]
    });
    popupClose = function(type, args) {
    //this.hide();
    //this.destroy();

    }
    //myPopup.subscribe("hide", popupClose);

    myPopup.setHeader('<div class="haloacl_infobutton"></div><span class="popup_title">'+'ACL:'+label+"</span>");
    myPopup.setBody('<div id="popup_content_'+id+'">');
    myPopup.render();
    myPopup.show();
    YAHOO.haloacl.loadContentToDiv('popup_content_'+id,'haclGetSDRightsPanel',{
        sdId:id,
        readOnly:'true'
    });

};

/**
 *  add tooltip to element
 *  @param tooltip-instance-name
 *  @param element to append to
 *  @param tooltip-text
 *
 */
YAHOO.haloacl.addTooltip = function(name, context, text){
    new YAHOO.widget.Tooltip(name, {
        context:context,
        text:text,
        zIndex :10
    });
}

/**
 *  discard changes in createacl
 *
 */
YAHOO.haloacl.discardChanges_createacl = function(){
    //YAHOO.haloacl.notification.createDialogYesNo = function (renderedTo,title,content,callback,yestext,notext){
    YAHOO.haloacl.notification.createDialogYesNo("content",gHACLLanguage.getMessage('discardChanges'),gHACLLanguage.getMessage('discardChangesMessage'),{
        yes:function(){
            window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=createACL';
        },
        no:function(){}
    },"Ok","Cancel");
}
/**
 *  discard changes in managegroups
 *
 */
YAHOO.haloacl.discardChanges_users = function(){
    //YAHOO.haloacl.notification.createDialogYesNo = function (renderedTo,title,content,callback,yestext,notext){
    YAHOO.haloacl.notification.createDialogYesNo("content",gHACLLanguage.getMessage('discardChanges'),gHACLLanguage.getMessage('discardChangesMessage'),{
        yes:function(){
            window.location.href=YAHOO.haloacl.specialPageUrl+'?activetab=manageUsers';
        },
        no:function(){}
    },"Ok","Cancel");
}