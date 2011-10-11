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
 * This file contains javascript used for the usertable
 * (in rightpanel, grouppanel)
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

/**
 *  creates usedatatable
 *  @param  target-div-id
 *  @param  panelid / idenfifier
 *
 */
YAHOO.haloacl.userDataTable = function(divid,panelid) {

    

    // custom defined formatter
    this.mySelectFormatter = function(elLiner, oRecord, oColumn, oData) {
		if (YAHOO.haloacl.clickedArrayUsers[panelid] == undefined){
			YAHOO.haloacl.clickedArrayUsers[panelid] = new Array();
		}

        var checkedFromJS = false;
        var groupsstring = ""+oRecord._oData.groups;
        for(i=0;i<YAHOO.haloacl.clickedArrayUsers[panelid].length;i++){
            if(YAHOO.haloacl.clickedArrayUsers[panelid][i] == oRecord._oData.name){
                checkedFromJS = true;
            }
        }
       
        if(oData == true || checkedFromJS == true){
            elLiner.innerHTML = "<input onClick='YAHOO.haloacl.handleDatatableClick_"+panelid+"(this);' id='checkbox_"+divid+"_"+oRecord._oData.name+"' type='checkbox' groups='"+groupsstring+"' checked='' class='"+divid+"_users' name='"+oRecord._oData.name+"' />";
        }else{
            elLiner.innerHTML = "<input onClick='YAHOO.haloacl.handleDatatableClick_"+panelid+"(this);' id='checkbox_"+divid+"_"+oRecord._oData.name+"' type='checkbox' groups='"+groupsstring+"' class='"+divid+"_users' name='"+oRecord._oData.name+"' />";
        }
        var a = new YAHOO.widget.Tooltip("hacl_toolbarcontainer_section3_tooltip", {
            context:"checkbox_"+divid+"_"+oRecord._oData.name,
            text:gHACLLanguage.getMessage('selectDeselectUser'),
            zIndex :10
        });
            
    };
    this.myGroupFormatter = function(elLiner, oRecord, oColumn, oData) {
        var groupsstring = ""+oRecord._oData.groups;
        var resultstring = "<div name='"+oRecord._oData.name+"' groups='"+groupsstring+"' class='haloacl_datatable_groupscol  datatable_usergroups haloacl_datatable_groupdiv"+this.panelid+"'></div>";
        elLiner.innerHTML = resultstring;
    };

    this.myNameFormatter = function(elLiner, oRecord, oColumn, oData) {
        //elLiner.innerHTML = "<span class='"+divid+"_usersgroups' groups=\""+oRecord._oData.groups+"\">"+oRecord._oData.name+"</span>";
        elLiner.innerHTML = "<span  class='userdatatable_name' groups=\""+oRecord._oData.groups+"\">"+oRecord._oData.name+"</span>";

    };

    // building shortcut for custom formatter
    //YAHOO.widget.DataTable.Formatter.mySelect = this.mySelectFormatter;
    //YAHOO.widget.DataTable.Formatter.myGroup = this.myGroupFormatter;
    //YAHOO.widget.DataTable.Formatter.myName = this.myNameFormatter;

    var myColumnDefs = [ // sortable:true enables sorting
 
    {
        key:"name",
        label:gHACLLanguage.getMessage('name'),
        sortable:false,
        formatter:this.myNameFormatter
    },
    {
        key:"groups",
        label:gHACLLanguage.getMessage('groups'),
        sortable:false
        ,
        formatter:this.myGroupFormatter
    },
    {
        key:"checked",
        label:gHACLLanguage.getMessage('selected'),
        formatter:this.mySelectFormatter
    }

    ];

    // datasource for this userdatatable
    var myDataSource = new YAHOO.util.DataSource("?action=ajax");
    myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
    myDataSource.connMethodPost = true;
    myDataSource.responseSchema = {
        resultsList: "records",
        fields: [
        {
            key:"id",
            parser:"number"
        },
        {
            key:"name"
        },
        {
            key:"groups"
        },
        {
            key:"checked"
        }
        ],
        metaFields: {
            totalRecords: "totalRecords" // Access to value in the server response
        }
    };

    // our customrequestbuilder (attached to the datasource)
    // this requestbuilder, builds a valid mediawiki-ajax-request
    var customRequestBuilder = function(oState, oSelf) {
        // Get states or use defaults
        oState = oState;
        var totalRecords = oState.pagination.totalRecords;
        var sort = (oState.sortedBy) ? oState.sortedBy.key : null;
        var dir = (oState.sortedBy && oState.sortedBy.dir == YAHOO.widget.DataTable.CLASS_DESC) ? "desc" : "asc";
        var startIndex = oState.pagination.recordOffset;
        var results = oState.pagination.rowsPerPage;
        /* make the initial cache of the form data */

        if(myDataTable.query == null){
            myDataTable.query = '';
        }

        var filter = $('datatable_filter_'+myDataTable.panelid).value;
        
        return "rs=haclGetUsersForUserTable&rsargs[]="
        +myDataTable.query+"&rsargs[]="+sort
        +"&rsargs[]="+dir
        +"&rsargs[]="+startIndex
        +"&rsargs[]="+results
        +"&rsargs[]="+filter;


    };



    var setupCheckboxHandling = function(){
        //if(YAHOO.haloacl.debug) console.log("checkAllSelectesUsers fired");
        if(YAHOO.haloacl.debug) console.log(YAHOO.haloacl.clickedArrayUsers);
        $$('.datatableDiv_'+panelid+'_users').each(function(item){
            //if(YAHOO.haloacl.debug) console.log("found element");
            //if(YAHOO.haloacl.debug) console.log(item.name);
            for(i=0;i<YAHOO.haloacl.clickedArrayUsers[panelid].length;i++){
                if(YAHOO.haloacl.clickedArrayUsers[panelid][i] == item.name){
                    item.checked = true;
                }
            }

        });

    };


    var handlePagination = function(state){

        //var divid = myPaginator._containers.parentNode.id;
        if(YAHOO.haloacl.debug) console.log("should be:"+"right_tabview_create_acl_right_0");
        //if(YAHOO.haloacl.debug) console.log("is:"+divid);
        
        var divid = myPaginator._containers[0].parentNode.children[0].children[0].children[0].id;

        if(YAHOO.haloacl.debug) console.log("changeRequest fired");
        var displaying = state.totalRecords - state.recordOffset;
        if(displaying > state.rowsPerPage){
            displaying = state.rowsPerPage
        };
        var to = displaying*1 + state.recordOffset*1;
        var from = state.totalRecords > 0 ? (state.recordOffset*1+1) : 0;
        var html = from + "<span style='font-weight:normal'>"+" - "+ "</span> "+ to+ "<span style='font-weight:normal'> "    +gHACLLanguage.getMessage('from') + "&nbsp;</span>" +state.totalRecords;

        //        var html = from + " " +gHACLLanguage.getMessage('from')+ " " + to   + " " +gHACLLanguage.getMessage('to')+ " " +state.totalRecords;
        $(divid).innerHTML = html;
        if(YAHOO.haloacl.debug) console.log($('datatablepaging_count_'+divid));
    };


    var myPaginator = new YAHOO.widget.Paginator({
        rowsPerPage:10,
        containers:'datatablepaging_'+divid,
        pageLinks:3
    });

    //myPaginator.subscribe("changeRequest",handlePagination);

  

    // userdatatable configuration
    var myConfigs = {
        initialRequest: "rs=haclGetUsersForUserTable&rsargs[]=all&rsargs[]=name&rsargs[]=asc&rsargs[]=0&rsargs[]=5&rsargs[]=", // Initial request for first page of data
        dynamicData: true, // Enables dynamic server-driven data
        sortedBy : {
            key:"name",
            dir:YAHOO.widget.DataTable.CLASS_ASC
        }, // Sets UI initial sort arrow
        paginator: myPaginator,
        generateRequest:customRequestBuilder
    };

    // instanciating datatable
    var myDataTable = new YAHOO.widget.DataTable(divid, myColumnDefs, myDataSource, myConfigs);

    // Update totalRecords on the fly with value from server
    myDataTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
        oPayload.totalRecords = oResponse.meta.totalRecords;
        return oPayload;
    }
    myDataTable.query = "";

    myDataTable.panelid = panelid;

    
    myDataTable.subscribe("postRenderEvent",function(){
        handlePagination(myPaginator.getState());
    });

    myDataTable.subscribe("postRenderEvent",function(){
        YAHOO.haloacl.highlightAlreadySelectedUsersInDatatable(panelid);
    //   setupCheckboxHandling();


    /*
        YAHOO.util.Event.addListener($$('.'+divid+'_users'),"click",function(){
            var fncname = "YAHOO.haloacl.refreshPanel_"+panelid.substr(14)+"();";
            eval(fncname);
        });
        */
        
    });




    //YAHOO.util.Event.addListener(myDataTable,"initEvent",myDataTable.checkAllSelectedUsers());

    // function called from grouptree to update userdatatable on GroupTreeClick
    myDataTable.executeQuery = function(query){
        if(!query == ""){
            myDataTable.query = query;
        }
        var oCallback = {
            success : myDataTable.onDataReturnInitializeTable,
            failure : myDataTable.onDataReturnInitializeTable,
            scope : myDataTable,
            argument : myDataTable.getState()
        };
        myDataSource.sendRequest(customRequestBuilder(myDataTable.getState(),null), oCallback);
    }


    // setting up clickevent-handling
    return myDataTable;

};

