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
		
		if ( WpPage::isItAWikiplacePage($title) ) {
			// this page belongs to our extension, so we have some checks to do
	
			if ($title->isKnown()) {
				// we are updating an existing wikiplace page
				wfDebugLog( 'wikiplace', 'onArticleSave: updating the WikiPlace page ['.$i.'}"'.$t.'"');
				return true; // "OK, you can edit"


			} else {
				// we are creating a new page
				wfDebugLog( 'wikiplace', 'onArticleSave: creating a new WikiPlace page ['.$i.']"'.$t.'"');
				
				switch (WpPage::canThisWikiplacePageBeCreated($title)) {
					case 0:
						return true; // "OK, you can create"
						break;
					case 1:
						throw new ErrorPageError( 'cannot_create_wp_page_title', 'cannot_identify_container_wikiplace' );
					case 2:
						throw new ErrorPageError( 'cannot_create_wp_page_title', 'it_is_not_your_wikiplace' );
					case 3:
						throw new ErrorPageError( 'cannot_create_wp_page_title', 'you_need_an_active_subscription' );
					case 4:
						throw new ErrorPageError( 'cannot_create_wp_page_title', 'subscription_max_nb_pages_reached' );
					default:
						throw new ErrorPageError( 'cannot_create_wp_page_title', 'unknown_error' );
				}
				
			}
			
		} else {
			
			// we are not concerned
			wfDebugLog( 'wikiplace', 'onArticleSave: saving a page that does not belong to a wikiplace: ['.$i.']"'.$t.'"');
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
		
		// now, the page is already stored in db, so if there is a problem, it's too late
		
		if ( WpPage::isItAWikiplacePage($title) ) {
							
			$wp = WpWikiplace::identifyContainerWikiPlaceOfThisNewTitle($title);
			if ($wp === null) {
				wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR, cannot identify the container wikiplace for title ['.$i.']"'.$t.'"');
				throw new MWException('cannot identify the container wikiplace');
			}
			
			$new_wp_page = WpPage::associateAPageToAWikiplace($title, $wp);
			
			if ($new_wp_page === null) {
				// something goes wrong
				wfDebugLog( 'wikiplace', 'onCreateArticle: ERROR, the page cannot be associated to its wikiplace: ['.$i.']"'.$t.'"');
				throw new MWException('Error while associating the page to a wikiplace.');
			}
			
			wfDebugLog( 'wikiplace', 'onCreateArticle: OK, the page is now associated to its wikiplace: ['.$i.']"'.$t.'"');
				
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
	
}
