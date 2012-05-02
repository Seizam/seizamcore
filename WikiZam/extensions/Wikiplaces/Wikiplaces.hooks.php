<?php

if (!defined('MEDIAWIKI')) {
	die(-1);
}

class WikiplacesHooks {
	
	private static $userCanCache = array(); // ['title']['user']

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
		
		if ( ! WpPage::isInWikiplaceNamespaces($title->getNamespace())  ||  ($action=='read') ) {
			return true; // skip
		}
				
		$article_id = $title->getArticleID();
		$user_id = $user->getId();
		
		$do;
		
		if (  ! $title->isKnown()  &&  ( ($action=='create') || ($action=='edit') || ($action=='upload') || ($action=='createpage') ) ) {
			$do = 'create';
		} elseif ($action=='move') {
			$do = 'move';
		} elseif ($action=='delete') {
			$do = 'delete';
		} else {
			wfDebugLog('wikiplaces', 'userCan: '.$action.' SKIP' .
					' title="' . $title->getPrefixedDBkey() . '"[' . $article_id . '],' . ($title->isKnown() ? 'known' : 'new') .
					' user="' . $user->getName() . '"[' . $user_id . ']' );
			return true; // action not handled here, so continue hook processing to let MW find an answer
		}
		
		if ( isset(self::$userCanCache[$article_id][$user_id][$do]) ) {
			$result = self::$userCanCache[$article_id][$user_id][$do];
			wfDebugLog('wikiplaces', 'userCan: ' . $do .' '. ($result ? 'ALLOWED' : 'DENIED') . '(cache hit)'.
					' article=[' . $article_id . ']' .
					' user=[' . $user_id . ']' .
					' action=' . $action ) ;
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

		self::$userCanCache[$article_id][$user_id][$do] = $result;

		wfDebugLog('wikiplaces', 'userCan: ' . $do .' '. ($result ? 'ALLOWED' : 'DENIED') .
				' title="' . $title->getPrefixedDBkey() . '"[' . $article_id . '] isKnown()=' . ($title->isKnown() ? 'known' : 'new') .
				' user="' . $user->getName() . '"[' . $user_id . ']' .
				' action=' . $action );
				
		return false; // stop hook processing, we have the answer

	}
	
	/**
	 * Can the user create the new Title?
	 * @param Title $title A new Title, not already stored.
	 * @param User $user
	 * @return boolean true=can, false=cannot 
	 */
	private static function userCanCreate( &$title, &$user ) {
		
		if ( ! $user->isLoggedIn() ) {
			wfDebugLog( 'wikiplaces', 'userCanCreate: DENY user is not logged in, page title: "'.$title->getFullText().'"');
			return false;
		}
		
		$msg;
		$user_id = $user->getId();
		
		if ( WpPage::isHomepage($title) ) {

			// this is a new Wikiplace
			$msg = 'new wikiplace';
			
			if ( preg_match('/[.]/', $title->getText()) ) {
				$result = false;
				$msg .= ', bad character in page title';
				
			} elseif ( ($reason=WpWikiplace::userCanCreateWikiplace($user_id)) !== true ) {
				$result = false; 
				$msg .= ', '.$reason;
				
			} else {
				$result = true; 
			}
			
		} else {

			// this is a subpage (can be regular article or talk or file)
			$msg = 'new wikiplace item';
			$namespace = $title->getNamespace();

			$wp = WpWikiplace::getBySubpage( $title->getDBkey(), $title->getNamespace() );
			
			if ( $wp === null ) { 
				$result = false; // no wikiplace can contain this subpage, so cannot create it
				$msg .= ', cannot find existing container Wikiplace';
				
			} elseif ( ! $wp->isOwner($user_id) ) { // checks the user who creates the page is the owner of the wikiplace
				$result = false;
				$msg .= 'current user is not Wikiplace owner';
				
			} else {
				
				if ($namespace == NS_FILE) {
					
					// the user is uploading a file
					$msg .= ', new file';
			
					if ( ($reason=WpPage::userCanUploadNewFile($user_id)) !== true ) {
						$result = false; // no active subscription or page creation quota is exceeded
						$msg .= ', '.$reason;
						
					} else {
						$result = true;
					}
					
			
				} else {
					
					// the user is creating a new page (regular or talk)
					$msg .= ', new subpage';
					
					if ( ($reason=WpPage::userCanCreateNewPage($user_id)) !== true ) {
						$result = false; // no active subscription or page creation quota is exceeded
						$msg .= ', '.$reason;
						
					} else {
						$result = true;
					}
					
				}
				
			}
			
		}
				
		wfDebugLog( 'wikiplaces', 'userCanCreate: '.($result?'ALLOW':'DENY').' '.$msg.', page title: "'.$title->getFullText().'"');
		
		return $result; 
		
	}
	