// --------------------
// --------------------
// --------------------

// ASSIGNED USERTABLE FROM JSARRAY
/**
 *  called from html to build assigned-user-table
 *  @param targetdiv
 *  @param panelid/identifier
 *  @param dont't show delete-icons
 */

YAHOO.haloacl.ROuserDataTableV2 = function(divid,panelid, noDelete){
    if(noDelete == "true") noDelete = true;
    if(noDelete == "false" || !noDelete) noDelete = false;
    
    if(YAHOO.haloacl.debug) console.log("ROuserDataTableV2 called");
    var groupstring = "";
    var grouparray = YAHOO.haloacl.getGroupsArray(panelid);
   
    if(grouparray != null){
        grouparray.each(function(item){
            if(groupstring == ""){
                groupstring = item;
            }else{
                groupstring += ","+item;
            }
        });
    }
    if(YAHOO.haloacl.debug) console.log("retrieving user for following groups");
    if(YAHOO.haloacl.debug) console.log(groupstring);
    if(YAHOO.haloacl.debug) console.log("---");

    var callback = function(data){
        var result = new Array();
        if(data != null){
            var usersFromGroupsArray = YAHOO.lang.JSON.parse(data.responseText);

            // also adding users from group-selection - so all members of a selected group will also be shown
            usersFromGroupsArray.each(function(item){
                var temp = new Array();
                temp['name'] = item.name;
                temp["groups"] = item.groups;
                temp["deletable"] = "group";
                result.push(temp);
            });
        }

        //console.log("users from groups array:");
        //console.log(result);
        
        // handling users form user-datatable on select and deselct tab
        if(YAHOO.haloacl.debug) console.log("panelid"+panelid);
        if(YAHOO.haloacl.clickedArrayUsers[panelid] != null){
            YAHOO.haloacl.clickedArrayUsers[panelid].each(function(item){
                if(item != ""){
                    // lets see if this users already exists in the datatabel
                    var reallyAddUser = "user";
                    result.each(function(el){
                        if(el.name == item){
                            if(el.deletable == "group"){
                                reallyAddUser = "groupuser";
                            }else if(el.deletable == "groupuser"){
                                reallyAddUser = "no";
                            }else if(el.deletable == "user"){
                                reallyAddUser = "groupuser";
                            }

                        /*
                            // remove it from array, as its added later again with other deletable tag
                            var elementToRemove = null;
                            for(i=0;i<result.length;i++){
                                if(result[i] == el.name){
                                    elementToRemove = i;
                                }
                            }
                            if(elementToRemove != null){
                                result.splice(elementToRemove,1);
                            }
                            */

                        }
                    });
                
                    if(reallyAddUser == "user"){
                        result.each(function(tempEl){
                            if(tempEl.name == item){
                                result = result.without(temp);
                            }
                        });
                        //console.log("clicked array user groups");
                        //console.log(YAHOO.haloacl.clickedArrayUsersGroups[panelid][item]);

                        var temp = new Array();
                        temp['name'] = item;
                        try{
                            //    if(tempEl && trim(tempEl) != ""){
                            //        temp['groups'] = tempEl.groups;
                            //    }else{
                            temp['groups'] = YAHOO.haloacl.clickedArrayUsersGroups[panelid][item];
                            if(temp['groups'] == "undefined"){
                                temp['groups'] = "";
                            }
                        //    }
                        }catch(e){
                            temp['groups'] = "";
                        }
                        temp['deletable'] = "user";
                        result.push(temp);
                        

                    }else if(reallyAddUser == "groupuser"){
                        result.each(function(temp){
                            if(temp.name == item){
                                result = result.without(temp);
                            }
                        });

                        var temp = new Array();
                        temp['name'] = item;
                        try{
                            if(tempEl && trim(tempEl) != ""){
                                temp['groups'] = tempEl.groups;
                            }else{
                                temp['groups'] = YAHOO.haloacl.clickedArrayUsersGroups[panelid][item];
                            }
                        }catch(e){
                            temp['groups'] = "";
                        }
                        temp['deletable'] = "groupuser";
                        result.push(temp);

                    }
                }
              
                
            });
        };
        return YAHOO.haloacl.ROuserDataTable(divid,panelid,result, noDelete);
    };


    var action = "haclGetUsersForGroups";
    var querystring = "rs="+action+"&rsargs[]="+groupstring;

    new Ajax.Request("?action=ajax",{
        method:'post',
        onSuccess:callback,
        onFailure:callback,
        parameters:querystring
    });

   
};


