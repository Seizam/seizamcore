<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

class WikiplacesHooks {

	private static $cacheUserCan = array(); // ['title']['user']

	# Schema updates for update.php
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {

		$tables = array(
			'wp_plan',
			'wp_subscription',
			'wp_old_usage',
			'wp_wikiplace',
			'wp_page',
			'wp_old_subscription'
		);

		$mysql_dir = dirname( __FILE__ ).'/schema/mysql';
		foreach ($tables as $table) {
			$updater->addExtensionUpdate( array( 'addTable', $table , "$mysql_dir/$table.sql", true ) );
		}

		return true;
	}


	
	
	/**
	 * @param Title $title the article (Article object) being saved
	 * @param User $user the user (User object) saving the article
	 * @param string $action the action
	 * @param boolean $result 
	 */
	public static function userCan( $title, &$user, $action, &$result ) {

		if (($action == 'read')) {
			return true; // skip
		}

		$article_id = $title->getArticleID();
		$user_id = $user->getId();

		$do;

		if (!$title->isKnown() && ( ($action == 'create') || ($action == 'edit') || ($action == 'upload') || ($action == 'createpage') || ($action == 'move-target') )) {
			$do = 'create';
		} elseif (($action == 'move') || ($action == 'delete')) {
			$do = $action;
		} else {
			wfDebugLog('wikiplaces', 'userCan: ' . $action . ' SKIP' .
					' title="' . $title->getPrefixedDBkey() . '"[' . $article_id . '],' . ($title->isKnown() ? 'known' : 'new') .
					' user="' . $user->getName() . '"[' . $user_id . ']');
			return true; // action not handled here, so continue hook processing to let MW find an answer
		}

		if (isset(self::$cacheUserCan[$article_id][$user_id][$do])) {
			$result = self::$cacheUserCan[$article_id][$user_id][$do];
			wfDebugLog('wikiplaces', 'userCan: ' . $do . ' ' . ($result ? 'ALLOWED' : 'DENIED') . '(cache hit)' .
					' article=[' . $article_id . ']' .
					' user=[' . $user_id . ']' .
					' action=' . $action);
			return false;
		}

		switch ($do) {
			case 'create':
				$result = self::userCanCreate($title, $user);
				break;
			case 'move':
				$result = self::userCanMove($title, $user);
				break;
			case 'delete':
				$result = self::userCanDelete($title, $user);
				break;
		}

		self::$cacheUserCan[$article_id][$user_id][$do] = $result;

		wfDebugLog('wikiplaces', 'userCan: ' . $do . ' ' . ($result ? 'ALLOWED' : 'DENIED') .
				' title="' . $title->getPrefixedDBkey() . '"[' . $article_id . '] isKnown()=' . ($title->isKnown() ? 'known' : 'new') .
				' user="' . $user->getName() . '"[' . $user_id . ']' .
				' action=' . $action);

		return false; // stop hook processing, we have the answer

	}

