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
 * This file contains javascript for the grouptree
 * (used in rightpanel and grouppanel)
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

/**
 *  debuggin purpose, not used productive
 */
function dump(arr,level) {
    var dumped_text = "";
    if(!level) level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for(var j=0;j<level+1;j++) level_padding += "    ";

    if(typeof(arr) == 'object') { //Array/Hashes/Objects
        for(var item in arr) {
            var value = arr[item];

            if(typeof(value) == 'object') { //If it is an array,
                dumped_text += level_padding + "'" + item + "' ...\n";
                dumped_text += dump(value,level+1);
            } else {
                dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
            }
        }
    } else { //Stings/Chars/Numbers etc.
        dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
    }
    return dumped_text;
}


// defining customnode
YAHOO.widget.CustomNode = function(oData, oParent, expanded, checked, typeOfGroup, canBeModified) {
    YAHOO.widget.CustomNode.superclass.constructor.call(this,oData,oParent,expanded);
    this.setUpCheck(checked || oData.checked);
	this.typeOfGroup = typeOfGroup;
	this.canBeModified = canBeModified;
};

// impl of customnode; extending textnode
YAHOO.extend(YAHOO.widget.CustomNode, YAHOO.widget.TextNode, {

    /**
     * True if checkstate is 1 (some children checked) or 2 (all children checked),
     * false if 0.
     * @type boolean
     */
    checked: false,

    /**
     * checkState
     * 0=unchecked, 1=some children checked, 2=all children checked
     * @type int
     */
    checkState: 0,

    /**
     * id of contained acl group
     * @type int
     */
    groupId: 0,


    /**
     * tree type
     * rw=read/write, r=read
     * @type string
     */
    treeType: "rw",

    /**
     * The node type
     * @property _type
     * @private
     * @type string
     * @default "TextNode"
     */
    _type: "CustomNode",

    textWidth:399,
	
	// Groups of different types can be decorated accordingly
	typeOfGroup : 'HaloACL',
	
	// <true>, if this group can be modified
	canBeModified : true,

    customNodeParentChange: function() {
    //this.updateParent();
    },

    // function called from constructor
    //  -> creates/registers events
    setUpCheck: function(checked) {
        // if this node is checked by default, run the check code to update
        // the parent's display state
        if (checked && checked === true) {
            this.check();
        // otherwise the parent needs to be updated only if its checkstate
        // needs to change from fully selected to partially selected
        } else if (this.parent && 2 === this.parent.checkState) {
            this.updateParent();
        }

        // set up the custom event on the tree for checkClick

        if (this.tree && !this.tree.hasEvent("checkClick")) {
            this.tree.createEvent("checkClick", this.tree);
        }

        this.tree.subscribe('clickEvent',this.checkClick);
        this.subscribe("parentChange", this.customNodeParentChange);
       
    },


    /**
     * set group id
     * @newGroupId int
     */
    setGroupId: function(newGroupId) {
        this.groupId = newGroupId;
    },

    /**
     * get group id
     */
    getGroupId: function() {
        return this.groupId;
    },

    /**
     * The id of the check element
     * @for YAHOO.widget.CustomNode
     * @type string
     */
    getCheckElId: function() { 
        return "ygtvcheck" + this.index; 
    },

    /**
     * Returns the check box element
     * @return the check html element (img)
     */
    getCheckEl: function() { 
        return document.getElementById(this.getCheckElId()); 
    },

    /**
     * The style of the check element, derived from its current state
     * @return {string} the css style for the current check state
     */
    getCheckStyle: function() { 
        return "ygtvcheck" + this.checkState;
    },


    /**
     * Invoked when the user clicks the check box
     */
    checkClick: function(oArgs) {
        var node = oArgs.node;
        var target = YAHOO.util.Event.getTarget(oArgs.event);
        if (YAHOO.util.Dom.hasClass(target,'ygtvspacer')) {
            if (node.checkState === 0) {
                node.check();
            } else {
                node.uncheck();
            }

            node.onCheckClick(node);
            this.fireEvent("checkClick", node);
            return false;
        }

    },

    


    /**
     * Override to get the check click event
     */
    onCheckClick: function() { 
    },

    /**
     * Refresh the state of this node's parent, and cascade up.
     */
    updateParent: function() {
        var p = this.parent;

        if (!p || !p.updateParent) {
            return;
        }

        var somethingChecked = false;
        var somethingNotChecked = false;

        for (var i=0, l=p.children.length;i<l;i=i+1) {

            var n = p.children[i];

            if ("checked" in n) {
                if (n.checked) {
                    somethingChecked = true;
                    // checkState will be 1 if the child node has unchecked children
                    if (n.checkState === 1) {
                        somethingNotChecked = true;
                    }
                } else {
                    somethingNotChecked = true;
                }
            }
        }

        if (somethingChecked) {
            p.setCheckState( (somethingNotChecked) ? 1 : 2 );
        } else {
            p.setCheckState(0);
        }

        p.updateCheckHtml();
        p.updateParent();
    },

    /**
     * If the node has been rendered, update the html to reflect the current
     * state of the node.
     */
    updateCheckHtml: function() { 
        if (this.parent && this.parent.childrenRendered) {
            this.getCheckEl().className = this.getCheckStyle();
        }
    },

    /**
     * Updates the state.  The checked property is true if the state is 1 or 2
     * 
     * @param the new check state
     */
    setCheckState: function(state) {
        this.checkState = state;
        this.checked = (state > 0);
        //this.tree.clickedTreeNodes[this.groupId] = this.checked;
        // this.tree.clickedHandler.add(this.groupId);
        //YAHOO.haloacl.clickedArrayGroups[this.tree.panelid][this.groupId] = this.checked;
        if(this.checked){
            YAHOO.haloacl.addGroupToGroupArray(this.tree.panelid, this.groupId);
        }else{
            YAHOO.haloacl.removeGroupFromGroupArray(this.tree.panelid, this.groupId);
        }
        // update usertable
        YAHOO.haloacl.highlightAlreadySelectedUsersInDatatable(this.tree.panelid);

        try{
            var fncname = "YAHOO.haloacl.refreshPanel_"+this.tree.panelid.substr(14)+"();";
            eval(fncname);
        }catch(e){}
    },

    /**
     * Updates the state.  The checked property is true if the state is 1 or 2
     *
     * @param the new check state
     */
    getLabelElId: function() {
        return this.labelElId;
    },

    /**
     * Check this node
     */
    check: function() {
        this.setCheckState(2);

        /*
         * don't update childs
        for (var i=0, l=this.children.length; i<l; i=i+1) {
            var c = this.children[i];
            if (c.check) {
                c.check();
            }
        }
        this.updateParent();
        */
        this.updateCheckHtml();

    },

    /**
     * Uncheck this node
     */
    uncheck: function() { 
        this.setCheckState(0);
        /*
        for (var i=0, l=this.children.length; i<l; i=i+1) {
            var c = this.children[i];
            if (c.uncheck) {
                c.uncheck();
            }
        }
        */
        this.updateCheckHtml();
    //this.updateParent();
    },
    
    setTreeType: function(newTreeType) { 
        this.treeType = newTreeType
    },



    /*    getHtml : function(){
      return "megatest";
    },
*/
    // Overrides YAHOO.widget.TextNode
    getContentHtml: function() {                                                                                                                                           
        var sb = [];

		
        if (this.treeType=="rw") {

            sb[sb.length] = '<td><span';
            sb[sb.length] = ' id="' + this.labelElId + '"';
            if (this.title) {
                sb[sb.length] = ' title="' + this.title + '"';
            }
            sb[sb.length] = ' class="haloacl_grouptree_title ' + this.labelStyle  + '"';
            sb[sb.length] = ' style="width:'+this.textWidth+'px" ';
            sb[sb.length] = ' >';
            sb[sb.length] = "<a href='javascript:"+this.tree.labelClickAction+"(\""+this.label+"\",\""+this.labelElId+"\");'>"+this.label+"</a>";
			
			if (this.typeOfGroup == 'LDAP') {
				sb[sb.length] = '<span class="ygtgrouptypeldap">';
				sb[sb.length] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				sb[sb.length] = '</span>';
			}

            sb[sb.length] = '</span>';
			sb[sb.length] = '</td>';
            sb[sb.length] = '<td';
            sb[sb.length] = ' id="' + this.getCheckElId() + '"';
            sb[sb.length] = ' class="' + this.getCheckStyle() + '"';
            sb[sb.length] = '>';
            sb[sb.length] = '<div class="ygtvspacer"></div></td>';


        } else {
            sb[sb.length] = '<td>';
            sb[sb.length] = '<div class="ygtvspacer"></div></td>';

            sb[sb.length] = '<td><span';
            sb[sb.length] = ' id="' + this.labelElId + '"';
            if (this.title) {
                sb[sb.length] = ' title="' + this.title + '"';
            }
            sb[sb.length] = ' class="haloacl_grouptree_title ' + this.labelStyle  + '"';
            sb[sb.length] = ' >';
            sb[sb.length] = this.label;
			if (this.typeOfGroup == 'LDAP') {
				sb[sb.length] = '<span class="ygtgrouptypeldap">';
				sb[sb.length] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				sb[sb.length] = '</span>';
			}
            sb[sb.length] = '</span>'
			
			sb[sb.length] = '</td>';
            
            sb[sb.length] = '<td';
            sb[sb.length] = ' id="' + this.getCheckElId() + '"';
            sb[sb.length] = ' class="' + this.getCheckStyle() + '-ro"';
            sb[sb.length] = '>';
            sb[sb.length] = '<div id="deletebutton_'+this.panelid+'_'+this.label+'" class="ygtvspacer" onClick="'+this.tree.labelClickAction+'(\''+this.label+'\',this);">&nbsp;</div></td>';
            YAHOO.haloacl.addTooltip('tooltip_deletebutton_'+this.panelid+'_'+this.label+'','deletebutton_'+this.panelid+'_'+this.label,"Click to remove Group from assigned Groups");



        }

        
        return sb.join("");                                                                                                                                                
    }  
});

