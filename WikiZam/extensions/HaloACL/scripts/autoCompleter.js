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
 * This file contains the AutoCompleter which is used in whitelist and createacl
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 03.04.2009
 *
 */


/**
 *  creates autocompleter
 *  @param name of ac
 *  @param target-div-id
 *
 */
YAHOO.haloacl.AutoCompleter = function(fieldName, containerName) {

    // datasource for this userdatatable
    var myDataSource = new YAHOO.util.DataSource("?action=ajax");
    myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSON;
    myDataSource.connMethodPost = true;
    myDataSource.responseSchema = {
        resultsList: "records",
        fields : ["name", "name"]
    };
 

    // Enable caching
   /// myDataSource.maxCacheEntries = 5;

    // Instantiate the AutoComplete
   

    var oAC = new YAHOO.widget.AutoComplete(fieldName, containerName, myDataSource);

    var fnCallback = function(e, args) {
        YAHOO.util.Dom.get(fieldName).value = args[2][1];

    }
  /*  oAC.itemSelectEvent.subscribe(function(e, args) {
        YAHOO.util.Dom.get(fieldName).value = args[2][1];

    });
    */

    oAC.forceSelection = false;

    oAC.generateRequest = function(sQuery) {
        // trying to add select protect
        var protect = null;
        $$('.create_acl_general_protect').each(function(item){
            if(item.checked){
                protect = item.value;
            }
        });

        return "rs=haclGetAutocompleteDocuments&rsargs[]=" + sQuery+"&rsargs[]="+protect;
    };
    var itemFocusHandler = function(sType, args){
        oAC.sendQuery("");
    }
    oAC.textboxFocusEvent.subscribe(itemFocusHandler);
          
  // Custom formatter to highlight the matching letters
    oAC.formatResult = function(oResultData, sQuery, sResultMatch) {

        var query = sQuery.toLowerCase(),
            name = oResultData[1],
            nameMatchIndex = name.toLowerCase().indexOf(query),
            displayname;

        if(nameMatchIndex > -1) {
            displayname = highlightMatch(name, query, nameMatchIndex);
        }
        else {
            displayname = name;
        }

        return displayname;

    };

    // Helper function for the formatter
    var highlightMatch = function(full, snippet, matchindex) {
        return full.substring(0, matchindex) +
                "<span class='match'>" +
                full.substr(matchindex, snippet.length) +
                "</span>" +
                full.substring(matchindex + snippet.length);
    };


    return {
        oDS: myDataSource,
        oAC: oAC
    };
};