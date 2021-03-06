<?php
/**
 * Hooks for Title Blacklist
 * @author Victor Vasiliev
 * @copyright © 2007-2010 Victor Vasiliev et al
 * @license GNU General Public License 2.0 or later
 */

/**
 * Hooks for the EmailBlacklist class
 *
 * @ingroup Extensions
 */
class EmailBlacklistHooks {

	/**
	 * AbortNewAccount hook
	 *
	 * @param User $user
	 */
	public static function abortNewAccount( $user, &$message ) {
		global $wgUser;
        $blacklisted = EmailBlacklist::singleton()->userCannot($user->getEmail(), $wgUser);
        if ($blacklisted instanceof EmailBlacklistEntry) {
            $message = wfMessage($blacklisted->getErrorMessage())->parse();
            return false;
        }
		return true;
	}

	/**
	 * EditFilter hook
	 *
	 * @param $editor EditPage
	 */
	public static function validateBlacklist( $editor, $text, $section, &$error ) {
		global $wgUser;
		$title = $editor->mTitle;

		if( $title->getNamespace() == NS_MEDIAWIKI && $title->getDBkey() == 'Emailblacklist' ) {

			$blackList = EmailBlacklist::singleton();
			$bl = $blackList->parseBlacklist( $text );
			$ok = $blackList->validate( $bl );
			if( count( $ok ) == 0 ) {
				return true;
			}

			$errlines = '* <tt>' . implode( "</tt>\n* <tt>", array_map( 'wfEscapeWikiText', $ok ) ) . '</tt>';
			$error = Html::openElement( 'div', array( 'class' => 'errorbox' ) ) .
				'Validation Error(s) in EmailBlacklist' .
				"\n" .
				$errlines .
				Html::closeElement( 'div' ) . "\n" .
				Html::element( 'br', array( 'clear' => 'all' ) ) . "\n";

			// $error will be displayed by the edit class
			return true;
		}
		return true;
	}

	/**
	 * ArticleSaveComplete hook
	 *
	 * @param Article $article
	 */
	public static function clearBlacklist( &$article, &$user,
		$text, $summary, $isminor, $iswatch, $section )
	{
		$title = $article->getTitle();
		if( $title->getNamespace() == NS_MEDIAWIKI && $title->getDBkey() == 'Emailblacklist' ) {
			EmailBlacklist::singleton()->invalidate();
		}
		return true;
	}
}
