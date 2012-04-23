<?php

if (!defined('MEDIAWIKI')) {
	die(-1);
}

class WikiplaceHooks {
	
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
	 * Called when creating a new page or editing an existing page
	 * This hook but say "OK, you can create" or "no, you can't, I abort the creation"
	 * but MediaWiki interpret this "NO" has there is a conflict while editing the page
	 * @todo:fix this (use hook userCan)
	 * @param Article $article the article (Article object) being saved
	 * @param User $user the user (User object) saving the article
	 * @param type $text the new article text
	 * @param type $summary the edit summary
	 * @param type $minor minor edit flag
	 * @param type $watchthis  watch the page if true, unwatch the page if false, do nothing if null (since 1.17.0)
	 * @param type $sectionanchor not used
	 * @param type $flags bitfield, see documentation for details
	 * @param type $status 
	 */
	// public static function onArticleSave( &$article, &$user, &$text, &$summary, $minor, $watchthis, $sectionanchor, &$flags, &$status ) {
	public static function userCanCreate( $title, &$user, $action, &$result ) {
		
		wfDebugLog( 'wikiplace', 'userCanCreate: '
				.' title="'.$title->getPrefixedDBkey().'"['.$title->getArticleId().'] isKnown()='.var_export($title->isKnown(),true)
				.' user="'.$user->getName().'"['.$user->getID().']'
				.' action="'.$action.'"');
		
		if ( ( ($action != 'create') && ($action != 'edit') && ($action != 'upload') && ($action != 'createpage') ) ||
				!WpPage::isInWikiplaceNamespaces($title) ||
				$title->isKnown() ) {
			return true;
		}
				
		if ( $user->getID() == 0 ) {
			wfDebugLog( 'wikiplace', 'userCanCreate: DENY user is not logged in');
			$result = false;
			return false;
		}
		
		$full_text = $title->getFullText();
		$article_id = $title->getArticleID();
		$user_id = $user->getId();
		
		if ( isset(self::$userCanCreateCache[$full_text][$user_id]) ) {
			$result = self::$userCanCreateCache[$full_text][$user_id];
			wfDebugLog( 'wikiplace', 'userCanCreate: CACHE HIT "'
					.( $result ? 'YES': 'NO' ).'" ['
					.$article_id.']"'.$full_text.'" ['
					.$user->getID().']"'.$user->getName().'"');
			return false; // stop hook processing
		}
		
		if ( WpPage::isHomepage($title)) {

			// this is a new Wikiplace

			if ( !WpWikiplace::userCanCreateWikiplace($user->getId()) ) {
				wfDebugLog( 'wikiplace', 'userCanCreate: DENY new Wikiplace, but no active sub or no more quota ['.$article_id.']"'.$full_text.'"');
				$result = false; // no active subscription or a creation quota is exceeded
				
			} else {
				wfDebugLog( 'wikiplace', 'userCanCreate: ALLOW new Wikiplace ['.$article_id.']"'.$full_text.'"');
				$result = true;
			}
			
		} else {

			// this is a Wikipage (can be regular article or talk or file)

			$wp = WpWikiplace::extractWikiplaceRoot($title);
			if ( $wp === null ) { 
				wfDebugLog( 'wikiplace', 'userCanCreate: DENY cannot extract container Wikiplace ['.$article_id.']"'.$full_text.'"');
				$result = false; // no wikiplace can contain this subpage, so cannot create it
				
			} elseif ( ! $wp->isOwner($user_id) ) { // checks the user who creates the page is the owner of the wikiplace
				wfDebugLog( 'wikiplace', 'userCanCreate: DENY new Wikipage, but current user is not Wikiplace owner ['.$article_id.']"'.$full_text.'"');
				$result = false;
				
			} else {
				
				if ($title->getNamespace() == NS_FILE) {
					
					// the user is uploading a file
			
					/** @todo: complete test */
					if (!WpPage::userCanUploadNewFile($user_id)) {
						wfDebugLog('wikiplace', 'userCanCreate: DENY new file, but no active sub or no more quota [' . $article_id . ']"' . $full_text . '"');
						$result = false; // no active subscription or page creation quota is exceeded
					} else {
						wfDebugLog('wikiplace', 'userCanCreate: ALLOW new sub page [' . $article_id . ']"' . $full_text . '"');
						$result = true;
					}
					
			
				} else {
					
					// the user is creating a new page (regular or talk)
					
					if (!WpPage::userCanCreateNewPage($user_id)) {
						wfDebugLog('wikiplace', 'userCanCreate: DENY new Wikipage, but no active sub or no more quota [' . $article_id . ']"' . $full_text . '"');
						$result = false; // no active subscription or page creation quota is exceeded
					} else {
						wfDebugLog('wikiplace', 'userCanCreate: ALLOW new Wikipage [' . $article_id . ']"' . $full_text . '"');
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
	 * @param type $wikipage the Article or WikiPage (object) saved. Article for MW < 1.18, WikiPage for MW >= 1.18
	 * @param type $user the user (object) who saved the article
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
		
		if ( ! WpPage::isInWikiplaceNamespaces($title)) {
			return true; // skip
		}
		
		$namespace = $title->getNamespace();
		$full_text = $title->getFullText();
		$article_id = $title->getArticleID();
		
		// now, the page is already stored in db, so if it should not, it's too late, so we just record it
			
		if (WpPage::isHomepage($title)) {

			// create a wikiplace from this homepage title				
			$wp = WpWikiplace::create($title, $user->getId());
			if ($wp === null) {
				wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR while creating wikiplace: ['.$article_id.']"'.$full_text.'"');
				throw new MWException('Cannot create wikiplace.');
			}		

			wfDebugLog( 'wikiplace', 'onCreateArticle: OK, wikiplace and its homepage created: ['.$article_id.']"'.$full_text.'"');

		} else {

			// this is a subpage of an existing existing wikiplace

			$wp = WpWikiplace::extractWikiplaceRoot($title);
			if ($wp === null) {
				wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR cannot identify container wikiplace: ['.$article_id.']"'.$full_text.'"');
				throw new MWException('Cannot identify the container wikiplace.');
			}

			$new_wp_page = WpPage::attachNewPageToWikiplace($title, $wp);
			if ($new_wp_page === null) {
				wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR while associating the page to the container wikiplace: ['.$article_id.']"'.$full_text.'"');
				throw new MWException('Cannot associate the page to a wikiplace.');
			}

			wfDebugLog( 'wikiplace', 'onCreateArticle: OK, the page is now associated to its wikiplace: ['.$article_id.']"'.$full_text.'"');

		} 
		
		return true;
	}
		
	
	/**
	 *
	 * @param type $tmr
	 * @return boolean Always true (processing is never stopped) 
	 */
	public static function onTransactionUpdated( $tmr ) {	
		wfDebugLog( 'wikiplace', 'onTransactionUpdated');

		$sub = WpSubscription::getByTransactionId($tmr['tmr_id']);
		if ($sub === null) {
			return true; // we are not concerned, so don't stop processing
		}
		
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
							$end = WpSubscription::calculateEndDate($start, $sub->get('plan')->get('wpp_period_months'));
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
		
		// if we arrive here, this transaction is about a subscription, but we do not know what to do... there is a problem!
		throw new MWException('The transaction of a subscription was updated, but this update is not managed ('.$sub->get('transactionStatus').'->'.$tmr['tmr_status'].')');	
		
	}
	
	
	/**
	 *
	 * @param Title $title
	 * @param User $user
	 * @param boolean $result 
	 */
	public static function isOwner ( $title, $user, &$result ) {
				
		if ( !WpPage::isInWikiplaceNamespaces($title) || !$title->isKnown() ) {
			return true; // skip
		}
			
		$owner = WpPage::findPageOwnerUserId($title);

		if ($owner === false) {
			wfDebugLog( 'wikiplace', 'isOwner DON\'T KNOW (current value = '.( $result ? 'YES' : 'NO' ).') because title "'.$title->getFullText().'" is not in a wikiplace');
			return true; // we don't know, so we don't stop hook processing
		}

		$result = $user->getId() == $owner;

		wfDebugLog( 'wikiplace', 'isOwner '.($result ? 'YES':'NO').' (title "'.$title->getDBkey().
				'" user ['.$user->getId().']"'.$user->getName().'" owner ['.$owner.'])');
		return false; // stop hook processing, because we have the answer
	}
	
}
