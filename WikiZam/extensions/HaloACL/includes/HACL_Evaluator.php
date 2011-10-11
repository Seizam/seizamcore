<?php
/**
 * @file
 * @ingroup HaloACL
 */

/*  Copyright 2009, ontoprise GmbH
*   This file is part of the HaloACL-Extension.
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
 * This is the main class for the evaluation of user rights for a protected object.
 * It implements the function "userCan" that is called from MW for granting or 
 * denying access to articles.
 * 
 * @author Thomas Schweitzer
 * Date: 13.03.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * 
 * 
 * @author Thomas Schweitzer
 * 
 */
class HACLEvaluator {
	
	//--- Constants ---
	
	//---- Constants for the modes of the evaluator ----
	const NORMAL = 0;
	const DENY_DIFF = 1;
	const ALLOW_PROPERTY_READ = 2;
	
	//--- Private fields ---
	
	// The current mode of the evaluator
	static $mMode = HACLEvaluator::NORMAL;
	
	// Saving protected properties is allowed if the value did not change
	static $mSavePropertiesAllowed = false;
	
	// String with logging information
	static $mLog = "";
	
	// Is logging HaloACL's activities enabled?
	static $mLogEnabled = false;
	
	/**
	 * Constructor for  HACLEvaluator
	 *
	 * @param type $param
	 * 		Name of the notification
	 */		
	function __construct() {
	}
	

	//--- getter/setter ---
	
