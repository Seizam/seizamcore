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
 * This file contains the class HACLDefaultSD.
 * 
 * @author Thomas Schweitzer
 * Date: 22.05.2009
 * 
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}


 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * This class manages the default security descriptor for users.
 * 
 * What happens when a user creates a new article? Does the user have to create 
 * the corresponding security descriptor or is it created automatically? 
 * And if so, what is its initial content?
 * 
 * "Default security descriptors" satisfy three scenarios:
 *    1. The wiki is by default an open wiki i.e. all new articles are accessible 
 *       by all users. Only if a page should be protected explicitly a security
 *       descriptor must be provided.
 *    2. New articles are automatically protected and belong to the author until
 *       he releases it. In this case a security descriptor must be created 
 *       automatically with an ACL that permits only access for the author.
 *    3. New articles are automatically protected and belong to users and groups
 *       that can be freely defined. In this case a security descriptor must be 
 *       created automatically with an ACL that can be configured. 
 * 
 * The solution for this is simple. Every user can define a template 
 * (not a MediaWiki template) for his default ACL. There is a special article 
 * with the naming scheme ACL:Template/<username> e.g. ACL:Template/Peter. This 
 * template article can contain any kind of valid ACL as described above. It can
 * define rights for the author alone or arbitrary combinations of users and 
 * groups.
 * 
 * If the user creates a new article, the system checks, if he has defined an
 * ACL template. If not, no security descriptor is created. This solves the 
 * problem of the first scenario, the open wiki. Otherwise, if the template 
 * exists, a security descriptor is created and filled with the content of the 
 * template. This serves the latter two scenarios.  
 * 
 * This class registers the hook "ArticleSaveComplete", which checks for each 
 * saved article, if a default SD has to be created.
 * 
 * @author Thomas Schweitzer
 * 
 */
class  HACLDefaultSD  {
	
	//--- Constants ---
//	const XY= 0;		// the result has been added since the last time
		
	//--- Private fields ---
	private $mXY;    		//string: comment
	
	/**
	 * Constructor for  HACLDefaultSD
	 *
	 * @param type $param
	 * 		Name of the notification
	 */		
	function __construct() {
//		$this->mXY = $xy;
	}
	

	//--- getter/setter ---
//	public function getXY()           {return $this->mXY;}

//	public function setXY($xy)               {$this->mXY = $xy;}
	
