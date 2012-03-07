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
	 *
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
		
		wfDebugLog( 'wikiplace', 'onCreateArticle: "'.$t.'"');
		
		if ($namespace != NS_MAIN) {
			return true; // continue without stopping hook processing
		}
		
		WpPage::create(0, $id);
		return true;
	}
	
	
	/**
	 *
	 * @param type $article the article (Article object) being saved
	 * @param type $user the user (User object) saving the article
	 * @param type $text the new article text
	 * @param type $summary the edit summary
	 * @param type $minor minor edit flag
	 * @param type $watchthis  watch the page if true, unwatch the page if false, do nothing if null (since 1.17.0)
	 * @param type $sectionanchor not used
	 * @param type $flags bitfield, see documentation for details
	 * @param type $status 
	 */
	public static function onArticleSave( &$article, &$user, &$text, &$summary,
 $minor, $watchthis, $sectionanchor, &$flags, &$status ) {
		
		$title = $article->getTitle();
		$t = $title->getFullText();
		
		wfDebugLog( 'wikiplace', 'onArticleSave: "'.$t.'"');
		
		if ( count(explode('/', $t)) < 2 ) {
			//throw new Exception();
			// too bad you can't pass parameter to errorpage
			throw new ErrorPageError( 'wikiplace_error_page_title', 'wikiplace_title_error' );
		}

		return true;
		
	}
	
	
	/*
	public static function onUserCan( $title, &$user, $action, &$result ) {	
		wfDebugLog( 'wikiplace', 'userCan');
		return true;
	}
	 */
	
	
	
	
	public static function onTransactionUpdated( $tmr ) {	
		wfDebugLog( 'wikiplace', 'onTransactionUpdated');
		return true;
	}
	
}
