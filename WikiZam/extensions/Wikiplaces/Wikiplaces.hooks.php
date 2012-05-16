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
            'wp_old_subscription'
        );

        $mysql_dir = dirname(__FILE__) . '/schema/mysql';
        foreach ($tables as $table) {
            $updater->addExtensionUpdate(array('addTable', $table, "$mysql_dir/$table.sql", true));
        }

        return true;
    }

    /**
     * @param Title $title the article (Article object) being saved
     * @param User $user the user (User object) saving the article
     * @param string $action the action
     * @param boolean $result 
     */
    public static function userCan($title, &$user, $action, &$result) {

        if (($action == 'read') || !WpPage::isInWikiplaceNamespaces($title->getNamespace())) {
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


        if (!$user->isLoggedIn()) {
            wfDebugLog('wikiplaces', 'userCan: DENIED user is not logged in');
            $result = false;
        } else {
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
            wfDebugLog('wikiplaces', 'userCan: ' . $do . ' ' . ($result ? 'ALLOWED' : 'DENIED') .
                    ' title="' . $title->getPrefixedDBkey() . '"[' . $article_id . '] isKnown()=' . ($title->isKnown() ? 'known' : 'new') .
                    ' user="' . $user->getName() . '"[' . $user_id . ']' .
                    ' action=' . $action);
        }

        self::$cacheUserCan[$article_id][$user_id][$do] = $result;

        return false; // stop hook processing, we have the answer
    }

    /**
     * Can the user create this new Title?
     * <ul>
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
        
        // in userCan() calling this function, we already checked that user is loggedin

        $msg;
        $user_id = $user->getId();

        if (WpPage::isHomepage($title)) {

            // this is a new Wikiplace
            $msg = 'new wikiplace';

            if ($user->isAllowed(WP_ADMIN_RIGHT)) {
                /** @todo: in futur release, admin will be able to create a wikiplace for someone else */
                $result = false;
                $msg .= ', admin cannot create wikiplace';
                
            } elseif (($reason = WpSubscription::userCanCreateWikiplace($user_id)) !== true) {
                $result = false;
                $msg .= ', ' . $reason;
                
            } elseif (WpWikiplace::isBlacklistedWikiplaceName($title->getDBkey())) {
                $result = false;
                $msg .= ', blacklisted name';
                
            } elseif (preg_match('/[.]/', $title->getText())) {
                $result = false;
                $msg .= ', bad character in page title';
                
            } else {
                $result = true;
                
            }
        } else {

            // this is a subpage (can be regular article or talk or file)
            $msg = 'new wikiplace item';
            $namespace = $title->getNamespace();

            if ($namespace == NS_FILE) {

                // the user is uploading a file
                $msg .= ', new file';
                $db_key = $title->getDBkey();

                if (WpPage::isPublicFile($db_key)) {
                    $msg .= ', public file';
                    $result = true;
                    
                } elseif (WpPage::isAdminFile($db_key)) {

                    $msg .= ', admin file';
                    if ($user->isAllowed(WP_ADMIN_RIGHT)) {
                        $result = true;
                        
                    } else {
                        $msg .= ', user not admin';
                        $result = false;
                        
                    }
                } elseif ($user->isAllowed(WP_ADMIN_RIGHT)) {
                    
                    $result = false;
                    $msg .= ', admin cannot upload file in wikiplaces';
                    
                } else {

                    $wp = WpWikiplace::getBySubpage($db_key, $title->getNamespace());

                    if ($wp === null) {
                        $result = false; // no wikiplace can contain this subpage, so cannot create it
                        $msg .= ', cannot find existing container Wikiplace';
                        
                    } elseif (!$wp->isOwner($user_id)) { // checks the user who creates the page is the owner of the wikiplace
                        $result = false;
                        $msg .= 'current user is not Wikiplace owner';
                        
                    } else {

                        if (($reason = WpSubscription::userCanUploadNewFile($user_id)) !== true) {
                            $result = false; // no active subscription or page creation quota is exceeded
                            $msg .= ', ' . $reason;
                            
                        } else {
                            $result = true;
                            
                        }
                    }
                }
            } else {

                // the user is creating a new page (regular or talk, but not a file)
                $msg .= ', new subpage';

                $wp = WpWikiplace::getBySubpage($title->getDBkey(), $title->getNamespace());

                if ($wp === null) {
                    $result = false; // no wikiplace can contain this subpage, so cannot create it
                    $msg .= ', cannot find existing container Wikiplace';
                    
                } elseif ($user->isAllowed(WP_ADMIN_RIGHT)) {
                    // admin is creating a subpage for someone else
                    $result = true;
                    $msg .= ', admin is creating a subpage for someone else';
                    
                } elseif (!$wp->isOwner($user_id)) { // checks the user who creates the page is the owner of the wikiplace
                    $result = false;
                    $msg .= ', current user is not Wikiplace owner';
                    
                } elseif (($reason = WpSubscription::userCanCreateNewPage($user_id)) !== true) {
                    $result = false; // no active subscription or page creation quota is exceeded
                    $msg .= ', ' . $reason;
                    
                } else {
                    $result = true;
                    
                }
            }
        }

        wfDebugLog('wikiplaces', 'userCanCreate: ' . ($result ? 'ALLOW' : 'DENY') . ' ' . $msg . ', page title: "' . $title->getFullText() . '"');

        return $result;
    }

    /**
     * For title in wikiplace namespace, checks if the current user can move it
     * <ul>
     * <li>User has an active subscription</li>
     * <li>Title is not a Wikiplace homepage</li>
     * <li>User is owner of this title</li>
     * </ul>
     * @param Title $title
     * @param User $user
     * @return boolean 
     */
    private static function userCanMove(&$title, &$user) {
        
        // in userCan() calling this function, we already checked that user is loggedin
        /** @todo: this admin bypass should be less permissive (currently, it can lead to inconsistent states) */
        return ( $user->isAllowed(WP_ADMIN_RIGHT)
                || ( WpSubscription::getActiveByUserId($user->getId()) != null
                && !WpPage::isHomepage($title)
                && WpPage::isOwner($title->getArticleID(), $user) ) );
    }

    /**
     * For title in wikiplace namespace, checks if the current user can delete it
     * <ul>
     * <li>Title is not a Wikiplace homepage</li>
     * <li>User is owner of this title</li>
     * </ul>
     * @param Title $title
     * @param User $user
     * @return boolean 
     */
    private static function userCanDelete(&$title, &$user) {

        // in userCan() calling this function, we already checked that user is loggedin
        /** @todo: this admin bypass should be less permissive (currently, it can lead to inconsistent states) */
        return ( $user->isAllowed(WP_ADMIN_RIGHT)
                || (!WpPage::isHomepage($title)
                && WpPage::isOwner($title->getArticleID(), $user) ) );
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
            if ($wikiplace === null) {
                wfDebugLog('wikiplaces', 'onArticleInsertComplete: error while creating wikiplace "' . $title->getPrefixedDBkey() . '"');
                throw new MWException('Error while creating wikiplace.');
            }
            
        } else {

            // this is a subpage 

            $db_key = $title->getDBkey();
            $namespace = $title->getNamespace();

            if (  ($title->getNamespace() == NS_FILE)  &&  ( WpPage::isPublicFile($db_key) || WpPage::isAdminFile($db_key) )  ) {
                wfDebugLog('wikiplaces', 'onArticleInsertComplete: public or admin file "' . $title->getPrefixedDBkey() . '"');
                return true; // no wikiplace to attach to, so exit
            }

            // searching existing container wikiplace
            $wikiplace = WpWikiplace::getBySubpage($db_key, $namespace);
            if ($wikiplace === null) {
                wfDebugLog('wikiplaces', 'onArticleInsertComplete: cannot identify container wikiplace "' . $title->getPrefixedDBkey() . '"');
                throw new MWException('Cannot identify the container wikiplace.');
            }
        }

        $new_wp_page = WpPage::create($article_id, $wikiplace->get('wpw_id'));

        if ($new_wp_page === null) {
            wfDebugLog('wikiplaces', 'onArticleInsertComplete: error while associating new page to its container wikiplace "' . $title->getPrefixedDBkey() . '"');
            throw new MWException('Error while associating new page to its container wikiplace.');
        }

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
            wfDebugLog('wikiplaces', 'onArticleInsertComplete: OK, but error while setting default restrictions to new page, article=[' . $wikipage->getId() . ']"' . $title->getPrefixedDBkey() . '"');
        } else {
            wfDebugLog('wikiplaces', 'onArticleInsertComplete: OK, article=[' . $wikipage->getId() . ']"' . $title->getPrefixedDBkey() . '"');
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
        $subscription = WpSubscription::getLastSubscription($user_id);
        if ($subscription == null) {
            wfDebugLog('wikiplaces', 'doCreateWikiplace: cannot create wikiplace, user has no subscription, user='.$user_id.' article_id='.$homepage_article_id);
            return null;
        }

        $wikiplace = WpWikiplace::create($homepage_article_id, $subscription);
        if ($wikiplace == null) {
            wfDebugLog('wikiplaces', 'doCreateWikiplace: error while creating wikiplace, user='.$user_id.' article_id='.$homepage_article_id);
            return null;
        }

        if (!$wikiplace->forceArchiveAndResetUsage(WpSubscription::getNow())) {
            wfDebugLog('wikiplaces', 'doCreateWikiplace: error while initialization of wikiplace usage, user='.$user_id.' article_id='.$homepage_article_id);
            return null;
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

        wfDebugLog('wikiplaces', 'onTitleMoveComplete: '
                . '[' . $renamed_page_id . ']"' . $old_name_title->getPrefixedDBkey() . '"'
                . ' renamed to "' . $new_name_title->getPrefixedDBkey() . '", redirect[' . $redirect_page_id . ']');

        if (!$old_in_wp_ns) { // from  something not in wp
            
            if (!$new_in_wp_ns) { // from  something not in wp  to  something not in wp
                
                return true; // nothing to do
                
            } elseif (WpPage::isHomepage($new_name_title)) { // from  something not in wp  to  a homepage
                
                $dest_wp = self::doCreateWikiplace($user->getId(), $renamed_page_id);
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot create wikiplace "'.$new_name_title->getPrefixedDBkey().'"');
                    throw new MWException('Cannot create wikiplace.');
                }
                $new_wp_page = WpPage::create($renamed_page_id, $dest_wp->get('wpw_id'));
                if ($new_wp_page == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot create wikiplace homepage "'.$new_name_title->getPrefixedDBkey().'"');
                    throw new MWException('Cannot create wikiplace homepage.');
                }
                
            } else { // from  something not in wp  to  a subpage
                
                $dest_wp = WpWikiplace::getBySubpage($new_name_title->getDBkey(), $new_name_title->getNamespace());
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot find container wikiplace for "'.$new_name_title->getPrefixedDBkey().'"');
                    throw new MWException('Cannot find container wikiplace.');
                }
                $new_wp_page = WpPage::create($renamed_page_id, $dest_wp->get('wpw_id'));
                if ($new_wp_page == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot create wikiplace homepage "'.$new_name_title->getPrefixedDBkey().'"');
                    throw new MWException('Cannot create wikiplace homepage.');
                }
                
            }
            
        } elseif (WpPage::isHomepage ($old_name_title)) { // from  a homepage
            
            // currently, this case is forbidden and cannot occur because items become orphan... but, now, it's too late :(
            wfDebugLog('wikiplaces', 'onTitleMoveComplete: ERROR cannot move homepage "' . $old_name_title->getPrefixedDBkey() . '"');
            throw new MWException('Cannot move wikiplace homepage.');
            
            if (!$new_in_wp_ns) { // from  a homepage  to  something not in wp
                
            } elseif (WpPage::isHomepage($new_name_title)) { // from  a homepage  to  a homepage
                
            } else { // from  a homepage  to  a subpage
                
            }
            
        } else { // from  a subpage
            
            if (!$new_in_wp_ns) { // from  a subpage  to  something not in wp
                
                // currently, this case is forbidden and should not occur, but actually it can be moved
                wfDebugLog('wikiplaces', 'onTitleMoveComplete: WARNING moving a subpage out of wikiplace space "' . $new_name_title->getPrefixedDBkey() . '"');
                
                if ($redirect_page_id != 0) {
                    $renamed_wp_page = WpPage::getByArticleId($renamed_page_id);
                    if ($renamed_wp_page == null) {
                        wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot find subpage to move [' . $renamed_page_id . ']');
                        throw new MWException('Cannot find subpage to move.');
                    }
                    $renamed_wp_page->setPageId($redirect_page_id);
                    
                } else {
                    if (!WpPage::delete($renamed_page_id)) {
                        wfDebugLog('wikiplaces', 'onTitleMoveComplete: ERROR while deleting the Wikiplace page [' . $renamed_page_id . ']');
                        throw new MWException('Error while deleting the Wikiplace page.');                       
                    }
                }
                
            } elseif (WpPage::isHomepage($new_name_title)) { // from  a subpage  to  a homepage
                
                $dest_wp = self::doCreateWikiplace($user->getId(), $renamed_page_id);
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot create wikiplace "'.$new_name_title->getPrefixedDBkey().'"');
                    throw new MWException('Cannot create wikiplace.');
                }
                $renamed_wp_page = WpPage::getByArticleId($renamed_page_id);
                if ($renamed_wp_page == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot find subpage to move [' . $renamed_page_id . ']');
                    throw new MWException('Cannot find subpage to move.');
                }
                if ($redirect_page_id != 0) {                 
                    $old_wp_id = $renamed_wp_page->get('wppa_wpw_id');
                    if (WpPage::create($redirect_page_id, $old_wp_id) == null) {
                        wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot create redirect subpage ['.$renamed_page_id.']');
                        throw new MWException('Cannot create redirect subpage.');
                    }                      
                }
                $dest_wp_id = $dest_wp->get('wpw_id');
                $renamed_wp_page->setWikiplaceId($dest_wp_id);
                
            } else { // from  a subpage  to  a subpage
                
                $dest_wp = WpWikiplace::getBySubpage($new_name_title->getDBkey(), $new_name_title->getNamespace());
                if ($dest_wp == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot find destination wikiplace "'.$new_name_title->getPrefixedDBkey().'"');
                    throw new MWException('Cannot find destination wikiplace.');
                }
                $renamed_wp_page = WpPage::getByArticleId($renamed_page_id);
                if ($renamed_wp_page == null) {
                    wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot find subpage to move [' . $renamed_page_id . ']');
                    throw new MWException('Cannot find subpage to move.');
                }
                if ($redirect_page_id != 0) {                 
                    $old_wp_id = $renamed_wp_page->get('wppa_wpw_id');
                    if (WpPage::create($redirect_page_id, $old_wp_id) == null) {
                        wfDebugLog('wikiplaces', 'onTitleMoveComplete: cannot create redirect subpage ['.$renamed_page_id.']');
                        throw new MWException('Cannot create redirect subpage.');
                    }                      
                }
                $dest_wp_id = $dest_wp->get('wpw_id');
                $renamed_wp_page->setWikiplaceId($dest_wp_id);
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

        wfDebugLog('wikiplaces', 'onArticleUndelete: trying to restore article=[' . $title->getArticleID() . ']"' . $title->getPrefixedDBkey());

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

            wfDebugLog('wikiplaces', 'onArticleUndelete: article [' . $title->getArticleID() . '] restored in wikiplace [' . $wp->get('wpw_id') . ']');
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
     * Hook to correct the action menu that displays "delete" a bit too much.
     * A direct correction would be to change the logic directly in SkinTemplate::buildContentNavigationUrls.
     * The menu builder now uses if( $wgUser->isAllowed( 'delete' ) ) ...
     * A better builder would use if( $$title->quickUserCan( 'delete' ) ) ...
     * 
     * @param SkinTemplate $skinTemplate
     * @param Array $content_navigation
     * @return Boolean True (=continue hook)
     * 
     */
    public static function SkinTemplateNavigation(&$skinTemplate, &$content_navigation) {
        $title = $skinTemplate->getRelevantTitle();
        if (isset($content_navigation['actions']['delete']) && !$title->quickUserCan('delete'))
            unset($content_navigation['actions']['delete']);
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

        if (!WpPage::isInWikiplaceNamespaces($title->getNamespace()) || !$title->isKnown()) {
            return true; // skip
        }

        $article_id = $title->getArticleID();
        $user_id = $user->getId();

        $result = WpPage::isOwner($article_id, $user);

        wfDebugLog('wikiplaces', 'isOwner: ' . ($result ? 'YES' : 'NO')
                . ', title=[' . $article_id . ']"' . $title->getPrefixedDBkey() .
                '", user=[' . $user_id . ']"' . $user->getName() . '"');

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
        $background['url'] = false;
        $navigation['content'] = false;
        $headerTitle['content'] = false;

        $title = $skin->getRelevantTitle();
        $ns = $title->getNamespace();
        if (WpPage::isInWikiplaceNamespaces($ns)) {
            $explosion = WpWikiplace::explodeWikipageKey($title->getText(), $ns);
            $wikiplaceKey = $explosion[0];

            // Wikiplace Title
            $headerTitle['content'] = self::makeFirstHeading($explosion, $title, $ns);


            // Wikiplace Background
            $backgroundKey = $wikiplaceKey . '/' . WPBACKGROUNDKEY;
            $backgroundTitle = Title::newFromText($backgroundKey, NS_WIKIPLACE);
            $backgroundPage = WikiPage::factory($backgroundTitle);
            $backgroundText = $backgroundPage->getText();
            if ($backgroundText) {
                $pattern = '/^https?\:\/\/[\w\-%\.\/\?\&]*\.(jpe?g|png|gif)$/i';
                if (preg_match($pattern, $backgroundText)) {
                    $background['url'] = $backgroundText;
                }
            }

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
        $tpl->set('wp_headertitle', $headerTitle);
        return true;
    }

    /*
     * Build pretty html FirstHeading from array of WikiPage Key elements
     */

    private static function makeFirstHeading($explosion, $title, $ns) {


        $excount = count($explosion);
        $text = '';

        // Pages not in main append namespace to title
        if ($ns != NS_MAIN) {
            $text .= '<span class="wpp-ns">' . $title->getNsText() . '</span>:';
        }

        // All pages except files
        if ($ns != NS_FILE && $ns != NS_FILE_TALK) {
            // We take the language variant off the explosion
            if (strlen($explosion[$excount - 1]) == 2) {
                $lang = $explosion[$excount - 1];
                array_pop($explosion);
                $excount--;
            }

            // First element of the array is the Homepage, we record it
            $mother = $explosion[0];

            // We print the Homepage
            if ($excount == 1) {
                $text .= Linker::linkKnown(Title::newFromText($mother, NS_MAIN), '<span class="wpp-hp">' . $mother . '</span>');
            } else {
                $text .= Linker::linkKnown(Title::newFromText($mother, NS_MAIN), '<span class="wpp-sp-hp">' . $mother . '</span>');
            }

            // We want to take care of the subpages now, we kick the homepage out.
            array_shift($explosion);

            // Foreach Subpages
            foreach ($explosion as $atom) {
                // The current element of the array is recorded
                $mother .= '/' . $atom;

                $text .= '/';
                // We print its link
                $text .= Linker::linkKnown(Title::newFromText($mother, $ns), '<span class="wpp-sp">' . $atom . '</span>');
            }

            // Appending Lang variant
            if (isset($lang))
                $text .= '/<span class="wpp-sp-lg">' . $lang . '</span>';
            // Page is NS_FILE or NS_FILE_TALK
        } else {
            // We take the extension off the explosion
            if (strlen($explosion[$excount - 1]) <= 4) {
                $ext = $explosion[$excount - 1];
                array_pop($explosion);
                $excount--;
            }
            // We print the Homepage
            $text .= Linker::linkKnown(Title::newFromText($explosion[0], NS_MAIN), '<span class="wpp-sp-hp">' . $explosion[0] . '</span>');
            $text .= '.';

            // We want to take care of the subpages now, we kick the homepage out.
            array_shift($explosion);

            $temp = '';
            // Reconstructing Page title
            foreach ($explosion as $atom)
                $temp .= $atom . '.';
            $temp = substr($temp, 0, -1);

            $temp = '<span class="wpp-file">' . $temp . '</span>';

            // Appending Ext
            if (isset($ext))
                $temp .= '.<span class="wpp-file-ext">' . $ext . '</span>';

            $text .= Linker::linkKnown($title, $temp);
        }

        return $text;
    }

}