	//--- Public methods ---
	
	
	/**
	 * This method is called, after an article has been saved. If the article
	 * belongs to the namespace ACL (i.e. a right, SD, group or whitelist)
	 * it is ignored. Otherwise the following happens:
	 * - Check the namespace of the article (must not be ACL)
	 * - Check if a dynamic SD has to be created because if a category in the article
	 * - Check if $user is a registered user
	 * - Check if the article already has an SD
	 * - Check if the user has defined a default SD
	 * - Create the default SD for the article.
	 *
	 * @param Article $article
	 * 		The article which was saved
	 * @param User $user
	 * 		The user who saved the article
	 * @param string $text
	 * 		The content of the article
	 * 
	 * @return true
	 */
	public static function articleSaveComplete(&$article, &$user, $text) {
		global $wgUser;
		
		if ($article->getTitle()->getNamespace() == HACL_NS_ACL) {
			// No default SD for articles in the namespace ACL
			return true;
		}

		// Create a dynamic group if requested
		self::createDynamicGroup($article, $user);
		
		// Dynamic SDs have precedence over a users default SD
		if (self::createDynamicSD($article, $user)) {
			// A dynamic SD was created => return
			return true;
		}
		
		if ($user->isAnon()) {
			// Don't create default SDs for anonymous users
			return true;
		}
		
		$articleID = $article->getTitle()->getArticleID();

        $sdAlreadyDefinied = false;
        $createCustomSD = false;
		if (HACLSecurityDescriptor::getSDForPE($articleID, HACLSecurityDescriptor::PET_PAGE) !== false) {
			// There is already an SD for the article
            $sdAlreadyDefinied = true;
        }

        // has user defined another template than default sd
        $articleContent = $article->getContent();

        /*

        $start =15+ strpos($articleContent,"{{#protectwith:");
        $templateToProtectWith = substr($articleContent,$start);
        $templateToProtectWith = substr($templateToProtectWith,0,strpos($templateToProtectWith, "}}"));

        if($templateToProtectWith != null && $templateToProtectWith != "") {
            $createCustomSD = true;
        }


        // remove protectwith from articlecontent
        $articleContent = preg_replace("/{{#protectwith:(.*)}}/is", "", $articleContent);

        #$article->doEdit($articleContent, "processed by defaultsd-generation");
        */
        if (isset($_SESSION)) {
        	if (isset($_SESSION['haloacl_toolbar'])
        	    && isset($_SESSION['haloacl_toolbar'][$user->getName()])) {
        		$templateToProtectWith = $_SESSION['haloacl_toolbar'][$user->getName()];
        		if (strpos($templateToProtectWith, 'Right/') !== false 
        		    || $templateToProtectWith== 'unprotected'){
        			$createCustomSD = true;
        		}
        		unset($_SESSION['haloacl_toolbar'][$user->getName()]);
        	}
        }
		
		// Did the user define a default SD
        // adding default sd to article
		
		if (!$sdAlreadyDefinied && !$createCustomSD) {
			global $haclgContLang;

			$ns = $haclgContLang->getNamespaces();
			$ns = $ns[HACL_NS_ACL];
			$template = $haclgContLang->getSDTemplateName();
			$defaultSDName = "$ns:$template/{$user->getName()}";
			$etc = haclfDisableTitlePatch();
			$defaultSD = Title::newFromText($defaultSDName);
			haclfRestoreTitlePatch($etc);
			if (!$defaultSD->exists()) {
				// No default SD defined
				return true;
			}

			// Create the default SD for the saved article
			// Get the content of the default SD
			$defaultSDArticle = new Article($defaultSD);
			$content = $defaultSDArticle->getContent();

			// Create the new SD
			$newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
			HACLSecurityDescriptor::PET_PAGE);

			$etc = haclfDisableTitlePatch();
			$newSD = Title::newFromText($newSDName);
			haclfRestoreTitlePatch($etc);

			$newSDArticle = new Article($newSD);
			$newSDArticle->doEdit($content, "Default security descriptor.", EDIT_NEW);

			return true;
		}

