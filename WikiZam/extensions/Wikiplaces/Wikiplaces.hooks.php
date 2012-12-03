<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

class WikiplacesHooks {

    private static $cacheUserCan = array(); // ['title']['user']

    # Schema updates for update.php

    public static function onLoadExtensionSchemaUpdates(DatabaseUpdater $updater) {

        $tables = array(
            'wp_plan',
            'wp_subscription',
            'wp_old_usage',
            'wp_wikiplace',
            'wp_page',
            'wp_old_subscription',
            'wp_invitation',
            'wp_invitation_category',
            'wp_wpi_wpp',
        );

        $mysql_dir = dirname(__FILE__) . '/schema/mysql';
        foreach ($tables as $table) {
            $updater->addExtensionUpdate(array('addTable', $table, "$mysql_dir/$table.sql", true));
        }

        $db = wfGetDB(DB_MASTER);

        if ($db->tableExists('wp_subscription') && !$db->fieldExists('wp_subscription', 'wps_wpi_id', __METHOD__)) {
            $db->sourceFile("$mysql_dir/add_wps_wpi_field.sql");
        }

        if ($db->tableExists('wp_old_subscription') && !$db->fieldExists('wp_old_subscription', 'wpos_wpi_id', __METHOD__)) {
            $db->sourceFile("$mysql_dir/add_wpos_wpi_field.sql");
        }

        return true;
    }

    /**
     * @param Title $title the article (Article object) being saved
     * @param User $user the user (User object) saving the article
     * @param string $action the action
     * @param array $result User permissions error to add
     */
    public static function getUserPermissionsErrors($title, &$user, $action, &$result) {

        $namespace = $title->getNamespace();

        // fast checks first
        if (($action == 'read')) {
            return true; // skip
        }

        // skip if the title is not in a wikiplace namespace
        if (!WpPage::isInWikiplaceNamespaces($namespace)) {

            // some actions have to be forbidden when not in wikiplaces
            /** @todo $wgWpSubscribersExtraRights is not the best name */
            global $wgWpSubscribersExtraRights;
            if (!$user->isAllowed(WP_ADMIN_RIGHT) && in_array($action, $wgWpSubscribersExtraRights)) {

                wfDebugLog('wikiplaces-debug', "$action forbidden (require susbcriber extra right) for {$title->getPrefixedDBkey()}");
                $result = false; // disallow
                return false; // stop hook processing
            } else {

                return true; // skip
            }
        }

        $db_key = $title->getDBkey();

        // dispatch for public and admin items
        if (WpPage::isPublic($namespace, $db_key)) {
            return self::publicUserCan($title, $user, $action, $result);
        } elseif (WpPage::isAdmin($namespace, $db_key)) {
            return self::adminUserCan($title, $user, $action, $result);
        }

        // now, we are sure that title isInWikiplace()
        $article_id = $title->getArticleID();
        $user_id = $user->getId();
        $do = null;

        // evaluate which action we are doing 
        switch ($action) {
            case 'create':
            case 'edit':
            case 'upload':
            case 'createpage':
            case 'move-target':
                if (!$title->isKnown()) {
                    $do = 'create';
                }
                break;
            case 'move':
            case 'delete':
            case 'autopatrol':
                $do = $action;
                break;
        }

        if ($do === null) {
            return true; // action not handled here, so continue hook processing to let MW find an answer
        }

        // try to read the answer from cache
        if (isset(self::$cacheUserCan[$article_id][$user_id][$do])) {
            $result = self::$cacheUserCan[$article_id][$user_id][$do];
            return empty($result); // stop hook processing if an error in result
        }

        // only connected users can create/move/delete
        if (!$user->isLoggedIn()) {
            wfDebugLog('wikiplaces-debug', "$do DENIED, user is not logged in");
            $result = array('wp-notloggedin'); // formers: 'notloggedin' and 'movenologin'
        } else {
            // so, the title is in a wikiplace namespace, and the user is connected
            switch ($do) {
                case 'create':
                    $result = self::wikiplaceUserCanCreate($title, $user);
                    break;
                case 'move':
                    $result = self::wikiplaceUserCanMove($title, $user);
                    break;
                case 'delete':
                    $result = self::wikiplaceUserCanDelete($title, $user);
                    break;
                case 'autopatrol':
                    $result = self::wikiplaceUserCanAutopatrol($title, $user);
                    break;
            }
            wfDebugLog('wikiplaces', "$do($action) " . (empty($result) ? 'ALLOWED' : 'DENIED') .
                    " on {$title->getPrefixedDBkey()}($article_id) " . ($title->isKnown() ? '(title known)' : '(new title)') .
                    " for user {$user->getName()}($user_id)");
        }

        // store in cache
        self::$cacheUserCan[$article_id][$user_id][$do] = $result;

        return empty($result); // stop hook processing if result = disallow
    }

