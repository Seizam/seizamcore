<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PreventDuplicate
 *
 * @author yannouk
 */
class PreventDuplicateHooks {
	//put your code here

	/**
	 * @param Title $title the article (Article object) being saved
	 * @param User $user the user (User object) saving the article
	 * @param string $action the action
	 * @param array $result User permissions error to add. If none, return true. $result can be 
	 * returned as a single error message key (string), or an array of error message keys when 
	 * multiple messages are needed (although it seems to take an array as one message key 
	 * with parameters?)
	 */
	public static function blockCreateDuplicate($title, $user, $action, &$result) {

		switch ($action) {
			case 'create':
			case 'edit':
			case 'upload':
			case 'createpage':
			case 'move-target':
				if (!$title->isKnown()) {
					break;
				}
			default:
				return true;
		}

		if ($duplicate = TitleKey::exactMatchTitle($title)) {

			wfDebugLog('preventduplicate', "{$user->getName()} cannot $action {$title->getPrefixedDBkey()} (duplicate {$duplicate->getPrefixedDBkey()} exists)");

			$result = array('pvdp-duplicate-exists', $duplicate->getPrefixedDBkey());
			return false;
		}

		return true;
	}


	/**
	 * NOTE: As of MediaWiki 1.18.0, $article is NULL 
	 */
	public static function onBeforeInitialize(&$title, $article, &$output, &$user, $request, $mediaWiki) {
		wfDebugLog('preventduplicate', "onBeforeInitialize()");
		return true;
	}

	/**
	 * TestCanonicalRedirect hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TestCanonicalRedirect
	 *
	 * @param $request WebRequest
	 * @param $title Title
	 * @param $output OutputPage
	 * @return bool
	 * @throws HttpError
	 */
	public static function redirectToDuplicateTitle($request, $title, $output) {
		
		// in wiki.php, test $title->getPrefixedDBKey() != $request->getVal( 'title' ) breaks, why ? 
		// var_export(array(	$title->getPrefixedDBKey() , $request->getVal( 'title' ) ), true)
		// array (
		//  0 => 'laila',
		//  1 => 'laila', this titledoesn't exists
		// )

		wfDebugLog('preventduplicate', wfGetPrettyBacktrace());

		if (( $title->getNamespace() == NS_SPECIAL )
				|| $title->exists()
				|| !( $duplicate = TitleKey::exactMatchTitle($title) )) {

			// skip
			return true;
		}

		wfDebugLog('preventduplicate', "{$title->getPrefixedDBkey()} asked, it doesn't exist but there is a duplicate title {$duplicate->getPrefixedDBkey()} already existing -> redirecting to it");

		$targetUrl = wfExpandUrl($duplicate->getFullURL(), PROTO_CURRENT);
		// Redirect to canonical url, make it a 301 to allow caching
		if ($targetUrl == $request->getFullRequestURL()) {
			$message = "Redirect loop detected!\n\n" .
					"This means the wiki got confused about what page was " .
					"requested; this sometimes happens when moving a wiki " .
					"to a new server or changing the server configuration.\n\n";

			if ($wgUsePathInfo) {
				$message .= "The wiki is trying to interpret the page " .
						"title from the URL path portion (PATH_INFO), which " .
						"sometimes fails depending on the web server. Try " .
						"setting \"\$wgUsePathInfo = false;\" in your " .
						"LocalSettings.php, or check that \$wgArticlePath " .
						"is correct.";
			} else {
				$message .= "Your web server was detected as possibly not " .
						"supporting URL path components (PATH_INFO) correctly; " .
						"check your LocalSettings.php for a customized " .
						"\$wgArticlePath setting and/or toggle \$wgUsePathInfo " .
						"to true.";
			}
			wfHttpError(500, "Internal error", $message);
		} else {
			$output->setSquidMaxage(1200);
			$output->redirect($targetUrl, '301');
		}

		return false; // Prevent the redirect from occurring
	}

}