		if($createCustomSD) {
			// now we create an new securitydescriptor
			if($templateToProtectWith != "unprotected") {
				global $haclgContLang;

				$ns = $haclgContLang->getNamespaces();
				$ns = $ns[HACL_NS_ACL];
				$defaultSDName = "$ns:$templateToProtectWith";
				$etc = haclfDisableTitlePatch();
				$defaultSD = Title::newFromText($defaultSDName);
				haclfRestoreTitlePatch($etc);
				if (!$defaultSD->exists()) {
					// No default SD defined
					return false;
				}

				// Create the default SD for the saved article
				// Get the content of the default SD

                                #$defaultSDArticle = new Article($defaultSD);
				#$content = $defaultSDArticle->getContent();

				// Create the new SD
				$newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
				HACLSecurityDescriptor::PET_PAGE);

				#$etc = haclfDisableTitlePatch();
				$newSD = Title::newFromText($newSDName);
				#haclfRestoreTitlePatch($etc);
                                $content = "
{{#predefined right:rights=".$defaultSDName."}}
{{#manage rights:assigned to=User:".$wgUser->getName()."}}
[[Category:ACL/ACL]]
";

				$newSDArticle = new Article($newSD);
				$newSDArticle->doEdit($content, "Custom security descriptor.");

				return true;

				// we delete the actual assigned sd, if it exists
			}else {
				$newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
				HACLSecurityDescriptor::PET_PAGE);

				$etc = haclfDisableTitlePatch();
				$newSD = Title::newFromText($newSDName);
				haclfRestoreTitlePatch($etc);

				$newSDArticle = new Article($newSD);
				if($newSDArticle->exists()) {
					$newSDArticle->doDelete("securitydescriptor removed");
				}
			}
		}

		return true;
	}

	
	/**
	 * This function is called when a user logs in. 
	 * 
	 * If $haclgNewUserTemplate is set, a default access rights template for new
	 * articles is created, if it does not already exist. 
	 * Furthermore, the quick access list of the user is filled with all right 
	 * templates given in $haclgDefaultQuickAccessRightMasterTemplates. 
	 *
	 * @param User $newUser
	 * 		User, whose default rights template is set.
	 * @return boolean true
	 */
	public static function newUser(User &$newUser, &$injectHTML) {
		
		// Get the content of the article with the master template in $haclgNewUserTemplate
		global $haclgNewUserTemplate, $haclgDefaultQuickAccessRightMasterTemplates;
		if (isset($haclgNewUserTemplate)) {
			// master template specified
			self::createUserDefaultTemplate($newUser);
		}
		if (isset($haclgDefaultQuickAccessRightMasterTemplates)) {
			self::setQuickAccessRights($newUser);
		}
		return true;
	}
	
	/**
	 * Checks if the given user can modify the given title, if it is a 
	 * default security descriptor.
	 *
	 * @param Title $title
	 * 		The title that is checked.
	 * @param User $user
	 * 		The user who wants to access the article.
	 * 
	 * @return array(bool rightGranted, bool hasSD)
	 * 		rightGranted:
	 * 			<true>, if title is the name for a default SD and the user is 
	 * 					allowed to create it or if it no default SD
	 * 			<false>, if title is the name for a default SD and the user is 
	 * 					 not allowed.
	 * 		hasSD:
	 * 			<true>, if the article is a default SD
	 * 			<false>, if not
	 */
	public static function userCanModify($title, $user) {
		// Check for default rights template
		if ($title->getNamespace() !== HACL_NS_ACL) {
			// wrong namespace
			return array(true, false);
		}
		
		// Is this the master template for default templates of new users?
		global $haclgNewUserTemplate;
		if (isset($haclgNewUserTemplate) 
		    && $title->getFullText() == $haclgNewUserTemplate) {
			// User must be a sysop or bureaucrat
			$groups = $user->getGroups();
			$r = (in_array('sysop', $groups) || in_array('bureaucrat', $groups));
			return array($r, true);
		}
		
		global $haclgContLang;
		$prefix = $haclgContLang->getSDTemplateName();
		if (strpos($title->getText(), "$prefix/") !== 0) {
			// wrong prefix
			return array(true, false);
		}
		// article is a default rights template
		$userName = substr($title->getText(), strlen($prefix)+1);
		// Is this the template of another user?
		if ($user->getName() != $userName) {
			// no rights for other users but sysops and bureaucrats
			$groups = $user->getGroups();
			$r = (in_array('sysop', $groups) || in_array('bureaucrat', $groups));
			return array($r, true);
		}
		// user has all rights on the template
		return array(true, true);
				
	}
	
	//--- Private methods ---

	/**
	 * If $haclgNewUserTemplate is set, a default access rights template for new
	 * articles is created, if it does not already exist. 
	 *
	 * @param User $newUser
	 * 		User, whose default rights template is set.
	 */
	private static function createUserDefaultTemplate(User &$newUser) {
		// Check if the user already has a default template
		global $haclgContLang, $haclgNewUserTemplate;
		$ns = $haclgContLang->getNamespaces();
		$ns = $ns[HACL_NS_ACL];
		$template = $haclgContLang->getSDTemplateName();
		$defaultTemplateName = "$ns:$template/{$newUser->getName()}";
		self::copyTemplate($haclgNewUserTemplate, $defaultTemplateName, 
						   array("user" => $newUser->getUserPage()->getFullText()));
	}
	
	/**
	 * Copies the quick access right master templates for the current user and
	 * adds them to his quick access list.
	 *
	 * @param User $newUser
	 * 	User, whose quick access rights are set.
	 */
	private static function setQuickAccessRights(User &$newUser) {
		global $haclgContLang, $haclgDefaultQuickAccessRightMasterTemplates;

		$ns = $haclgContLang->getNamespaces();
		$ns = $ns[HACL_NS_ACL];
		$template = $haclgContLang->getSDTemplateName();
		$r = $haclgContLang->getPredefinedRightName();
		$rightPrefix = "$ns:$template/QARMT/";
		$userRightPrefix = "$ns:$r/";
		
		$uid = $newUser->getId();
		$quickACL = HACLQuickacl::newForUserId($uid);
		$sdAdded = false;
		foreach ($haclgDefaultQuickAccessRightMasterTemplates as $right) {
			// assemble the name of the right for the user
			if (strpos($right, $rightPrefix) !== 0) {
				// Rights must have a name like "ACL:Template/QARMT/<right name>"
				continue;
			}
			$destRight = $userRightPrefix
						 .$newUser->getName().'/'
						 .substr($right, strlen($rightPrefix));
			self::copyTemplate($right, $destRight, 
			                   array("user" => $newUser->getUserPage()->getFullText()));
			
			$sdID = HACLSecurityDescriptor::idForSD($destRight);
			if ($sdID) {
				$quickACL->addSD_ID($sdID);
				$sdAdded = true;
			}
		}
		if ($sdAdded) {
			$quickACL->save();
		}
	}
	
	/**
	 * Copies the content of the right template with the name $source into the
	 * article with the name $dest. If $source does not exist or if $dest already
	 * exists, the operation is aborted. The source template may contain
	 * variables whose values are defined in $variables.
	 *
	 * @param string $source
	 * 		Name of the article that will be copied.
	 * @param string $dest
	 * 		Name of the article that will be created as copy of $source.
	 * @param array<string, string> $variables
	 * 		The names of variables that may appear in the template and the values
	 * 		that replace these variables
	 * 
	 * @return bool
	 * 		<true> if the operation was successful or
	 * 		<false> if copying the articles failed
	 */
	private static function copyTemplate($source, $dest, $variables) {
		
		// Check if destination article already exists
		$etc = haclfDisableTitlePatch();
		$destTitle = Title::newFromText($dest);
		haclfRestoreTitlePatch($etc);
		$destExists = $destTitle->exists();
//		if ($destTitle->exists()) {
//			// The destination article already exists
//			return true;
//		}

		//-- Copy the content of the source article --
		// Get the content of the source article
		$etc = haclfDisableTitlePatch();
		$sourceTitle = Title::newFromText($source);
		haclfRestoreTitlePatch($etc);
		$sourceArticle = new Article($sourceTitle);
		if (!$sourceTitle->exists()) {
			// The source article does not exist
			return false;
		}
		$content = $sourceArticle->getContent();
		
		// Replace all variables by their values
		foreach ($variables as $var => $value) {
			$content = str_replace("{{{".$var."}}}", $value, $content);
		}
		
		HACLParserFunctions::getInstance()->reset();
		// Create the destination article
		$newArticle = new Article($destTitle);
		$newArticle->doEdit($content, "Changed/created by HaloACL.", 
		                    $destExists? EDIT_UPDATE : EDIT_NEW);
		
		return true;
	}
	
	/**
	 * Checks if the $article contains a category that is associated with a dynamic
	 * SD for the giver $user.
	 * If this is the case, the new corresponding SD is created or an existing
	 * SD is replaced.
	 * 
	 * @param Article $article
	 * 		This article may get dynamic SD
	 * @param User $user
	 * 		The user who saves the article.
	 * 
	 * @return Returns
	 * 		<true>, if an SD was created or modified
	 * 		<false>, if not
	 */
	private static function createDynamicSD(Article $article, User $user) {
		
		$dsd = self::getMatchingDynamicSD($article, $user);
		if (is_null($dsd)) {
			// no matching dynamic SD found
			return false;
		}
		
		// Create the SD for the article
		self::createDynamicSDForArticle($article, $user, $dsd);
		
		return true;
	}
	
	/**
	 * Checks if the $article contains a category that is associated with a dynamic
	 * group for the giver $user.
	 * If this is the case, the new corresponding group is created. If the group
	 * already exists, it is not changed.
	 * 
	 * @param Article $article
	 * 		This article may get dynamic group
	 * @param User $user
	 * 		The user who saves the article.
	 * 
	 * @return Returns
	 * 		<true>, if a group was created or modified
	 * 		<false>, if not
	 */
	private static function createDynamicGroup(Article $article, $user) {
		
		$dgroup = self::getMatchingDynamicGroup($article, $user);
		if (is_null($dgroup)) {
			// no matching dynamic group found
			return false;
		}
		
		// Create the group for the article
		self::createDynamicGroupForArticle($article, $user, $dgroup);
		
		return true;
	}
	
	/**
	 * Creates the dynamic SD for the $article and the given $user. The dynamic
	 * SD is described by the associative array $dynamicSDRule.
	 * If the article already has an SD it is replaced by the new SD.
	 * 
	 * @param Article $article
	 * 		Article whose SD is set
	 * @param User $user
	 * 		User who sets the SD
	 * @param array $dynamicSDRule
	 * 		This descriptor contains the following keys:
	 * 		category => Name of the category to which the article must belong
	 * 		sd       => Name of the SD template whose content is copied
	 * 		user     => User(s) for whom this this rule can be applied
	 */
	private static function createDynamicSDForArticle(Article $article, User $user, 
	                                                  $dynamicSDRule) {
		// By default unauthorized users can not change the SD
		$allowSDChange = array_key_exists("allowUnauthorizedSDChange", $dynamicSDRule)
							? $dynamicSDRule["allowUnauthorizedSDChange"]
							: false;
		try {					
			HACLSecurityDescriptor::setAllowUnauthorizedSDChange($allowSDChange);				
		                                                  	// Delete the current SD of the article, if present
			$sdID = HACLSecurityDescriptor::getSDForPE($article->getID(),
													   HACLSecurityDescriptor::PET_PAGE);
			if ($sdID !== false) {
				// Delete the SD article
				$sd = HACLSecurityDescriptor::newFromID($sdID);
				$sd->delete($user);
			}
	
			$sdName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
													   HACLSecurityDescriptor::PET_PAGE);
	
			// Assemble the name of the template which is copied
			global $haclgContLang;
			$source = $dynamicSDRule['sd'];
			$catName = Title::makeTitle(NS_CATEGORY, $dynamicSDRule['category']);
			$catName = $catName->getFullText();
			$variables = array(
				"user"        => $user->getUserPage()->getFullText(),
				"articleName" => $article->getTitle()->getFullText(),
				"category"    => $catName
			);
			
			self::copyTemplate($source, $sdName, $variables);
		} catch (Exception$e) {
			// Make sure that unauthorized changes are prohibited at the end 
			// of this method.
			HACLSecurityDescriptor::setAllowUnauthorizedSDChange(false);
			throw $e;
		}				
		HACLSecurityDescriptor::setAllowUnauthorizedSDChange(false);
		
	}
	