	/**
	 * Can the user create this new Title?
	 * <ul>
	 * <li>User has to be logged in</li>
	 * <li>Title has to be in a Wikiplace namespace or user has to be allowed to WP_BYPASS_OTHERS_NS_RESTRICTIONS</li>
	 * <li>If Title is a Wikiplace homepage
	 * <ul>
	 * <li>no . in title name</li>
	 * <li>her subscription is sufficient (active, quotas, ...)</li>
	 * </ul></li>
	 * <li>If Title is a Wikiplace subpage<ul>
	 * <li>container wikiplace already exists</li>
	 * <li>user is owner of the container wikiplace</li>
	 * <li>her subscription is sufficient (active, quotas, ...)</li>
	 * </ul></li>
	 * </ul>
	 * @param Title $title A new Title, not already stored.
	 * @param User $user
	 * @return boolean true=can, false=cannot 
	 */
	private static function userCanCreate(&$title, &$user) {

		if (!$user->isLoggedIn()) {
			wfDebugLog('wikiplaces', 'userCanCreate: DENY user is not logged in, page title: "' . $title->getFullText() . '"');
			return false;
		}

		if (!WpPage::isInWikiplaceNamespaces($title->getNamespace()) && !$user->isAllowed(WP_BYPASS_OTHERS_NS_RESTRICTIONS)) {
			wfDebugLog('wikiplaces', 'userCanCreate: DENY user cannot create in non wikiplace namespace, page title: "' . $title->getFullText() . '"');
			return false;
		}

		$msg;
		$user_id = $user->getId();

		if (WpPage::isHomepage($title)) {

			// this is a new Wikiplace
			$msg = 'new wikiplace';

			if (preg_match('/[.]/', $title->getText())) {
				$result = false;
				$msg .= ', bad character in page title';
			} elseif (($reason = WpSubscription::userCanCreateWikiplace($user_id)) !== true) {
				$result = false;
				$msg .= ', ' . $reason;
			} else {
				$result = true;
			}
			
		} else {

			// this is a subpage (can be regular article or talk or file)
			$msg = 'new wikiplace item';
			$namespace = $title->getNamespace();

			$wp = WpWikiplace::getBySubpage($title->getDBkey(), $title->getNamespace());

			if ($wp === null) {
				$result = false; // no wikiplace can contain this subpage, so cannot create it
				$msg .= ', cannot find existing container Wikiplace';
				
			} elseif ( ! $wp->isOwner($user_id) ) { // checks the user who creates the page is the owner of the wikiplace
				$result = false;
				$msg .= 'current user is not Wikiplace owner';
				
			} else {

				if ($namespace == NS_FILE) {

					// the user is uploading a file
					$msg .= ', new file';

					if (($reason = WpSubscription::userCanUploadNewFile($user_id)) !== true) {
						$result = false; // no active subscription or page creation quota is exceeded
						$msg .= ', ' . $reason;
					} else {
						$result = true;
					}
					
			
				} else {

					// the user is creating a new page (regular or talk)
					$msg .= ', new subpage';

					if (($reason = WpSubscription::userCanCreateNewPage($user_id)) !== true) {
						$result = false; // no active subscription or page creation quota is exceeded
						$msg .= ', ' . $reason;
					} else {
						$result = true;
					}
					
				}
				
			}
			
		}

		wfDebugLog('wikiplaces', 'userCanCreate: ' . ($result ? 'ALLOW' : 'DENY') . ' ' . $msg . ', page title: "' . $title->getFullText() . '"');

		return $result;
		
	}

	/**
	 * For title in wikiplace namespace, checks if the current user can move it
	 * <ul>
	 * <li>User is logged in</li>
	 * <li>if page is not in a wp namespace<ul>
	 * <li>user has WP_BYPASS_OTHERS_NS_RESTRICTIONS right</li></ul></li>
	 * <li>if page is in a wp namespace<ul>
	 * <li>User has an active subscription</li>
	 * <li>Title is not a Wikiplace homepage</li>
	 * <li>User is owner of this title</li>
	 * </ul></li>
	 * </ul>
	 * @param Title $title
	 * @param User $user
	 * @return boolean 
	 */
	private static function userCanMove(&$title, &$user) {

		return ( $user->isLoggedIn() && (
				(!WpPage::isInWikiplaceNamespaces($title->getNamespace()) && $user->isAllowed(WP_BYPASS_OTHERS_NS_RESTRICTIONS) )
				||
				( ( WpSubscription::getActiveByUserId($user->getId()) != null ) &&
				!WpPage::isHomepage($title) &&
				WpPage::isOwner($title->getArticleID(), $user->getId()) ) ) );
	}

	/**
	 * For title in wikiplace namespace, checks if the current user can delete it
	 * <ul>
	 * <li>User is logged in</li>
	 * <li>if page is not in a wp namespace<ul>
	 * <li>user has WP_BYPASS_OTHERS_NS_RESTRICTIONS right</li></ul></li>
	 * <li>if page is in a wp namespace<ul>
	 * <li>Title is not a Wikiplace homepage</li>
	 * <li>User is owner of this title</li>
	 * </ul></li>
	 * </ul>
	 * @param Title $title
	 * @param User $user
	 * @return boolean 
	 */
	private static function userCanDelete(&$title, &$user) {

		return ( $user->isLoggedIn() && (
				(!WpPage::isInWikiplaceNamespaces($title->getNamespace()) && $user->isAllowed(WP_BYPASS_OTHERS_NS_RESTRICTIONS) )
				||
				(!WpPage::isHomepage($title) &&
				WpPage::isOwner($title->getArticleID(), $user->getId()) ) ) );
	}