/**
 * Initialization of a Group Tree
 * @param {Object} divname
 * 		Name of the div that contains the tree
 * @param {String} purpose
 * 		The purpose of the tree. The purpose influences the appearance of the tree.
 */ 
YAHOO.widget.GroupTree = function(divname, purpose) {
    YAHOO.widget.GroupTree.superclass.constructor.call(this,divname);
    this.construct(purpose);
};

// Definition of the GroupTree
YAHOO.extend(YAHOO.widget.GroupTree, YAHOO.widget.TreeView, {
	
	// The purpose of this tree
	purpose: 'normal',
	
	// The content in which the tree is used: RightPanel or GroupPanel
	context: 'RightPanel',
	
	/**
	 * 
	 * @param {String} purpose
	 */
	construct: function(purpose) {
		this.purpose = purpose;
	},
	
	setContext: function(context) {
		this.context = context;
	}

});

/*
 * treeview-dataconnect
 * @param mediawiki / rs-action
 * @param list (object) of parameters to be added
 * @param callback for asyncRequest
 */
YAHOO.haloacl.treeviewDataConnect = function(action,parameterlist,callback){
    var url= "?action=ajax";
    var appendedParams = '';
    appendedParams = '&rs='+action;
    var temparray = new Array();

    /*
    for(param in parameterlist){
        temparray.push(parameterlist[param]);
    }
    */
    var querystring = "rs="+action;

    if(parameterlist != null){
        for(param in parameterlist){
            // temparray.push(parameterlist[param]);
            querystring = querystring + "&rsargs[]="+parameterlist[param];
        }
    }

    appendedParams = appendedParams + "&rsargs[]="+ temparray;
    YAHOO.util.Connect.asyncRequest('POST', url, callback,querystring);
};