    /**
     * @param Title $title the article (Article object) being saved
     * @param User $user the user (User object) saving the article
     * @param string $action the action
     * @param array $result 
     */
    public static function publicUserCan($title, &$user, $action, &$result) {

        if (( $action == 'move' || $action == 'move-target' || $action == 'delete' ) && !($user->isAllowed(WP_ADMIN_RIGHT))) {
            $result = array('forbidden-admin-action');
            return false; // forbidden, that's it .
        }
        return true; // skip
    }

    /**
     * @param Title $title the article (Article object) being saved
     * @param User $user the user (User object) saving the article
     * @param string $action the action
     * @param array $result 
     */
    public static function adminUserCan($title, &$user, $action, &$result) {

        if ($action == 'read' || $user->isAllowed(WP_ADMIN_RIGHT)) {
            return true; // allowed, but other extensions can still change this
        }

        $result = array('forbidden-admin-action');
        ;
        return false; // forbidden, that's it .
    }

    /**
     * Can the user create this new Title?
     * @param Title $title A new Title (=not known/not stored) in a Wikiplace namespace
     * @param User $user A logged in user
     * @return array Empty array = can, array containing i18n key + args = cannot 
     */
    public static function wikiplaceUserCanCreate(&$title, &$user) {

        // in userCan() calling this function, we already checked that user is loggedin

        $msg;
        $user_id = $user->getId();

        // ensure we are not creating a duplicate in wikiplace
        if (class_exists('TitleKey') && ($duplicate = TitleKey::exactMatchTitle($title))) {
            wfDebugLog('wikiplaces-debug', "user cannot create {$title->getPrefixedDBkey()} duplicate {$duplicate->getPrefixedDBkey()} exists");
            return array('wp-duplicate-exists', $duplicate->getPrefixedDBkey());
        }

        // ensure to keep "talk page" <-> "regular page" mirrored named
        if ($title->isTalkPage()) {

            // ensure to not create a talk having a subject in different case ( ie: Talk:wp/page but wp/PAGE exists )
            $subject = $title->getSubjectPage();
            if (!$subject->isKnown() && class_exists('TitleKey') && ( $duplicate_subject = TitleKey::exactMatchTitle($subject) )) {
                // the subject page doesn't exist, but there is a subject page with different case, abort
                wfDebugLog('wikiplaces-debug', "user cannot create {$title->getPrefixedDBkey()} duplicate subject {$duplicate_subject->getPrefixedDBkey()} exists");
                return array('wp-duplicate-related', $duplicate_subject->getPrefixedDBkey(), $duplicate_subject->getTalkPage()->getPrefixedDBkey());
            }
        } else {

            // ensure to not create an article with an existing talk in different case
            $talk = $title->getTalkPage();
            if (!$talk->isKnown() && class_exists('TitleKey') && ( $duplicate_talk = TitleKey::exactMatchTitle($talk) )) {
                // the talk page doesn't exist, but there is a talk page with different case, abort
                wfDebugLog('wikiplaces-debug', "user cannot create {$title->getPrefixedDBkey()} duplicate talk {$duplicate_talk->getPrefixedDBkey()} exists");
                return array('wp-duplicate-related', $duplicate_talk->getPrefixedDBkey(), $duplicate_talk->getSubjectPage()->getPrefixedDBkey());
            }
        }

        if (WpPage::isHomepage($title)) {

            // this is a new Wikiplace
            $msg = 'new wikiplace';

            if (($reason = WpSubscription::userCanCreateWikiplace($user_id)) !== true) {
                $result = array($reason);
                $msg .= ', ' . $reason;
            } elseif (WpWikiplace::isBlacklistedWikiplaceName($title->getDBkey())) {
                $result = array('badtitletext');
                $msg .= ', blacklisted name';
            } elseif (preg_match('/[.]/', $title->getText())) {
                $result = array('badtitletext');
                $msg .= ', bad character in page title';
            } else {
                $result = array();
            }
        } else {

            // this can be regular article or talk or file)

            $namespace = $title->getNamespace();

            if ($namespace == NS_FILE || $namespace == NS_FILE_TALK) {

                // the user is uploading a file or creating a file talk
                $msg = 'new file/file_talk';
                $db_key = $title->getDBkey();

                if (WpPage::isPublic($namespace, $db_key)) {
                    $msg .= ', in public space';
                    $result = array();
                } elseif (WpPage::isAdmin($namespace, $db_key)) {

                    $msg .= ', in admin space';

                    if ($user->isAllowed(WP_ADMIN_RIGHT)) {
                        $result = array();
                    } else {
                        $msg .= ', user not admin';
                        $result = array('protectedpage');
                    }
                } else {

                    $msg .= ', attached to a wikiplace';
                    $wp = WpWikiplace::getBySubpage($db_key, $title->getNamespace());

                    if ($wp === null) {
                        $result = array('wp-no-container-found'); // no wikiplace can contain this, so cannot create it
                        $msg .= ', cannot find existing container Wikiplace';
                    } elseif ($user->isAllowed(WP_ADMIN_RIGHT)) {
                        // admin is working for someone else
                        $result = array();
                        $msg .= ', admin is working for someone else';
                    } elseif (!$wp->isOwner($user_id)) { // checks the current user is the owner of the wikiplace
                        $result = array('wp-not-owner');
                        $msg .= ', current user is not Wikiplace owner';
                    } else {

                        $reason;
                        if ($namespace == NS_FILE) {
                            $reason = WpSubscription::userCanUploadNewFile($user_id);
                        } else {
                            $reason = WpSubscription::userCanCreateNewPage($user_id);
                        }

                        if ($reason !== true) {
                            $result = array($reason); // no active subscription or page creation quota is exceeded
                            $msg .= ', ' . $reason;
                        } else {
                            $result = array();
                        }
                    }
                }
            } else {

                // the user is creating a new page (regular or talk, but not a file or file_talk)
                $msg = ', new subpage';

                $wp = WpWikiplace::getBySubpage($title->getDBkey(), $title->getNamespace());

                if ($wp === null) {
                    $result = array('wp-no-container-found'); // no wikiplace can contain this subpage, so cannot create it
                    $msg .= ', cannot find existing container Wikiplace';
                } elseif ($user->isAllowed(WP_ADMIN_RIGHT)) {
                    // admin is creating a subpage for someone else
                    $result = array();
                    $msg .= ', admin is creating a subpage for someone else';
                } elseif (!$wp->isOwner($user_id)) { // checks the user who creates the page is the owner of the wikiplace
                    $result = array('wp-not-owner');
                    $msg .= ', current user is not Wikiplace owner';
                } elseif (($reason = WpSubscription::userCanCreateNewPage($user_id)) !== true) {
                    $result = array($reason); // no active subscription or page creation quota is exceeded
                    $msg .= ', ' . $reason;
                } else {
                    $result = array();
                }
            }
        }

        wfDebugLog('wikiplaces-debug', "user can" . (empty($result) ? '' : 'not') . " create {$title->getFullText()}: $msg");

        return $result;
    }