	/**
	 * Called when creating a new article, but after onArticleSave
	 * @param WikiPage $wikipage the Article or WikiPage (object) saved. Article for MW < 1.18, WikiPage for MW >= 1.18
	 * @param User $user the user (object) who saved the article
	 * @param type $text the new article content
	 * @param type $summary the article summary (comment)
	 * @param type $isMinor minor edit flag
	 * @param type $isWatch watch the page if true, unwatch the page if false, do nothing if null (since 1.17.0)
	 * @param type $section not used as of 1.8 (automatically set to "null")
	 * @param type $flags bitfield, see source code for details; passed to Article::doedit()
	 * @param type $revision The newly inserted revision object (as of 1.11.0)
	 * @return boolean true to continue hook processing or false to abort
	 */
	public static function onArticleInsertComplete($wikipage, $user, $text, $summary, $isMinor, $isWatch, $section, $flags, $revision) {

		$title = $wikipage->getTitle();

		if (!WpPage::isInWikiplaceNamespaces($title->getNamespace())) {
			return true; // skip
		}

		$article_id = $wikipage->getId();
		$wikiplace;

		// currently, the page is already stored in 'page' db table

		if (WpPage::isHomepage($title)) {

			// create a wikiplace from this homepage				

			$wikiplace = self::doCreateWikiplace($user->getId(), $article_id);
			wfDebugLog('wikiplaces', 'onArticleInsertComplete: wikiplace created and initialized, article=[' . $article_id . ']"' . $title->getPrefixedDBkey() . '"');
		} else {

			// this is a subpage of an existing existing wikiplace

			$wikiplace = WpWikiplace::getBySubpage($title->getDBkey(), $title->getNamespace());
			if ($wikiplace === null) {
				throw new MWException('Cannot identify the container wikiplace.');
			}

		}

		$new_wp_page = WpPage::create($article_id, $wikiplace->get('wpw_id'));

		if ($new_wp_page === null) {
			throw new MWException('Error while associating new page to its container wikiplace.');
		}

		wfDebugLog('wikiplaces', 'onArticleInsertComplete: new page associated to its wikiplace, article=[' . $article_id . ']"' . $title->getPrefixedDBkey() . '"');

		// restrict applicable actions to owner, except for read
		$actions_to_rectrict = array_diff(
				$title->getRestrictionTypes(), // array( 'read', 'edit', ... )
				array('read'));
		$restrictions = array();
		foreach ($actions_to_rectrict as $action) {
			$restrictions[$action] = WP_DEFAULT_RESTRICTION_LEVEL;
		}

		$ok = false;
		wfRunHooks('SetRestrictions', array($wikipage, $restrictions, &$ok));
		if (!$ok) {
			wfDebugLog('wikiplaces', 'onArticleInsertComplete: ERROR while setting default restrictions to new page, article=[' . $wikipage->getId() . ']"' . $title->getPrefixedDBkey() . '"');
		} else {
			wfDebugLog('wikiplaces', 'onArticleInsertComplete: OK default restrictions set to new page, article=[' . $wikipage->getId() . ']"' . $title->getPrefixedDBkey() . '"');
		}

		return true;
		
	}

		

	/**
	 *
	 * @param int $user_id
	 * @param int $homepage_article_id
	 * @return WpWikiplace 
	 */
	private static function doCreateWikiplace($user_id, $homepage_article_id) {

		// creating a new wikiplace
		$subscription = WpSubscription::getLastSubscription($user_id);
		if ($subscription == null) {
			throw new MWException('Cannot create wikiplace, user has no subscription.');
		}

		$wikiplace = WpWikiplace::create($homepage_article_id, $subscription);
		if ($wikiplace == null) {
			throw new MWException('Error while creating wikiplace.');
		}

		if (!$wikiplace->forceArchiveAndResetUsage(WpSubscription::getNow())) {
			throw new MWException('Error while initialization of wikiplace usage.');
		}

		return $wikiplace;
		
	}