/*
 * function for dynamic node-loading
 * @param node
 * @parm callback on complete
 */
YAHOO.haloacl.loadNodeData = function(node, fnLoadComplete)  {

    var nodeLabel = encodeURI(node.label);

    var panelid = node.tree.panelid;


    //prepare our callback object
    var callback = {
        panelid:"",

        //if our XHR call is successful, we want to make use
        //of the returned data and create child nodes.
        success: function(oResponse) {
            YAHOO.haloacl.buildNodesFromData(node,YAHOO.lang.JSON.parse(oResponse.responseText));
            oResponse.argument.fnLoadComplete();
        },

        failure: function(oResponse) {
            oResponse.argument.fnLoadComplete();
        },
        argument: {
            "node": node,
            "fnLoadComplete": fnLoadComplete
        },
        timeout: 7000
    };
    YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForRightPanel',{
        query:nodeLabel
    },callback);

};





/*
 * function to build nodes from data
 * @param parent node / root
 * @param data
 */
YAHOO.haloacl.buildNodesFromData = function(parentNode,data,panelid){
    var loadNodeData = function(node, fnLoadComplete)  {
        var nodeLabel = encodeURI(node.label);
        //prepare our callback object
        var callback = {
            panelid:"",
            success: function(oResponse) {
                YAHOO.haloacl.buildNodesFromData(node,YAHOO.lang.JSON.parse(oResponse.responseText,parentNode.tree.panelid));
                oResponse.argument.fnLoadComplete();
            },
            failure: function(oResponse) {
                oResponse.argument.fnLoadComplete();
            },
            argument: {
                "node": node,
                "fnLoadComplete": fnLoadComplete
            },
            timeout: 7000
        };
        YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForRightPanel',{
            query:nodeLabel
        },callback);

    };
	
	hideImmutableNodes = false;
	tree = parentNode.tree;
	context = "RightPanel";
	if (tree.context != undefined) {
		context = tree.context; 
	}
	if (tree.purpose != undefined) {
		hideImmutableNodes = (tree.purpose == 'editGroups' 
							  && context == "GroupPanel"
							  && haclgAllowLDAPGroupMembers == false); 
	}
	

    for(var i= 0, len = data.length; i<len; ++i){
        var element = data[i];
		
		if (element.canBeModified == false && hideImmutableNodes) {
			continue;
		}

        var elementWidth = 340;
        if(parentNode.textWidth != null){
            elementWidth = parentNode.textWidth - 18;
        }

        var tmpNode = new YAHOO.widget.CustomNode(element.name, parentNode, false, element.checked, element.type, element.canBeModified);
        
        tmpNode.textWidth = elementWidth;

        if(panelid == "undefined" || panelid == null){
            panelid = parentNode.tree.panelid;
        }

        tmpNode.setGroupId(element.name);

        // check checkbox if during this js-session it has been checked
        if(panelid){
            if (YAHOO.haloacl.isNameInGroupArray(panelid, element.name)){
                tmpNode.check();
            }
        }

        // recursive part, if children were supplied
        if(element.children != null){
            YAHOO.haloacl.buildNodesFromData(tmpNode,element.children,panelid);
            tmpNode.expand();
        }else{
            tmpNode.setDynamicLoad(loadNodeData);
        }
    };
    YAHOO.haloacl.highlightAlreadySelectedUsersInDatatable(panelid);

};