	/**
	 * Creates the dynamic group for the $article and the given $user. The dynamic
	 * group is described by the associative array $dynamicGroupRule.
	 * If the article already has an associated group, it is not changed.
	 * 
	 * @param Article $article
	 * 		Article whose group is created
	 * @param User $user
	 * 		User who saves the article
	 * @param array $dynamicGroupRule
	 * 		This descriptor contains the following keys:
	 * 		category => Name of the category to which the article must belong
	 * 		groupTemplate => Name of the group template whose content is copied
	 * 		user     => User(s) for whom this this rule can be applied
	 * 		name	 => The pattern of the group's name. It may contain the
	 * 					variable {{{articleName}}} which will be replaced by
	 * 					the name of the article $article (without namespace).
	 */
	private static function createDynamicGroupForArticle(Article $article, User $user, 
	                                                     array $dynamicGroupRule) {

	    // Get the name of the new group
	    $articleName = $article->getTitle()->getText();
		$groupName = $dynamicGroupRule['name'];
		$groupName = str_replace('{{{articleName}}}', $articleName, $groupName);
		
		// Does the group already exist?
		$groupTitle = Title::newFromText($groupName);
		if ($groupTitle->exists()) {
			// existing group is not changed
			return;
		}
		
		// Find values of all variables
		$source = $dynamicGroupRule['groupTemplate'];
		$catName = Title::makeTitle(NS_CATEGORY, $dynamicGroupRule['category']);
		$catName = $catName->getFullText();
		$variables = array(
			"user"        => $user->getUserPage()->getFullText(),
			"articleName" => $article->getTitle()->getFullText(),
			"category"    => $catName
		);
		
		HACLGroup::setAllowUnauthorizedGroupChange(true);
		try {
			self::copyTemplate($source, $groupName, $variables);
			// remove namespace from $groupname
			$groupName = substr($groupName, strpos($groupName, ':')+1);
			self::createParentGroups($groupName, $dynamicGroupRule['category']);
		} catch (Exception $e) {
			HACLGroup::setAllowUnauthorizedGroupChange(false);
			throw $e;
		}
		HACLGroup::setAllowUnauthorizedGroupChange(false);
		
	}
	
