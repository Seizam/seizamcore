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

/**
 * Base class for all HaloACL language classes.
 * @author Thomas Schweitzer
 */
abstract class HACLLanguage {

	//-- Constants --
	
	//---IDs of parser functions ---
	const PF_ACCESS = 1;
	const PF_MANAGE_RIGHTS = 2;
	const PF_MANAGE_GROUP = 3;
	const PF_PREDEFINED_RIGHT = 4;
	const PF_PROPERTY_ACCESS = 5;
	const PF_WHITELIST = 6;
	const PF_MEMBER = 7;

	//---IDs of parser function parameters ---
	const PFP_ASSIGNED_TO	= 8;
	const PFP_ACTIONS		= 9;
	const PFP_DESCRIPTION	= 10;
	const PFP_RIGHTS		= 11; 
	const PFP_PAGES			= 12; 
	const PFP_MEMBERS		= 13; 
	const PFP_NAME			= 14;
	
	//--- IDs of categories --- 
	const CAT_GROUP					= 14;
	const CAT_RIGHT					= 15;
	const CAT_SECURITY_DESCRIPTOR	= 16;
	
	//--- IDs for strings of the naming convention ---
	const NC_GROUP = 17;
			
	
	// the special message arrays ...
	protected $mNamespaces;
	protected $mNamespaceAliases = array();
	protected $mPermissionDeniedPage;
	protected $mPermissionDeniedPageContent;
	protected $mParserFunctions = array();
	protected $mParserFunctionsParameters = array();
	protected $mActionNames = array();
	protected $mCategories = array();
	protected $mWhitelist = "";
	protected $mPetPrefixes = array();
	protected $mSDTemplateName;			// Part of the name of default SDs for users
	protected $mPredefinedRightName;	// Part of the name of a predefined right
	protected $mNamingConvention = array();
	protected $mLabelNSMain = "main";			// Label of the namespace Main


	public function getPredefinedRightName() {
		return $this->mPredefinedRightName;
	}


	/**
	 * Function that returns an array of namespace identifiers.
	 */

	public function getNamespaces() {
		return $this->mNamespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any.
	 */
	public function getNamespaceAliases() {
		return $this->mNamespaceAliases;
	}
	
	/**
	 * Returns the name of the page that informs the user, that access to
	 * a requested page is denied. A page with this name must be created in the 
	 * wiki.
	 */
	public function getPermissionDeniedPage() {
		return $this->mPermissionDeniedPage;
	}
	
	/**
	 * Returns the content of the page that informs the user, that access to
	 * a requested page is denied. The page is created during setup of the 
	 * extension.
	 */
	public function getPermissionDeniedPageContent() {
		return $this->mPermissionDeniedPageContent;
	}
	
	/**
	 * Users can define a default security descriptor in an article with a
	 * certain naming convention like "ACL:Template/<username>".
	 * This method returns the part after the namespace e.g. "Template" in english
	 *
	 */
	public function getSDTemplateName() {
		return $this->mSDTemplateName;
	}
	
	/**
	 * This method returns the language dependent name of a parser function.
	 * 
	 * @param int $parserFunctionID
	 * 		ID of the parser function i.e. one of PF_ACCESS, 
	 *      PF_MANAGE_RIGHTS, PF_MANAGE_GROUP, PF_PREDEFINED_RIGHT,
	 * 		PF_PROPERTY_ACCESS, PF_WHITELIST, PF_MEMBER
	 * 
	 * @return string 
	 * 		The language dependent name of the parser function.
	 */
	public function getParserFunction($parserFunctionID) {
		return $this->mParserFunctions[$parserFunctionID];
	}
	
	/**
	 * This method returns the language dependent name of a parser function 
	 * parameter.
	 * 
	 * @param int $parserFunctionParameterID
	 * 		ID of the parser function parameter i.e. one of PF_ACCESS, 
	 *      PF_MANAGE_RIGHTS, PF_MANAGE_GROUP, PF_PREDEFINED_RIGHT,
	 * 		PF_PROPERTY_ACCESS, PF_WHITELIST, PF_MEMBER
	 * 
	 * @return string 
	 * 		The language dependent name of the parser function.
	 */
	public function getParserFunctionParameter($parserFunctionParameterID) {
		return $this->mParserFunctionsParameters[$parserFunctionParameterID];
	}
	
	/**
	 * This method returns the language dependent names of all actions that
	 * are used in rights. 
	 *
	 * @return array(int => string)
	 * 		A mapping from action IDs to action names. The possible IDs are
	 * 		HACLRight::READ, HACLRight::FORMEDIT, HACLRight::WYSIWYG, 
	 *      HACLRight::EDIT, HACLRight::CREATE, HACLRight::MOVE, 
	 *		HACLRight::ANNOTATE and	HACLRight::DELETE.
	 * 
	 */
	public function getActionNames() {
		return $this->mActionNames;
	}
	
	/**
	 * Returns the name of the category which certain elements of an ACL must
	 * belong to. 
	 *
	 * @param int $cattype
	 * 		Type of the category: CAT_GROUP, CAT_RIGHT or CAT_SECURITY_DESCRIPTOR
	 * @return string
	 * 		Name of the category
	 */
	public function getCategory($cattype) {
		return $this->mCategories[$cattype];
	}
	
	/**
	 * Returns the name of the article (with or without namespace) that contains
	 * the whitelist. 
	 *
	 * @param bool $withNS
	 * 		true => Name with namespace (default)
	 * 		false => Name without namespace 
	 * 
	 * @return string
	 * 		Complete name of the whitelist.
	 */
	public function getWhitelist($withNS = true) {
		return (($withNS) ? $this->mNamespaces[HACL_NS_ACL].':'
		                  : '').$this->mWhitelist;
	}
	
	/**
	 * Security descriptors protect different types of elements i.e. pages,
	 * instances of categories and namespaces and properties. The name of a
	 * security descriptor has a prefix that matches this type. The prefix
	 * depends on the language. This method return the name of the prefix for given 
	 * type. 
	 * Example: ACL:Page/X is the security descriptor for page X. The prefix is 
	 *          "Page". 
	 *
	 * @param int $peType
	 * 		Type of the protected element which is one of:
	 * 		HACLSecurityDesriptor::PET_PAGE
	 * 		HACLSecurityDesriptor::PET_CATEGORY
	 * 		HACLSecurityDesriptor::PET_NAMESPACE
	 * 		HACLSecurityDesriptor::PET_PROPERTY
	 * 		HACLSecurityDesriptor::PET_RIGHT
	 * 
	 * @return string
	 * 		Prefix for the given type
	 */
	public function getPetPrefix($peType) {
		return $this->mPetPrefixes[$peType];
	}
	
	/**
	 * Elements of the protections system often follow a naming convention.
	 * This method returns strings for certain parts that are used in the names
	 * of these elements.
	 *
	 * @param int $ncID
	 * 		ID of a name that is part an element's name which is one of:
	 * 		NC_GROUP
	 */
	public function getNamingConvention($ncID) {
		return $this->mNamingConvention[$ncID];
	}
	
	public function getLabelOfNSMain() {
		return $this->mLabelNSMain;
		
	}
}


