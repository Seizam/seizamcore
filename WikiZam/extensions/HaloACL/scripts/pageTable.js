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
 * This file contains a template for datatables
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */
YAHOO.haloacl.pageDataTable = function(divid) {

    // custom defined formatter
    this.myCustomFormatter = function(elLiner, oRecord, oColumn, oData) {
        if(oData == true){
            elLiner.innerHTML = "<input type='checkbox' checked='' class='"+divid+"_users' name='"+oRecord._oData.name+"' />";
        }else{
            elLiner.innerHTML = "<input type='checkbox' class='"+divid+"_users' name='"+oRecord._oData.name+"' />";
        }
            
    };

    // building shortcut for custom formatter
    YAHOO.widget.DataTable.Formatter.myCustom = this.myCustomFormatter;

    var myColumnDefs = [ // sortable:true enables sorting
    {
        key:"name",
        label:gHACLLanguage.getMessage('name'),
        sortable:true
    },
    {
        key:"checked",
        label:gHACLLanguage.getMessage('selected')
      //  formatter:"myCustom"
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

        if(myPageTable.query == null){
            myPageTable.query = '';
        }

        return "rs=haclGetWhitelistPages&rsargs[]="
        +myPageTable.query+"&rsargs[]="+sort
        +"&rsargs[]="+dir
        +"&rsargs[]="+startIndex
        +"&rsargs[]="+results;


    };

    // userdatatable configuration
    var myConfigs = {
        initialRequest: "rs=haclGetWhitelistPages&rsargs[]=test&rsargs[]=name&rsargs[]=asc&rsargs[]=0&rsargs[]=25", // Initial request for first page of data
        dynamicData: true, // Enables dynamic server-driven data
        sortedBy : {
            key:"id",
            dir:YAHOO.widget.DataTable.CLASS_ASC
        }, // Sets UI initial sort arrow
        paginator: new YAHOO.widget.Paginator({
            rowsPerPage:25,
            containers:'datatablepaging_whitelistDatatableDiv'
        }),
        generateRequest:customRequestBuilder
    };

    // instanciating datatable
    var myPageTable = new YAHOO.widget.DataTable(divid, myColumnDefs, myDataSource, myConfigs);

    // Update totalRecords on the fly with value from server
    myPageTable.handleDataReturnPayload = function(oRequest, oResponse, oPayload) {
        oPayload.totalRecords = oResponse.meta.totalRecords;
        return oPayload;
    }
    myPageTable.query = "";

    // function called from grouptree to update userdatatable on GroupTreeClick
    myPageTable.executeQuery = function(query){
        myPageTable.query = query;
        var oCallback = {
            success : myPageTable.onDataReturnInitializeTable,
            failure : myPageTable.onDataReturnInitializeTable,
            scope : myPageTable,
            argument : myPageTable.getState()
        };
        myDataSource.sendRequest(customRequestBuilder(myPageTable.getState(),null), oCallback);
    }

    return myPageTable;

};

