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
 * This file contains javasript used in manageQuickacl
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */


/**
 *  creates qucikacltable
 *  @param  target-div-id
 *  @param  panelid
 *
 */

YAHOO.haloacl.quickaclTable = function(divid,panelid) {

    // custom defined formatter
    this.myQuickSelectFormatter = function(elLiner, oRecord, oColumn, oData) {
        var checkedFromJS = false;

        if(YAHOO.haloacl.quickAclClicks.indexOf(""+oRecord._oData.id) != -1){
            checkedFromJS = true;
        }

        if(oData == true || checkedFromJS == true){
            elLiner.innerHTML = '<div id="anchorPopup_'+oRecord._oData.id+'" class="haloacl_infobutton" onclick="javascript:YAHOO.haloaclrights.popup(\''+oRecord._oData.id+'\',\''+oRecord._oData.name+'\',\''+oRecord._oData.id+'\');return false;"></div>';
            elLiner.innerHTML += "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id=\""+oRecord._oData.name+"\"><input  onclick='YAHOO.haloacl.updateQuickaclCount(this);YAHOO.haloacl.quickACLCheck(this);' type='checkbox'  checked='' class='"+divid+"_template' name='"+oRecord._oData.id+"' /></span>";
        //elLiner.innerHTML += '<div id="popup_'+oRecord._oData.id+'"></div>';

        }else{
            elLiner.innerHTML = '<div id="anchorPopup_'+oRecord._oData.id+'" class="haloacl_infobutton" onclick="javascript:YAHOO.haloaclrights.popup(\''+oRecord._oData.id+'\',\''+oRecord._oData.name+'\',\''+oRecord._oData.id+'\');return false;"></div>';
            elLiner.innerHTML += "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id=\""+oRecord._oData.name+"\"><input onclick='YAHOO.haloacl.updateQuickaclCount(this);YAHOO.haloacl.quickACLCheck(this);' type='checkbox'  class='"+divid+"_template' name='"+oRecord._oData.id+"' /></span>";
        //elLiner.innerHTML += '<div id="popup_'+oRecord._oData.id+'"></div>';

        }
            
    };
    

    this.myQuickNameFormatter = function(elLiner, oRecord, oColumn, oData) {
        elLiner.innerHTML = "<span class='"+divid+"_usersgroups' groups=\""+oRecord._oData.groups+"\">"+oRecord._oData.name+"</span>";

    };

    // building shortcut for custom formatter
    //YAHOO.widget.DataTable.Formatter.myQuickSelect = this.myQuickSelectFormatter;
    //YAHOO.widget.DataTable.Formatter.myQuickName = this.myQuickNameFormatter;

    var myColumnDefs = [ // sortable:true enables sorting

    {
        key:"name",
        label:gHACLLanguage.getMessage('name'),
        sortable:false,
        formatter:this.myQuickNameFormatter
    },
   
    {
        key:"checked",
        label:gHACLLanguage.getMessage('delete'),
        formatter:this.myQuickSelectFormatter
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
        
        return "rs=haclGetQuickACLData&rsargs[]="
        +myDataTable.query+"&rsargs[]="+sort
        +"&rsargs[]="+dir
        +"&rsargs[]="+startIndex
        +"&rsargs[]="+results
        +"&rsargs[]="+filter;


    };



    // whitelisttable configuration
    var myConfigs = {
        initialRequest: "rs=haclGetQuickACLData&rsargs[]=all&rsargs[]=name&rsargs[]=asc&rsargs[]=0&rsargs[]=5&rsargs[]=", // Initial request for first page of data
        dynamicData: true, // Enables dynamic server-driven data
        sortedBy : {
            key:"name",
            dir:YAHOO.widget.DataTable.CLASS_ASC
        }, // Sets UI initial sort arrow
        //    paginator: myPaginator,
        generateRequest:customRequestBuilder
    };

    // instanciating datatable
    var myDataTable = new YAHOO.widget.DataTable(divid, myColumnDefs, myDataSource, myConfigs);

    // Update totalRecords on the fly with value from server
    myDataTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
        oPayload.totalRecords = oResponse.meta.totalRecords;
        if($('haloacl_quickacl_count') != null){
            $('haloacl_quickacl_count').innerHTML = oPayload.totalRecords;
        }
        return oPayload;
    }
    myDataTable.query = "";



    myDataTable.subscribe("postRenderEvent",function(){
        YAHOO.haloacl.updateQuickaclCount();
    });


    //YAHOO.util.Event.addListener(myDataTable,"initEvent",myDataTable.checkAllSelectedUsers());

    // function called from grouptree to update userdatatable on GroupTreeClick
    myDataTable.executeQuery = function(query){

        var oCallback = {
            success : myDataTable.onDataReturnInitializeTable,
            failure : myDataTable.onDataReturnInitializeTable,
            scope : myDataTable,
            argument : myDataTable.getState()
        };
        if(YAHOO.haloacl.debug) console.log("sending request");
        myDataSource.sendRequest('rs=haclGetQuickACLData&rsargs[]='+query+'&rsargs[]=name&rsargs[]=asc&rsargs[]=0&rsargs[]=5&rsargs[]="', oCallback);
        if(YAHOO.haloacl.debug) console.log("reqeust sent");
        
    }


    // setting up clickevent-handling
    return myDataTable;

   
};

    // --------------------
    // --------------------
    // --------------------