	/**
	 *
	 * @param Title $title
	 * @param User $user
	 * @return boolean 
	 */
	private static function userCanMove( &$title, &$user ) {

		return (  $user->isLoggedIn()  && 
				! WpPage::isHomepage($title)  &&
				WpPage::isOwner($title->getArticleID(), $user->getId()) ) ;
		
	}
	
	/**
	 *
	 * @param Title $title
	 * @param User $user
	 * @return boolean 
	 */
	private static function userCanDelete( &$title, &$user ) {

		return (  $user->isLoggedIn()  && 
				! WpPage::isHomepage($title)  &&
				WpPage::isOwner($title->getArticleID(), $user->getId()) ) ;
		
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
	public static function onArticleInsertComplete( $wikipage, $user, $text, $summary, $isMinor, $isWatch, $section, $flags, $revision ) {
		
		$title = $wikipage->getTitle();
		
		if ( ! WpPage::isInWikiplaceNamespaces( $title->getNamespace() ) ) {
			return true; // skip
		}
		
		self::onWikiplaceItemCreated();
		
		// restrict applicable actions to owner, except for read
		$actions_to_rectrict = array_diff(
				$wikipage->getTitle()->getRestrictionTypes(), // array( 'read', 'edit', ... )
				array( 'read') );
		$restrictions = array();
		foreach ( $actions_to_rectrict as $action) {
			$restrictions[$action] = WP_DEFAULT_RESTRICTION_LEVEL;
		} 

		$ok = false;
		wfRunHooks( 'SetRestrictions', array( $wikipage , $restrictions , &$ok ) );
		if ( !$ok ) {
			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while setting default restrictions to new page, article=['.$article_id.']"'.$prefixed_db_key.'"');
		} else {
			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: OK default restrictions set to new page, article=['.$article_id.']"'.$prefixed_db_key.'"');
		}
		
		return true;
		
	}
	
	private static function onWikiplaceItemCreated( $title, $user ) {
		
		// these 2 vars are only used to generate debug messages
		$prefixed_db_key = $title->getPrefixedDBkey();
		$article_id = $title->getArticleID();
		
		// currently, the page is already stored in 'page' db table
			
		if ( WpPage::isHomepage($title) ) {			
			
			// create a wikiplace from this homepage				
			
			$subscription = WpSubscription::getActiveByUserId($user->getId());
			if ( $subscription == null ) {
				// the user has no subscription, so we can't create her wikiplace
				wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: ERROR no active subscription, article=['.$article_id.']"'.$prefixed_db_key.'", user=['.$user->getId().']');
				throw new MWException('Cannot create wikiplace because user has no active subscription.');
			}
			
			$wikiplace = WpWikiplace::create( $wikipage, $subscription );
			if ( $wikiplace == null ) {
				wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: ERROR while creating wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while creating wikiplace.');
			}	
			
			if ( ! $wikiplace->forceArchiveAndResetUsage( WpSubscription::getNow() )) {
				wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: ERROR while initialization of wikiplace usage, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while initialization of wikiplace usage.');		
			}
			
			$new_wp_page = WpPage::create( $wikipage->getId() , $wikiplace->get('wpw_id'));

			if ($new_wp_page === null) {
				wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: ERROR while associating homepage to its container wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while associating homepage to its container wikiplace.');
			}
			
			wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: OK, wikiplace and its homepage created and initialized, article=['.$article_id.']"'.$prefixed_db_key.'"');

			
		} else {

			// this is a subpage of an existing existing wikiplace

			$wikiplace = WpWikiplace::getBySubpage( $title->getDBkey(), $title->getNamespace() );
			if ($wikiplace === null) {
				wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: ERROR cannot identify container wikiplace: ['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Cannot identify the container wikiplace.');
			}

			if ( WpPage::create($wikipage->getId(), $wikiplace->get('wpw_id')) == null ) {
				wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: ERROR while associating subpage to its container wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while associating subpage to its container wikiplace.');
			}

			wfDebugLog( 'wikiplaces', 'onWikiplaceItemCreated: OK, the page is now associated to its wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');

		} 
		

	}
		

	/**
	 * Occurs when moving a page:
	 * <ul>
	 * <li>old page renamed</li>
	 * <li>new page created, with old name, containing a redirect to the new one</li>
	 * </ul>
	 * @param Title $title old title
	 * @param Title $newtitle
	 * @param User $user user who did the move
	 * @param int $oldid database ID of the page that's been moved
	 * @param int $newid database ID of the created redirect
	 * @return boolean true to continue hook processing or false to abort
	 */
	public static function onTitleMoveComplete ( &$title, &$newtitle, &$user, $oldid, $newid  ) {
		
		wfDebugLog( 'wikiplaces', 'onTitleMoveComplete: ['.$oldid.']"'.$title->getPrefixedDBkey().'" renamed to "'.$newtitle->getPrefixedDBkey().'", redirect['.$newid.']');
		
		// first step, move the old page to the new wikiplace if necessary
		
		$renamed_page = WpPage::getByArticleId( $oldid );
		if ($renamed_page === null) {
			wfDebugLog( 'wikiplaces', 'onTitleMoveComplete: ERROR cannot find original wikiplace page: ['.$oldid.']"');
			throw new MWException('Cannot find the original page.');
		}
		
		$new_wikiplace = WpWikiplace::extractWikiplaceRoot( $newtitle->getPrefixedDBkey(), $newtitle->getNamespace() );
		if ($new_wikiplace === null) {
			wfDebugLog( 'wikiplaces', 'onTitleMoveComplete: ERROR cannot identify container wikiplace: "'.$newtitle->getPrefixedDBkey().'"');
			throw new MWException('Cannot identify the container wikiplace.');
		}
		
		$old_wikiplace_id = $renamed_page->get('wppa_wpw_id');
		$new_wikiplace_id = $new_wikiplace->get('wpw_id');
		
		if ( $old_wikiplace_id != $new_wikiplace_id ) {
			$renamed_page->setWikiplaceId($new_wikiplace_id);
		}
					
					
		// second step, if a redirect was created, associate this new page to original wikiplace
		
		if ( $newid == 0 ) {
			// no redirect created
			return true;
		}

		if ( WpPage::create( $newid, $old_wikiplace_id ) == null ) {
			wfDebugLog( 'wikiplaces', 'onTitleMoveComplete: ERROR while associating redirect to its container wikiplace, article=['.$newid.']"'.$title->getPrefixedDBkey().'"');
			throw new MWException('Error while associating subpage to its container wikiplace.');
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
	public static function onArticleDeleteComplete ( &$article, &$user, $reason, $id ) {
		wfDebugLog( 'wikiplaces', 'onArticleDeleteComplete: article=['.$id.']"'.$article->getTitle()->getPrefixedDBkey().'"');
		
		if ( ! WpPage::delete($id) ) {
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
	public static function onArticleUndelete ( &$title, $create, $comment ) {
		wfDebugLog( 'wikiplaces', 'onArticleUndelete: article=['.$title->getArticleID().']"'.$title->getPrefixedDBkey().'"');
		
		return true;
	}
	
	/**
	 * Search for a subscription attached to this transaction, and if found, update it.
	 * @param array $tmr
	 * @return boolean False (=stop hook) only if the transaction is for a subscription.
	 */
	public static function onTransactionUpdated( $tmr ) {	

		$sub = WpSubscription::getByTransactionId( $tmr['tmr_id'] );
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
	public static function isOwner ( $title, $user, &$result ) {
				
		if ( ! WpPage::isInWikiplaceNamespaces( $title->getNamespace() )  || ! $title->isKnown() ) {
			return true; // skip
		}
			
		$article_id = $title->getArticleID();
		$user_id = $user->getId();
		
		$result = WpPage::isOwner( $article_id , $user_id );
		
		wfDebugLog( 'wikiplaces', 'isOwner: '.($result ? 'YES':'NO')
				.', title=['.$article_id.']"'.$title->getPrefixedDBkey().
				'", user=['.$user_id.']"'.$user->getName().'"');

		if ( ! $result ) {
			wfDebugLog( 'wikiplaces',  wfGetPrettyBacktrace() );
		}
		
		return false; // stop hook processing, because we have the answer
		
	}
	
}
