<?php
/**
 * @file
 * @ingroup HaloACL_Maintenance
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
 * Maintenance script for setting up the database tables for Halo ACL
 * 
 * @author Thomas Schweitzer
 * Date: 21.04.2009
 * 
 */
if (array_key_exists('SERVER_NAME', $_SERVER) && $_SERVER['SERVER_NAME'] != NULL) {
    echo "Invalid access! A maintenance script MUST NOT accessed from remote.";
    return;
}

$mediaWikiLocation = dirname(__FILE__) . '/../../..';
require_once "$mediaWikiLocation/maintenance/commandLine.inc";
$dir = dirname(__FILE__);
$haclgIP = "$dir/../../HaloACL";

require_once("$haclgIP/includes/HACL_Storage.php");
require_once("$haclgIP/includes/HACL_GlobalFunctions.php");

$delete = array_key_exists('delete', $options);
$createUsers = array_key_exists('createUsers', $options);
$ldapDomain = @$options['ldapDomain'];
$help = array_key_exists('help', $options) || array_key_exists('h', $options);
$initDefaults = array_key_exists('initDefaults', $options);

global $haclgBaseStore;
echo "The current store is: $haclgBaseStore \n";

if ($help) {
	echo "Command line parameters for HACL_Setup\n";
	echo "======================================\n";
	echo "no parameter: Setup the database tables for HaloACL\n";
	echo "--delete: Delete all database tables of HaloACL\n";
	echo "--initDefaults: Create the following default groups and global permissions:\n";
	echo "                     Knowledge architect\n";
	echo "                     Knowledge consumer\n";
	echo "                     Knowledge provider\n";
	echo "                     sysop\n";
	echo "                     bureaucrat\n";
	echo "                     These groups are a part of HaloACL's ontology bundle.\n";
	echo "                     You should set the default permissions of all features defined with \$haclgFeature in\n";
	echo "                     HACL_Initialize.php to 'deny' e.g. \$haclgFeature['read']['default'] = \"deny\";\n";
	echo "--createUsers --ldapDomain=\"domain name\": Create the users of the LDAP domain with the name \"domain name\" in the wiki. Domain names with spaces must be quoted.\n";
	echo "\n";
} else if ($createUsers) {
	echo "Creating user accounts for all LDAP users...";
	if (!isset($ldapDomain)) {
		echo "\nPlease specify the LDAP domain with option --ldapDomain ";
		die();
	} else {
		echo "Using LDAP domain: $ldapDomain\n";
		$_SESSION['wsDomain'] = $ldapDomain;
	}
	$newUsers = HACLStorage::getDatabase()->createUsersFromLDAP();
	if (empty($newUsers)) {
		echo "There are no new users on the LDAP server.\n";
	} else {
		echo "Created the following user accounts:\n";
		foreach ($newUsers as $u) {
			echo "$u\n";
		}
		echo "\ndone.\n";
	}
} else if ($delete) {
	echo "Deleting database tables for HaloACL...";
	HACLStorage::getDatabase()->dropDatabaseTables();
	echo "done.\n";
} else if ($initDefaults) {
		
		echo "Importing default rights and groups: ";
		$maintenanceDir = "$mediaWikiLocation/maintenance/";
		$ontologyBundle = "$haclgIP/ontologyBundle/dump.xml";
		$output = array();
		exec("php \"$maintenanceDir/importDump.php\" \"$ontologyBundle\"", $output);
		echo implode("\n",$output);
		echo "Importing done.\n\n";
		
		echo "Refreshing all pages in namespace ACL...\n";
		global $wgUser;
		$wgUser = User::newFromName('WikiSysop');
		refreshACLPages();
		echo "done.\n";
		
		echo "Setting global permissions:\n";
		$permissions = array(
			"Knowledge architect" => array('read', 'edit', 'manage', 'upload'),
			"Knowledge consumer" => array('read', 'edit'),
			"user" => array('read', 'edit'),  // all registered users
			"Knowledge provider" => array('read', 'edit', 'upload'),
			"sysop" => array('read', 'edit', 'manage', 'upload', 'administrate', 'technical', 'createaccount'),
			"bureaucrat" => array('read', 'edit', 'manage', 'upload', 'administrate', 'technical', 'createaccount')
		);
		
		// Store all permission for features
		foreach ($permissions as $group => $perms) {
			try {
				if ($group === "user") {
					$gid = HACLGroupPermissions::REGISTERED_USERS;
				} else {
					$g = HACLGroup::newFromName("Group/$group");
					$gid = $g->getGroupID();
				}
				foreach ($perms as $p) {
					HACLGroupPermissions::storePermission($gid, $p, true);
				}
				echo "Setting permissions for group '$group':". implode(',', $perms)."\n";
			} catch (HACLGroupException $e) {
				echo "Unknown group '$group'. Setting permissions for this group is skipped.\n";
			}
		}
	
} else {
	echo "Setup program for HaloACL\n";
	echo "=========================\n";
	echo "For help, please start with option --h or --help. \n\n";
	echo "Setting up database tables for HaloACL...";
	HACLStorage::getDatabase()->initDatabaseTables();
	echo "done.\n";
	
	// Create page "Permission denied".
	echo "Creating predefined pages...";
	
	global $haclgContLang, $wgLanguageCode;
	haclfInitContentLanguage($wgLanguageCode);
	$pd = $haclgContLang->getPermissionDeniedPage();
	$t = Title::newFromText($pd);
	$a = new Article($t);
	$a->doEdit($haclgContLang->getPermissionDeniedPageContent(),"", EDIT_NEW);
	echo "done.\n";
} 

/**
 * Parses all pages in the namespace ACL
 */
function refreshACLPages() {
	$pages = HACLStorage::getDatabase()->getAllACLPages();
	foreach ($pages as $page) {
		$title = Title::newFromText($page, HACL_NS_ACL);
		echo "    Refreshing: ".$title->getFullText()."\n";
		$article = new Article($title);
		$content = $article->getContent();
		// Set the article's content
		$success = $article->doEdit($content, 'Refreshing article during setup', 
		                            EDIT_UPDATE);
	}
}