    /**
     * For title in wikiplace namespace, checks if the current user can move it
     * @param Title $title Title in a wikiplace namespace
     * @param User $user Logged in user
     * @return array Empty array = can, array containing i18n key + args = cannot 
     */
    private static function wikiplaceUserCanMove(&$title, &$user) {

        $back = array();

        if (WpPage::isHomepage($title)) {
            $back[] = 'badarticleerror';
        } elseif ($user->isAllowed(WP_ADMIN_RIGHT)) {
            // ok
        } elseif (WpSubscription::newActiveByUserId($user->getId()) == null) {
            $back[] = 'wp-no-active-sub';
        } elseif (!WpPage::isOwner($title->getArticleID(), $user)) {
            $back[] = 'wp-not-owner';
        }

        return $back;
    }

    /**
     * For title in wikiplace namespace, checks if the current user can delete it
     * @param Title $title Title in a wikiplace namespace
     * @param User $user Loggedin user
     * @return array Empty array = can, array containing i18n key + args = cannot 
     */
    private static function wikiplaceUserCanDelete(&$title, &$user) {

        $back = array();

        if ($user->isAllowed(WP_ADMIN_RIGHT)) {
            // ok
        } elseif (WpPage::isHomepage($title)) {
            $back[] = 'badarticleerror';
        } elseif (!WpPage::isOwner($title->getArticleID(), $user)) {
            $back[] = 'wp-not-owner';
        }

        return $back;
    }

