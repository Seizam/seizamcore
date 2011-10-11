<?php
/**
 * @file
 * @ingroup HaloACL_Language
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

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $haclgIP;
include_once($haclgIP . '/languages/HACL_Language.php');
include_once($haclgIP . '/includes/HACL_Right.php');
include_once($haclgIP . '/includes/HACL_SecurityDescriptor.php');


/**
 * English language labels for important HaloACL labels (namespaces, ,...).
 *
 * @author Thomas Schweitzer
 */
class HACLLanguageEn extends HACLLanguage {

	protected $mNamespaces = array(
		HACL_NS_ACL       => 'ACL',
		HACL_NS_ACL_TALK  => 'ACL_talk'
	);

	protected $mPermissionDeniedPage = "Permission denied";
	
	protected $mPermissionDeniedPageContent = "You are not allowed to perform the requested action on this page.\n\nReturn to [[Main Page]].";
	
	protected $mParserFunctions = array(
		HACLLanguage::PF_ACCESS				=> 'access', 
		HACLLanguage::PF_MANAGE_RIGHTS		=> 'manage rights',
		HACLLanguage::PF_MANAGE_GROUP		=> 'manage group',
		HACLLanguage::PF_PREDEFINED_RIGHT	=> 'predefined right',
		HACLLanguage::PF_PROPERTY_ACCESS	=> 'property access',
		HACLLanguage::PF_WHITELIST			=> 'whitelist',
		HACLLanguage::PF_MEMBER				=> 'member'
	);
	
	protected $mParserFunctionsParameters = array(
		HACLLanguage::PFP_ASSIGNED_TO	=> 'assigned to', 
		HACLLanguage::PFP_ACTIONS		=> 'actions', 
		HACLLanguage::PFP_DESCRIPTION	=> 'description', 
		HACLLanguage::PFP_RIGHTS		=> 'rights', 
		HACLLanguage::PFP_PAGES			=> 'pages', 
		HACLLanguage::PFP_MEMBERS		=> 'members',
		HACLLanguage::PFP_NAME			=> 'name'		
	);
	
	protected $mActionNames = array(
		HACLRight::READ     => 'read',
		HACLRight::FORMEDIT => 'formedit',
		HACLRight::WYSIWYG	=> 'wysiwyg',
		HACLRight::EDIT     => 'edit',
		HACLRight::CREATE   => 'create',
		HACLRight::MOVE     => 'move',
		HACLRight::ANNOTATE => 'annotate',
		HACLRight::DELETE   => 'delete',
		HACLRight::ALL_ACTIONS => '*'
	);
	
	protected $mCategories = array(
		HACLLanguage::CAT_GROUP		=> 'Category:ACL/Group',
		HACLLanguage::CAT_RIGHT		=> 'Category:ACL/Right',
		HACLLanguage::CAT_SECURITY_DESCRIPTOR => 'Category:ACL/ACL',
	);
	
	protected $mWhitelist = "Whitelist";
	
	protected $mPetPrefixes = array(
		HACLSecurityDescriptor::PET_PAGE	  => 'Page',
		HACLSecurityDescriptor::PET_CATEGORY  => 'Category',
		HACLSecurityDescriptor::PET_NAMESPACE => 'Namespace',
		HACLSecurityDescriptor::PET_PROPERTY  => 'Property',
		HACLSecurityDescriptor::PET_RIGHT	  => 'Right'
	);
	
	protected $mSDTemplateName = "Template";
    protected $mPredefinedRightName = "Right";
    protected $mNamingConvention = array(
		HACLLanguage::NC_GROUP => 'Group'
	);
    
	protected $mLabelNSMain = "Main";
	
}


