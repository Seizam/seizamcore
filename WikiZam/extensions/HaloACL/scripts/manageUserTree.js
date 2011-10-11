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
 * This class provides javascript for the manageGroups tree
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */


// defining customnode
YAHOO.widget.ManageUserNode = function(oData, oParent, expanded, checked) {
    YAHOO.widget.ManageUserNode.superclass.constructor.call(this,oData,oParent,expanded);
    this.setUpCheck(checked || oData.checked);

};

// impl of customnode; extending textnode
YAHOO.extend(YAHOO.widget.ManageUserNode, YAHOO.widget.TextNode, {

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

    b2bChecked:false,

    /**
     * id of contained acl group
     * @type int
     */
    groupId: 0,


    information:"",

    description:"",


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

    textWidth: 0,

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
            this.b2bChecked = true;
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
    setTextWidth: function(tw) {
        this.textWidth = tw;
    },
    getTextWidth:function() {
        return this.textWidth;
    },

    setInformation: function(info){
        this.information = info;
    },

    setDescription:function(desc){
        this.description = desc
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

    // NO update parent here
    /*
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
         */
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
        if(YAHOO.haloacl.checkedInGroupstree == null){
            YAHOO.haloacl.checkedInGroupstree = new Array();
        }
        YAHOO.haloacl.checkedInGroupstree.push(this.label);
        /*
        for (var i=0, l=this.children.length; i<l; i=i+1) {
            var c = this.children[i];
            if (c.check) {
                c.check();
            }
        }
         */
        this.updateCheckHtml();
    //this.updateParent();
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


    // Overrides YAHOO.widget.TextNode
    getContentHtml: function() {                                                                                                                                           
        var sb = [];
        if(this.b2bChecked){
            this.check();
        }


        sb[sb.length] = '<td><span';
        sb[sb.length] = ' id="manageUserRow_' + this.label + '"';
        if (this.title) {
            sb[sb.length] = ' title="' + this.title + '"';
        }
        sb[sb.length] = ' class="haloacl_manageuser_list_title_modified_group ' + this.labelStyle  + '"';
        sb[sb.length] = ' style="width:'+this.textWidth+'px" ';
        //console.log("textWidth: "+this.getTextWidth());
        sb[sb.length] = ' >';
        sb[sb.length] = "<a href='javascript:"+this.tree.labelClickAction+"(\""+this.label+"\");'>"+this.label+"</a>";

        if(this.description != ""){
            sb[sb.length] = "&nbsp;&nbsp;"+this.description;
        }

        sb[sb.length] = '</span></td>';
        sb[sb.length] = '<td><span';
        sb[sb.length] = ' id="' + this.labelElId + '"';
        sb[sb.length] = ' class="haloacl_manageuser_list_information_modified_group">';
        sb[sb.length] = '<div id="tt1_group'+this.labelElId+this.label+'" class="haloacl_infobutton_groupdesc"  ></div>';
        this.tt1 = new YAHOO.widget.Tooltip("tt1_group"+this.labelElId, {
            context:this.labelElId,
            text:this.information,
            zIndex :10
        });
        sb[sb.length] = '</span></td>';
        
        sb[sb.length] = 
        	'<td>' +
        		'<span class="">' +
        			'<a id="haloacl_group_edit_'+escape(this.label)+'" ' +
        			   'class="haloacl_manageuser_list_edit" ' +
        			   'href="javascript:YAHOO.haloacl.manageUsers_handleEdit(\''+this.label+'\');">&nbsp;' +
        			'</a>' +
        		'</span>' +
        	'</td>';
        // sb[sb.length] = '<td><span class="haloacl_manageuser_list_delete">delete</span></td>';
        sb[sb.length] = '<td';
        sb[sb.length] = ' id="' + this.getCheckElId() + '"';
        sb[sb.length] = ' class="' + this.getCheckStyle() + '"';
        sb[sb.length] = '>';
        sb[sb.length] = '<div class="ygtvspacer haloacl_manageuser_checkbox"></div></td>';
        
        return sb.join("");                                                                                                                                                
    }  
});



/*
 * treeview-dataconnect
 * @param mediawiki / rs-action
 * @param list (object) of parameters to be added
 * @param callback for asyncRequest
 */
YAHOO.haloacl.manageUser.treeviewDataConnect = function(action,parameterlist,callback){
    var url= "?action=ajax";
    var appendedParams = '';
    appendedParams = '&rs='+action;
    var temparray = new Array();
    for(param in parameterlist){
        temparray.push(parameterlist[param]);
    }
    appendedParams = appendedParams + "&rsargs[]="+ temparray;
    YAHOO.util.Connect.asyncRequest('POST', url, callback,appendedParams);
};

/*
 * function for dynamic node-loading
 * @param node
 * @parm callback on complete
 */
YAHOO.haloacl.manageUser.loadNodeData = function(node, fnLoadComplete)  {

    var nodeLabel = encodeURI(node.label);


    //prepare our callback object
    var callback = {
        panelid:"",

        //if our XHR call is successful, we want to make use
        //of the returned data and create child nodes.
        success: function(oResponse) {
            YAHOO.haloacl.manageUser.buildNodesFromData(node,YAHOO.lang.JSON.parse(oResponse.responseText,panelid));
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
    YAHOO.haloacl.manageUser.treeviewDataConnect('haclGetGroupsForManageUser',{
        query:nodeLabel
    },callback);

};





/*
 * function to build nodes from data
 * @param parent node / root
 * @param data
 */
YAHOO.haloacl.manageUser.buildNodesFromData = function(parentNode,data,panelid){

    var loadNodeData = function(node, fnLoadComplete)  {
        var nodeLabel = encodeURI(node.label);
        //prepare our callback object
        var callback = {
            panelid:"",
            success: function(oResponse) {
                YAHOO.haloacl.manageUser.buildNodesFromData(node,YAHOO.lang.JSON.parse(oResponse.responseText,panelid));
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
        YAHOO.haloacl.manageUser.treeviewDataConnect('haclGetGroupsForManageUser',{
            query:nodeLabel
        },callback);

    };

    var groupsInTree = false;
    for(var i= 0, len = data.length; i<len; ++i){
        var element = data[i];

        var elementWidth = 399;
        //console.log(parentNode);
        try{
            if(parentNode.getTextWidth() != 0){
                elementWidth = parentNode.getTextWidth() - 18;
            }
        }catch(e){}

        var tmpNode;
        if(YAHOO.haloacl.checkedInGroupstree && YAHOO.haloacl.checkedInGroupstree.indexOf(element.name) != -1){
            tmpNode = new YAHOO.widget.ManageUserNode(element.name, parentNode,false,true);
        }else{
            tmpNode = new YAHOO.widget.ManageUserNode(element.name, parentNode,false);
        }
        tmpNode.setGroupId(element.name);
        tmpNode.setInformation(element.description);

        tmpNode.setTextWidth(elementWidth);
        
        // recursive part, if children were supplied
        if(element.children != null){
            YAHOO.haloacl.buildNodesFromData(tmpNode,element.children,panelid);
            tmpNode.expand();
        }else{
            tmpNode.setDynamicLoad(loadNodeData);
        }

        groupsInTree = true;
        
    };
    if(!groupsInTree){
        if(parentNode.label == gHACLLanguage.getMessage('groups')){
            var tmpNode =  new YAHOO.widget.TextNode(
            {
                label:gHACLLanguage.getMessage("noGroupsAvailable")
            },
            parentNode,
            false);
        //$(tmpNode.contentElId).setAttribute("id", "haloacl_nogroup_info_node");
        }
    //tmpNode.setDynamicLoad();
    }else{
        if($('haloacl_manageuser_count') != null){
            $('haloacl_manageuser_count').innerHTML = parentNode.tree.getRoot().getNodeCount()*1-1;
        }
    }
   

};




/*
 * function to build user tree and add labelClickAction
 * @param tree
 * @param data
 * @param labelClickAction (name)
 */
YAHOO.haloacl.manageUser.buildUserTree = function(tree,data) {

    var tmpNode = new YAHOO.widget.TextNode(gHACLLanguage.getMessage('groups'), tree.getRoot(),false);
    tmpNode.expand();

    YAHOO.haloacl.manageUser.buildNodesFromData(tmpNode,data,tree.panelid);

    //using custom loadNodeDataHandler
    var loadNodeData = function(node, fnLoadComplete)  {
        var nodeLabel = encodeURI(node.label);
        //prepare our callback object
        var callback = {
            panelid:"",
            success: function(oResponse) {
                YAHOO.haloacl.manageUser.buildNodesFromData(node,YAHOO.lang.JSON.parse(oResponse.responseText,tree.panelid));
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
        YAHOO.haloacl.manageUser.treeviewDataConnect('haclGetGroupsForManageUser',{
            query:nodeLabel
        },callback);

    };



    //tree.setDynamicLoad(loadNodeData);
    tree.draw();

};




/*
 * function to be called from outside to init a tree
 * @param tree-instance
 */
YAHOO.haloacl.manageUser.buildTreeFirstLevelFromJson = function(tree){
    var callback = {
        success: function(oResponse) {
            var data = YAHOO.lang.JSON.parse(oResponse.responseText);
            YAHOO.haloacl.manageUser.buildUserTree(tree,data);
        },
        failure: function(oResponse) {
        }
    };
    YAHOO.haloacl.manageUser.treeviewDataConnect('haclGetGroupsForManageUser',{
        query:'all'
    },callback);
};



/**
 * returns a new treeinstance
 */
YAHOO.haloacl.getNewManageUserTree = function(divname,panelid){
    var instance = new YAHOO.widget.TreeView(divname);
    instance.panelid = panelid;
   
    return instance;
};

// GROUP ADDING
YAHOO.haloacl.addingGroupCounter  = 1;

/**
 * Find a group by its label starting at <parentNode>.
 * @param Object parentNode
 * 		The group node where the search for the group starts
 * @param string query
 * 		The name of the searched group.
 * @return
 * 	<null> if the requested group does not exits or
 *  the node of the requested group.
 */
YAHOO.haloacl.manageUser.findGroup = function(parentNode, query){
	
	// Is parentNode the requested node?	
	if (parentNode.label == query) {
		return parentNode;
	}
	
	// Check recursively if one of parentNode's children is the requested node
	var nodes;
    nodes = parentNode.children;
    for (var i=0, l=nodes.length; i<l; ++i) {
        var child = nodes[i];
        var requestedNode = YAHOO.haloacl.manageUser.findGroup(child, query);
        if (requestedNode != null) {
        	return requestedNode;
        }
    }
	
	// Nothing found => return null
	return null;

}


/**
 *  adds subgroup on same level
 *  @param tree-instance
 *  @param groupname
 *
 */
YAHOO.haloacl.manageUser.addNewSubgroupOnSameLevel = function(tree,groupname){
    var nodeToAttachTo = YAHOO.haloacl.manageUser.findGroup(tree,groupname);
    if (nodeToAttachTo == null) {
    	// Group not found
    	return;
    }
    // Attach the new node to the parent of the found node.
	YAHOO.haloacl.manageUser.addSubgroup(tree, nodeToAttachTo.parent);                                     
};



/**
 *  adds subgroup (real subgroup; not same level)
 *  @param tree-instance
 *  @param groupname
 *
 */
YAHOO.haloacl.manageUser.addNewSubgroup = function(tree,groupname){
    var nodeToAttachTo = groupname == "" ? tree.children[0]  // add to root node
                                         : YAHOO.haloacl.manageUser.findGroup(tree,groupname);
	YAHOO.haloacl.manageUser.addSubgroup(tree, nodeToAttachTo);                                     
};

/**
 * Adds a new sub-group to the <groupNode>.
 * @param Object tree
 * 		The tree that contains the hierarchy of groups
 * 
 * @param Object groupNode
 * 		The group node in the group tree that gets a new node.
 * 
 */
YAHOO.haloacl.manageUser.addSubgroup = function(tree, groupNode) {
	if (tree == undefined || groupNode == undefined) {
		return;
	}
	
	// removing no-group-available-node if existing
	try {
		var nodes = tree.children[0].children;
		for (var i = 0, l = nodes.length; i < l; i = i + 1) {
			var n = nodes[i];
			var temp = n.label;
			if (temp.indexOf(gHACLLanguage.getMessage("noGroupsAvailable")) >= 0) {
				tree.tree.removeNode(n);
			}
		}
	} catch (e) {}
		
	var elementWidth = 399;
	try {
		if (groupNode.getTextWidth() != 0) {
			elementWidth = groupNode.getTextWidth() - 18;
		}
	} catch (e) {}
	
	if (YAHOO.haloacl.debug) console.log(groupNode);
	
	var tmpNode = new YAHOO.widget.ManageUserNode(gHACLLanguage.getMessage('newSubgroup') 
	                                              + YAHOO.haloacl.addingGroupCounter, 
	                                              groupNode, false);
	YAHOO.haloacl.addingGroupCounter++;

	tmpNode.description = gHACLLanguage.getMessage('clickEditToCreate');
	tmpNode.setTextWidth(elementWidth);
	
	groupNode.collapse();
	groupNode.expand();
}


/**
 *  applies filter on tree
 *  @param tree-instance
 *  @param query
 *
 */
YAHOO.haloacl.manageUser.applyFilterOnTree = function(tree,filtervalue){
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
                YAHOO.haloacl.manageUser.buildUserTree(tree,data);
            },
            failure: function(oResponse) {
            }
        };
        YAHOO.haloacl.treeviewDataConnect('haclGetGroupsForManageUser',{
            query:'all',
            filtervalue:filtervalue
        },callback);

        //tree.setDynamicLoad(loadNodeData);
        tree.draw();
    }
}

YAHOO.haloacl.manageUsers_handleEdit = function (groupname) {
	YAHOO.haloacl.manageUser_handleGroupSelect(groupname);
	// Find the parent group
	var group = YAHOO.haloacl.manageUser.findGroup(YAHOO.haloacl.treeInstancemanageuser_grouplisting.getRoot(), groupname);
	if (group) {
		YAHOO.haloacl.manageUser_parentGroup = group.parent.label;
	}
	var label = gHACLLanguage.getMessage('haclEditingGroup');
	$('haloacl_panel_name_manageUserGroupsettings').innerHTML = "[ "+label+":" + groupname + " ]"
	if (YAHOO.haloacl.debug) console.log("handle edit called for groupname:" + groupname);
	new Ajax.Request('index.php?action=ajax&rs=haclGetGroupDetails&rsargs[]=' + groupname, {
		method: 'post',
		onSuccess: function (o) {
			var magic = YAHOO.lang.JSON.parse(o.responseText);

			// getting modificationrights
			YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsModificationRight', 'haclGetRightsPanel', {
				panelid: 'manageUserGroupSettingsModificationRight',
				predefine: 'modification'
			});
			// reloading modificationrights
			//$('right_tabview_manageUserGroupSettingsModificationRight').firstChild.fristChild.firstChild.click();

			if (YAHOO.haloacl.debug) console.log(magic);
			YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsRight', 'haclGetManageUserGroupPanel', {
				panelid: 'manageUserGroupSettingsRight',
				name: magic['name'],
				description: magic['description'],
				users: magic['memberUsers'],
				groups: magic['memberGroups'],
				manageUsers: magic['manageUsers'],
				manageGroups: magic['manageGroups']
			});
			$('haloacl_manageUser_editing_container').show();
			
			// Remove the buttons for saving / discarding the group if it has
			// dynamic members.
			if (magic.hasDynamicMembers === true) {
				$('haloacl_save_discard_form').hide();
				$('haloacl_dynamic_group_msg').show();
			} else {
				$('haloacl_save_discard_form').show();		
				$('haloacl_dynamic_group_msg').hide();	
			}
			
			if ($('ManageACLDetail')) {
				$('ManageACLDetail').scrollTo();
			}
		}
	});


	if (groupname.indexOf("new subgroup") > 0) {
		null;
	} else {
		//                 YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsModificationRight','haclGetRightsPanel',{panelid:'manageUserGroupSettingsModificationRight',predefine:'modification'});
		YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsRight', 'haclGetManageUserGroupPanel', {
			panelid: 'manageUserGroupSettingsRight'
		});
		$('haloacl_manageUser_editing_container').show();
		$('manageUserGroupSettingsModificationRight').scrollTo();
		$('haloacl_save_discard_form').show();		
		$('haloacl_dynamic_group_msg').hide();	

	}
}