    /**
     * For title in wikiplace namespace, checks if the current user can autopatrol the edit.
     * @param type $title
     * @param type $user 
     * @return array Empty array = can, array containing i18n key + args = cannot 
     */
    private static function wikiplaceUserCanAutopatrol($title, $user) {
        $back = array();
        
        if (!$user->isAllowed(WP_ADMIN_RIGHT) && !WpPage::isOwner($title->getArticleID(), $user)) {
            $back[] = 'wp-not-owner';
        }
        
        return $back;
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
        $namespace = $title->getNamespace();
        $db_key = $title->getDBkey();

        if (!WpPage::isInWikiplace($namespace, $db_key)) {
            return true; // skip
        }

        $article_id = $wikipage->getId();
        $user_id = $user->getId();
        $wikiplace = null;
        $pdb_key = $title->getPrefixedDBkey(); // only used by log messages
        // currently, the page is already stored in 'page' db table

        if (WpPage::isHomepage($title)) {

            // create a wikiplace from this homepage				
            $wikiplace = self::doCreateWikiplace($user_id, $article_id);
            if ($wikiplace === null) {
                wfDebugLog('wikiplaces', 'onArticleInsertComplete(): ERROR while creating wikiplace "' . $pdb_key . '" for user[' . $user_id . ']');
                // throw new MWException('Error while creating wikiplace.');
                return true;
            }
        } else {

            // this is a subpage, searching existing container wikiplace
            $wikiplace = WpWikiplace::getBySubpage($db_key, $namespace);
            if ($wikiplace === null) {
                wfDebugLog('wikiplaces', 'onArticleInsertComplete(): ERROR cannot identify container wikiplace "' . $pdb_key . '" for user[' . $user_id . ']');
                // throw new MWException('Cannot identify the container wikiplace.');
                return true;
            }
        }

        $new_wp_page = WpPage::create($article_id, $wikiplace->getId());

        if ($new_wp_page === null) {
            wfDebugLog('wikiplaces', 'onArticleInsertComplete(): ERROR while associating new page to its container wikiplace "' . $pdb_key . '"  for user[' . $user_id . ']');
            // throw new MWException('Error while associating new page to its container wikiplace.');
            return true;
        }

        if (in_array($namespace, array(NS_MAIN, NS_FILE, NS_WIKIPLACE))) {

            // restrict applicable actions to owner, except for read

            $actions_to_rectrict = array_diff(
                    $title->getRestrictionTypes(), // array( 'read', 'edit', ... )
                    array('read'));
            $restrictions = array();
            foreach ($actions_to_rectrict as $action) {
                $restrictions[$action] = WP_DEFAULT_RESTRICTION_LEVEL;
            }

            $ok = false;
            wfRunHooks('POSetProtection', array($wikipage, $restrictions, &$ok));

            if (!$ok) {
                wfDebugLog('wikiplaces', 'onArticleInsertComplete(): WARNING Wikiplace proccess OK but ERROR while setting default restrictions to new page, article=[' . $article_id . ']"' . $pdb_key . '"  for user[' . $user_id . ']');
            } else {
                wfDebugLog('wikiplaces-debug', 'onArticleInsertComplete(): OK, article=[' . $article_id . ']"' . $pdb_key . '"  for user[' . $user_id . ']');
            }
        } else {
            wfDebugLog('wikiplaces-debug', 'onArticleInsertComplete(): OK, no default restrictions to set, article=[' . $article_id . ']"' . $pdb_key . '"  for user[' . $user_id . ']');
        }

        return true;
    }