	//--- Public methods ---
	
	
	/**
	 * This function is called from the userCan-hook of MW. This method decides
	 * if the article for the given title can be accessed.
	 * See  further information at: http://www.mediawiki.org/wiki/Manual:Hooks/userCan  
	 *
	 * @param Title $title
	 * 		The title object for the article that will be accessed.
	 * @param User $user
	 * 		Reference to the current user.
	 * @param string $action
	 * 		Action concerning the title in question
	 * @param boolean $result
	 * 		Reference to the result propagated along the chain of hooks.
	 * 
	 * @return boolean
	 * 		true
	 */
	public static function userCan($title, $user, $action, &$result) {
		global $wgRequest;

		self::startLog($title, $user, $action);
		
//		echo $title->getFullText().":".$action." (Request:".$wgRequest->getText('action').")\n";

		if ($title == null) {
// let other hooks decide			$result = true;
			self::finishLog("Title is <null>.", $result, true);
			return true;
		}
		$etc = haclfDisableTitlePatch();

		// Check if property access is requested.
		global $haclgProtectProperties;
		if ($haclgProtectProperties) {
			self::log("Properties are protected.");
			$r = self::checkPropertyAccess($title, $user, $action);
			if ($r !== -1) {
				haclfRestoreTitlePatch($etc);
				$result = $r;
				self::finishLog("Right for property evaluated.", $result, true);
				if (self::$mMode == self::DENY_DIFF) {
					// Can not return an error msg because of MW-bug: 
					// http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Risk_of_returning_a_string_value
					//$r = wfMsg('hacl_ad_access_denied');
					$r = false;
				} 
				return $r;
			}
		}
		
		//Special handling of action "wysiwyg". This is passed as 
		// "action=edit&mode=wysiwyg"
		if ($action == 'edit') {
			$action = $wgRequest->getVal('mode', 'edit');
			if ($action != 'edit') {
				self::log("Action updated to: $action");
			}
		}
		
		$actionID = HACLRight::getActionID($action);
		if ($actionID == 0) {
			// unknown action => nothing can be said about this
			haclfRestoreTitlePatch($etc);
			self::finishLog("Unknown action.", true, true);
			return true;
		}
		
		// no access to the page "Permission denied" is allowed.
		// together with the TitlePatch which returns this page, this leads
		// to MediaWiki's "Permission error"
		global $haclgContLang;
		if ($title->getText() == $haclgContLang->getPermissionDeniedPage()) {
//			$r = $actionID == HACLRight::READ;
			$r = false;
			haclfRestoreTitlePatch($etc);
			self::finishLog('Special handling of "Permission denied" page.', $r, $r);
			$result = $r;
			return $r;
	    }
		
		$articleID = (int) $title->getArticleID();
		if ($articleID == 0) {
			$articleID = haclfArticleID($title->getFullText());
		}
		$userID = $user->getId();
		
		if ($articleID == 0) {
			// The article does not exist yet
			if ($actionID == HACLRight::CREATE || $actionID == HACLRight::EDIT) {
				self::log('Article does not exist yet. Checking right to create.');
				
				// Check right for creation of default SD template. Users
				// can only create their own template. Sysops and bureaucrats
				// can create them for everyone.
				list ($r, $sd) = HACLDefaultSD::userCanModify($title, $user);
				if ($sd) {
					haclfRestoreTitlePatch($etc);
					$result = $r;
					self::finishLog("Checked right for creating the default user template.", $r, $r);
					return $r;
				}
										
				// Check if the user is allowed to create an SD
				$allowed = self::checkSDCreation($title, $user);
				if ($allowed == false) {
					haclfRestoreTitlePatch($etc);
					$result = false;
					self::finishLog("Checked right for creating a security descriptor.", $result, false);
					return false;
				}
				
				// Check if page is a subpage whose parent prohibits create or
				// edit
				if ($title->isSubpage() && $title->getNamespace() != HACL_NS_ACL) {
					$parentTitleText = $title->getBaseText();
					$parentTitle = Title::newFromText($parentTitleText, $title->getNamespace());
					return self::userCan($parentTitle, $user, $action, $result);
				}
				
			}

			// Check if the article belongs to a namespace with an SD
		    list($r, $sd) = self::checkNamespaceRight($title, $userID, $actionID);
			haclfRestoreTitlePatch($etc);
			if ($r === false) {
				$result = $r;
				$ns = $title->getNsText();
				// Can not return an error msg because of MW-bug: 
				// http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Risk_of_returning_a_string_value
				//$r = wfMsg('hacl_ad_create_namespace', $ns);
			}
			self::finishLog("Checked if the user is allowed to create an article with in the given namespace.", $r, $r);
		    return $r;
		}
		
		// Check rights for managing ACLs
		list($r, $sd) = self::checkACLManager($title, $user, $actionID);
		if ($sd) {
			// User tries to access an ACL article
			haclfRestoreTitlePatch($etc);
			if ($r === false) {
				$result = $r;
			}
			self::finishLog("Checked if user can modify an access control entity (SD, right or group).", $r, $r);
			return $r;
		}
		
		$submit = $wgRequest->getText('action');
		$submit = $submit == 'submit'; 
		$savePage = $wgRequest->getCheck('wpSave');
		$edit = $wgRequest->getText('action');
		$edit = $edit == 'edit'; 
		$sameTitle = $wgRequest->getText('title');
		$sameTitle = str_replace(' ', '_', $sameTitle) == str_replace(' ', '_', $title->getFullText());
		// Check if the article contains protected properties that avert
		// editing the article
		// There is no need to check for protected properties if an edited article 
		// is submitted. An article with protected properties may be saved if their
		// values are not changed. This is checked in method "onEditFilter" when
		// the article is about to be saved.
		if (($submit && !$savePage) || ($edit && $sameTitle)) {
			// First condition:
			// The article is submitted but not saved (preview). This causes, that
			// the wikitext will be displayed. 
			// Second condition:
			// The requested article is edited. Nevertheless, the passed $action
			// might be "read" as MW tries to show the articles source
			// => prohibit this, if it contains properties without read-access
			$allowed = self::checkProperties($title, $userID, HACLRight::EDIT);
		} else {
			$allowed = $savePage || self::checkProperties($title, $userID, $actionID);
		}
		if (!$allowed) {
			haclfRestoreTitlePatch($etc);
			$result = false;
			self::finishLog("The article contains protected properties.", $result, false);
			// Can not return an error msg because of MW-bug: 
			// http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Risk_of_returning_a_string_value
			//			return $edit ? wfMsg('hacl_ad_access_denied') : false;
			return false;
		}
		
		// Check if there is a security descriptor for the article.
		$hasSD = HACLSecurityDescriptor::getSDForPE($articleID, HACLSecurityDescriptor::PET_PAGE) !== false;
		
		// first check page rights
		if ($hasSD) {
			self::log("The article is protected with a security descriptor.");
			
			$r = self::hasRight($articleID, HACLSecurityDescriptor::PET_PAGE,
			                    $userID, $actionID);
			                    
			if (!$r && $submit && $sameTitle) {
				// The article is submitted but the right 'edit' is not granted 
				// => check if the article has one of the rights 'formedit',
				//    'wysiwyg' or 'annotate'
				$r = self::checkAllowSubmit($articleID, HACLSecurityDescriptor::PET_PAGE,
				                            $userID, $actionID);
				
			}
			                    
			if ($r) {
				haclfRestoreTitlePatch($etc);
// let other hooks decide				$result = true;
				self::finishLog("Found an appropriate access right.", $result, true);
				return true;
			}
		}
		
		// check namespace rights
		list($r, $sd) = self::checkNamespaceRight($title, $userID, $actionID);
		$hasSD = $hasSD ? true : $sd;
		if (!$r && $hasSD && $submit && $sameTitle) {
			// The article is submitted but the right 'edit' is not granted 
			// => check if the article has one of the rights 'formedit',
			//    'wysiwyg' or 'annotate'
			$r = self::checkAllowSubmit($title->getNamespace(), 
										HACLSecurityDescriptor::PET_NAMESPACE,
			                            $userID, $actionID);
		}
		if ($sd && $r) {
			haclfRestoreTitlePatch($etc);
// let other hooks decide			$result = true;
			self::finishLog("Action allowed by a namespace right.", $result, true);
			return true;
		}
	
		// check category rights
		list($r, $sd) = self::hasCategoryRight($title->getFullText(), $userID, $actionID);
		$hasSD = $hasSD ? true : $sd;
		if (!$r && $hasSD && $submit && $sameTitle) {
			// The article is submitted but the right 'edit' is not granted 
			// => check if the article has one of the rights 'formedit',
			//    'wysiwyg' or 'annotate'
			$r = self::checkAllowSubmit($title->getFullText(), 
										HACLSecurityDescriptor::PET_CATEGORY,
			                            $userID, $actionID);
		}
		
		if ($sd && $r) {
			haclfRestoreTitlePatch($etc);
// let other hooks decide			$result = true;
			self::finishLog("Action allowed by a category right.", $result, true);
			return true;
		}

		// Check if this is a subpage that inherits rights from its parent
		if ($title->isSubpage()) {
			$parentTitleText = $title->getBaseText();
			$parentTitle = Title::newFromText($parentTitleText, $title->getNamespace());
			return self::userCan($parentTitle, $user, $action, $result);	
		}
		
		// check the whitelist
		if (HACLWhitelist::isInWhitelist($articleID)) {
			$r = $actionID == HACLRight::READ;
			// articles in the whitelist can be read
			haclfRestoreTitlePatch($etc);
			if ($r === false) {
				$result = $r;
				// Can not return an error msg because of MW-bug: 
				// http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Risk_of_returning_a_string_value
				//$r = wfMsg('hacl_ad_access_denied');
			}
			self::finishLog("Read access was determined by the Whitelist.", $result, true);
			return $r;
		}
		
		if (!$hasSD) {
			global $haclgOpenWikiAccess;
			// Articles with no SD are not protected if $haclgOpenWikiAccess is
			// true. Otherwise access is denied
			haclfRestoreTitlePatch($etc);
			if ($haclgOpenWikiAccess) {
				// Wiki is open for HaloACL but other extensions can still 
				// prohibit access.
				self::finishLog("No security descriptor for article found. HaloACL is configured to Open Wiki Access.", true, true);
// let other hooks decide				$result = true;
				return true;
			}
		}
		
		// permission denied
		haclfRestoreTitlePatch($etc);
		self::finishLog("No matching right for article found.", false, false);
		
		$result = false;
		// Can not return an error msg because of MW-bug: 
		// http://www.mediawiki.org/wiki/Manual:Hooks/userCan#Risk_of_returning_a_string_value
		//return wfMsg('hacl_ad_access_denied');
		
		return false;
	}

	
	/**
	 * Checks, if the given user has the right to perform the given action on
	 * the given title. The hierarchy of categories is not considered here.
	 *
	 * @param int $titleID
	 * 		ID of the protected object (which is the namespace index if the type
	 * 		is PET_NAMESPACE)
	 * @param string $peType
	 * 		The type of the protection to check for the title. One of
	 * 		HACLSecurityDescriptor::PET_PAGE
	 * 		HACLSecurityDescriptor::PET_CATEGORY
	 * 		HACLSecurityDescriptor::PET_NAMESPACE
	 * 		HACLSecurityDescriptor::PET_PROPERTY
	 * @param int $userID
	 * 		ID of the user who wants to perform an action
	 * @param int $actionID
	 * 		The action, the user wants to perform. One of the constant defined
	 * 		in HACLRight: READ, FORMEDIT, WYSIWYG, EDIT, ANNOTATE, CREATE, MOVE and DELETE.
	 * @return bool
	 * 		<true>, if the user has the right to perform the action
	 * 		<false>, otherwise
	 */
	public static function hasRight($titleID, $type, $userID, $actionID) {
		// retrieve all appropriate rights from the database
		$rightIDs = HACLStorage::getDatabase()->getRights($titleID, $type, $actionID);
		
		// Check for all rights, if they are granted for the given user
		foreach ($rightIDs as $r) {
			$right = HACLRight::newFromID($r);
			if ($right->grantedForUser($userID)) {
				return true; 
			}
		}
		
		return false;
		
	}