/*
 * filter tree
 * @param parent node / root
 * @param filter String
 */
YAHOO.haloacl.filterNodesGroupUser = function(parentNode,filter){

    filter = filter.toLowerCase();
    
    var nodes;
    nodes = parentNode.children;

    for(var i=0, l=nodes.length; i<l; i=i+1) {
        var n = nodes[i];
        var temp = n.label;
        temp = temp.toLowerCase();
        if (temp.indexOf(filter) < 0) {
            document.getElementById(n.getLabelElId()).parentNode.parentNode.style.display = "none";
        } else {
            document.getElementById(n.getLabelElId()).parentNode.parentNode.style.display = "inline";
        }
        
    /*
        if (n.checkState > 0) {
            var tmpNode = new YAHOO.widget.CustomNode(n.label, rwTree.getRoot(),false);
            tmpNode.setCheckState(n.checkState);
            tmpNode.setTreeType("r");
        }
        */

    }

};

/*
 * function to build user tree and add labelClickAction
 * @param tree
 * @param data
 * @param labelClickAction (name)
 */
YAHOO.haloacl.buildUserTree = function(tree, data) {
    var loadNodeData = function(node, fnLoadComplete)  {
        var nodeLabel = encodeURI(node.label);
        //prepare our callback object
        var callback = {
            panelid:"",
            success: function(oResponse) {
                YAHOO.haloacl.buildNodesFromData(node,YAHOO.lang.JSON.parse(oResponse.responseText),tree.panelid);
                oResponse.argument.fnLoadComplete();
            },
            failure: function(oResponse) {
                oResponse.argument.fnLoadComplete();
            },
            argument: {
                "node": node,
                "fnLoadComplete": fnLoadComplete
            },
            timeout: 7000
        };
        YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForRightPanel',{
            query:nodeLabel
        },callback);

    };
    var tmpNode = new YAHOO.widget.TextNode(gHACLLanguage.getMessage('groups'), tree.getRoot(),false);
    //tmpNode.setDynamicLoad(loadNodeData);
    YAHOO.haloacl.buildNodesFromData(tmpNode,data,tree.panelid);
    if(data.length == 0){
        new YAHOO.widget.TextNode(gHACLLanguage.getMessage("noGroupsAvailable"), tmpNode,false);
    }

    tmpNode.expand();

    //tree.setDynamicLoad(loadNodeData);
    tree.draw();

};