    /**
     * Try to create a wikiplace owned by the given user.
     * <ul>
     * <li>try to fetch last user subscription</li>
     * <li>try to create new wikiplace record linked to $homepage_article_id</li>
     * <li>try to initialize the new wikiplace usage</li>
     * </ul>
     * @param int $user_id
     * @param int $homepage_article_id
     * @return WpWikiplace or null if an error occured
     */
    private static function doCreateWikiplace($user_id, $homepage_article_id) {

        // creating a new wikiplace
        $subscription = WpSubscription::newByUserId($user_id);
        if ($subscription == null) {
            wfDebugLog('wikiplaces', 'doCreateWikiplace() ERROR cannot create wikiplace, user has no subscription, user=' . $user_id . ' article_id=' . $homepage_article_id);
            return null;
        }

        $wikiplace = WpWikiplace::create($homepage_article_id, $subscription);
        if ($wikiplace == null) {
            wfDebugLog('wikiplaces', 'doCreateWikiplace() ERROR while creating wikiplace, user=' . $user_id . ' article_id=' . $homepage_article_id);
            return null;
        }

        if (!$wikiplace->forceArchiveAndResetUsage()) {
            wfDebugLog('wikiplaces', 'doCreateWikiplace() ERROR while initialization of wikiplace usage, user=' . $user_id . ' article_id=' . $homepage_article_id);
            return null;
        }

        return $wikiplace;
    }