/**
 *  this function is called from V2 (upper function) !!!
 *  @param targetdiv
 *  @param panelid
 *  @param data to display
 *  @param dontÄt show delete icon
 */
YAHOO.haloacl.ROuserDataTable = function(divid,panelid,dataarray, noDelete) {

    // custom defined formatter
    this.mySelectFormatter = function(elLiner, oRecord, oColumn, oData) {
        if(oRecord._oData.deletable !="group" && noDelete == false){
            elLiner.innerHTML = "<a id='"+panelid+"assigned"+oRecord._oData.name+"' class='removebutton' href=\"javascript:YAHOO.haloacl.removeUserFromUserArray('"+panelid+"','"+oRecord._oData.name+"','"+oRecord._oData.deletable+"');\">&nbsp;</a>";
            YAHOO.haloacl.addTooltip("tooltip"+panelid+"assigned"+oRecord._oData.name,panelid+"assigned"+oRecord._oData.name,"Click to remove User from assigned Users");
        }else{
            elLiner.innerHTML = "&nbsp;";
        }

    };

    this.myGroupFormatter = function(elLiner, oRecord, oColumn, oData) {
        var groupsstring = ""+oRecord._oData.groups;
        var resultstring = "<div name='"+oRecord._oData.name+"' groups='"+groupsstring+"' class='haloacl_datatable_groupscol  datatable_usergroups haloacl_datatable_groupdiv"+panelid+"'></div>";
        elLiner.innerHTML = resultstring;
    };
    this.myNameFormatter = function(elLiner, oRecord, oColumn, oData) {
        //elLiner.innerHTML = "<span class='"+divid+"_usersgroups' groups=\""+oRecord._oData.groups+"\">"+oRecord._oData.name+"</span>";
        elLiner.innerHTML = "<span name=\""+oRecord._oData.name+"\" class='userdatatable_name userdatatable_name_"+panelid+"' groups=\""+oRecord._oData.groups+"\">"+oRecord._oData.name+"</span>";

    };

    // building shortcut for custom formatter
    /*    YAHOO.widget.DataTable.Formatter.myGroup = this.myGroupFormatter;
    YAHOO.widget.DataTable.Formatter.myName = this.myNameFormatter;
    YAHOO.widget.DataTable.Formatter.mySelect = this.mySelectFormatter;
*/
    var myColumnDefs = [ // sortable:true enables sorting
   
    {
        key:"name",
        label:gHACLLanguage.getMessage('name'),
        sortable:false,
        formatter:this.myNameFormatter
    },
    {
        key:"groups",
        label:gHACLLanguage.getMessage('groups'),
        sortable:false,
        formatter:this.myGroupFormatter
    },
    
    {
        key:"deletable",
        label:gHACLLanguage.getMessage('remove'),
        formatter:this.mySelectFormatter
    }
    ];

    // datasource for this userdatatable
    var myDataSource2 = new YAHOO.util.DataSource(dataarray);
    myDataSource2.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;



    var myPaginator = new YAHOO.widget.Paginator({
        rowsPerPage:10,
        containers:'datatablepaging_'+divid,
        pageLinks:3
    });

    // userdatatable configuration
    var myConfigs = {
        sortedBy : {
            key:"name",
            dir:YAHOO.widget.DataTable.CLASS_ASC,
            paginator:myPaginator
        }
    };

    // instanciating datatable
    var myDataTable = new YAHOO.widget.DataTable(divid, myColumnDefs, myDataSource2, myConfigs);
    myDataTable.panelid = panelid;
    myDataTable.subscribe("postRenderEvent",function(){
        var callback = function(){
        //YAHOO.util.Event.addListener($$('.removebutton'),"click",function(){
        //var fncname = "YAHOO.haloacl.refreshPanel_"+panelid.substr(14)+"();";
        //eval(fncname);
        //});
        };
        YAHOO.haloacl.highlightAlreadySelectedUsersInRODatatable(panelid,callback);
    });

    // setting up clickevent-handling
    return myDataTable;

};