	/**
	 * Tries to find the dynamic SD rule that matches the conditions given
	 * by the $article and the $user.
	 * 
	 * @param Article $article
	 * 		This article may get dynamic SD
	 * @param User $user
	 * 		The user who saves the article.
	 * 
	 * @return The matching dynamic SD description or <null> if there is none.
	 */
	private static function getMatchingDynamicSD(Article $article, User $user) {
		global $haclgDynamicSD;
		
		$categories = self::getCategories($article);
		if (empty($categories) || !isset($haclgDynamicSD)) {
			return null;
		}
		
		// Is there a rule for Dynamic SDs for this article?
		foreach ($haclgDynamicSD as $dsd) {
			
			// Verify the dynamic SD rule
			if (!(array_key_exists('user', $dsd)
			      && array_key_exists('category', $dsd)
			      && array_key_exists('sd', $dsd) )) {
				// dynamic SD rule is incomplete
				throw new HACLSDException(HACLSDException::INCOMPLETE_DYNAMIC_SD_RULE, $dsd);
			}
			
			// Does the category match the article's categories?
			if (in_array($dsd['category'], $categories)
			    && self::validUserInRule($dsd, $user)) {
				// Found a matching dynamic SD
				return $dsd;
			}
		}
		
		return null;
		
	}

