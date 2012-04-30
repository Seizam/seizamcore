<?php

if (!defined('MEDIAWIKI')) {
	die(-1);
}

class WikiplacesHooks {
	
	private static $userCanCreateCache = array(); // ['title']['user']

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
	public static function userCanCreate( $title, &$user, $action, &$result ) {
		
		if ( ( ($action != 'create') && ($action != 'edit') && ($action != 'upload') && ($action != 'createpage') ) ||
				! WpPage::isInWikiplaceNamespaces($title->getNamespace()) ||
				$title->isKnown() ) {
			return true; // skip
		}

		if ( ! $user->isLoggedIn() ) {
			wfDebugLog( 'wikiplaces', 'userCanCreate: DENY user is not logged in');
			$result = false; // can't
			return false; // stop hook
		}
		
		$full_text = $title->getFullText();
		$article_id = $title->getArticleID();
		$user_id = $user->getId();
		
		// fetch value in cache if possible
		if ( isset(self::$userCanCreateCache[$full_text][$user_id]) ) {
			
			$result = self::$userCanCreateCache[$full_text][$user_id];
			
			wfDebugLog( 'wikiplaces', 'userCanCreate: CACHE HIT "'
					.( $result ? 'YES': 'NO' ).'" ['
					.$article_id.']"'.$full_text.'" ['
					.$user->getID().']"'.$user->getName().'"');
			 
			return false; // stop hook
		}
		
		wfDebugLog( 'wikiplaces', 'userCanCreate: EVALUATE '
		. ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . '] isKnown()=' . var_export($title->isKnown(), true)
		. ' user="' . $user->getName() . '"[' . $user->getID() . ']'
		. ' action="' . $action . '"');
				
		
		if ( WpPage::isHomepage($title) ) {

			// this is a new Wikiplace
			
			if ( preg_match('/[.]/', $title->getText()) ) {
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY bad character in name, article=['.$article_id.']"'.$full_text.'"');
				$result = false;
			} elseif ( ($reason=WpWikiplace::userCanCreateWikiplace($user->getId())) !== true ) {
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new wikiplace, reason='.$reason.', article=['.$article_id.']"'.$full_text.'"');
				$result = false; // can't
			} else {
				wfDebugLog( 'wikiplaces', 'userCanCreate: ALLOW new Wikiplace, article=['.$article_id.']"'.$full_text.'"');
				$result = true; // can
			}
			
		} else {

			// this is a subpage (can be regular article or talk or file)

			$wp = WpWikiplace::extractWikiplaceRoot( $title->getDBkey(), $title->getNamespace() );
			
			if ( $wp === null ) { 
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY cannot extract container Wikiplace, article=['.$article_id.']"'.$full_text.'"');
				$result = false; // no wikiplace can contain this subpage, so cannot create it
				
			} elseif ( ! $wp->isOwner($user_id) ) { // checks the user who creates the page is the owner of the wikiplace
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new subpage, current user is not Wikiplace owner, article=['.$article_id.']"'.$full_text.'"');
				$result = false;
				
			} else {
				
				if ($title->getNamespace() == NS_FILE) {
					
					// the user is uploading a file
			
					if ( ($reason=WpPage::userCanUploadNewFile($user_id)) !== true ) {
						wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new file, reason='.$reason.', article=[' . $article_id . ']"' . $full_text . '"');
						$result = false; // no active subscription or page creation quota is exceeded
					} else {
						wfDebugLog( 'wikiplaces', 'userCanCreate: ALLOW new file, article=[' . $article_id . ']"' . $full_text . '"');
						$result = true;
					}
					
			
				} else {
					
					// the user is creating a new page (regular or talk)
					
					if ( ($reason=WpPage::userCanCreateNewPage($user_id)) !== true ) {
						wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new subpage, reason='.$reason.', article=[' . $article_id . ']"' . $full_text . '"');
						$result = false; // no active subscription or page creation quota is exceeded
					} else {
						wfDebugLog( 'wikiplaces', 'userCanCreate: ALLOW new subpage, article=[' . $article_id . ']"' . $full_text . '"');
						$result = true;
					}
					
				}
				
			}
			
		}
		
		self::$userCanCreateCache[$full_text][$user_id] = $result;
				
		return false; // stop hook processing
		
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
	 */
	public static function onArticleInsertComplete( $wikipage, $user, $text, $summary, $isMinor, $isWatch, $section, $flags, $revision ) {
		
		$title = $wikipage->getTitle();
		
		if ( ! WpPage::isInWikiplaceNamespaces( $title->getNamespace() ) ) {
			return true; // skip
		}
		
		// these 2 vars are only used to generate debug messages
		$prefixed_db_key = $title->getPrefixedDBkey();
		$article_id = $title->getArticleID();
		
		// currently, the page is already stored in 'page' db table
			
		if ( WpPage::isHomepage($title) ) {			
			
			// create a wikiplace from this homepage				
			
			$subscription = WpSubscription::getActiveByUserId($user->getId());
			if ( $subscription == null ) {
				// the user has no subscription, so we can't create her wikiplace
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR cannot create wikiplace because user has no active subscription, article=['.$article_id.']"'.$prefixed_db_key.'", user=['.$user->getId().']');
				throw new MWException('Cannot create wikiplace because user has no active subscription.');
			}
			
			$wikiplace = WpWikiplace::create( $wikipage, $subscription );
			if ( $wikiplace == null ) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while creating wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while creating wikiplace.');
			}	
			
			if ( ! $wikiplace->forceArchiveAndResetUsage( WpSubscription::getNow() )) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while initialization of wikiplace usage, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while initialization of wikiplace usage.');		
			}
			
			$new_wp_page = WpPage::create( $wikipage , $wikiplace);

			if ($new_wp_page === null) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while associating homepage to its container wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while associating homepage to its container wikiplace.');
			}
			
			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: OK, wikiplace and its homepage created and initialized, article=['.$article_id.']"'.$prefixed_db_key.'"');

			
		} else {

			// this is a subpage of an existing existing wikiplace

			$wikiplace = WpWikiplace::extractWikiplaceRoot( $title->getDBkey(), $title->getNamespace() );
			if ($wikiplace === null) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR cannot identify container wikiplace: ['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Cannot identify the container wikiplace.');
			}

			if ( WpPage::create($wikipage, $wikiplace) == null ) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while associating subpage to its container wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Error while associating subpage to its container wikiplace.');
			}

			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: OK, the page is now associated to its wikiplace, article=['.$article_id.']"'.$prefixed_db_key.'"');

		} 
		
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
		
		return true; // don't stop hook processing, except if an error raising an exception occured 
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
			
		$owner = WpPage::findOwnerUserIdByArticleId( $title->getArticleID() );

		if ($owner == 0) {
			wfDebugLog('wikiplaces', 'isOwner: WARNING unknown page in wikiplace namespace'
					.', title="' . $title->getPrefixedDBkey() .
					'", user=[' . $user->getId() . ']"' . $user->getName() .
					'", hookCurrentResult=' . ( $result ? 'YES' : 'NO' ) );
			
			return true; // we don't know, so we don't stop hook processing
		}

		// set answer
		$result = $user->getId() == $owner;
		
		wfDebugLog( 'wikiplaces', 'isOwner: '.($result ? 'YES':'NO')
				.', title="'.$title->getPrefixedDBkey().
				'", user=['.$user->getId().']"'.$user->getName().
				'", owner=['.$owner.']');

		return false; // stop hook processing, because we have the answer
		
	}
	
}