	/**
	 * Occurs when moving a page:
	 * <ul>
	 * <li>old page renamed</li>
	 * <li>new page created, with old name, containing a redirect to the new one</li>
	 * </ul>
	 * @param Title $old_name_title old title
	 * @param Title $new_name_title
	 * @param User $user user who did the move
	 * @param int $renamed_page_id database ID of the page that's been moved
	 * @param int $redirect_page_id database ID of the created redirect
	 * @return boolean true to continue hook processing or false to abort
	 */
	public static function onTitleMoveComplete(&$old_name_title, &$new_name_title, &$user, $renamed_page_id, $redirect_page_id) {

		$old_in_wp_ns = WpPage::isInWikiplaceNamespaces($old_name_title->getNamespace());
		$new_in_wp_ns = WpPage::isInWikiplaceNamespaces($new_name_title->getNamespace());

		if (!$old_in_wp_ns && !$new_in_wp_ns) {
			return true;
		}

		if (!$old_in_wp_ns XOR !$new_in_wp_ns) {
			// this case should'nt happened ; for now it can be managed properly
			wfDebugLog('wikiplaces', 'onTitleMoveComplete: WARNING source or destination not in wikiplace namespaces '
					. '[' . $renamed_page_id . ']"' . $old_name_title->getPrefixedDBkey() . '"'
					. ' renamed to "' . $new_name_title->getPrefixedDBkey() . '", redirect[' . $redirect_page_id . ']');
		}

		wfDebugLog('wikiplaces', 'onTitleMoveComplete: '
				. '[' . $renamed_page_id . ']"' . $old_name_title->getPrefixedDBkey() . '"'
				. ' renamed to "' . $new_name_title->getPrefixedDBkey() . '", redirect[' . $redirect_page_id . ']');

		$dest_wikiplace;

		if (WpPage::isHomepage($new_name_title)) {

			$dest_wikiplace = self::doCreateWikiplace($user->getId(), $renamed_page_id);
			wfDebugLog('wikiplaces', 'onTitleMoveComplete: new wikiplace created and initialized, article="' . $new_name_title->getPrefixedDBkey() . '"');
		} else {

			// destination is a subpage
			$dest_wikiplace = WpWikiplace::getBySubpage($new_name_title->getDBkey(), $new_name_title->getNamespace());
			if ($dest_wikiplace == null) {
				throw new MWException('Error while searching destination wikiplace.');
			}
			
		}

		// move the page if necessary
		$renamed_wp_page = WpPage::getByArticleId($renamed_page_id);
		if ($renamed_wp_page == null) {
			throw new MWException('Error while searching Wikiplace page to move.');
		}

		$dest_wp_id = $dest_wikiplace->get('wpw_id');
		$old_wp_id = $renamed_wp_page->get('wppa_wpw_id');
		if ($dest_wp_id != $old_wp_id) {
			// wikiplace has changed
			wfDebugLog('wikiplaces', 'onTitleMoveComplete: changing container wikiplace for page "' . $new_name_title->getPrefixedDBkey() . '"');
			$renamed_wp_page->setWikiplaceId($dest_wp_id);
		}

		// if a redirect was created, associate this new page to original wikiplace

		if ($redirect_page_id != 0) {
			wfDebugLog('wikiplaces', 'onTitleMoveComplete: associating the redirect to old wikiplace, old title="' . $old_name_title->getPrefixedDBkey() . '"');
			if (WpPage::create($redirect_page_id, $old_wp_id) == null) {
				throw new MWException('Error while associating redirect subpage to its container wikiplace.');
			}
		}

		return true;
	}

	/**
	 *
	 * @param WikiPage $article the article that was deleted. WikiPage in MW >= 1.18, Article in 1.17.x and earlier.
	 * @param User $user the user that deleted the article
	 * @param string $reason
	 * @param int $id id of the article that was deleted (added in 1.13)
	 * @return boolean true to continue hook processing or false to abort
	 */
	public static function onArticleDeleteComplete(&$article, &$user, $reason, $id) {

		if (!WpPage::isInWikiplaceNamespaces($article->getTitle()->getNamespace())) {
			return true;
		}

		wfDebugLog('wikiplaces', 'onArticleDeleteComplete: article=[' . $id . ']"' . $article->getTitle()->getPrefixedDBkey() . '"');

		if (!WpPage::delete($id)) {
			throw new MWException('Error while deleting the Wikiplace page.');
		}

		return true;
	}