    /**
     * Occurs when a page  has been moved. If there is a problem, it's too late to cancel it.
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

        $old_in_wp = WpPage::isInWikiplace($old_name_title->getNamespace(), $old_name_title->getDBkey());
        $new_in_wp = WpPage::isInWikiplace($new_name_title->getNamespace(), $new_name_title->getDBkey());

        if (!$old_in_wp && !$new_in_wp) {
            return true;
        }

        $old_pdb_key = $old_name_title->getPrefixedDBkey();
        $new_pdb_key = $new_name_title->getPrefixedDBkey();

        wfDebugLog('wikiplaces-debug', "moved $old_pdb_key($renamed_page_id) to $new_pdb_key " . ( ($redirect_page_id != 0) ? "with a redirect ($redirect_page_id)" : "without redirect"));

        if (!$old_in_wp) { // from  something not in wp
            if (WpPage::isHomepage($new_name_title)) { // from  something not in wp  to  a homepage
                $dest_wp = self::doCreateWikiplace($user->getId(), $renamed_page_id);
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR cannot create wikiplace '$new_pdb_key'");
                    // throw new MWException('Cannot create wikiplace.');
                    return true;
                }
                $new_wp_page = WpPage::create($renamed_page_id, $dest_wp->getId());
                if ($new_wp_page == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot create wikiplace homepage '$new_pdb_key'");
                    // throw new MWException('Cannot create wikiplace homepage.');
                }
            } else { // from  something not in wp  to  a subpage
                $dest_wp = WpWikiplace::getBySubpage($new_name_title->getDBkey(), $new_name_title->getNamespace());
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot find container wikiplace for '$new_pdb_key'");
                    // throw new MWException('Cannot find container wikiplace.');
                    return true;
                }
                $new_wp_page = WpPage::create($renamed_page_id, $dest_wp->getId());
                if ($new_wp_page == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot create wikiplace homepage '$new_pdb_key'");
                    // throw new MWException('Cannot create wikiplace homepage.');
                }
            }
        } elseif (WpPage::isHomepage($old_name_title)) { // from  a homepage
            // currently, this case is forbidden and cannot occur because items become orphan... but, now, it's too late :(
            wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot move homepage '$old_pdb_key'");
            // throw new MWException('Cannot move wikiplace homepage.');

            if (!$new_in_wp) { // from  a homepage  to  something not in wp
            } elseif (WpPage::isHomepage($new_name_title)) { // from  a homepage  to  a homepage
            } else { // from  a homepage  to  a subpage
            }
        } else { // from  a subpage
            if (!$new_in_wp) { // from  a subpage  to  something not in wp
                // currently, this case is forbidden and should not occur, but actually it can be moved
                wfDebugLog('wikiplaces', "onTitleMoveComplete() WARNING moving a subpage out of wikiplace space '$new_pdb_key'");

                if ($redirect_page_id != 0) {
                    $renamed_wp_page = WpPage::newFromArticleId($renamed_page_id);
                    if ($renamed_wp_page == null) {
                        wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot find subpage to move ($renamed_page_id)");
                        // throw new MWException('Cannot find subpage to move.');
                        return true;
                    }
                    $renamed_wp_page->setPageId($redirect_page_id);
                } else {
                    if (!WpPage::delete($renamed_page_id)) {
                        wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: while deleting the Wikiplace page ($renamed_page_id)");
                        // throw new MWException('Error while deleting the Wikiplace page.');
                    }
                }
            } elseif (WpPage::isHomepage($new_name_title)) { // from  a subpage  to  a homepage
                $dest_wp = self::doCreateWikiplace($user->getId(), $renamed_page_id);
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot create wikiplace '$new_pdb_key'");
                    // throw new MWException('Cannot create wikiplace.');
                    return true;
                }
                $renamed_wp_page = WpPage::newFromArticleId($renamed_page_id);
                if ($renamed_wp_page == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot find subpage to move ($renamed_page_id)");
                    // throw new MWException('Cannot find subpage to move.');
                    return true;
                }
                if ($redirect_page_id != 0) {
                    $old_wp_id = $renamed_wp_page->getWikiplaceId();
                    if (WpPage::create($redirect_page_id, $old_wp_id) == null) {
                        wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot create redirect subpage ($renamed_page_id)");
                        // throw new MWException('Cannot create redirect subpage.');
                        return true;
                    }
                }
                $dest_wp_id = $dest_wp->getId();
                $renamed_wp_page->setWikiplaceId($dest_wp_id);
            } else { // from  a subpage  to  a subpage
                $dest_wp = WpWikiplace::getBySubpage($new_name_title->getDBkey(), $new_name_title->getNamespace());
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot find destination wikiplace '$new_pdb_key'");
                    // throw new MWException('Cannot find destination wikiplace.');
                    return true;
                }
                $renamed_wp_page = WpPage::newFromArticleId($renamed_page_id);
                if ($renamed_wp_page == null) {
                    wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot find subpage to move '$renamed_page_id'");
                    // throw new MWException('Cannot find subpage to move.');
                    return true;
                }
                $old_wp_id;
                if ($redirect_page_id != 0) {
                    $old_wp_id = $renamed_wp_page->getWikiplaceId();
                    if (WpPage::create($redirect_page_id, $old_wp_id) == null) {
                        wfDebugLog('wikiplaces', "onTitleMoveComplete() ERROR: cannot create redirect subpage ($renamed_page_id)");
                        // throw new MWException('Cannot create redirect subpage.');
                        return true;
                    }
                }
                $dest_wp_id = $dest_wp->getId();
                if ($old_wp_id != $dest_wp_id) {
                    $renamed_wp_page->setWikiplaceId($dest_wp_id);
                }
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

        wfDebugLog('wikiplaces-debug', 'onArticleDeleteComplete() article=[' . $id . ']"' . $article->getTitle()->getPrefixedDBkey() . '"');

        if (!WpPage::delete($id)) {
            wfDebugLog('wikiplaces', 'onArticleDeleteComplete() ERROR: while deleting the Wikiplace page [' . $article->getTitle()->getPrefixedDBkey() . ']');
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

        $article_id = $title->getArticleID();
        $pdb_key = $title->getPrefixedDBkey();

        wfDebugLog('wikiplaces-debug', 'onArticleUndelete() trying to restore article=[' . $article_id . ']"' . $pdb_key);

        if (WpPage::isHomepage($title)) {

            // WARNING, this case shouldn't be allowed and we should arrive here, because where are not sure the user restoring
            // the wikiplace is the effective owner (ie: an admin, another artist, ?)
            // so, who is this wikiplace owner ?
            wfDebugLog('wikiplaces', 'onArticleUndelete() ERROR wikiplace homepage restored, unknown owner, article="' . $pdb_key . '"');
            throw new MWException('Error: wikiplace homepage restored, but unknown owner.');
        } else {

            // restoring a subpage
            $wp = WpWikiplace::getBySubpage($title->getDBkey(), $namespace);
            if ($wp == null) {
                wfDebugLog('wikiplaces', 'onArticleUndelete() ERROR while searching container wikiplace, article=[' . $article_id . ']"' . $pdb_key . '"');
                throw new MWException('Error while searching container wikiplace.');
            }

            $wp_id = $wp->getId();
            if (WpPage::create($article_id, $wp_id) == null) {
                wfDebugLog('wikiplaces', 'onArticleUndelete() ERROR while associating restored subpage to its wikiplace, article=[' . $article_id . ']"' . $pdb_key . '"');
                throw new MWException('Error while associating the restored subpage to its container wikiplace.');
            }

            wfDebugLog('wikiplaces-debug', 'onArticleUndelete() article [' . $article_id . '] restored in wikiplace [' . $wp_id . ']');
        }

        return true;
    }

    /**
     * Search for a subscription attached to this transaction, and if found, update it.
     * @param array $tmr
     * @return boolean False (=stop hook) only if the transaction is for a subscription.
     */
    public static function onTransactionUpdated($tmr) {

        $sub = WpSubscription::newFromTransactionId($tmr['tmr_id']);
        if ($sub === null) {
            return true; // we are not concerned, so don't stop processing
        }

        $sub->onTransactionUpdated($tmr);

        return false; // the transaction update has been processed, so no other hook should take care of it 
    }

