<?php

if (!defined('MEDIAWIKI')) {
	die(-1);
}

class WikiplaceHooks {

	# Schema updates for update.php
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		
		$tables = array(
			'wp_plan', 
			'wp_subscription',
			'wp_usage',
			'wp_wikiplace',
			'wp_page'
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
	 * @todo:fix this
	 * @param Article $article the article (Article object) being saved
	 * @param type $user the user (User object) saving the article
	 * @param type $text the new article text
	 * @param type $summary the edit summary
	 * @param type $minor minor edit flag
	 * @param type $watchthis  watch the page if true, unwatch the page if false, do nothing if null (since 1.17.0)
	 * @param type $sectionanchor not used
	 * @param type $flags bitfield, see documentation for details
	 * @param type $status 
	 */
	public static function onArticleSave( &$article, &$user, &$text, &$summary, $minor, $watchthis, $sectionanchor, &$flags, &$status ) {
		
		$title = $article->getTitle();
		$t = $title->getFullText();
		$i = $title->getArticleID();
		
		if ( WpPage::isThisPageInTheWikiplaceDomain($title) ) {
			// this page belongs to our extension, so we have some checks to do
	
			if ($title->isKnown()) {
				// we are updating an existing wikiplace page
				// nothing special to check
				wfDebugLog( 'wikiplace', 'onArticleSave: ALLOW updating the WikiPlace page ['.$i.']"'.$t.'"');
				return true; // "OK, you can edit"


			} else {
				// we are creating a new page
				
				if ( WpPage::isItAWikiplaceHomePage($title)) {
					
					// this is a homepage, so we are creating a new wikiplace

					if ( !WpWikiplace::doesTheUserCanCreateANewWikiplace($user->getId()) ) {
						wfDebugLog( 'wikiplace', 'onArticleSave: DENY new homepage but no sub or no more quota ['.$i.']"'.$t.'"');
						return false; // no active subscription or a creation quota is exceeded
					}

					wfDebugLog( 'wikiplace', 'onArticleSave: ALLOW new homepage ['.$i.']"'.$t.'"');
					return true; // all ok :)
					
					
				} else {
					
					// this is a subpage

					$wp = WpWikiplace::identifyContainerWikiPlaceOfThisNewTitle($title);
					if ($wp === null) { 
						wfDebugLog( 'wikiplace', 'onArticleSave: DENY new WikiPlace page but cannot identify wikiplace ['.$i.']"'.$t.'"');
						return false; // no wikiplace can contain this subpage, so cannot create it
					}

					$wp_owner_id = $wp->get('wpw_owner_user_id');
					/** @todo:this check would be better if placed in rights management system, and when it will be, this test here will become useless */
					if ($wp_owner_id != $user->getId()) { // checks the user who creates the page is the owner of the wikiplace
						wfDebugLog( 'wikiplace', 'onArticleSave: DENY new WikiPlace page but current user != wikiplace owner ['.$i.']"'.$t.'"');
						return false;
					}

					if ( ! WpPage::doesTheUserCanCreateANewPage($wp_owner_id) ) {
						wfDebugLog( 'wikiplace', 'onArticleSave: DENY new WikiPlace page but no sub or no more quota ['.$i.']"'.$t.'"');
						return false; // no active subscription or page creation quota is exceeded
					}

					return true; // all ok :)
				}
				
			}
			
		} else {
			
			// we are not concerned
			wfDebugLog( 'wikiplace', 'onArticleSave: ALLOW saving a page that does not belong to the wikiplace namespace: ['.$i.']"'.$t.'"');
			return true; // "OK, you can create"

		}
		
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
	public static function onCreateArticle( $wikipage, $user, $text, $summary, $isMinor, $isWatch, $section, $flags, $revision ) {
		
		$id = $wikipage->getId();
		$title = $wikipage->getTitle();
		$namespace = $title->getNamespace();
		$t = $title->getFullText();
		$i = $title->getArticleID();
		$user_id = $user->getId();
		
		// now, the page is already stored in db, so if there is a problem, it's too late
		
		if (WpPage::isThisPageInTheWikiplaceDomain($title)) {
			
			if (WpPage::isItAWikiplaceHomePage($title)) {
				
				// create a wikiplace from this homepage title				
				$wp = WpWikiplace::create($title, $user_id);
				if ($wp === null) {
					wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR while creating wikiplace: ['.$i.']"'.$t.'"');
					throw new MWException('Cannot create wikiplace.');
				}		
				
				wfDebugLog( 'wikiplace', 'onCreateArticle: OK, wikiplace and its homepage created: ['.$i.']"'.$t.'"');
				
			} else {
				
				// this is a subpage of an existing existing wikiplace
			
				$wp = WpWikiplace::identifyContainerWikiPlaceOfThisNewTitle($title);
				if ($wp === null) {
					wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR cannot identify container wikiplace: ['.$i.']"'.$t.'"');
					throw new MWException('Cannot identify the container wikiplace.');
				}
			
				$new_wp_page = WpPage::associateAPageToAWikiplace($title, $wp);
				if ($new_wp_page === null) {
					wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR while associating the page to the container wikiplace: ['.$i.']"'.$t.'"');
					throw new MWException('Cannot associate the page to a wikiplace.');
				}
						
				wfDebugLog( 'wikiplace', 'onCreateArticle: OK, the page is now associated to its wikiplace: ['.$i.']"'.$t.'"');
				
			} 
		
			
		} else {
			
			wfDebugLog( 'wikiplace', 'onCreateArticle: creating a page that does not belong to a wikiplace: ['.$i.']"'.$t.'"');
			
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
							$now = WpPlan::getNow();
							$end = WpPlan::calculateTick($now, $sub->get('plan')->get('wpp_period_months'));
							$tick = WpPlan::calculateTick($now, 1);
							$sub->set('wps_start_date',	$now, false );	// 3rd p = false = do not update db
							$sub->set('wps_next_monthly_tick',	$tick, false );	// 3rd p = false = do not update db
							$sub->set('wps_end_date', $end, false );	// 3rd p = false = do not update db
							$sub->set('wps_active',	true, false );	// 3rd p = false = do not update db
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
		throw new MWException('The transaction of a subscription was updated, but the system doesn\'t know what to do... ('.$sub->get('transactionStatus').'->'.$tmr['tmr_status'].')');	
		
	}
	
	
	/**
	 *
	 * @param Title $title
	 * @param User $user
	 * @param boolean $result 
	 */
	public static function onIsOwner ( $title, $user, &$result ) {
				
		$owner = WpPage::findWikiplacePageOwnerUserId($title);
		
		if ($owner === false) {
			wfDebugLog( 'wikiplace', 'onIsOwner DON\'T KNOW (title "'.$title->getDBkey().'" is not in a wikiplace)');
			return true; // we don't know, so we don't stop hook processing
		}
		
		$result = $user->getId() == $owner;
		
		wfDebugLog( 'wikiplace', 'onIsOwner '.($result ? 'YES':'NO').' (title "'.$title->getDBkey().
				'" user ['.$user->getId().']"'.$user->getName().'" owner ['.$owner.'])');
		return false; // stop hook processing, because we have the answer
	}
	
}