	/**
	 *
	 * @param Title $title
	 * @param boolean $create
	 * @param string $comment 
	 * @return boolean true to continue hook processing or false to abort
	 */
	public static function onArticleUndelete(&$title, $create, $comment) {

		$namespace = $title->getNamespace();

		if (!WpPage::isInWikiplaceNamespaces($namespace)) {
			return true;
		}

		if (WpPage::isHomepage($title)) {

			// WARNING, this case shouldn't be allowed and we should arrive here, because where are not sure the user restoring
			// the wikiplace is the effective owner (ie: an admin, another artist, ?)
			// so, who is this wikiplace owner ?
			wfDebugLog('wikiplaces', 'onArticleUndelete: ERROR wikiplace homepage restored, unknown owner, article="' . $title->getPrefixedDBkey() . '"');
			throw new MWException('Error: wikiplace homepage restored, but unknown owner.');
		} else {

			// restoring a subpage
			$wp = WpWikiplace::getBySubpage($title->getDBkey(), $namespace);
			if ($wp == null) {
				throw new MWException('Error while searching container wikiplace.');
			}

			if (WpPage::create($title->getArticleID(), $wp->get('wpw_id')) == null) {
				throw new MWException('Error while associating the restored subpage to its container wikiplace.');
			}

			wfDebugLog('wikiplaces', 'onArticleUndelete: article=[' . $title->getArticleID() . ']"' . $title->getPrefixedDBkey() . '" restored in wikiplace [' . $wp->get('wpw_id') . ']');
		}

		return true;
	}

	/**
	 * Search for a subscription attached to this transaction, and if found, update it.
	 * @param array $tmr
	 * @return boolean False (=stop hook) only if the transaction is for a subscription.
	 */
	public static function onTransactionUpdated($tmr) {

		$sub = WpSubscription::getByTransactionId($tmr['tmr_id']);
		if ($sub === null) {
			return true; // we are not concerned, so don't stop processing
		}

		$sub->onTransactionUpdated($tmr);

		return false; // the transaction update has been processed, so no other hook should take care of it 
		
	}

	
	/**
	 *
	 * @param Title $title
	 * @param User $user
	 * @param boolean
	 */
	public static function isOwner($title, $user, &$result) {

		if (!WpPage::isInWikiplaceNamespaces($title->getNamespace()) || !$title->isKnown()) {
			return true; // skip
		}

		$article_id = $title->getArticleID();
		$user_id = $user->getId();

		$result = WpPage::isOwner($article_id, $user_id);

		wfDebugLog('wikiplaces', 'isOwner: ' . ($result ? 'YES' : 'NO')
				. ', title=[' . $article_id . ']"' . $title->getPrefixedDBkey() .
				'", user=[' . $user_id . ']"' . $user->getName() . '"');

		/* 		if ( ! $result ) {
		  wfDebugLog( 'wikiplaces',  wfGetPrettyBacktrace() );
		  }
		 */
		return false; // stop hook processing, because we have the answer
		
	}


    /**
     * skinTemplateOutputPageBeforeExec hook
     * 
     * Cooks the skin template Seizam-Style!
     * 
     * @param SkinSkinzam $skin
     * @param SkinzamTemplate $tpl
     */
    public static function skinTemplateOutputPageBeforeExec(&$skin, &$tpl) {
        $background = array();
        $background['url'] = false;
        $navigation['content'] = false;

        $ns = $skin->getRelevantTitle()->getNamespace();
        if (WpPage::isInWikiplaceNamespaces($ns)) {
            $wikiplaceText = WpWikiplace::extractWikiplaceRoot($skin->getRelevantTitle()->getDBkey(), $ns);

            // Wikiplace Background
            $backgroundText = $wikiplaceText . '/' . WPBACKGROUNDKEY;
            $backgroundTitle = Title::newFromText($backgroundText, NS_WIKIPLACE);
            $backgroundPage = WikiPage::factory($backgroundTitle);
            $backgroundPageContent = $backgroundPage->getText();
            if ($backgroundPageContent) {
                $pattern = '/^https?\:\/\/[\w\-%\.\/\?\&]*\.(jpe?g|png|gif)$/i';
                if (preg_match($pattern, $backgroundPageContent)) {
                    $background['url'] = $backgroundPageContent;
                }
            }

            // Wikiplace Navigation Menu
            $navigationText = $wikiplaceText . '/' . WPNAVIGATIONKEY;
            $navigationTitle = Title::newFromText($navigationText, NS_WIKIPLACE);
            $navigationPage = WikiPage::factory($navigationTitle);
            $navigationPageContent = $navigationPage->getText();
            if ($navigationPageContent) {
                $navigation['content'] = $navigationPageContent;
            }
        }
        $tpl->set('wp_background', $background);
        $tpl->set('wp_navigation', $navigation);
        return true;
    }

}