	/**
	 * Checks, if the given user has the right to perform the given action on
	 * the given property. (This happens only if protection of semantic properties
	 * is enabled (see $haclgProtectProperties in HACL_Initialize.php))
	 *
	 * @param mixed(Title|int) $propertyTitle
	 * 		ID or title of the property whose rights are evaluated
	 * @param int $userID
	 * 		ID of the user who wants to perform an action
	 * @param int $actionID
	 * 		The action, the user wants to perform. One of the constant defined
	 * 		in HACLRight: READ, FORMEDIT, EDIT
	 * @return bool
	 *		<true>, if the user has the right to perform the action
	 * 		<false>, otherwise
	 */
	public static function hasPropertyRight($propertyTitle, $userID, $actionID) {
		global $haclgProtectProperties;
		if (!$haclgProtectProperties 
		    || $propertyTitle == null 
		    || (self::$mMode == self::ALLOW_PROPERTY_READ 
		        && $actionID == HACLRight::READ)) {
			// Protection of properties is disabled or property has no page.
			return true;
		}
		
		if ($propertyTitle instanceof Title) {
			$propertyTitle = $propertyTitle->getArticleID();
		}
		
		$hasSD = HACLSecurityDescriptor::getSDForPE($propertyTitle, HACLSecurityDescriptor::PET_PROPERTY) !== false;
			
		if (!$hasSD) {
			global $haclgOpenWikiAccess;
			// Properties with no SD are not protected if $haclgOpenWikiAccess is
			// true. Otherwise access is denied
			return $haclgOpenWikiAccess;
		}
		return self::hasRight($propertyTitle, 
							  HACLSecurityDescriptor::PET_PROPERTY,
		                      $userID, $actionID);
		
	}
	
