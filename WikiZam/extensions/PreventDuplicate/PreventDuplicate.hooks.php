<?php

/**
 *
 * @author yannouk
 */
class PreventDuplicateHooks {

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
				if (!$title->isKnown() && ($duplicate = TitleKey::exactMatchTitle($title))) {
					$result = array('pvdp-duplicate-exists', $duplicate->getPrefixedDBkey());
					wfDebugLog('preventduplicate', "cannot $action {$title->getPrefixedDBkey()}, duplicate {$duplicate->getPrefixedDBkey()} exists");
					return false;
				}
		}

		return true;
	}

	/**
	 * If requested title doesn't exist, redirect to duplicate title (same name but different case) if exists.
	 * NOTE: As of MediaWiki 1.18.0, $article is NULL 
	 * @param Title $title
	 * @param type $article
	 * @param type $output
	 * @param type $user
	 * @param type $request
	 * @param type $mediaWiki
	 * @return boolean 
	 */
	public static function redirectDuplicate(&$title, $article, &$output, &$user, $request, $mediaWiki) {

		if (( $title->getNamespace() != NS_SPECIAL )
				&& !$title->isKnown()
				&& ( $duplicate = TitleKey::exactMatchTitle($title) )) {

			wfDebugLog('preventduplicate', "{$title->getPrefixedDBkey()} asked, doesn't exist, suggest to use {$duplicate->getPrefixedDBkey()} instead");

			$title = $duplicate;
		}

		return true;
	}

}