/**
 *  handels highlighting and group-sorting for userdatatable
 *  @param panelid
 *  @param callback
 *
 */
YAHOO.haloacl.highlightAlreadySelectedUsersInDatatable = function(panelid,callback){
    //if(YAHOO.haloacl.debug) console.log("autoselectevent fired for panel:"+panelid);
    //if(YAHOO.haloacl.debug) console.log("searching for users in following class:"+'.datatableDiv_'+panelid+'_users');
    //if(YAHOO.haloacl.debug) console.log("listing known selections for panel:");
    
    /* non sorted part */
    /*
    
    $$('.datatableDiv_'+panelid+'_usersgroups').each(function(item){
        $(item).removeClassName("groupselected");
    });

    $$('.datatableDiv_'+panelid+'_usersgroups').each(function(item){
        var name = $(item).readAttribute("name");
        //if(YAHOO.haloacl.debug) console.log("checking for name:"+name);
        if(YAHOO.haloacl.isNameInGroupArray(panelid,name)){
            $(item).addClassName("groupselected");
        }
    });
     */
    /* non sorted end */
    $$('.haloacl_datatable_groupdiv'+panelid).each(function(divitem){
        if(YAHOO.haloacl.debug) console.log("processing divitem:");
        if(YAHOO.haloacl.debug) console.log(divitem);
        
        var highlighted = new Array();
        var nonHighlighted = new Array();
        var groups = $(divitem).readAttribute("groups");
        if(groups == null || groups == "undefined"){
            groups = "";
        }
        var groupsarray = groups.split(",");
        if(YAHOO.haloacl.debug) console.log("got groupsarray:");
        if(YAHOO.haloacl.debug) console.log(groupsarray);

        for(i=0;i<groupsarray.length;i++){
            var item = groupsarray[i];
            if(item != ""){
                if(YAHOO.haloacl.isNameInGroupArray(panelid,item)){
                    highlighted.push(item);
                }else{
                    nonHighlighted.push(item);
                }
            }
        }

        var result = "<div class='haloacl_usertable_groupsrow_before_tooltip' style='float:left'>";
        for(i=0;i<highlighted.length;i++){
            result += "<span class='groupselected'>";
            if(i != 0)result+=",";
            result+= ""+highlighted[i];
            result+="</span>&nbsp;";
        }
        for(i=0;i<nonHighlighted.length;i++){
            result +="<span class='groupunselected'>";
            if(i != 0 || highlighted.length > 0)result+=",";
            result+= ""+nonHighlighted[i];
            result+="</span>&nbsp;";
        }
        result +="</div>";

        try{
            var username = divitem.parentNode.parentNode.previousElementSibling.firstChild.firstChild;

            if(highlighted.length > 0 || YAHOO.haloacl.isNameInUserArray(panelid,username.innerHTML)){
                $(username).setAttribute("style", "font-weight:bold");
            }else{
                $(username).setAttribute("style", "");
            }
        }catch(e){}
        var divname = $(divitem).readAttribute("name");
        
        //var innerhtml =result+ '<div class="haloacl_infobutton" style="float:left;display:inline"></div><div id="tt1'+panelid+divname+'"></div>';
        var innerhtml =result+ '<div id="tt1'+panelid+divname+'"></div>';


        divitem.innerHTML = innerhtml;
        
        var test = new YAHOO.widget.Tooltip('tt1'+panelid+divname, {
            context:divitem,
            text:result,
            zIndex :10,
            constraintoviewport:false
        });
        if(YAHOO.haloacl.debug) console.log(test);

    });

    if(callback != null){
        callback();
    }
};


