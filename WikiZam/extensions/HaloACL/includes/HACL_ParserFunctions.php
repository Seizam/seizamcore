<?php
/**
 * @file
 * @ingroup HaloACL
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
 * This file contains the implementation of parser functions for HaloACL.
 *
 * @author Thomas Schweitzer
 * Date: 30.04.2009
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

//--- Includes ---
global $haclgIP;
//require_once("$haclgIP/...");
$wgExtensionFunctions[] = 'haclfInitParserfunctions';

$wgHooks['LanguageGetMagic'][] = 'haclfLanguageGetMagic';


function haclfInitParserfunctions() {
	global $wgParser;

	HACLParserFunctions::getInstance();

	$wgParser->setFunctionHook('haclaccess', array('HACLParserFunctions', 'access'), SFH_OBJECT_ARGS);
	$wgParser->setFunctionHook('haclpropertyaccess', array('HACLParserFunctions', 'propertyAccess'), SFH_OBJECT_ARGS);
	$wgParser->setFunctionHook('haclpredefinedright', array('HACLParserFunctions', 'predefinedRight'));
	$wgParser->setFunctionHook('haclwhitelist', array('HACLParserFunctions', 'whitelist'));
	$wgParser->setFunctionHook('haclmanagerights', array('HACLParserFunctions', 'manageRights'));
	$wgParser->setFunctionHook('haclmember', array('HACLParserFunctions', 'addMember'), SFH_OBJECT_ARGS);
	$wgParser->setFunctionHook('haclmanagegroup', array('HACLParserFunctions', 'manageGroup'));

}

function haclfLanguageGetMagic( &$magicWords, $langCode ) {
	global $haclgContLang;
	$magicWords['haclaccess']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_ACCESS));
	$magicWords['haclpropertyaccess']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_PROPERTY_ACCESS));
	$magicWords['haclpredefinedright']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_PREDEFINED_RIGHT));
	$magicWords['haclwhitelist']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_WHITELIST));
	$magicWords['haclmanagerights']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_RIGHTS));
	$magicWords['haclmember']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_MEMBER));
	$magicWords['haclmanagegroup']
		= array( 0, $haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_GROUP));

	return true;
}


/**
 * The class HACLParserFunctions contains all parser functions of the HaloACL
 * extension. The following functions are parsed:
 * - access
 * - property access
 * - predefined right
 * - whitelist
 * - manage rights
 * - member
 * - manage group
 *
 * @author Thomas Schweitzer
 *
 */
class HACLParserFunctions {

	//--- Constants ---

	//--- Private fields ---
	// Title: The title to which the functions are applied
	private $mTitle = 0;

	// array(HACLRight): All inline rights of the title
	private $mInlineRights = array();

	// array(HACLRight): All inline rights of the title for properties
	private $mPropertyRights = array();

	// array(string): All predefined rights that are referenced
	private $mPredefinedRights = array();

	// array(string): All pages that are added to the whitelist
	private $mWhitelist = array();

	// array(string): Users who can change a right
	private $mRightManagerUsers = array();

	// array(string): Groups who can change a right
	private $mRightManagerGroups = array();

	// array(string): Users who can change a group
	private $mGroupManagerUsers = array();

	// array(string): Groups who can change a group
	private $mGroupManagerGroups = array();

	// array(string): Users who are member of a group
	private $mUserMembers = array();

	// array(string): Groups who are member of a group
	private $mGroupMembers = array();
	
	// array(string): Queries for dynamic members of groups. These members can
	// be groups and users.								
	private $mDynamicMemberQueries = array(); 

	// bool: true if all parser functions of an article are valid
	private $mDefinitionValid = true;

	// string: Type of the definition: group, right, sd, whitelist, invalid
	private $mType = 'invalid';
	
	// bool: true, if the current article contains definitions that have to be
	//   saved in the database
	private $mArticleContainsDefinitions = false;
	
	// bool: true, if the article was saved
	private $mArticleSaved = false;

	// HACLParserFunctions: The only instance of this class
	private static $mInstance = null;

	// array(string): fingerprints of all invokations of parser functions
	// The parser may be called several times in the same article but the data
	// generated in the parser functions must only be saved once.
	private $mFingerprints = array();

	/**
	 * Constructor for HACLParserFunctions. This object is a singleton.
	 */
	private function __construct() {
	}

	//--- Getters / setters
	public function getInlineRights() { return $this->mInlineRights; }
	public function getUserMembers()	{ return $this->mUserMembers; }
	public function getGroupMembers()	{ return $this->mGroupMembers; }
	public function getDynamicMemberQueries()	{ return $this->mDynamicMemberQueries; }

	//--- Public methods ---
	
	public static function getInstance() {
		if (is_null(self::$mInstance)) {
			self::$mInstance = new self;
		}
		return self::$mInstance;
	}

	/**
	 * Resets the singleton instance of this class. Normally this instance is
	 * only used for parsing ONE article. If several articles are parsed in
	 * one invokation of the wiki system, this singleton has to be reset (e.g.
	 * for unit tests).
	 *
	 */
	public function reset() {
		$this->mTitle = 0;
		$this->mInlineRights = array();
		$this->mPropertyRights = array();
		$this->mPredefinedRights = array();
		$this->mWhitelist = array();
		$this->mRightManagerUsers = array();
		$this->mRightManagerGroups = array();
		$this->mGroupManagerUsers = array();
		$this->mGroupManagerGroups = array();
		$this->mUserMembers = array();
		$this->mGroupMembers = array();
		$this->mDynamicMemberQueries = array();
		$this->mFingerprints = array();
		$this->mDefinitionValid = true;
		$this->mType = 'invalid';
		$this->mArticleContainsDefinitions = false;
		$this->mArticleSaved = false;
	}