    /**
     * Hook to <ul>
     * <li>correct the action menu that displays "delete" a bit too much.
     * A direct correction would be to change the logic directly in SkinTemplate::buildContentNavigationUrls.
     * The menu builder now uses if( $wgUser->isAllowed( 'delete' ) ) ...
     * A better builder would use if( $$title->quickUserCan( 'delete' ) ) ...</li>
     * <li>add a "Set as background" action for files</li>
     * </ul>
     * @param SkinTemplate $skinTemplate
     * @param Array $content_navigation
     * @return Boolean True (=continue hook)
     * 
     */
    public static function SkinTemplateNavigation(&$skinTemplate, &$content_navigation) {
        $title = $skinTemplate->getRelevantTitle();

        // removes "delete" action if necessary
        if (isset($content_navigation['actions']['delete']) && !$title->quickUserCan('delete')) {
            unset($content_navigation['actions']['delete']);
        }

        // adds a "Set as background" action for files
        global $wgUser;

        if (WpWikiplace::isTitleValidForBackground($title) &&
                count(WpWikiplace::factoryAllOwnedByUserId($wgUser->getId())) != 0) {
            $content_navigation['actions']['background'] = array(
                'class' => false,
                'text' => wfMessage('wp-background-action')->text(),
                'href' => SpecialWikiplaces::getLocalUrlForSetAsBackground($title->getPrefixedDBkey()));
        }

        return true;
    }
    
    /**
     * If the page is in a Wikiplace namespace, search the owner and answer.
     * If the page is in a Wikiplace namespace but cannot be found, state only 
     * admins users are owner
     * @param Title $title
     * @param User $user
     * @param boolean
     */
    public static function isOwner($title, $user, &$result) {

        $namespace = $title->getNamespace();
        $db_key = $title->getDBkey();

        if (WpPage::isPublic($namespace, $db_key)
                || !WpPage::isInWikiplaceNamespaces($namespace)
                || !$title->isKnown()
                || WpPage::isAdmin($namespace, $db_key)) {
            return true; // skip
        }

        $article_id = $title->getArticleID();
        $user_id = $user->getId();

        $result = WpPage::isOwner($article_id, $user);

        wfDebugLog('wikiplaces-debug', "{$user->getName()}($user_id) is" . (($result ? '' : ' not')) . " owner of {$title->getPrefixedDBkey()}($article_id)" . wfGetPrettyBacktrace());

        return false; // stop hook processing, because we have the answer
    }

