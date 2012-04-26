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
				!WpPage::isInWikiplaceNamespaces($title->getNamespace()) ||
				$title->isKnown() ) {
			
			wfDebugLog('wikiplaces', 'userCanCreate: SKIP '
					. ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . '] isKnown()=' . var_export($title->isKnown(), true)
					. ' user="' . $user->getName() . '"[' . $user->getID() . ']'
					. ' action="' . $action . '"');
			
			return true;
		}

		wfDebugLog( 'wikiplaces', 'userCanCreate: EVALUATE '
				. ' title="' . $title->getPrefixedDBkey() . '"[' . $title->getArticleId() . '] isKnown()=' . var_export($title->isKnown(), true)
				. ' user="' . $user->getName() . '"[' . $user->getID() . ']'
				. ' action="' . $action . '"');
		 
		
		if ( ! $user->isLoggedIn() ) {
			wfDebugLog( 'wikiplaces', 'userCanCreate: DENY user is not logged in');
			$result = false;
			return false;
		}
		
		$full_text = $title->getFullText();
		$article_id = $title->getArticleID();
		$user_id = $user->getId();
		
		if ( isset(self::$userCanCreateCache[$full_text][$user_id]) ) {
			$result = self::$userCanCreateCache[$full_text][$user_id];
			/*
			wfDebugLog( 'wikiplaces', 'userCanCreate: CACHE HIT "'
					.( $result ? 'YES': 'NO' ).'" ['
					.$article_id.']"'.$full_text.'" ['
					.$user->getID().']"'.$user->getName().'"');
			 */
			return false; // stop hook processing
		}
		
		if ( WpPage::isHomepage($title)) {

			// this is a new Wikiplace

			if ( !WpWikiplace::userCanCreateWikiplace($user->getId()) ) {
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new Wikiplace, but no active sub or no more quota ['.$article_id.']"'.$full_text.'"');
				$result = false; // no active subscription or a creation quota is exceeded
				
			} else {
				wfDebugLog( 'wikiplaces', 'userCanCreate: ALLOW new Wikiplace ['.$article_id.']"'.$full_text.'"');
				$result = true;
			}
			
		} else {

			// this is a Wikipage (can be regular article or talk or file)

			$wp = WpWikiplace::extractWikiplaceRoot( $title->getDBkey(), $title->getNamespace() );
			if ( $wp === null ) { 
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY cannot extract container Wikiplace ['.$article_id.']"'.$full_text.'"');
				$result = false; // no wikiplace can contain this subpage, so cannot create it
				
			} elseif ( ! $wp->isOwner($user_id) ) { // checks the user who creates the page is the owner of the wikiplace
				wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new Wikipage, but current user is not Wikiplace owner ['.$article_id.']"'.$full_text.'"');
				$result = false;
				
			} else {
				
				if ($title->getNamespace() == NS_FILE) {
					
					// the user is uploading a file
			
					/** @todo: complete test */
					if (!WpPage::userCanUploadNewFile($user_id)) {
						wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new file, but no active sub or no more quota [' . $article_id . ']"' . $full_text . '"');
						$result = false; // no active subscription or page creation quota is exceeded
					} else {
						wfDebugLog( 'wikiplaces', 'userCanCreate: ALLOW new sub page [' . $article_id . ']"' . $full_text . '"');
						$result = true;
					}
					
			
				} else {
					
					// the user is creating a new page (regular or talk)
					
					if (!WpPage::userCanCreateNewPage($user_id)) {
						wfDebugLog( 'wikiplaces', 'userCanCreate: DENY new Wikipage, but no active sub or no more quota [' . $article_id . ']"' . $full_text . '"');
						$result = false; // no active subscription or page creation quota is exceeded
					} else {
						wfDebugLog( 'wikiplaces', 'userCanCreate: ALLOW new Wikipage [' . $article_id . ']"' . $full_text . '"');
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
			$status = WpWikiplace::create($wikipage, $user->getId());
			if ( ! $status->isGood() ) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while creating wikiplace: ['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Cannot create wikiplace.');
			}		

			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: OK, wikiplace and its homepage created: ['.$article_id.']"'.$prefixed_db_key.'"');

		} else {

			// this is a subpage of an existing existing wikiplace

			$wp = WpWikiplace::extractWikiplaceRoot( $title->getDBkey(), $title->getNamespace() );
			if ($wp === null) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR cannot identify container wikiplace: ['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Cannot identify the container wikiplace.');
			}

			$status = WpPage::create($wikipage, $wp);
			if ( ! $status->isGood() ) {
				wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while associating the page to the container wikiplace: ['.$article_id.']"'.$prefixed_db_key.'"');
				throw new MWException('Cannot associate the page to a wikiplace.');
			}

			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: OK, the page is now associated to its wikiplace: ['.$article_id.']"'.$prefixed_db_key.'"');

		} 
		
		$restrictions = array(
			'edit' => 'owner',
			'move' => 'owner',
			'upload' => 'owner' ) ;
		
		$ok = false;
		wfRunHooks( 'SetRestrictions', array( $wikipage , $restrictions , &$ok ) );
		if ( !$ok ) {
			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: ERROR while setting default restrictions to new page: ['.$article_id.']"'.$prefixed_db_key.'"');
		} else {
			wfDebugLog( 'wikiplaces', 'onArticleInsertComplete: OK default restrictions set to new page: ['.$article_id.']"'.$prefixed_db_key.'"');
		}
		
		return true;
	}
		
	
	/**
	 *
	 * @param type $tmr
	 * @return boolean Always true (processing is never stopped) 
	 */
	public static function onTransactionUpdated( $tmr ) {	

		$sub = WpSubscription::getByTransactionId( $tmr['tmr_id'] );
		if ($sub === null) {
			return true; // we are not concerned, so don't stop processing
		}
		
		wfDebugLog( 'wikiplaces', 'onTransactionUpdated:'
				.' tmr_id='.$tmr['tmr_id']
				.' wps_id='.$sub->get('wps_id') 
				.' old_tmr_status='.$sub->get('wps_tmr_status')
				.' new_tmr_status='.$tmr['tmr_status'] );
				
		// $sub != null, so this tmr affects a subscription
		switch ($sub->get('wps_tmr_status')) {
			
			case 'PE':
				// was pending
				switch ($tmr['tmr_status']) {
				
					case 'OK':
						// PE -> OK
						
						if ($sub->get('wps_start_date') == null) {
							// first subscription, so activates it from now
							$start = WpSubscription::getNow();
							$end = WpSubscription::calculateEndDateFromStart($start, $sub->get('plan')->get('wpp_period_months'));
							$sub->set('wps_start_date',	$start, false ); // 3rd param = false = do not update db now
							$sub->set('wps_end_date', $end, false ); 
							$sub->set('wps_active',	true, false ); 
						} 
						// if startDate not null, this is a renewal, it will be activated later when needed
						
						$sub->set('wps_tmr_status', 'OK'); // no 3rd p = update db now
						return false; // this is our transaction, no more process to be done	
						
					case 'KO':
						// PE -> KO
						$sub->set('wps_tmr_status', 'KO', false);
						$sub->set('wps_active', false);  // in case of a renewal, it can be activated even if pending, so need to ensure that is false
						return false; // this is our transaction, no more process to be done	
						
					case 'PE':
						// PE -> PE   =>   don't care
						return false;
				}
				break;
			
		}
		
		// if we arrive here, this transaction is about a subscription, but we do not know what to do
		throw new MWException('The transaction of a subscription was updated, but this update is not managed ('.$sub->get('wps_tmr_status').'->'.$tmr['tmr_status'].')');	
		
	}
	
	
	/**
	 *
	 * @param Title $title
	 * @param User $user
	 * @param boolean $result 
	 */
	public static function isOwner ( $title, $user, &$result ) {
				
		if ( ! WpPage::isInWikiplaceNamespaces( $title->getNamespace() )  || ! $title->isKnown() ) {
			return true; // skip
		}
			
		$owner = WpPage::findOwnerUserIdByArticleId( $title->getArticleID() );

		if ($owner === false) {
			wfDebugLog('wikiplaces', 'isOwner: WARNING (title="' . $title->getPrefixedDBkey() .
					'" user=[' . $user->getId() . ']"' . $user->getName() .
					'" hookCurrentResult=' . ( $result ? 'YES' : 'NO' ) . 
					') unknown page in wikiplace namespace');
			return true; // we don't know, so we don't stop hook processing
		}

		// set answer
		$result = $user->getId() == $owner;

		wfDebugLog( 'wikiplaces', 'isOwner: '.($result ? 'YES':'NO').' (title="'.$title->getPrefixedDBkey().
				'" user=['.$user->getId().']"'.$user->getName().
				'" owner=['.$owner.'])');
		
		// stop hook processing, because we have the answer
		return false; 
		
	}
	
}