	/**
	 * Tries to find the dynamic group rule that matches the conditions given
	 * by the $article and the $user.
	 * 
	 * @param Article $article
	 * 		This article may get dynamic group
	 * @param User $user
	 * 		The user who saves the article.
	 * 
	 * @return The matching dynamic group rule or <null> if there is none.
	 */
	private static function getMatchingDynamicGroup(Article $article, User $user) {
		global $haclgDynamicGroup;
		
		$categories = self::getCategories($article);
		if (empty($categories) || !isset($haclgDynamicGroup)) {
			return null;
		}
		
		// Is there a rule for Dynamic Groups for this article?
		foreach ($haclgDynamicGroup as $dgr) {
			
			// Verify the dynamic group rule
			if (!(array_key_exists('user', $dgr)
			      && array_key_exists('category', $dgr)
			      && array_key_exists('name', $dgr)
			      && array_key_exists('groupTemplate', $dgr) )) {
				// dynamic SD rule is incomplete
				throw new HACLSDException(HACLSDException::INCOMPLETE_DYNAMIC_GROUP_RULE, $dgr);
			}
			
			// Does the category match the article's categories?
			// Does the current user match?
			if (in_array($dgr['category'], $categories)
			    && self::validUserInRule($dgr, $user)) {
				// Found a match
				return $dgr;
			}
		}
		
		return null;
		
	}
	
	/**
	 * Returns the names of categories of the given $article.
	 * 
	 * @param Article $article
	 * 		Article whose categories are retrieve
	 * 
	 */
	private static function getCategories(Article $article) {
	
		// Does the article belong to a category
		$cat = $article->getTitle()->getParentCategories();
		if (empty($cat)) {
			// no categories
			return $cat;
		}
		
		// Transform the array of categories
		$categories = array();
		foreach ($cat as $c => $a) {
			// Remove the namespace from the category.
			$c = explode(':', $c);
			$categories[] = $c[1];
		}
		return $categories;
	}
	