	/** 
	 * This function is called, before an article is saved. 
	 * If protection of properties is switched on, it checks if the article contains
	 * properties that have been changed and for which the current user has no 
	 * access rights. In that case, saving the article is aborted and an error
	 * message is displayed.
	 * 
	 * @param EditPage $editor
	 * @param string $text
	 * @param $section
	 * @param string $error
	 * 		If a property is not accessible, this error message is modified and
	 * 		displayed on the editor page.
	 * 
	 * @return bool
	 * 		true
	 */ 
	 public static function onEditFilter($editor, $text, $section, &$error) {
		global $wgParser, $wgUser;
		$article = $editor->mArticle;
		$options = new ParserOptions;
	//	$options->setTidy( true );
		$options->enableLimitReport();
		self::$mMode = HACLEvaluator::ALLOW_PROPERTY_READ;
		$output = $wgParser->parse($article->preSaveTransform($text), 
		                           $article->mTitle, $options);
		self::$mMode = HACLEvaluator::NORMAL;
		                           
		$protectedProperties = "";
		$protectionActive = NULL;
		if (isset($output->mSMWData)) {
			foreach ($output->mSMWData->getProperties() as $name => $prop) {
				$wpv = $prop->getWikiPageValue();
				if (!$wpv) {
					// There's no page for the property
					continue;
				}
				$allowed = self::hasPropertyRight(
										$wpv->getTitle(), 
										$wgUser->getId(),
										HACLRight::FORMEDIT);
				if (!$allowed) {
					$store = smwfGetStore();
					if ($store instanceof HACLSMWStore) {
						$protectionActive = $store->setProtectionActive(false);
					}
					// Access to property is restricted
					if (!isset($oldPV)) {
						// Get all old properties of the page from the semantic store
						$oldPV = smwfGetStore()->getSemanticData($editor->mTitle);
					}
				    if (self::propertyValuesChanged($prop, $oldPV, $output->mSMWData)) {
						$protectedProperties .= "* $name\n";
				    }
				}
			}
		}
		if (empty($protectedProperties)) {
			// No properties are protected 
			// => switch off property protection for the rest of the saving process
			global $haclgProtectProperties;
			$haclgProtectProperties = false;
			self::$mSavePropertiesAllowed = true;
			return true;
		}
		
		if (!is_null($protectionActive)) {
			$store->setProtectionActive($protectionActive);
		}
		self::$mSavePropertiesAllowed = false;
		$error = wfMsgForContent('hacl_sp_cant_save_article', $protectedProperties);
		
		// Special handling for semantic forms
		if (defined('SF_VERSION')) {
			include_once('includes/SpecialPage.php');
			$spt = SpecialPage::getTitleFor('EditData');
			$url = $spt->getFullURL();
			$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			if (strpos($referer, $url) === 0) {
				// A semantic form was saved.
				// => abort with an error message
				global $wgOut;
				$wgOut->addWikiText($error);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * This method is called when the difference of two revisions of an article is
	 * about to be displayed.
	 * If one of the revisions contains a property that can not be read, the mode
	 * for the ACL evaluator is set accordingly for following calls to the userCan
	 * hook.
	 *
	 * @param DifferenceEngine $diffEngine
	 * @param Revision $oldRev
	 * @param Revision $newRev
	 * @return boolean true
	 */
	public static function onDiffViewHeader(DifferenceEngine $diffEngine, $oldRev, $newRev) {
		global $wgUser;
		
		$newText = $diffEngine->mNewtext;
		if (!isset($newText)) {
			$diffEngine->loadText();
		}
		$newText = $diffEngine->mNewtext;
		$oldText = $diffEngine->mOldtext;
	
		global $wgParser;
		$options = new ParserOptions;
		$output = $wgParser->parse($newText, $diffEngine->mTitle, $options);
	
		if (isset($output->mSMWData)) {
			foreach ($output->mSMWData->getProperties() as $name => $prop) {
				$prop = $prop->getWikiPageValue();
				if ($prop) {
					$prop = $prop->getTitle();
					if (!self::checkPropertyAccess($prop, $wgUser, "propertyread")) {
						HACLEvaluator::$mMode = HACLEvaluator::DENY_DIFF;
						return true;
					}
				}
			}
		}

		$output = $wgParser->parse($oldText, $diffEngine->mTitle, $options);
	
		if (isset($output->mSMWData)) {
			foreach ($output->mSMWData->getProperties() as $name => $prop) {
				$prop = $prop->getWikiPageValue();
				if ($prop) {
					$prop = $prop->getTitle();
					if (!self::checkPropertyAccess($prop, $wgUser, "propertyread")) {
						HACLEvaluator::$mMode = HACLEvaluator::DENY_DIFF;
						return true;
					}
				}
			}
		}
		
		return true;
	}	
	
	/**
	 * This function is called before the value of a property is printed into
	 * an article by the SMWParserExtension.
	 * It checks if the property or its value is protected. In this case the text
	 * is replaced.
	 * 
	 * @param SMWDataValue $propertyValue
	 * 		The value of this property will be printed.
	 * @param string $text
	 * 		The text that will appear in the article. It might be changed by this
	 * 		function.
	 */
	public static function onPropertyBeforeOutput(SMWDataValue &$propertyValue, &$text) {
		global $wgUser;
		
		$protected = false;
		$pt = $propertyValue->getProperty();
		$pt = $pt->getWikiPageValue()->getTitle();
		if (!self::hasPropertyRight($pt, $wgUser, HACLRight::READ)) {
			// The property is protected
			$protected = true;
		} else {
			if ($propertyValue instanceof SMWWikiPageValue) {
				$vt = $propertyValue->getTitle();
				wfRunHooks('userCan', array(&$vt, &$wgUser, "read", &$allowed));
				if ($allowed === false) {
					// The value of the property can not be read
					$protected = true;
				}
			}
		}
		if ($protected) {
			$text = "<nowiki>***</nowiki>";
			$errMsg = wfMsgForContent('hacl_protected_property_error');
			$propertyValue->addError($errMsg);
		}
		return true;
	}
	
	/**
	 * This hook function is called before a template or a transclusion is
	 * inserted by the parser. It checks if the $title has read access rights for
	 * the current user. If not, the template is skipped.
	 * 
	 * @param unknown_type $parser
	 * @param unknown_type $title
	 * @param unknown_type $skip
	 * @param unknown_type $id
	 */
	public static function onBeforeParserFetchTemplateAndtitle($parser, $title, $skip, $id) {
		// Check if the title is accessible (read) for the current user
		global $wgUser;
		$allowed = true;
		self::userCan($title, $wgUser, "read", $allowed);
		if ($allowed) {
			return true;
		}
		$skip = true;
		return true;
		// Template or page must not be included => return permission denied page.
//		global $haclgContLang;
//		$pdp = $haclgContLang->getPermissionDeniedPage();
//		$title = Title::newFromText($pdp);
//		
//		return true;
	}

	/**
	 * This function is called by semantic forms when it wants to decide if a
	 * page can be edited. It checks if the current user in $wgUser has the 
	 * right 'formedit' for the given $title.
	 * 
	 * @param bool $title
	 * 		The title of the article whose rights are checked.
	 * 
	 * @param bool $userCanEdit
	 * 		This parameter is set 'false' if the article is not editable and 'true'
	 * 		if it is.
	 */
	public static function onSfUserCanEditPage($title, &$userCanEdit) {
		global $wgUser;
		$allowed = true;
		self::userCan($title, $wgUser, "formedit", $allowed);
		$userCanEdit = $allowed;
		return true;
	}
	
	/**
	 * This method is important if the mode of the access control is 
	 * "closed wiki access" or if an SD for an instance of a protected category, 
	 * namespace or subpage is about to be created. 
	 * If the wiki access is open, articles without security
	 * descriptor have full access. If it is closed, nobody can access the article
	 * until a security descriptor is defined. Only the latest author of the article
	 * can do this. This method checks, if a security descriptor can be created.
	 * 
	 * If an article is an instance of a protected category, namespace or a subpage, 
	 * creating an SD for it is restricted. The modification rights of the category's,
	 * namespace's or parent page's SD are applied.
	 *
	 * @param Title $title
	 * 		Title of the article that will be created
	 * @param User $user
	 * 		User who wants to create the article
	 * @return bool|string
	 * 		<true>, if the user can create the security descriptor
	 * 		<false>, if not
	 * 		"n/a", if this method is not applicable for the given article creation 
	 */
	public static function checkSDCreation($title, $user) {
		if ($title->getNamespace() != HACL_NS_ACL) {
			// The title is not in the ACL namespace => not applicable
			return "n/a";
		}
		
		list($peName, $peType) = HACLSecurityDescriptor::nameOfPE($title->getText());
		
		// Check if article belongs to a protected category
		// Only the users who can modify the SD of the protecting category can 
		// create a new SD for the protected page.

		if ($peType == HACLSecurityDescriptor::PET_PAGE ||
		    $peType == HACLSecurityDescriptor::PET_CATEGORY) {
			list ($r, $hasSD) = self::hasCategorySDCreationRight($peName, $user->getId());
			if ($r === false && $hasSD === true) {
				return false;
			}
		}
		$t = Title::newFromText($peName);
		
		// Check if article belongs to a protected namespace
		if ($peType == HACLSecurityDescriptor::PET_PAGE) {
			list ($r, $hasSD) = self::checkNamespaceSDCreationRight($t, $user->getId());		
			if ($r === false && $hasSD === true) {
				return false;
			}
		}
		
		// Check if page is a subpage
		if ($title->isSubpage()) {
			$parentTitleText = $title->getBaseText();
			if (strpos($parentTitleText, '/') !== false) {
				// Top level of sub-pages not yet reached 
				// => inherit parent rights
				$parentTitle = Title::newFromText($parentTitleText, $title->getNamespace());
				// Check if the parent article has an SD that can be modified
				$hasSD = false;
				$r = false;
				if ($parentTitle->exists()) {
					list($r, $hasSD) = self::checkACLManager($parentTitle, $user, HACLRight::EDIT);
					if ($r === true && $hasSD === true) {
						return $r;
					}
				}
				// Check inherited rights
				$allowed = self::checkSDCreation($parentTitle, $user);
				if ($allowed === 'n/a') {
					// no explicit rights found for parents
					if ($hasSD) {
						// The current right does not allow modification of the SD
						return false;
					}
				}
				return $allowed;
			}
		}
		
		global $haclgOpenWikiAccess;
		if ($haclgOpenWikiAccess) {
			// the wiki is open => not applicable
			return "n/a";
		}
		if ($peType != HACLSecurityDescriptor::PET_PAGE &&
		    $peType != HACLSecurityDescriptor::PET_PROPERTY) {
		    // only applicable to pages and properties
		    return "n/a";
		}
		
		// get the latest author of the protected article
		$article = new Article($t);
		if (!$article->exists()) {
			// article does not exist => no applicable
			return "n/a";
		}
		$authors = $article->getLastNAuthors(1);
		
		return $authors[0] == $user->getName();
		
	}
	
	
	//--- Private methods ---
	
	/**
	 * Checks, if the given user has the right to perform the given action on
	 * the given title. The hierarchy of categories is evaluated.
	 *
	 * @param mixed string|array<string> $parents
	 * 		If a string is given, this is the name of an article whose parent
	 * 		categories are evaluated. Otherwise it is an array of parent category 
	 * 		names
	 * @param int $userID
	 * 		ID of the user who wants to perform an action
	 * @param int $actionID
	 * 		The action, the user wants to perform. One of the constant defined
	 * 		in HACLRight: READ, FORMEDIT, EDIT, ANNOTATE, CREATE, MOVE and DELETE.
	 * @param array<string> $visitedParents
	 * 		This array contains the names of all parent categories that were already
	 * 		visited.
	 * @return array(bool rightGranted, bool hasSD)
	 * 		rightGranted:
	 *	 		<true>, if the user has the right to perform the action
	 * 			<false>, otherwise
	 * 		hasSD:
	 * 			<true>, if there is an SD for the article
	 * 			<false>, if not
	 */
	private static function hasCategoryRight($parents, $userID, $actionID, 
	                                        $visitedParents = array()) {
	    if (is_string($parents)) {
	    	// The article whose parent categories shall be evaluated is given
	    	$t = Title::newFromText($parents);
	    	return self::hasCategoryRight(array_keys($t->getParentCategories()),$userID, $actionID);
	    } else if (is_array($parents)) {
	    	if (empty($parents)) {
	    		return array(false, false);
	    	}
	    } else {
	    	return array(false, false);
	    }
	    
		// Check for each parent if the right is granted
		$parentTitles = array();
	    $hasSD = false;                   	
	    foreach ($parents as $p) {
	    	$parentTitles[] = $t = Title::newFromText($p);
	    	
			if (!$hasSD) {
				$hasSD = (HACLSecurityDescriptor::getSDForPE($t->getArticleID(), HACLSecurityDescriptor::PET_CATEGORY) !== false);
			}
			$r = self::hasRight($t->getArticleID(), HACLSecurityDescriptor::PET_CATEGORY,
			                    $userID, $actionID);
			if ($r) {
				return array(true, $hasSD);			                    
			}
		}
		
		// No parent category has the required right
		// => check the next level of parents
		$parents = array();
		foreach ($parentTitles as $pt) {
			$ptParents = array_keys($pt->getParentCategories());
			foreach ($ptParents as $p) {
				if (!in_array($p, $visitedParents)) {
			    	$parents[] = $p;
			    	$visitedParents[] = $p;
			    }
			}
		}
		
		// Recursively check all parents
		list($r, $sd) = self::hasCategoryRight($parents, $userID, $actionID, $visitedParents);
		return array($r, $sd ? true : $hasSD);
		
	}
	
	/**
	 * Checks, if the given user has the right to create an SD for the given 
	 * category (as category right) or page.
	 * Assume that a category or page is protected by the SD of its super 
	 * category. If every user could create a new SD for this page, the protection
	 * by categories could not be granted. Consequently the set of users who can
	 * create such an SD is restricted:
	 * Pages which are protected by a category inherit the SD of the category, thus
	 * the modification rights of the category's SD are inherited as well. So only 
	 * users who can modify the SD of the category can create a new SD for the page.
	 * 
	 * Only the SDs of the first parent categories that are found are evaluated 
	 * as these inherit the modification rights of their parents. So there is no
	 * need to crawl all parents recursively. 
	 *
	 * @param mixed string|array<string> $parents
	 * 		If a string is given, this is the name of an article whose parent
	 * 		categories are evaluated. Otherwise it is an array of parent category 
	 * 		names
	 * @param int $userID
	 * 		ID of the user who wants to perform an action
	 * @param array<string> $visitedParents
	 * 		This array contains the names of all parent categories that were already
	 * 		visited.
	 * @return array(bool rightGranted, bool hasSD)
	 * 		rightGranted:
	 *	 		<true>, if the user has the right to create the SD
	 * 			<false>, otherwise
	 * 		hasSD:
	 * 			<true>, if there is an SD for the article
	 * 			<false>, if not
	 * 	 */
	private static function hasCategorySDCreationRight($parents, $userID, 
	                                        $visitedParents = array()) {
	    if (is_string($parents)) {
	    	// The article whose parent categories shall be evaluated is given
	    	$t = Title::newFromText($parents);
	    	return self::hasCategorySDCreationRight(array_keys($t->getParentCategories()),$userID);
	    } else if (is_array($parents)) {
	    	if (empty($parents)) {
	    		// no parents => page/category is not protected
	    		return array(false, false);
	    	}
	    } else {
	    	// Invalid parameter $parent
	    	return array(false, false);
	    }
	    
		// Check for each parent if the right is granted
		$parentTitles = array();
		$sdFound = false;
	    foreach ($parents as $p) {
	    	$parentTitles[] = $t = Title::newFromText($p);
	    	
			$sd = HACLSecurityDescriptor::getSDForPE($t->getArticleID(), 
			                                         HACLSecurityDescriptor::PET_CATEGORY);
			if ($sd !== false) {
				$sd = HACLSecurityDescriptor::newFromID($sd);
				if ($sd->userCanModify($userID)) {
					// User has modification rights for the category's SD.
					return array(true, true);                                 
				}
				$sdFound = true;
			}
		}
		if ($sdFound) {
			// The parent categories owned an SD, but the user is not allowed to
			// modify them.
			return array(false, true);
		}
		
		// No parent category has an SD
		// => check the next level of parents
		$parents = array();
		foreach ($parentTitles as $pt) {
			$ptParents = array_keys($pt->getParentCategories());
			foreach ($ptParents as $p) {
				if (!in_array($p, $visitedParents)) {
			    	$parents[] = $p;
			    	$visitedParents[] = $p;
			    }
			}
		}
		
		// Go up one level of parents
		return self::hasCategorySDCreationRight($parents, $userID, $visitedParents);
	}
	
	
	/**
	 * Checks if access is granted to the namespace of the given title.
	 *
	 * @param Title $t
	 * 		Title whose namespace is checked
	 * @param int $userID
	 * 		ID of the user who want to access the namespace
	 * @param int $actionID
	 * 		ID of the action the user wants to perform
	 * 
	 * @return array(bool rightGranted, bool hasSD)
	 * 		rightGranted:
	 *	 		<true>, if the user has the right to perform the action
	 * 			<false>, otherwise
	 * 		hasSD:
	 * 			<true>, if there is an SD for the article
	 * 			<false>, if not
	 *  
	 */
	private static function checkNamespaceRight(Title $t, $userID, $actionID) {
		$nsID = $t->getNamespace();
		$hasSD = HACLSecurityDescriptor::getSDForPE($nsID, HACLSecurityDescriptor::PET_NAMESPACE) !== false;
			
		if (!$hasSD) {
			global $haclgOpenWikiAccess;
			// Articles with no SD are not protected if $haclgOpenWikiAccess is
			// true. Otherwise access is denied
			return array($haclgOpenWikiAccess, false);
		}
		
		return array(self::hasRight($nsID, HACLSecurityDescriptor::PET_NAMESPACE,
		                            $userID, $actionID), $hasSD);
		
	}

	/**
	 * Checks if the user can create an SD for an article in the given namespace.
	 * If a namespace is protected by an SD, only the managers of this SD have the
	 * right to create new SDs for articles in this namespace. Otherwise every 
	 * user could overwrite the security settings of the namespace for single
	 * articles.
	 *
	 * @param Title $t
	 * 		Title whose namespace is checked
	 * @param int $userID
	 * 		ID of the user who want to access the namespace
	 * 
	 * @return array(bool rightGranted, bool hasSD)
	 * 		rightGranted:
	 *	 		<true>, if the user has the right to create a new SD for the article
	 * 			<false>, otherwise
	 * 		hasSD:
	 * 			<true>, if there is an SD for the article
	 * 			<false>, if not
	 *  
	 */
	private static function checkNamespaceSDCreationRight(Title $t, $userID) {
		$nsID = $t->getNamespace();
		$sd = HACLSecurityDescriptor::getSDForPE($nsID, HACLSecurityDescriptor::PET_NAMESPACE);
		if ($sd !== false) {
			$sd = HACLSecurityDescriptor::newFromID($sd);
			return array($sd->userCanModify($userID), true);
		}
		
		return array(false, false);
		
	}
	
	/**
	 * This method checks if a user wants to modify an article in the namespace
	 * ACL.
	 *
	 * @param Title $t
	 * 		The title.
	 * @param User $user
	 * 		User-object of the user.
	 * @param int $actionID
	 * 		ID of the action. The actions FORMEDIT, WYSIWYG, EDIT, ANNOTATE, 
	 *      CREATE, MOVE and DELETE are relevant for managing an ACL object.
	 * 
	 * @return array(bool rightGranted, bool hasSD)
	 * 		rightGranted:
	 *	 		<true>, if the user has the right to perform the action
	 * 			<false>, otherwise
	 * 		hasSD:
	 * 			<true>, if there is an SD for the article
	 * 			<false>, if not
	 */
	private static function checkACLManager(Title $t, $user, $actionID) {
		if ($t->getNamespace() != HACL_NS_ACL) {
			return array(true, false);
		}
		
		$userID = $user->getId();
		if ($userID == 0) {
			// No access for anonymous users
			return array(false, true);
		}

		// Check if this is a default SD template
		list($r, $sd) = HACLDefaultSD::userCanModify($t, $user);
		if ($sd) {
			return array($r, $sd);
		}
		
		if ($actionID == HACLRight::READ) {
			// Read access for all registered users
			return array(true, true);
		}

		// Check for groups
		try {
			$group = HACLGroup::newFromID($t->getArticleID());
			if (!$group->canBeModified()) {
				// some groups can not be modified at all
				return array(false, true);
			}
			return array($group->userCanModify($userID), true);
		} catch (HACLGroupException $e) {
			// Check for security descriptors
			try {
				$sd = HACLSecurityDescriptor::newFromID($t->getArticleID());
				return array($sd->userCanModify($userID), true);
			} catch (HACLSDException $e) {
				// Check for the Whitelist
				global $haclgContLang;
				if ($t->getText() == $haclgContLang->getWhitelist(false)) {
					// User must be a sysop
					return array(HACLWhitelist::userCanModify($userID), true);
				}
			}
		}
		
		// Sysops and bureaucrats can modify the SD
		$groups = $user->getGroups();
		if (in_array('sysop', $groups) || in_array('bureaucrat', $groups)) {
			return array(true, true);
		}
		return array(false, true);
	}
	
	/**
	 * This method checks if a user wants to edit an article with protected 
	 * properties. (This happens only if protection of semantic properties
	 * is enabled (see $haclgProtectProperties in HACL_Initialize.php))
	 *
	 * @param Title $t
	 * 		The title.
	 * @param int $userID
	 * 		ID of the user.
	 * @param int $actionID
	 * 		ID of the action. The actions FORMEDIT, WYSIWYG, EDIT, ANNOTATE, 
	 *      CREATE, MOVE and DELETE are relevant for managing an ACL object.
	 * 
	 * @return bool
	 * 		rightGranted:
	 *	 		<true>, if the user has the right to perform the action
	 * 			<false>, otherwise
	 */
	private static function checkProperties(Title $t, $userID, $actionID) {

		global $haclgProtectProperties;
		global $wgRequest;
		if (!$haclgProtectProperties || !defined('SMW_VERSION')) {
			// Properties are not protected.
			return true;
		}
		
		if ($actionID == HACLRight::READ) {
			// The article is only read but not edited => action is allowed
			return true;
		}
		// Articles with protected properties are protected if an unauthorized
		// user wants to edit it
		if ($actionID != HACLRight::WYSIWYG &&
			$actionID != HACLRight::EDIT &&
			$actionID != HACLRight::ANNOTATE) {

//			$a = @$wgRequest->data['action'];
			$a = $wgRequest->getVal('action');
			if (isset($a)) {
				// Some web request are translated to other actions before they
				// are passed to the userCan hook. E.g. action=history is passed
				// as action=read.
				// Articles with protected properties can be viewed, because the
				// property values are replaced by dummy text but showing the wikitext
				// (e.g. in the history) must be prohibited.
				
				// Define exceptions for actions that display only rendered text
				static $actionExceptions = array("purge","render","raw");
				if (in_array($a,$actionExceptions)) {
					return true;
				}
				
			} else {
				return true;
			}
			
		}

		// Get all properties of the page
		$store = smwfGetStore(); 
		if (!($store instanceof HACLSMWStore)) {
			throw new HACLException(HACLException::INTERNAL_ERROR, 
									"Expected an instance of HACLSMWStore as semantic store!");			
		}
		
		// We need ALL properties of the title
		$pa = $store->setProtectionActive(false);
		$semdata = smwfGetStore()->getSemanticData($t);
		$store->setProtectionActive($pa);
		$props = $semdata->getProperties();

		foreach ($props as $p) {
//			if (!$p->isShown()) {
//				// Ignore invisible(internal) properties
//				continue;
//			}
			// Check if a property is protected
			$wpv = $p->getWikiPageValue();
			if (!$wpv) {
				// no page for property
				continue;
			}
			$t = $wpv->getTitle();
			
			if (!self::hasPropertyRight($t, $userID, $actionID)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Checks if access to a property should be evaluated. This is the case if
	 * the string $action is one of 'propertyread', 'propertyformedit' or 
	 * 'propertyedit'. 
	 *
	 * @param Title $title
	 * 		Title object for the property whose rights are checked.
	 * @param User $user
	 * 		User who wants to access the property
	 * @param string $action
	 * 		If this is one of 'propertyread', 'propertyformedit' or 'propertyedit'
	 * 		property rights are checked
	 * @return bool / int
	 * 		<true>:  Access to the property is granted.
	 * 		<false>: Access to the property is denied.
	 *      -1: $action is not concerned with properties.
	 */
	private static function checkPropertyAccess(Title $title, $user, $action) {
		if (self::$mMode == HACLEvaluator::DENY_DIFF) {
			return false;
		}
		if (self::$mMode == HACLEvaluator::ALLOW_PROPERTY_READ
			&& $action == 'propertyread') {
				return true; 
		}
		
		switch ($action) {
			case 'propertyread':
				$actionID = HACLRight::READ;
				break;
			case 'propertyformedit':
				$actionID = HACLRight::FORMEDIT;
				break;
		    case 'propertyedit':
				$actionID = HACLRight::EDIT;
		    	break;
		    default:
		    	// No property access requested
		    	return -1;
		}
		if (self::$mSavePropertiesAllowed) {
			return true;
		}
		return self::hasPropertyRight($title, $user->getId(), $actionID);
	}
	
	/**
	 * This function checks if an article may be submitted even if the 'edit' right
	 * is not given. This is the case when 'formedit', 'wysiwyg' or 'annotate'
	 * are granted. 
	 *
	 * @param int $articleID
	 * 		ID of the protected object (which is the namespace index if the type
	 * 		is PET_NAMESPACE)
	 * @param string $peType
	 * 		The type of the protection to check for the title. One of
	 * 		HACLSecurityDescriptor::PET_PAGE
	 * 		HACLSecurityDescriptor::PET_CATEGORY
	 * 		HACLSecurityDescriptor::PET_NAMESPACE
	 * 		HACLSecurityDescriptor::PET_PROPERTY
	 * @param int $userID
	 * 		ID of the user who wants to perform an action
	 * @param int $actionID
	 * 		Must be HACLRight::EDIT, otherwise 'false' is returned.
	 * @return bool
	 * 		<true>, if the user has the right to perform the action
	 * 		<false>, otherwise
	 * 	 */
	private static function checkAllowSubmit($articleID, $peType, $userID, $actionID) {
		if ($actionID != HACLRight::EDIT) {
			return false;
		}
		
		// Check if one of the edit rights is granted for articles
		if ($peType == HACLSecurityDescriptor::PET_PAGE ||
		    $peType == HACLSecurityDescriptor::PET_NAMESPACE) {
			$r = self::hasRight($articleID, $peType, $userID, HACLRight::FORMEDIT) ||
				 self::hasRight($articleID, $peType, $userID, HACLRight::ANNOTATE) ||
				 self::hasRight($articleID, $peType, $userID, HACLRight::WYSIWYG);
		} else if ($peType == HACLSecurityDescriptor::PET_CATEGORY) {
			$r = self::hasCategoryRight($articleID, $userID, HACLRight::FORMEDIT) ||
				 self::hasCategoryRight($articleID, $userID, HACLRight::ANNOTATE) ||
				 self::hasCategoryRight($articleID, $userID, HACLRight::WYSIWYG);
		}
		return $r;
	}
	
	/**
	 * This function checks if the values of the property $property have changed
	 * in the comparison of the semantic database ($oldValues) and the wiki text
	 * that is about to be stored ($newValues). 
	 *
	 * @param SMWPropertyValue $property
	 * 		The property whose old and new values are compared.
	 * @param SMWSemanticData $oldValues
	 * 		The semantic data object with the old values
	 * @param SMWSemanticData $newValues
	 * 		The semantic data object with the new values
	 * @return boolean
	 * 		<true>, if values have been added, removed or changed,
	 * 		<false>, if values are exactly the same.
	 */
	private static function propertyValuesChanged(SMWPropertyValue $property, 
												  SMWSemanticData $oldValues, 
												  SMWSemanticData $newValues) {
												  	
		
		// Get all old values of the property										  	
		$oldPV = $oldValues->getPropertyValues($property);
		$oldValues = array();
		self::$mMode = HACLEvaluator::ALLOW_PROPERTY_READ;
		foreach ($oldPV as $v) {
//			$hash = implode("\t", $v->getDBkeys());
			$hash = $v->getHash();
			if ($v instanceof SMWNumberValue) {
				$hash = strval(floatval($hash));
			}
			$oldValues[$hash] = false;
		}
		self::$mMode = HACLEvaluator::NORMAL;
		
		// Get all new values of the property
		$newPV = $newValues->getPropertyValues($property);
		foreach ($newPV as $v) {
			self::$mMode = HACLEvaluator::ALLOW_PROPERTY_READ;
			$wv = $v->getWikiValue();
			if (empty($wv)) {
				// A property has an empty value => can be ignored
				continue;
			}
//			$nv = implode("\t", $v->getDBkeys());
			$nv = $v->getHash();
			if ($v instanceof SMWNumberValue) {
				$nv = strval(floatval($nv));
			}
			self::$mMode = HACLEvaluator::NORMAL;
			if (array_key_exists($nv, $oldValues)) {
				// Old value was not changed
				$oldValues[$nv] = true;
			} else {
				// A new value was added
				return true;
			}
		}
		
		foreach ($oldValues as $stillThere) {
			if (!$stillThere) {
				// A property value has been deleted
				return true;
			}
		}
		
		// Property values have not changed.
		return false;
	}
	
	/**
	 * Starts the log for an evaluation. The log string is assembled in self::mLog.
	 *
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 */
	private static function startLog($title, $user, $action) {
		global $wgRequest, $haclgEvaluatorLog;
		
		self::$mLogEnabled = $haclgEvaluatorLog 
		                     && $wgRequest->getVal('hacllog', 'false') == 'true';

//self::$mLogEnabled = true;		                     
		
		if (!self::$mLogEnabled) {
			// Logging is disabled
			return;
		}
		self::$mLog = "";
		
		self::$mLog .= "HaloACL Evaluation Log\n";
		self::$mLog .= "======================\n\n";
		self::$mLog .= "Article: ". (is_null($title) ? "null" 
		                                             : $title->getFullText()). 
		                                               " (Request: ".$wgRequest->getText('title').")\n"; 
		self::$mLog .= "User: ". $user->getName(). "\n";
		self::$mLog .= "Action: $action (Request: ".$wgRequest->getText('action').")\n"; 

	}
	
	/**
	 * Adds a message to the evaluation log.
	 *
	 * @param string $msg
	 * 		The message to add.
	 */
	private static function log($msg) {
		if (!self::$mLogEnabled) {
			// Logging is disabled
			return;
		}
		self::$mLog .= "$msg\n";
	}
	
	/**
	 * Finishes the log for an evaluation.
	 *
	 * @param string $msg
	 * 		This message is added to the log.
	 * @param bool $result
	 * 		The result of the evaluation: 
	 * 			true - action is allowed
	 *  		false - action is forbidden
	 * @param bool $returnVal
	 * 		Return value of the userCan-hook:
	 * 			true - HaloACL may have no opinion about the requested right. Other
	 *                 extensions must decide.
	 * 			false - HaloACL found a right and stops the chain of userCan-hooks
	 */
	private static function finishLog($msg, $result, $returnVal) {
		if (!self::$mLogEnabled) {
			// Logging is disabled
			return;
		}
		
		self::$mLog .= "$msg\n";
		self::$mLog .= "The action is ". ($result ? "allowed.\n" : "forbidden.\n");
		if ($returnVal) {
			// HaloACL has no opinion about the requested right.
			self::$mLog .= "The system and other extensions can still decide if this action is allowed.\n";
		} else {
			self::$mLog .= "The right is determined by HaloACL. No other extensions can influence this.\n";
		}
		self::$mLog .= "\n\n";
		
		echo self::$mLog;
	}
	
}