/*
 * builds mirrored, read only user tree for "assigned" panel from existing r/w user tree in "select" panel
 * @param tree
 * @param rwTree
 */
YAHOO.haloacl.buildUserTreeRO = function(rwTree,tree) {

    var callback = {
        success: function(oResponse) {
            
            var data = YAHOO.lang.JSON.parse(oResponse.responseText);
            /*
            // das ganze rekursiv in funktion auslagern

            var groupsInTree = false;
            for(var i=0, l=data.length; i<l; i=i+1) {
                var n = data[i];

                if (tree && YAHOO.haloacl.isNameInGroupArray(tree.panelid, n.name)){
                    var tmpNode = new YAHOO.widget.CustomNode(n.name, tree.getRoot(),false);
                    tmpNode.setGroupId(n.name);
                    tmpNode.setCheckState(2);
                    tmpNode.setTreeType("r");
                    groupsInTree = true;
                }

            }
            if(!groupsInTree){
                var tmpNode =  new YAHOO.widget.TextNode(gHACLLanguage.getMessage("noGroupsAvailable"), tree.getRoot(),false);
            }

            if(tree != null){
                tree.draw();
            }
            */


            var groupsInTree = false;
            var groupsarray = YAHOO.haloacl.getGroupsArray(tree.panelid);
			if(tree){
            	for(var i=0;i<groupsarray.length;i++){
                    var name=groupsarray[i];
                    if(name != ""){
						var type = 'HaloACL';
						var cbm = true;
						// find the group in the received data
			            for (var j = 0, l = data.length; j < l; ++j) {
							var g = data[j];
							if (g.name == name) {
								type = g.type;
								cbm = g.canBeModified;
								break;
							}	
						}
                        var tmpNode = new YAHOO.widget.CustomNode(name, tree.getRoot(), false, false, type, cbm);
                        tmpNode.setGroupId(name);
                        tmpNode.setCheckState(2);
                        tmpNode.setTreeType("r");
                        groupsInTree = true;
                    }
                }
            }
            if(!groupsInTree){
                var tmpNode =  new YAHOO.widget.TextNode(gHACLLanguage.getMessage("noGroupsAvailable"), tree.getRoot(),false);
            }

            if(tree != null){
                tree.draw();
            }

        },
        failure: function(oResponse) {
        }
    };
    YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForRightPanel',{
        query:'all'
    },callback);

};