    /**
     * Called by img_auth.php when a file has fully been sent to a client
     * @param Title $title
     * @param string $filename
     * @return boolean Always returns true
     */
    public static function onImgAuthFullyStreamedFile(&$title, $filename) {
        $namespace = $title->getNamespace();
        $db_key = $title->getDBkey();

        // skip if the file looks not attached to a wikiplace
        if (!WpPage::isInWikiplace($namespace, $db_key)) {
            return true; // nothing to do
        }

        // get file infos
        $stat = stat($filename);
        if (!$stat) {
            return true; // should not occur, but just in case, avoid a PHP error
        }

        // prepare update infos
        $root = WpWikiplace::extractWikiplaceRoot($db_key, $namespace);
        $size = $stat['size']; // in bytes

        WpWikiplace::updateBandwidthUsage($root, $size);

        return true;
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
        $background['url'] = false;
        $navigation['content'] = false;
        $headerTitle['content'] = false;

        $title = $skin->getRelevantTitle();
        $ns = $title->getNamespace();
        if ($title->exists() && WpPage::isInWikiplaceNamespaces($ns)) {
            $explosion = WpWikiplace::explodeWikipageKey($title->getText(), $ns);
            $wikiplaceKey = $explosion[0];

            // Wikiplace Background?g|png|gif)$/i';
            $background['url'] = self::getBackgroundUrl($wikiplaceKey);

            // Wikiplace Navigation Menu
            $navigationKey = $wikiplaceKey . '/' . WPNAVIGATIONKEY;
            $navigationTitle = Title::newFromText($navigationKey, NS_WIKIPLACE);
            $navigationPage = WikiPage::factory($navigationTitle);
            $navigationText = $navigationPage->getText();
            if ($navigationText) {
                $navigationArticle = Article::newFromTitle($navigationTitle, $skin->getContext());
                $navigation['content'] = $navigationArticle->getOutputFromWikitext($navigationText)->getText();
            }
        }
        $tpl->set('wp_background', $background);
        $tpl->set('wp_navigation', $navigation);
        return true;
    }

    private static function getBackgroundUrl($wikiplaceKey) {
        $backgroundKey = $wikiplaceKey . '/' . WPBACKGROUNDKEY;
        $backgroundTitle = Title::newFromText($backgroundKey, NS_WIKIPLACE);
        $backgroundPage = WikiPage::factory($backgroundTitle);
        $backgroundText = $backgroundPage->getText();
        if (!$backgroundText) {
            return false;
        }

        $patternForFile = '/^\[\[File:([^\]\|]+).*\]\]/m';
        $matchesForFile = array();
        if (preg_match($patternForFile, $backgroundText, $matchesForFile)) {
            $fileKey = $matchesForFile[1];
            $file = wfFindFile($fileKey);
            if ($file && WpWikiplace::isExtensionValidForBackground($file->getExtension()))
                return $file->getFullUrl();
        }

        /** @todo Remove the background by url feature */
        $patternForUrl = '/^(https?\:\/\/[\w\-%\.\/\?\&]*\.(jpe?g|png|gif))/im';
        $matchesForUrl = array();
        if (preg_match($patternForUrl, $backgroundText, $matchesForUrl)) {
            return $matchesForUrl[1];
        }

        return false;
    }

    

    /**
     *
     * @param Title $title
     * @param String $copywarnMsg 
     */
    public static function EditPageCopyrightWarning($title, &$copywarnMsg) {
        $ns = $title->getNamespace();
        if (WpPage::isInWikiplaceNamespaces($ns)) {
            $copywarnMsg = array('copyrightwarning3',
                '[[' . wfMsgForContent('copyrightpage') . ']]');
        }
        return true;
    }

}