YAHOO.haloacl.manageUsers_saveGroup = function () {
	if (YAHOO.haloacl.debug) console.log("modificationxml:");
	if (typeof YAHOO.haloacl.buildRightPanelXML_manageUserGroupSettingsModificationRight != "function") {
		// The function is defined after group settings have been saved. 
		YAHOO.haloacl.notification.createDialogOk("content", "Groups", gHACLLanguage.getMessage('saveGroupSettingsFirst'), {
			yes: function () {}
		});
		return;
	}
	var modxml = YAHOO.haloacl.buildRightPanelXML_manageUserGroupSettingsModificationRight(true);
	if (YAHOO.haloacl.debug) console.log(modxml);
	var callback = function (result) {
		if (result.status == '200') {
			//parse result
			//YAHOO.lang.JSON.parse(result.responseText);
			try {
				genericPanelSetSaved_manageUsersPanel(true);
				genericPanelSetName_manageUsersPanel("saved");
				genericPanelSetDescr_manageUsersPanel(result.responseText);
			} catch (e) {}
			YAHOO.haloacl.notification.createDialogOk("content", "Groups", gHACLLanguage.getMessage("groupSaved"), {
				yes: function () {
					window.location.href = YAHOO.haloacl.specialPageUrl + '?activetab=manageUsers';
					//YAHOO.haloacl.loadContentToDiv('manageUserGroupSettingsModificationRight','haclGetRightsPanel',{panelid:'manageUserGroupSettingsModificationRight',predefine:'modification',readOnly:'true'});
				}
			});


		} else {
			YAHOO.haloacl.notification.createDialogOk("content", "Groups", result.responseText, {
				yes: function () {}
			});
		}
	};
	var parentgroup = YAHOO.haloacl.manageUser_parentGroup;

	YAHOO.haloacl.sendXmlToAction(modxml, 'haclSaveGroup', callback, parentgroup);

}