/*
 * pre-"ticks" groups from an array. Used when displaying a right panel of an exisiting right
 * @param groups : Array of group IDs (json encoded)
 * @param tree : tree reference
 */
YAHOO.haloacl.preloadCheckedGroups = function(groups, tree) {
    var data = YAHOO.lang.JSON.parse(groups);

    if(YAHOO.haloacl.debug) console.log("data preload"+data);

    for(var i=0, l=data.length; i<l; i=i+1) {
        var groupId = data[i];
        YAHOO.haloacl.addGroupToGroupArray(tree.panelid, groupId);
    }

}





/*
 * function to be called from outside to init a tree
 * @param tree-instance
 */
YAHOO.haloacl.buildTreeFirstLevelFromJson = function(tree, context){
    var callback = {
        success: function(oResponse) {
            var data = YAHOO.lang.JSON.parse(oResponse.responseText);
			tree.setContext(context);
            YAHOO.haloacl.buildUserTree(tree, data);
        },
        failure: function(oResponse) {
        }
    };
    YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForRightPanel',{
        query:'all'
    },callback);
};

/*
 * returns checked nodes
 * USE ONE OF BOTH PARAMS, so ONE HAS TO BE NULL
 *
 * @param tree
 * @param nodes
 */
YAHOO.haloacl.getCheckedNodesFromTree = function(tree, nodes){
    if(nodes == null){
        nodes = tree.getRoot().children;
    }
    checkedNodes = [];
    for(var i=0, l=nodes.length; i<l; i=i+1) {
        var n = nodes[i];
        //if (n.checkState > 0) { // if we were interested in the nodes that have some but not all children checked
        if (n.checkState === 2) {
            checkedNodes.push(n.label); // just using label for simplicity
        }

        if (n.hasChildren()) {
            checkedNodes = checkedNodes.concat(YAHOO.haloacl.getCheckedNodesFromTree(null, n.children));
        }
    }

    return checkedNodes;
};
/**
 *  applies filter on tree
 *  @param tree-instance
 *  @param query
 *
 */
YAHOO.haloacl.applyFilterOnTree = function(tree,filtervalue){
    if(tree.lastFilterStart == null || tree.lastFilterStart == "undefined"){
        tree.lastFilterStart = 0;
    }
    var now = new Date();
    now = now.getTime();
    if(filtervalue == "" || tree.lastFilterStart + YAHOO.haloacl.filterQueryDelay <= now){
        tree.lastFilterStart = now;
        tree = tree.tree;

        //tree.removeChildren();
        //tree.removeChildren();
        var callback = {
            success: function(oResponse) {
                tree.removeChildren(tree.getRoot());
            
                var data = YAHOO.lang.JSON.parse(oResponse.responseText);
                YAHOO.haloacl.buildUserTree(tree,data);
            },
            failure: function(oResponse) {
            }
        };
        YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForRightPanel',{
            query:'all',
            filtervalue:filtervalue
        },callback);

        //tree.setDynamicLoad(loadNodeData);
        tree.draw();
    }
}

/**
 * returns a new treeinstance
 * @param targetdiv
 * @param panelid
 */
YAHOO.haloacl.getNewTreeview = function(divname, panelid, purpose){
    var instance = new YAHOO.widget.GroupTree(divname, purpose);
    instance.panelid = panelid;
    if(!YAHOO.haloacl.clickedArrayGroups[panelid]){
        YAHOO.haloacl.clickedArrayGroups[panelid] = new Array();
    }
    return instance;
};