	/**
	 * Callback for parser function "#access:".
	 * This parser function defines an access control entry (ACE) in form of an
	 * inline right definition. It can appear several times in an article and
	 * has the following parameters:
	 * assigned to: This is a comma separated list of user groups and users whose
	 *              access rights are defined. The special value stands for all
	 *              anonymous users. The special value user stands for all
	 *              registered users.
	 * actions: This is the comma separated list of actions that are permitted.
	 *          The allowed values are read, edit, formedit, create, move,
	 *          annotate and delete. The special value comprises all of these actions.
	 * description:This description in prose explains the meaning of this ACE.
	 * name: (optional) A short name for this inline right
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 */
	public static function access(&$parser) {
		$params = self::$mInstance->getParameters(func_get_args());
		$fingerprint = self::$mInstance->makeFingerprint("access", $params);
		self::$mInstance->prepareTitle($parser);

		// handle the parameter "assigned to".
		list($users, $groups, $dynamicAssignees, $em1, $warnings) 
			= self::$mInstance->assignedTo($params);

		// handle the parameter 'action'
		list($actions, $em2) = self::$mInstance->actions($params);

		// handle the (optional) parameter 'description'
		global $haclgContLang;
		$descPN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_DESCRIPTION);
		$descPN = strtolower($descPN);
		$description = array_key_exists($descPN, $params)
						? $params[$descPN]
						: "";
		if (is_array($description)) {
			// If the description contains a template parameter '{{{...}}}', the
			// description is returned as array
			array_shift($description);
			$description = implode(' ', $description);
		}
		// handle the (optional) parameter 'name'
		$namePN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_NAME);
		$namePN = strtolower($namePN);
		$name = array_key_exists($namePN, $params)
					? $params[$namePN]
					: "";
		if (is_array($name)) {
			// If the $name contains a template parameter '{{{...}}}', the
			// $name is returned as array
			array_shift($name);
			$name = implode(' ', $name);
		}
					
		$errMsgs = $em1 + $em2;

		if (count($errMsgs) == 0) {
			// no errors
			// => create and store the new right for later use.
			if (!in_array($fingerprint, self::$mInstance->mFingerprints)) {
				$ir = new HACLRight(self::$mInstance->actionNamesToIDs($actions), 
				                    $groups, $users, $dynamicAssignees, 
				                    $description, $name);
				self::$mInstance->mInlineRights[] = $ir;
				self::$mInstance->mFingerprints[] = $fingerprint;
				$dynamicAssignees = $ir->queryDynamicAssignees(HACLRight::NAME);
				$dynamicAssignees['queries'] = $ir->getDynamicAssigneeQueries();
			}
		} else {
			self::$mInstance->mDefinitionValid = false;
		}

		// Format the defined right in Wikitext
		if (!empty($name)) {
			$text = wfMsgForContent('hacl_pf_rightname_title', trim($name))
					.wfMsgForContent('hacl_pf_rights', implode(' ,', $actions));
		} else {
			$text = wfMsgForContent('hacl_pf_rights_title', implode(' ,', $actions));
		}
		$text .= self::$mInstance->showAssignees($users, $groups, $dynamicAssignees);
		$text .= self::$mInstance->showDescription($description);
		$text .= self::$mInstance->showErrors($errMsgs);
		$text .= self::$mInstance->showWarnings($warnings);
		
		return $text;

	}

	/**
	 * Callback for parser function "#property access:".
	 * This parser function defines an access control entry (ACE) in form of an
	 * inline right definition for a property. It can appear several times in an
	 * article and has the following parameters:
	 * assigned to: This is a comma separated list of user groups and users whose
	 *              access rights are defined. The special value stands for all
	 *              anonymous users. The special value user stands for all
	 *              registered users.
	 * actions: This is the comma separated list of actions that are permitted.
	 *          The allowed values are read, edit, formedit, create, move,
	 *          annotate and delete. The special value comprises all of these actions.
	 * description:This description in prose explains the meaning of this ACE.
	 * name: (optional) A short name for this inline right
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 *
	 */
	public static function propertyAccess(&$parser) {
		$params = self::$mInstance->getParameters(func_get_args());
		$fingerprint = self::$mInstance->makeFingerprint("propertyaccess", $params);
		self::$mInstance->prepareTitle($parser);

		// handle the parameter "assigned to".
		list($users, $groups, $dynamicAssignees, $em1, $warnings) 
			= self::$mInstance->assignedTo($params);

		// handle the parameter 'action'
		list($actions, $em2) = self::$mInstance->actions($params);

		// handle the (optional) parameter 'description'
		global $haclgContLang;
		$descPN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_DESCRIPTION);
		$descPN = strtolower($descPN);
		$description = array_key_exists($descPN, $params)
			? $params[$descPN]
			: "";
		if (is_array($description)) {
			// If the $name contains a template parameter '{{{...}}}', the
			// $name is returned as array
			array_shift($description);
			$description = implode(' ', $description);
		}
			
		// handle the (optional) parameter 'name'
		$namePN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_NAME);
		$namePN = strtolower($namePN);
		$name = array_key_exists($namePN, $params)
			? $params[$namePN]
			: "";
		if (is_array($name)) {
			// If the $name contains a template parameter '{{{...}}}', the
			// $name is returned as array
			array_shift($name);
			$name = implode(' ', $name);
		}
			

		$errMsgs = $em1 + $em2;

		if (count($errMsgs) == 0) {
			// no errors
			// => create and store the new right for later use.
			if (!in_array($fingerprint, self::$mInstance->mFingerprints)) {
				$ir = new HACLRight(self::$mInstance->actionNamesToIDs($actions), 
				                    $groups, $users, $dynamicAssignees, 
				                    $description, $name);
				self::$mInstance->mPropertyRights[] = $ir;
				self::$mInstance->mFingerprints[] = $fingerprint;
				// get all dynamic assignees of this right
				$dynamicAssignees = $ir->queryDynamicAssignees(HACLRight::NAME);
				$dynamicAssignees['queries'] = $ir->getDynamicAssigneeQueries();
			}
		} else {
			self::$mInstance->mDefinitionValid = false;
		}

		// Format the defined right in Wikitext
		if (!empty($name)) {
			$text = wfMsgForContent('hacl_pf_rightname_title', trim($name))
					.wfMsgForContent('hacl_pf_rights', implode(' ,', $actions));
		} else {
			$text = wfMsgForContent('hacl_pf_rights_title', implode(' ,', $actions));
		}
		$text .= self::$mInstance->showAssignees($users, $groups, $dynamicAssignees);
		$text .= self::$mInstance->showDescription($description);
		$text .= self::$mInstance->showErrors($errMsgs);
		$text .= self::$mInstance->showWarnings($warnings);
		
		return $text;
	}

	/**
	 * Callback for parser function "#predefined right:".
	 * Besides inline right definitions ACLs can refer to other sets of rights
	 * that are defined in another article. This parser function established the
	 * connection. It can appear several times in security descriptors and
	 * articles with predefined rights. There is only one parameter:
	 * rights: This is a comma separated list of article names with the prefix
	 *         ACL:Right/
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 */
	public static function predefinedRight(&$parser) {
		self::$mInstance->prepareTitle($parser);

		$params = self::$mInstance->getParameters(func_get_args());
		$fingerprint = self::$mInstance->makeFingerprint("predefinedRight", $params);

		// handle the parameter 'rights'
		list($rights, $em, $warnings) = self::$mInstance->rights($params);

		if (count($em) == 0) {
			// no errors
			// => store the rights for later use.
			if (!in_array($fingerprint, self::$mInstance->mFingerprints)) {
				foreach ($rights as $r) {
					try {
						$rightDescr = HACLSecurityDescriptor::newFromName($r);
						self::$mInstance->mPredefinedRights[] = $rightDescr;
					} catch (HACLSDException $e) {
						// There is an article with the name of the right but it does
						// not define a right (yet)
						$em[] = wfMsgForContent('hacl_invalid_predefined_right', $r);
						self::$mInstance->mDefinitionValid = false;
					}
				}
				self::$mInstance->mFingerprints[] = $fingerprint;
			}
		} else {
			self::$mInstance->mDefinitionValid = false;
		}

		// Format the rights in Wikitext
		$text = wfMsgForContent('hacl_pf_predefined_rights_title');
		$text .= self::$mInstance->showRights($rights);
		$text .= self::$mInstance->showErrors($em);
		$text .= self::$mInstance->showWarnings($warnings);
		
		return $text;

	}

	/**
	 * Callback for parser function "#whitelist:".
	 * This parser function can only appear in the article ACL:Whitelist,
	 * however several times. It has only one parameter:
	 * pages: This is a comma separated list of full article names that can be
	 *        read by everyone.
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 */
	public static function whitelist(&$parser) {
		self::$mInstance->prepareTitle($parser);

		$params = self::$mInstance->getParameters(func_get_args());

		global $wgContLang, $haclgContLang;
		$errMsgs = array();
		$pages = array();

		$pagesPN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_PAGES);
		$pagesPN = strtolower($pagesPN);
		if (!array_key_exists($pagesPN, $params)) {
			// The parameter "pages" is missing.
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter', $pagesPN);
		} else {
			$pages = $params[$pagesPN];
			$pages = explode(',', $pages);
			// trim pages
			for ($i = 0; $i < count($pages); ++$i) {
				$pn = trim($pages[$i]);
				$etc = haclfDisableTitlePatch();
				$t = Title::newFromText($pn);
				haclfRestoreTitlePatch($etc);
				// Create page names with correct upper/lower cases
				$pages[$i] = $t->getFullText();
			}
			if (count($pages) == 0) {
				$errMsgs[] = wfMsgForContent('hacl_missing_parameter_values', $pagesPN);
			} else {
				self::$mInstance->mWhitelist = array_merge(self::$mInstance->mWhitelist, $pages);
			}
		}
		// Remove duplicate pages from the whitelist
		self::$mInstance->mWhitelist = array_unique(self::$mInstance->mWhitelist);

		if (count($errMsgs) > 0) {
			self::$mInstance->mDefinitionValid = false;
		}

		// Format the whitelist in Wikitext
		$text = wfMsgForContent('hacl_pf_whitelist_title');
		// Show the whitelist pages in the same format as predefined rights
		$text .= self::$mInstance->showRights($pages, false);
		$text .= self::$mInstance->showErrors($errMsgs);
		return $text;
	}

	/**
	 * Callback for parser function "#manage rights:".
	 * This function can be used in security descriptors and predefined rights.
	 * It defines which user or group can change the ACL.
	 * assigned to: This is a comma separated list of users and groups that can
	 *              modify the security descriptor.
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 */
	public static function manageRights(&$parser) {

		$params = self::$mInstance->getParameters(func_get_args());
		$fingerprint = self::$mInstance->makeFingerprint("manageRights", $params);

		self::$mInstance->prepareTitle($parser);

		// handle the parameter "assigned to".
		list($users, $groups, $dynamicAssignees, $errMsgs, $warnings) = 
			self::$mInstance->assignedTo($params);

		if (count($errMsgs) == 0) {
			// no errors
			// => store the list of assignees for later use.
			if (!in_array($fingerprint, self::$mInstance->mFingerprints)) {
				self::$mInstance->mRightManagerUsers  = array_merge(self::$mInstance->mRightManagerUsers, $users);
				self::$mInstance->mRightManagerGroups = array_merge(self::$mInstance->mRightManagerGroups, $groups);
				self::$mInstance->mFingerprints[] = $fingerprint;
			}
		} else {
			self::$mInstance->mDefinitionValid = false;
		}

		// Format the right managers in Wikitext
		$text = wfMsgForContent('hacl_pf_right_managers_title');
		$text .= self::$mInstance->showAssignees($users, $groups, null);
		$text .= self::$mInstance->showErrors($errMsgs);
		$text .= self::$mInstance->showWarnings($warnings);
		
		return $text;
	}

	/**
	 * Callback for parser function "#member:".
	 * This function can appear (several times) in ACL group definitions. It
	 * defines a list of users and ACL groups that belong to the group.
	 * members: This is a comma separated list of users and groups that belong
	 *          to the group.
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 */
	public static function addMember(&$parser) {
		$params = self::$mInstance->getParameters(func_get_args());
		$fingerprint = self::$mInstance->makeFingerprint("addMember", $params);
		
		self::$mInstance->prepareTitle($parser);

		// handle the parameter "assigned to".
		list($users, $groups, $dynamicMemberQueries, $errMsgs, $warnings) 
			= self::$mInstance->assignedTo($params, false);

		$dynamicMembers = array();
		if (count($errMsgs) == 0) {
			// no errors
			// => store the list of members for later use.
			if (!in_array($fingerprint, self::$mInstance->mFingerprints)) {
				self::$mInstance->mUserMembers  = array_merge(self::$mInstance->mUserMembers, $users);
				self::$mInstance->mGroupMembers = array_merge(self::$mInstance->mGroupMembers, $groups);
				self::$mInstance->mDynamicMemberQueries = array_merge(self::$mInstance->mDynamicMemberQueries, $dynamicMemberQueries);

				// Get all dynamic members for this member definition
				$memberCache = HACLDynamicMemberCache::getInstance(); 
				foreach ($dynamicMemberQueries as $dmq) {
					$members = HACLGroup::executeDMQuery($dmq);
					$memberCache->addMembers(-1, $members);
				}
				$dynamicMembers = $memberCache->getMembers(-1, HACLRight::NAME);
				$dynamicMembers['queries'] = $dynamicMemberQueries;
				$memberCache->clearCache(-1);
				
				self::$mInstance->mFingerprints[] = $fingerprint;
			}
		} else {
			self::$mInstance->mDefinitionValid = false;
		}


		// Format the group members in Wikitext
		$text = wfMsgForContent('hacl_pf_group_members_title');
		$text .= self::$mInstance->showAssignees($users, $groups, $dynamicMembers, false);
		$text .= self::$mInstance->showErrors($errMsgs);
		$text .= self::$mInstance->showWarnings($warnings);
		
		return $text;
	}

	/**
	 * Callback for parser function "#manage group:".
	 * This function can be used in ACL group definitions. It defines which user
	 * or group can change the group.
	 * assigned to: This is a comma separated list of users and groups that can
	 *              modify the group.
	 *
	 * @param Parser $parser
	 * 		The parser object
	 *
	 * @return string
	 * 		Wikitext
	 *
	 *
	 * @throws
	 * 		HACLException(HACLException::INTERNAL_ERROR)
	 * 			... if the parser function is called for different articles
	 */
	public static function manageGroup(&$parser) {
		$params = self::$mInstance->getParameters(func_get_args());
		$fingerprint = self::$mInstance->makeFingerprint("managerGroup", $params);

		self::$mInstance->prepareTitle($parser);
		
		// handle the parameter "assigned to".
		list($users, $groups, $dynamicAssignees, $errMsgs, $warnings)
			= self::$mInstance->assignedTo($params);

		if (count($errMsgs) == 0) {
			// no errors
			// => store the list of assignees for later use.
			if (!in_array($fingerprint, self::$mInstance->mFingerprints)) {
				self::$mInstance->mGroupManagerUsers  = array_merge(self::$mInstance->mGroupManagerUsers, $users);
				self::$mInstance->mGroupManagerGroups = array_merge(self::$mInstance->mGroupManagerGroups, $groups);
				self::$mInstance->mFingerprints[] = $fingerprint;
			}
		} else {
			self::$mInstance->mDefinitionValid = false;
		}

		// Format the right managers in Wikitext
		$text = wfMsgForContent('hacl_pf_group_managers_title');
		$text .= self::$mInstance->showAssignees($users, $groups, null);
		$text .= self::$mInstance->showErrors($errMsgs);
		$text .= self::$mInstance->showWarnings($warnings);
		
		return $text;
	}
	
	/**
	 * This method is called, after an article has been saved. If the article
	 * belongs to the namespace ACL (i.e. a right, SD, group or whitelist)
	 * its content is transferred to the database.
	 *
	 * @param Article $article
	 * @param User $user
	 * @param string $text
	 * @return true
	 */
	public static function articleSaveComplete(&$article, &$user, $text) {
		if ($article->getTitle()->getNamespace() == HACL_NS_ACL) {
			// The article is in the ACL namespace.
			// Check if there is some corresponding definition in the ACL database.
			// The content of the definition has to be deleted, as the article
			// may no longer contain definitions.
			$id = $article->getTitle()->getArticleID();
			try {
				$group = HACLGroup::newFromID($id);
				// It is a group
				// => remove all current members, however the group remains in the
				//    hierarchy of groups, as it might be "revived"
				$group->removeAllMembers();
				// The empty group article can now be changed by everyone
				$group->setManageGroups(null);
				$group->setManageUsers("*,#");
				$group->save();
			} catch (HACLGroupException $e) {
				try {
					$sd = HACLSecurityDescriptor::newFromID($id);
					// It is a right or security descriptor
					// => remove all current rights, however the right remains in
					//    the hierarchy of rights, as it might be "revived"
					$sd->removeAllRights();
					// The empty right article can now be changed by everyone
					$sd->setManageGroups(null);
					$sd->setManageUsers("*,#");
					$sd->save();
				} catch (HACLSDException $e) {
					// Check if it is the whitelist
					global $haclgContLang;
					if ($article->getTitle()->getFullText() == $haclgContLang->getWhitelist()) {
						try {
							$wl = new HACLWhitelist(self::$mInstance->mWhitelist);
							$wl->save();
						} catch (HACLWhitelistException $e) {}
					}

				}
			}

		}
		if (self::$mInstance->mTitle == null) {
			// No parser function found in the article.
			return true;
		}

		// Store the definition in the database.
		self::$mInstance->saveDefinition();

		// The cache must be invalidated, so that error messages can be
		// generated when the article is displayed for the first time after
		// saving.
		$article->mTitle->invalidateCache();
		self::$mInstance->mArticleSaved = true;
		
		return true;

	}

	/**
	 * This method is called, when an article is deleted. If the article
	 * belongs to the namespace ACL (i.e. a right, SD, group or whitelist)
	 * its removal is reflected in the database.
	 *
	 * @param unknown_type $article
	 * @param unknown_type $user
	 * @param unknown_type $reason
	 */
	public static function articleDelete(&$article, &$user, &$reason) {
		if ($article->getTitle()->getNamespace() == HACL_NS_ACL) {
			// The article is in the ACL namespace.
			// Check if there is some corresponding definition in the ACL database.
			// The content of the definition has to be deleted.
			$id = $article->getTitle()->getArticleID();
			try {
				$group = HACLGroup::newFromID($id);
				// It is a group
				// => remove all current members, however the group remains in the
				//    hierarchy of groups, as it might be "revived"
				$group->delete();
			} catch (HACLGroupException $e) {
				try {
					$sd = HACLSecurityDescriptor::newFromID($id);
					$sdname = $sd->getSDName();
					$sdid = $sd->getSDID();
					// It is a right or security descriptor
					// => remove all current rights, however the right remains in
					//    the hierarchy of rights, as it might be "revived"
					$sd->delete();

					// if sd was a template, also remove it from quickacls
					try {
						if(preg_match("/Right\//is", $sdname) || preg_match("/Template\//is", $sdname)) {
							HACLQuickacl::removeQuickAclsForSD($sdid);
						}
					}catch(Exception $e){}

				} catch (HACLSDException  $e) {
					// Check if it is the whitelist
					global $haclgContLang;
					if ($article->getTitle()->getText() == $haclgContLang->getWhitelist(false)) {
						// Create an empty whitelist and save it.
						$wl = new HACLWhitelist();
						$wl->save();
					}
				}
			}
		} else {
			// If a protected article is deleted, its SD will be deleted as well
			$sd = HACLSecurityDescriptor::getSDForPE($article->getTitle()->getArticleID(),
			                                         HACLSecurityDescriptor::PET_PAGE);
			if ($sd) {
				$t = Title::newFromID($sd);
				$a = new Article($t);
				$a->doDelete("");
			}
		}
		return true;
	}

	/**
	 * This method is called, when an article is moved. If the article has a
	 * security descriptor of type page or property, the SD is moved accordingly.
	 *
	 * @param unknown_type $specialPage
	 * @param unknown_type $oldTitle
	 * @param unknown_type $newTitle
	 */
	public static function articleMove(&$specialPage, &$oldTitle, &$newTitle) {
		$newName = $newTitle->getFullText();
		// Check if the old title has an SD
		$sd = HACLSecurityDescriptor::getSDForPE($newTitle->getArticleID(),
		HACLSecurityDescriptor::PET_PAGE);
		if ($sd !== false) {
			// move SD for page
			$oldSD = Title::newFromID($sd);
			$oldSD = $oldSD->getFullText();
			$newSD = HACLSecurityDescriptor::nameOfSD($newName,
			HACLSecurityDescriptor::PET_PAGE);

			self::move($oldSD, $newSD);
		}

		$sd = HACLSecurityDescriptor::getSDForPE($newTitle->getArticleID(),
		HACLSecurityDescriptor::PET_PROPERTY);
		if ($sd !== false) {
			// move SD for property
			$oldSD = Title::newFromID($sd);
			$oldSD = $oldSD->getFullText();
			$newSD = HACLSecurityDescriptor::nameOfSD($newName,
			HACLSecurityDescriptor::PET_PROPERTY);
			self::move($oldSD, $newSD);
		}
		return true;
	}

	/**
	 * This method is called just before an article's HTML is sent to the
	 * client. If the article contains definitions for ACLs, their consistency
	 * is checked and error messages are added to the article.
	 *
	 * @param unknown_type $out
	 * @param unknown_type $text
	 * @return bool true
	 *
	 */
	public static function outputPageBeforeHTML(&$out, &$text) {
		global $haclgContLang;
		if (self::$mInstance->mTitle == null) {
			// no parser function in this article
			return true;
		}
		global $wgRequest;
		$action = $wgRequest->getVal('action', 'view');
		if (self::$mInstance->mArticleContainsDefinitions 
		    && !self::$mInstance->mArticleSaved
		    && $action !== 'view') {
			// The article contains unsaved definitions (probably during import
			// of pages)
			// => save them now
			global $wgUser;
			$article = Article::newFromID(self::$mInstance->mTitle->getArticleID());
			self::articleSaveComplete($article, $wgUser, "");
		}
		
		$msg = self::$mInstance->checkConsistency();

		// Check if this article is already represented in the database
		$id = self::$mInstance->mTitle->getArticleID();
		$etc = haclfDisableTitlePatch();
		$wl = Title::newFromText($haclgContLang->getWhitelist());
		haclfRestoreTitlePatch($etc);
		$wlid = $wl->getArticleID();

		$isWhitelist = ($id == $wlid);
		if ($msg === true &&
			!HACLGroup::exists($id) &&
			!HACLSecurityDescriptor::exists($id) &&
			!$isWhitelist) {
			$msg = array(wfMsgForContent('hacl_acl_element_not_in_db'));
		}

		if ($isWhitelist) {
			// Check if the whitelist defined in the article matches the
			// whitelist in the database. Articles that did no exist when the
			// whitelist was saved were not added to the database.
			$wl = HACLWhitelist::newFromDB();
			$wl = $wl->getPages();
			$inArticle = array_diff(self::$mInstance->mWhitelist, $wl);
			if (!empty($inArticle)) {
				// There are more pages in the article than in the database
				$m = wfMsgForContent('hacl_whitelist_mismatch');
				$m .= '<ul>';
				foreach ($inArticle as $ia) {
					$m .= '<li>'.$ia.'</li>';
				}
				$m .= '</ul>';
				if (!is_array($msg)) {
					$msg = array();
				}
				$msg[] = $m;
			}
		}

		if ($msg !== true) {
			$out->addHTML(wfMsgForContent('hacl_consistency_errors'));
			$out->addHTML(wfMsgForContent('hacl_definitions_will_not_be_saved'));
			$out->addHTML("<ul>");
			foreach ($msg as $m) {
				$out->addHTML("<li>$m</li>");
			}
			$out->addHTML("</ul>");
		}
		return true;
	}

	//--- Private methods ---
	
	/**
	 * The parser functions are called for a certain article, which is identified
	 * by its title. This singleton stores the current title and collects all
	 * security definitions of the article until the hook articleSaveComplete is
	 * called. However, in bulk operations like an import of articles this hook
	 * will not be called and the article changes without any notification. In 
	 * this case this function stores all collected definitions of the current
	 * article, resets this singleton and sets the new title.
	 * 
	 * @param Parser $parser
	 * 		The parser contains the title of the currently parsed article.
	 */
	private function prepareTitle(Parser $parser) {
		$title = $parser->getTitle();
		if ($this->mTitle == null) {
			$this->mTitle = $title;
		} else if ($title->getArticleID() != $this->mTitle->getArticleID()) {
			// The parsed article changed
			// => store the current definitions
			global $wgUser;
			$article = Article::newFromID($this->mTitle->getArticleID());
			if (isset($article)) {
				self::articleSaveComplete($article, $wgUser, "");
			}
			
			// reset this singleton
			$this->reset();
			
			// Set the new title for the following parsing operations
			$this->mTitle = $title;
// Previous versions threw this exception			
//			throw new HACLException(HACLException::INTERNAL_ERROR,
//                "The parser functions are called for different articles.");
		}
		$this->mArticleContainsDefinitions = true;
		$this->mArticleSaved = false;
		
	}

	/**
	 * This class collects all functions for ACLs of an article. The collected
	 * definitions are finally saved to the database with this method.
	 * If there is already a definition for the article, it will be replaced.
	 *
	 * @return bool
	 * 		true, if saving was successful
	 * 		false, if not
	 */
	private function saveDefinition() {

		// Check if all definitions for ACL are consistent.
		if (self::$mInstance->checkConsistency() !== true ||
			!$this->mDefinitionValid) {
			return false;
		}

		switch ($this->mType) {
			case 'invalid':
				return false;
			case 'group':
				return $this->saveGroup();
				break;
			case 'right':
				return $this->saveSecurityDescriptor(true);
				break;
			case 'sd':
				return $this->saveSecurityDescriptor(false);
				break;
			case 'whitelist':
				return $this->saveWhitelist();
				break;

		}

	}

	/**
	 * Saves a group based on the definitions given in the current article.
	 *
	 * @return bool
	 * 		true, if saving was successful
	 * 		false, if not
	 */
	private function saveGroup() {
		$t = $this->mTitle;
		// group does not exist yet
		$group = new HACLGroup($t->getArticleID(), $t->getText(),
								$this->mGroupManagerGroups,
								$this->mGroupManagerUsers);
		$group->save();
		$group->removeAllMembers();
		foreach ($this->mGroupMembers as $m) {
			$group->addGroup($m);
		}
		foreach ($this->mUserMembers as $m) {
			$group->addUser($m);
		}
		$group->addDynamicMemberQueries($this->mDynamicMemberQueries);

		return true;
	}

	/**
	 * Saves a right or security descriptor based on the definitions given in
	 * the current article.
	 *
	 * @param bool $isRight
	 * 		true  => save a right
	 * 		false => save a security descriptor
	 *
	 * @return bool
	 * 		true, if saving was successful
	 * 		false, if not
	 */
	private function saveSecurityDescriptor($isRight) {
		$t = $this->mTitle;
		try {
			$sd = HACLSecurityDescriptor::newFromID($t->getArticleID());
			// The right already exists. => delete the rights it contains
			$sd->removeAllRights();
		} catch (HACLSDException $e) {
		}
		list($pe, $peType) = HACLSecurityDescriptor::nameOfPE($t->getText());
		$sd = new HACLSecurityDescriptor($t->getArticleID(), $t->getText(), $pe,
										$peType,
										$this->mRightManagerGroups,
										$this->mRightManagerUsers);
		$sd->save();

		// add all inline rights
		$sd->addInlineRights($this->mInlineRights);
		// add all property rights
		$sd->addInlineRights($this->mPropertyRights);
		// add all predefined rights
		$sd->addPredefinedRights($this->mPredefinedRights);

		return true;
	}


	/**
	 * Saves the whitelist based on the definitions given in the current article.
	 *
	 * @return bool
	 * 		true, if saving was successful
	 * 		false, if not
	 */
	private function saveWhitelist() {
		try {
			$wl = new HACLWhitelist($this->mWhitelist);
			$wl->save();
		} catch (HACLWhitelistException $e) {
			// Some articles could not be added to the whitelist, because they
			// do not exits
		}
		return true;
	}

	/**
	 * Checks the consistency of all used parser functions in the current article.
	 * The following conditions must be met:
	 *
	 * Groups:
	 * 	- belong to Category:ACL/Group
	 *  - Namespace must be ACL
	 *  - must have members (users or groups)
	 *	- must have managers (users or groups)
	 *
	 * Predefined Rights:
	 * 	- belong to Category:ACL/Right
	 *  - Namespace must be ACL
	 *	- must have managers (users or groups)
	 *  - must have inline or predefined rights
	 *
	 * Security Descriptors:
	 * 	- belong to Category:ACL/ACL
	 *  - Namespace must be ACL
	 *	- must have managers (users or groups)
	 *  - must have inline or predefined rights
	 *  - a namespace can only be protected if it is not member of $haclgUnprotectableNamespaces
	 *
	 * Whitelist:
	 *  - must have a list of pages
	 *
	 * As side-effect the type of the definition is determined and stored in
	 * $this->mType. The possibles values are 'group', 'right', 'sd', 'whitelist'
	 * and 'invalid'.
	 *
	 * @return array(string)|bool
	 * 		An array of error messages of <true>, if the parser functions are
	 * 		consistent.
	 *
	 */
	private function checkConsistency() {
		global $haclgContLang;
		$msg = array();

		// Get the direct parent categories of the article
		$cats = self::$mInstance->mTitle->getParentCategories();

		$gCat = $haclgContLang->getCategory(HACLLanguage::CAT_GROUP);
		$rCat = $haclgContLang->getCategory(HACLLanguage::CAT_RIGHT);
		$sdCat = $haclgContLang->getCategory(HACLLanguage::CAT_SECURITY_DESCRIPTOR);
		$isGroup = array_key_exists($gCat, $cats);
		$isRight = array_key_exists($rCat, $cats);
		$isSD    = array_key_exists($sdCat, $cats);
		$isWhitelist = $this->mTitle->getFullText() == $haclgContLang->getWhitelist();

		// Page must belong only to one category
		$belongsToCat = array();
		if ($isGroup) {
			$belongsToCat[] = $gCat;
			$this->mType = 'group';
		}
		if ($isRight) {
			$belongsToCat[] = $rCat;
			$this->mType = 'right';
		}
		if ($isSD) {
			$belongsToCat[] = $sdCat;
			$this->mType = 'sd';
		}
		if ($isWhitelist) {
			$this->mType = 'whitelist';
		}

		if (count($belongsToCat) > 1 ||
			(count($belongsToCat) == 1 && $isWhitelist) ) {
			$msg[] = wfMsgForContent('hacl_too_many_categories',
			implode(', ', $belongsToCat));
			$this->mType = 'invalid';
		}

		// The namespace must be ACL
		if ($this->mTitle->getNamespace() != HACL_NS_ACL) {
			$msg[] = wfMsgForContent('hacl_wrong_namespace');
		}

		// Check if the definition of a group is complete and valid
		if ($isGroup) {
			// check for members
			if (count($this->mGroupMembers) == 0 &&
			    count($this->mUserMembers) == 0 &&
			    count($this->mDynamicMemberQueries) == 0) {
				$msg[] = wfMsgForContent('hacl_group_must_have_members');
			}
			// check for managers
			if (count($this->mGroupManagerGroups) == 0 &&
			count($this->mGroupManagerUsers) == 0) {
				$msg[] = wfMsgForContent('hacl_group_must_have_managers');
			}
			// check if the group is overloaded
			$groupName = $this->mTitle->getText();
			global $haclgContLang;
			$gWOPre = "";
			$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP)."/";
			if (strpos($groupName, $prefix) === 0) {
				$gWOPre = substr($groupName, strlen($prefix));
			}
				
			if (HACLGroup::isOverloaded($groupName) || HACLGroup::isOverloaded($gWOPre)) {
				$msg[] = wfMsgForContent("hacl_group_overloaded", $groupName);
			}
		}

		// Check if the definition of a right or security descriptor is complete and valid
		if ($isRight || $isSD) {
			// check for inline or predefined rights
			if (count($this->mInlineRights) == 0 &&
				count($this->mPredefinedRights) == 0 &&
				count($this->mPropertyRights) == 0) {
				$msg[] = wfMsgForContent('hacl_right_must_have_rights');
			}
			// check for managers
			if (count($this->mRightManagerGroups) == 0 &&
				count($this->mRightManagerUsers) == 0) {
				$msg[] = wfMsgForContent('hacl_right_must_have_managers');
			}
		}

		// Check if the definition of a whitelist is complete and valid
		if ($isWhitelist) {
			// check for whitelist
			if (count($this->mWhitelist) == 0) {
				$msg[] = wfMsgForContent('hacl_whitelist_must_have_pages');
			}
		}
		if ($this->mType != 'invalid') {
			// Check for invalid parser functions
			$ivpf = $this->findInvalidParserFunctions($this->mType);
			$msg = array_merge($msg, $ivpf);
		}

		if (!$isRight && !$isSD && !$isGroup && !$isWhitelist) {
			// This is no whitelist, right, SD or group, but parser functions
			// are applied

			// Are functions for groups present?
			if (count($this->mGroupManagerGroups) > 0 ||
			count($this->mGroupManagerUsers) > 0 ||
			count($this->mUserMembers) > 0 ||
			count($this->mGroupMembers) > 0) {
				$msg[] = wfMsgForContent('hacl_add_to_group_cat');
			}
			// Are functions for rights/SDs present?
			if (count($this->mRightManagerGroups) > 0 ||
			count($this->mRightManagerUsers) > 0 ||
			count($this->mInlineRights) > 0 ||
			count($this->mPropertyRights) > 0 ||
			count($this->mPredefinedRights) > 0) {
				$msg[] = wfMsgForContent('hacl_add_to_right_cat');
			}

			// Are functions for a whitelist present?
			if (count($this->mWhitelist) > 0) {
				$msg[] = wfMsgForContent('hacl_add_to_whitelist');
			}

		}

		// a namespace can only be protected if it is not member of
		// $haclgUnprotectableNamespaces
		global $haclgUnprotectableNamespaces;
		if ($isSD) {
			$sdName = self::$mInstance->mTitle->getFullText();
			list($pe, $peType) = HACLSecurityDescriptor::nameOfPE($sdName);
			if ($peType == HACLSecurityDescriptor::PET_NAMESPACE) {
				if (in_array($pe, $haclgUnprotectableNamespaces)) {
					// This namespace can not be protected
					$msg[] = wfMsgForContent('hacl_unprotectable_namespace');
				}
			}
		}

		if (count($msg) == 0 && !$this->mDefinitionValid) {
			$msg[] = wfMsgForContent('hacl_errors_in_definition');
		}

		return count($msg) == 0 ? true : $msg;

	}

	/**
	 * Checks if invalid parser functions were used in groups, rights, security
	 * descriptors or whitelists.
	 *
	 * @param string $type
	 * 		One of 'group', 'right', 'sd' or 'whitelist'
	 *
	 * @return array(string)
	 * 		An array of error messages. (May be empty.)
	 */
	private function findInvalidParserFunctions($type) {
		$msg = array();
		global $haclgContLang;

		if (count($this->mInlineRights) > 0) {
			if ($type == 'group' || $type == 'whitelist') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_ACCESS));
			}
		}
		if (count($this->mPropertyRights) > 0) {
			if ($type == 'group' || $type == 'whitelist') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_PROPERTY_ACCESS));
			}
		}
		if (count($this->mPredefinedRights) > 0) {
			if ($type == 'group' || $type == 'whitelist') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_PREDEFINED_RIGHT));
			}
		}
		if (count($this->mWhitelist) > 0) {
			if ($type == 'group' || $type == 'sd' || $type == 'right') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_WHITELIST));
			}
		}
		if (count($this->mRightManagerGroups) > 0 ||
			count($this->mRightManagerUsers) > 0) {
			if ($type == 'group' || $type == 'whitelist') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_RIGHTS));
			}
		}

		if (count($this->mGroupManagerGroups) > 0 ||
			count($this->mGroupManagerUsers) > 0) {
			if ($type == 'right' || $type == 'sd' || $type == 'whitelist') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_MANAGE_GROUP));
			}
		}
		if (count($this->mUserMembers) > 0 ||
			count($this->mGroupMembers) > 0) {
			if ($type == 'right' || $type == 'sd' || $type == 'whitelist') {
				$msg[] = wfMsgForContent("hacl_invalid_parser_function",
					$haclgContLang->getParserFunction(HACLLanguage::PF_MEMBER));
			}
		}
		return $msg;
	}

	/**
	 * Returns the parser function parameters that were passed to the parser-function
	 * callback.
	 *
	 * @param array(mixed) $args
	 * 		Arguments of a parser function callback
	 * @return array(string=>string)
	 * 		Array of argument names and their values.
	 */
	private function getParameters($args) {
		$parameters = array();
		$parser = $args[0];
		
		if (is_string($args[1])) {
			// Arguments are given as strings
			for ($i = 1, $len = count($args); $i < $len; ++$i) {
				$arg = $args[$i];
				if (preg_match('/^\s*(.*?)\s*=\s*(.*?)\s*$/', $arg, $p) == 1) {
					$parameters[strtolower($p[1])] = $p[2];
				}
			}
			return $parameters;
		}
		
		// Arguments are given as preprocessor nodes and possibly string
		$frame = $args[1];
		$pfParameters = $args[2];
		
		foreach ($pfParameters as $p) {
			if (is_string($p)) {
				if (preg_match('/^\s*(.*?)\s*=\s*(.*?)\s*$/', $p, $matches) == 1) {
					$parameters[strtolower($matches[1])] = $matches[2];
				}
			} else if ($p instanceof PPNode_DOM) {
				$children = $p->getChildren();
				// three children expected: parameter name, =, value
				if ($children->getLength() !== 3) {
					// unexpected number of children
					continue;
				}
				$paramName = trim($children->item(0)->getFirstChild()->__toString());
				$values = array();
				$valueNode = $children->item(2);
				// The value may contain names of groups and users, but also
				// templates and parser functions
				$parts = $valueNode->getChildren();
				$length = $parts->getLength();
				for ($i = 0; $i < $length; ++$i) {
					$child = $parts->item($i);
					// Get the type of the child node. Possible values are
					// #text and template 
					$name = $child->getName();
					if ($name === '#text') {
						$value = $child->__toString();
					} else if ($name === 'template') {
						$text = $frame->expand($child, PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES);
						if (strpos($text, "{{#ask") === false
						    && strpos($text, "{{#sparql") === false) {
							// normal templates have to be expanded
							$text = $frame->expand($child); 	
						}
						$value = $text;
					}
					if (!empty($value)) {
						$values[] = $value;
					}
				}
				$parameters[$paramName] = count($values) == 1 
											? $values[0] : $values;
			}
		}

		return $parameters;
	}

	/**
	 * This method handles the parameter "assignedTo" of the parser functions
	 * #access, #property access, #manage rights and #manage groups.
	 * If $isAssignedTo is false, the parameter "members" for parser function
	 * #members is handled. The values have the same format as for "assigned to".
	 *
	 * @param array(string=>string) $params
	 * 		Array of argument names and their values. These are the arguments
	 * 		that were passed to the parser function as returned by the method
	 * 		getParameters().
	 *
	 * @param bool $isAssignedTo
	 * 		true  => parse the parameter "assignedTo"
	 * 		false => parse the parameter "members"
	 * @return array(users:array(string), groups:array(string),
	 * 				 dynamic assignees: array(string), 
	 *               error messages:array(string), warnings: array(string))
	 */
	private function assignedTo($params, $isAssignedTo = true) {
		global $wgContLang, $haclgContLang;
		$userNs = $wgContLang->getNsText(NS_USER);
		$errMsgs = array();
		$warnings = array();
		$users = array();
		$groups = array();
		$dynamicAssignees = array();

		$assignedToPN = $isAssignedTo
						? $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_ASSIGNED_TO)
						: $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_MEMBERS);
		$assignedToPN = strtolower($assignedToPN);						
		if (!array_key_exists($assignedToPN, $params)) {
			// The parameter "assigned to" is missing.
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter', $assignedToPN);
			return array($users, $groups, $dynamicAssignees, $errMsgs, $warnings);
		}

		$etc = haclfDisableTitlePatch();

		$assignedTo = $params[$assignedToPN];
		if (is_array(($assignedTo))) {
			// The array might contain queries for dynamic assignees
			// => sort them out
			foreach ($assignedTo as $k => $at) {
				if (strpos($at, '{{#ask') === 0 
				    || strpos($at, '{{#sparql') === 0) {
				   	$dynamicAssignees[] = $at;
				   	unset($assignedTo[$k]);
				}
			}
			$assignedTo = implode('', $assignedTo);
		}
		
		$assignedTo = explode(',', $assignedTo);
		// read assigned users and groups
		foreach ($assignedTo as $assignee) {
			$assignee = trim($assignee);
			if (empty($assignee)) {
				continue;
			}
			if (strpos($assignee, $userNs) === 0 ||
				$assignee == '*' || $assignee == '#') {
				// user found
				if ($assignee != '*' && $assignee != '#') {
					$user = substr($assignee, strlen($userNs)+1);
					// Check if the user exists
					if (User::idFromName($user) == 0) {
						// User does not exist => add a warning
						$warnings[] = wfMsgForContent("hacl_unknown_user", $user);
					} else {
						$users[] = $user;
					}
				} else {
					$users[] = $assignee;
				}
			} else {
				// group found
				// Check if the group exists
				if (HACLGroup::idForGroup($assignee) == null) {
					// Group does not exist => add a warning 
					$warnings[] = wfMsgForContent("hacl_unknown_group", $assignee);
				} else {
					// Check if the group is overloaded i.e. if there are several
					// definitions for the same group name.
					global $haclgContLang;
					$assWOPre = "";
					$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP)."/";
					if (strpos($assignee, $prefix) === 0) {
						$assWOPre = substr($assignee, strlen($prefix));
					}
					
					if (HACLGroup::isOverloaded($assignee) || HACLGroup::isOverloaded($assWOPre)) {
						$warnings[] = wfMsgForContent("hacl_group_overloaded", $assignee);
					}
					$groups[] = $assignee;
				}
			}
		}
		if (count($users) == 0 && count($groups) == 0 && count($dynamicAssignees) == 0) {
			// No users/groups specified at all => add error message
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter_values', $assignedToPN);
		}
		haclfRestoreTitlePatch($etc);

		return array($users, $groups, $dynamicAssignees, $errMsgs, $warnings);
	}

	/**
	 * This method handles the parameter "actions" of the parser functions
	 * #access and #property access.
	 *
	 * @param array(string=>string) $params
	 * 		Array of argument names and their values. These are the arguments
	 * 		that were passed to the parser function as returned by the method
	 * 		getParameters().
	 *
	 * @return array(actions:array(string), error messages:array(string))
	 */
	private function actions($params) {
		global $wgContLang, $haclgContLang;
		$errMsgs = array();
		$actions = array();

		$actionsPN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_ACTIONS);
		$actionsPN = strtolower($actionsPN);
		if (!array_key_exists($actionsPN, $params)) {
			// The parameter "actions" is missing.
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter', $actionsPN);
			return array($actions, $errMsgs);
		}

		$actions = $params[$actionsPN];
		$actions = explode(',', $actions);
		// trim actions
		$possibleActions = $haclgContLang->getActionNames();
		for ($i = 0; $i < count($actions); ++$i) {
			$actions[$i] = trim($actions[$i]);
			// Check if the action is valid
			if (array_search($actions[$i], $possibleActions) === false) {
				$errMsgs[] = wfMsgForContent('hacl_invalid_action', $actions[$i]);
			}
		}
		if (count($actions) == 0) {
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter_values', $actionsPN);
		}

		return array($actions, $errMsgs);
	}

	/**
	 * This method handles the parameter "rights" of the parser function
	 * #predefined right.
	 *
	 * @param array(string=>string) $params
	 * 		Array of argument names and their values. These are the arguments
	 * 		that were passed to the parser function as returned by the method
	 * 		getParameters().
	 *
	 * @return array(rights:array(string), error messages:array(string), warnings:array(string))
	 */
	private function rights($params) {
		global $wgContLang, $haclgContLang;
		$errMsgs = array();
		$warnings = array();
		$rights = array();

		$rightsPN = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_RIGHTS);
		$rightsPN = strtolower($rightsPN);
		if (!array_key_exists($rightsPN, $params)) {
			// The parameter "rights" is missing.
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter', $rightsPN);
			return array($rights, $errMsgs);
		}

		$rights = $params[$rightsPN];
		$rights = explode(',', $rights);
		// trim rights
		for ($i = 0; $i < count($rights); ++$i) {
			$rights[$i] = trim($rights[$i]);
			// Check if the right exists
			if (HACLSecurityDescriptor::idForSD($rights[$i]) == 0) {
				// The right does not exist
				$warnings[] = wfMsgForContent('hacl_invalid_predefined_right', $rights[$i]);
				unset($rights[$i]);
			}
		}
		if (count($rights) == 0) {
			$errMsgs[] = wfMsgForContent('hacl_missing_parameter_values', $rightsPN);
		} else {
			// Create new indices in the array (in case invalid rights have been removed)
			$rights = array_values($rights);
		}

		return array($rights, $errMsgs, $warnings);
	}

	/**
	 * Converts an array of language dependent action names as they are used in
	 * rights to a combined (ORed) action ID.
	 *
	 * @param array(string) $actionNames
	 * 		Language dependent action names like 'read' or 'lesen'.
	 *
	 * @return int
	 * 		An action ID that is the ORed combination of action IDs they are
	 *     defined as constants in the class HACLRight.
	 */
	private function actionNamesToIDs($actionNames) {
		global $haclgContLang;
		$actions = $haclgContLang->getActionNames();

		$actionID = 0;
		foreach ($actionNames as $an) {
			$id = array_search($an, $actions);
			$actionID |= ($id === false) ? 0 : $id;
		}
		return $actionID;
	}

	/**
	 * Formats the wikitext for displaying assignees of a right or members of a
	 * group.
	 *
	 * @param array(string) $users
	 * 		Array of user names (without namespace "User"). May be empty.
	 * @param array(string) $groups
	 * 		Array of group names (without namespace "ACL"). May be emtpy.
	 * @param array('users' => array(string), 
	 *              'groups' => array(string),
	 *              'queries' => array(string)) $dynamicAssignees
	 * 		Array of dynamic assignees with users or groups. May be <null>.
	 * @param bool $isAssignedTo
	 * 		true  => output for "assignedTo"
	 * 		false => output for "members"
	 * @return string
	 * 		A formatted wikitext with users and groups
	 */
	private function showAssignees($users, $groups, $dynamicAssignees, $isAssignedTo = true) {
		global $wgContLang;
		$userNS = $wgContLang->getNsText(NS_USER);
		$aclNS  = $wgContLang->getNsText(HACL_NS_ACL);
		
		$text = "";
		if (count($users) > 0) {

			$text .= $isAssignedTo
				? ':;'.wfMsgForContent('hacl_assigned_user')
				: ':;'.wfMsgForContent('hacl_user_member');
			$first = true;
			foreach ($users as $u) {
				if (!$first) {
					$text .= ', ';
				} else {
					$first = false;
				}
				if ($u == '*') {
					$text .= wfMsgForContent('hacl_anonymous_users');
				} else if ($u == '#') {
					$text .= wfMsgForContent('hacl_registered_users');
				} else {
					$text .= "[[$userNS:$u|$u]]";
				}
			}
			$text .= "\n";
		}
		if (count($groups) > 0) {
			$text .= $isAssignedTo
				? ':;'.wfMsgForContent('hacl_assigned_groups')
				: ':;'.wfMsgForContent('hacl_group_member');
			$first = true;
			foreach ($groups as $g) {
				if (!$first) {
					$text .= ', ';
				} else {
					$first = false;
				}
				$text .= "[[$aclNS:$g|$g]]";
			}
			$text .= "\n";

		}
		
		$msgDynTitle = $isAssignedTo 
						? 'hacl_dynamic_assignees' : 'hacl_dynamic_members';
		$msgDynQueries = $isAssignedTo 
						? 'hacl_dyn_assigned_queries' : 'hacl_dyn_member_queries';
		$msgDynUsers  = $isAssignedTo 
						? 'hacl_dyn_assigned_users' : 'hacl_dyn_member_users';
		$msgDynGroups = $isAssignedTo 
						? 'hacl_dyn_assigned_groups' : 'hacl_dyn_member_groups';
		if (!is_null($dynamicAssignees) 
		    && array_key_exists('queries', $dynamicAssignees)
		    && count($dynamicAssignees['queries']) > 0) {
			$text .= ':;'.wfMsgForContent($msgDynTitle).":\n";
			$text .= '::;'.wfMsgForContent($msgDynQueries)."\n";
			foreach ($dynamicAssignees['queries'] as $q) {
				global $wgParser;
				$text .= $wgParser->insertStripItem("::*<code>$q</code>\n", $wgParser->mStripState);
			}
			if (array_key_exists('users', $dynamicAssignees)
				&& count($dynamicAssignees['users']) > 0) {
				$text .= '::;'.wfMsgForContent($msgDynUsers)."\n";
				foreach ($dynamicAssignees['users'] as $u) {
					$text .= "::* [[$userNS:$u|$u]]\n";
				}
			}
			if (array_key_exists('groups', $dynamicAssignees)
				&& count($dynamicAssignees['groups']) > 0) {
				$text .= '::;'.wfMsgForContent($msgDynGroups)."\n";
				foreach ($dynamicAssignees['groups'] as $g) {
					$text .= "::* [[$aclNS:$g|$g]]\n";
				}
			}
			
		}
		return $text;
	}

	/**
	 * Formats the wikitext for displaying the description of a right.
	 *
	 * @param string $description
	 * 		A description. Empty descriptions are allowed.
	 *
	 * @return string
	 * 		A formatted wikitext with the description.
	 */
	private function showDescription($description) {
		$text = "";
		if (!empty($description)) {
			$text .= ":;".wfMsgForContent('hacl_description').$description;
		}
		return $text;
	}

	/**
	 * Formats the wikitext for displaying the error messages of a parser function.
	 *
	 * @param array(string) $messages
	 * 		An array of error messages. May be empty.
	 *
	 * @return string
	 * 		A formatted wikitext with all error messages.
	 */
	private function showErrors($messages) {
		$text = "";
		if (!empty($messages)) {
			$text .= "\n:;".wfMsgForContent('hacl_error').
			wfMsgForContent('hacl_definitions_will_not_be_saved').
                "\n";
			$text .= ":*".implode("\n:*", $messages);
		}
		return $text;
	}
	
	/**
	 * Formats the wikitext for displaying the warnings of a parser function.
	 *
	 * @param array(string) $messages
	 * 		An array of warnings. May be empty.
	 *
	 * @return string
	 * 		A formatted wikitext with all warnings.
	 */
	private function showWarnings($messages) {
		$text = "";
		if (!empty($messages)) {
			$text .= "\n:;".wfMsgForContent('hacl_warning').
			wfMsgForContent('hacl_will_not_work_as_expected').
                "\n";
			$text .= ":*".implode("\n:*", $messages);
		}
		return $text;
		
	}

	/**
	 * Formats the wikitext for displaying predefined rights.
	 *
	 * @param array(string) $rights
	 * 		An array of rights. May be empty.
	 * @param bool $addACLNS
	 * 		If <true>, the ACL namespace is added to the pages if it is missing.
	 *
	 * @return string
	 * 		A formatted wikitext with all rights.
	 */
	private function showRights($rights, $addACLNS = true) {
		$text = "";
		global $wgContLang;
		$aclNS = $wgContLang->getNsText(HACL_NS_ACL);

		foreach ($rights as $r) {
			// Rights can be given without the namespace "ACL". However, the
			// right should be linked correctly. So if the namespace is missing,
			// the link is adapted.
			if (strpos($r, $aclNS) === false && $addACLNS) {
				$r = "$aclNS:$r|$r";
			}
			$text .= '*[['.$r."]]\n";
		}
		return $text;
	}

	/**
	 * Moves the article with the name $from to the location $to.
	 *
	 * @param string $from
	 * 		Original name of the article.
	 * @param string $to
	 * 		New name of the article
	 *
	 */
	private static function move($from, $to) {
		$etc = haclfDisableTitlePatch();
		$from = Title::newFromText($from);
		$to = Title::newFromText($to);
		haclfRestoreTitlePatch($etc);

		$fromArticle = new Article($from);
		$text = $fromArticle->getContent();

		$toArticle = new Article($to);
		$toArticle->doEdit($text,"");

		$fromArticle->doDelete("");
	}

	/**
	 * Creates a fingerprint from a parser function name and its parameters.
	 *
	 * @param string $functionName
	 * 		Name of the parser function
	 * @param array(string=>string|array) $params
	 * 		Parameters of the parser function
	 * @return string
	 * 		The fingerprint
	 */
	private static function makeFingerprint($functionName, $params) {
		$fingerprint = "$functionName";
		foreach ($params as $k => $v) {
			if (is_array($v)) {
				$v = implode('|', $v);
			}
			$fingerprint .= $k.$v;
		}
		return $fingerprint;
	}
}