	/**
	 * Checks if the current $user matches the user(s) given in the $rule for
	 * dynamic SDs or groups.
	 * 
	 * @param array<string=>mixed> $rule
	 * 		This array must contain the key 'user' whose value is a user name or 
	 * 		array of user names.
	 * @param User $currentUser
	 * 		The current user
	 * 
	 * @return bool
	 * 		<true> if the current user matches one of the users in the rule and
	 * 		<false> if not
	 */
	private static function validUserInRule(array $rule, User $currentUser) {
		$currentUserID = $currentUser->getId();
		$dgrUsers = $rule['user'];
		if (!is_array($dgrUsers)) {
			$dgrUsers = array($dgrUsers);
		}
		foreach ($dgrUsers as $u) {
			$uid = haclfGetUserID($u);
			$uid = $uid[0];
			if (($uid === 0 && $currentUserID === 0)   // granted for anonymous users
				|| ($uid === -1 && $currentUserID > 0) // granted for registered users
				|| ($uid === $currentUserID)) { 	   // user matches exactly
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Creates the parent groups for a group with name $groupName that was 
	 * created automatically. 
	 * The parent group will be named "Automatic groups for $category". The parent
	 * group of this will be "Automatic groups". The strings "Automatic groups( for )"
	 * are specified in the global variables $haclgDynamicRootGroup and 
	 * $haclgDynamicCategoryGroup. The manager of the new groups is given in
	 * $haclgDynamicGroupManager.
	 * If any of these global variables is not defined, no parent groups will be
	 * created.
	 * 
	 * @param string $groupName
	 * 		Name of the group whose parents are created  (without namespace)
	 * @param string $category
	 * 		Name of the category for which the group was created (without namespace).
	 * 
	 */
	private static function createParentGroups($groupName, $category) {
		global $haclgDynamicRootGroup, $haclgDynamicCategoryGroup, 
		       $haclgDynamicGroupManager;
		
		if (!isset($haclgDynamicRootGroup) 
		    || !isset($haclgDynamicCategoryGroup) 
		    || !isset($haclgDynamicGroupManager)
		    || (!array_key_exists("groups", $haclgDynamicGroupManager) && 
		        !array_key_exists("users", $haclgDynamicGroupManager)) ) {
		    // Important global variables missing
		    	return;
		}
		$userManager = array_key_exists("users", $haclgDynamicGroupManager)
						? $haclgDynamicGroupManager['users']
						: null;
		$groupManager = array_key_exists("groups", $haclgDynamicGroupManager)
						? $haclgDynamicGroupManager['groups']
						: null;
						
		global $haclgContLang;
    	$prefix = $haclgContLang->getNamingConvention(HACLLanguage::NC_GROUP);
    	
    	$grandparentGroup = "$prefix/$haclgDynamicRootGroup";
    	$parentGroup      = "$prefix/$haclgDynamicCategoryGroup$category";
    	
    	// Create/update parent group
    	try {
    		$group = HACLGroup::newFromName($parentGroup);
    	} catch (HACLGroupException $e) {
    		// group does not exist yet => create it
    		HACLGroup::createEmptyArticle($parentGroup);
    		$group = new HACLGroup(null, $parentGroup, $groupManager, $userManager);
    	}
    	$group->addGroup($groupName);
    	$group->saveArticle();
    	
    	// Create/update root group
    	try {
    		$group = HACLGroup::newFromName($grandparentGroup);
    	} catch (HACLGroupException $e) {
    		// group does not exist yet => create it
    		HACLGroup::createEmptyArticle($grandparentGroup);
    		$group = new HACLGroup(null, $grandparentGroup, $groupManager, $userManager);
    	}
    	$group->addGroup($parentGroup);
    	$group->saveArticle();
    	
	}
}