// readnonly-part (assigned tab)

YAHOO.haloacl.highlightAlreadySelectedUsersInRODatatable = function(panelid){
    //YAHOO.haloacl.debug = true;

    $$('.haloacl_datatable_groupdiv'+panelid).each(function(divitem){
        try{
            if(YAHOO.haloacl.debug) console.log("processing divitem:");
            if(YAHOO.haloacl.debug) console.log(divitem);

            var highlighted = new Array();
            var nonHighlighted = new Array();

            var groups = $(divitem).readAttribute("groups");
            if(groups == null || groups == "undefined"){
                groups = "";
            }

            var groupsarray = groups.split(",");
            if(YAHOO.haloacl.debug) console.log("got groupsarray:");
            if(YAHOO.haloacl.debug) console.log(groupsarray);

            for(i=0;i<groupsarray.length;i++){
                var item = groupsarray[i];
                if(item != ""){
                    if(YAHOO.haloacl.isNameInGroupArray(panelid,item)){
                        highlighted.push(item);
                    }else{
                        nonHighlighted.push(item);
                    }
                }
            }

            var result = "<div class='haloacl_usertable_groupsrow_before_tooltip' style='float:left'>";
            for(i=0;i<highlighted.length;i++){
                result += "<span class='groupselected'>";
                if(i != 0)result+=",";
                result+= ""+highlighted[i];
                result+="</span>&nbsp;";
            }
            for(i=0;i<nonHighlighted.length;i++){
                result +="<span class='groupunselected'>";
                if(i != 0 || highlighted.length > 0)result+=",";
                result+= ""+nonHighlighted[i];
                result+="</span>&nbsp;";
            }
            result +="</div>";

            try{
                var username = divitem.parentNode.parentNode.previousElementSibling.firstChild.firstChild;

                if(highlighted.length > 0 || YAHOO.haloacl.isNameInUserArray(panelid,username.innerHTML)){
                    $(username).setAttribute("style", "font-weight:bold");
                }else{
                    $(username).setAttribute("style", "");
                }
            }catch(e){}

            var divname = $(divitem).readAttribute("name");
            if(YAHOO.haloacl.debug) console.log("got divname:"+divname);
            if(YAHOO.haloacl.debug) console.log($(divitem));

            //var innerhtml =result+ '<div class="haloacl_infobutton" style="float:left;display:inline"></div><div id="tt1'+panelid+divname+'"></div>';
            var innerhtml =result+ '<div id="tt1'+panelid+divname+'"></div>';


            divitem.innerHTML = innerhtml;

            var test = new YAHOO.widget.Tooltip('tt1'+panelid+divname, {
                context:divitem,
                text:result,
                zIndex :10,
                constraintoviewport:false
            });
            if(YAHOO.haloacl.debug) console.log(test);


        }catch(e){
            if(YAHOO.haloacl.debug) console.log(e);
        }


    });
//YAHOO.haloacl.debug = false;
 
};


/**
 *  applies filter on assigned-userdatatable
 *  @param classname of usernames
 *  @param query
 *
 */
YAHOO.haloacl.filterUserDatatableJS = function(classname,filter){
    filter = filter.toLowerCase();
    $$('.'+classname).each(function(item){
        var temp = $(item).readAttribute("name");
        temp = temp.toLowerCase();
        //console.log("temp:"+temp);
        //console.log("filter:"+filter);
        if(temp.indexOf(filter) >= 0 || filter == null || filter == ""){
            $(item).parentNode.parentNode.parentNode.style.display = "inline";
        }else{
            $(item).parentNode.parentNode.parentNode.style.display = "none";
        }
    });
};




