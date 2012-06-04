<?php

class WpPage {

	private $wppa_id, // int(10) unsigned NOT NULL AUTO_INCREMENT 
			$wppa_wpw_id, // int(10) unsigned
			$wppa_page_id;   // int(10) unsigned

	private function __construct($id, $wikiplaceId, $pageId) {

		$this->wppa_id = intval($id);
		$this->wppa_wpw_id = intval($wikiplaceId);
		$this->wppa_page_id = intval($pageId);
	}

	/**
	 * Returns the Wikiplace identifier this page belongs to
	 * @return int 
	 */
	public function getWikiplaceId() {
		return $this->wppa_wpw_id;
	}

	/**
	 * Get the Wikiplace instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	private static function constructFromDatabaseRow($row) {

		if ($row === null) {
			throw new MWException('Cannot construct the page, no databse row given.');
		}

		if (!isset($row->wppa_id) || !isset($row->wppa_wpw_id) || !isset($row->wppa_page_id)) {
			throw new MWException('Cannot construct the page from the supplied row, missing field.');
		}

		return new self(intval($row->wppa_id), intval($row->wppa_wpw_id), intval($row->wppa_page_id));
	}

	/**
	 *
	 * @param type $conds
	 * @param type $multiple
	 * @return type 
	 */
	private static function search($conds, $multiple = false) {

		$dbr = wfGetDB(DB_SLAVE);

		$tables = array('wp_page', 'page');
		$vars = array('wppa_id', 'wppa_wpw_id', 'wppa_page_id', 'page_id', 'page_namespace', 'page_title');
		$fname = __METHOD__;
		$options = array();
		$join_conds = array('page' => array('INNER JOIN', 'wppa_page_id = page_id'));

		if ($multiple) {

			$results = $dbr->select($tables, $vars, $conds, $fname, $options, $join_conds);
			$pages = array();
			foreach ($results as $row) {
				$page = self::constructFromDatabaseRow($row);
				$page->fetchPage($row);
				$pages[] = $page;
			}
			$dbr->freeResult($results);
			return $pages;
		} else {

			$result = $dbr->selectRow($tables, $vars, $conds, $fname, $options, $join_conds);
			if ($result === false) {
				// not found, so return null
				return null;
			}
			return self::constructFromDatabaseRow($result);
		}
	}

	/**
	 * Restore from DB, using <b>article id</b>
	 * @param int $id 
	 * @return WpPage if found, or null if not
	 */
	public static function newFromArticleId($id) {

		if (($id === null) || !is_int($id) || ($id < 1)) {
			throw new MWException('Cannot search page, invalid article identifier.');
		}

		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow('wp_page', '*', array('wppa_page_id' => $id), __METHOD__);

		if ($result === false) {
			// not found, so return null
			return null;
		}

		return self::constructFromDatabaseRow($result);
	}

	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function newFromId($id) {

		if (($id === null) || !is_int($id) || ($id < 1)) {
			throw new MWException('Cannot search page, invalid identifier.');
		}

		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow('wp_page', '*', array('wppa_id' => $id), __METHOD__);

		if ($result === false) {
			// not found, so return null
			return null;
		}

		return self::constructFromDatabaseRow($result);
	}

	
	public static function newFromArticleDbKey($name) {

		if (($name === null) || !is_string($name)) {
			throw new MWException('Cannot search page, invalid name.');
		}

		return self::search(array('page_title' => $name));
	}

	/**
	 * Trigger MediaWiki article creation using template "Wikiplace homepage" as content
	 * @param string $new_wikiplace_name The new 'wikiplace_name'
	 * @param User $user The user creating the homepage
	 * @param string $template Template name
	 * @return Title/array The created homepage if ok, array containing error message + arg if error occured 
	 * <ul>
	 * <li><b>wp-bad-title</b> MediaWiki is unable to create this homepage. Title may contain bad characters.</li>
	 * <li><b<wp-title-already-exists</b> This homepage already exists</li>
	 * <li><b>sz-internal-error</b> if Mediawiki returned an error while creating the article:
	 * <ul>
	 * <li>The ArticleSave hook aborted the edit but didn't set the fatal flag of $status</li>
	 * <li>In update mode, but the article didn't exist</li>
	 * <li>In update mode, the article changed unexpectedly</li>
	 * <li>Warning that the text was the same as before</li>
	 * <li>In creation mode, but the article already exists</li>  
	 * </ul>
	 * </li>
	 * </ul>
	 */
	public static function createHomepage($new_wikiplace_name, $user, $template) {

		if (($new_wikiplace_name === null) || (!is_string($new_wikiplace_name))) {
			throw new MWException('Cannot create homepage, invalid name.');
		}

		$title = Title::newFromText($new_wikiplace_name);

		if (!($title instanceof Title)) {
			return array ( 'wp-bad-title' );
		}

		if ($title->isKnown()) {
			return array ( 'wp-title-already-exists' );
		}
		
		// as seen in EditPage->getEditPermissionErrors() ( called by EditPage->edit() )
		$permErrors = $title->getUserPermissionsErrors( 'edit', $user );
		$permErrors = array_merge( $permErrors,
				wfArrayDiff2( $title->getUserPermissionsErrors( 'create', $user ), $permErrors ) );
		if ( $permErrors ) { // creation impossible
			// var_export( $permErrors ) = array ( 0 => array ( 0 => 'pvdp-duplicate-exists', 1 => 'my_wikiplace_&_me', ), ) 
			return $permErrors[0]; // strange, but only key 0 seems to be used by MW when reading errors
		}

		$text = '{{subst:'.$template.'}}';

		// now store the new page in mediawiki, this will trigger a hook that will really create the WpPage
		$article = new Article($title);
		$status = $article->doEdit($text, '', EDIT_NEW, false, $user);

		if (!$status->isgood()) {
			return array ( 'sz-internal-error' );
		}

		return $title;
	}

	/**
	 * Trigger MediaWiki article creation of the wikiplace subpage. Use template "Wikiplace subpage" as content.
	 * @param WpWikiplace $wikiplace
	 * @param string $new_page_name
	 * @param User $user The user who creates the subpage
	 * @return Title/array the created Title, or an array containing i18n message key + args if an error occured
	 * <ul>
	 * <li><b>wp-bad-title</b> MediaWiki is unable to create this page. Title may contain bad characters.</li>
	 * <li><b<wp-title-already-exists</b> This page already exists</li>
	 * <li><b>sz-internal-error</b> if Mediawiki returned an error while creating the article:
	 * <ul>
	 * <li>The ArticleSave hook aborted the edit but didn't set the fatal flag of $status</li>
	 * <li>In update mode, but the article didn't exist</li>
	 * <li>In update mode, the article changed unexpectedly</li>
	 * <li>Warning that the text was the same as before</li>
	 * <li>In creation mode, but the article already exists</li>  
	 * </ul>
	 * </li>
	 * </ul>
	 */
	public static function createSubpage($wikiplace, $new_page_name, $user) {

		if (($wikiplace === null) || !($wikiplace instanceof WpWikiplace) ||
				( $new_page_name === null) || !is_string($new_page_name)) {
			throw new MWException('Cannot create Wikiplace page (wrong argument)');
		}

		$title = Title::newFromText($wikiplace->getName() . '/' . $new_page_name);

		// $title can be Title, or null on an error.

		if (!($title instanceof Title)) {
			return array('wp-bad-title');
		}

		if ($title->isKnown()) {
			return array('wp-title-already-exists');
		}

		// as seen in EditPage->getEditPermissionErrors() ( called by EditPage->edit() )
		$permErrors = $title->getUserPermissionsErrors( 'edit', $user );
		$permErrors = array_merge( $permErrors,
				wfArrayDiff2( $title->getUserPermissionsErrors( 'create', $user ), $permErrors ) );
		if ( $permErrors ) { // creation impossible 
			return $permErrors[0]; // strange, but only key 0 seems to be used by MW when reading errors
		}
		
		$text = '{{subst:Wikiplace subpage}}';

		// now store the new page in mediawiki, this will trigger the WikiplaceHook, wich will 
		// allow the page saving
		$article = new Article($title);
		$status = $article->doEdit($text, '', EDIT_NEW, false, $user);

		if (!$status->isgood()) {
			return array('sz-internal-error');
		}

		return $title;
	}

	/**
	 * Check that the $title is a homepage and not a subpage. The test is performed using
	 * the MediaWiki Title db_key and namespace, but doesn't ensure that the corresponding 
	 * wikiplace already exists.
	 * @param Title $title
	 * @return boolean 
	 */
	public static function isHomepage($title) {
		if (!($title instanceof Title)) {
			throw new MWException('Argument is not a MediaWiki Title.');
		}
		return ( ($title->getNamespace() == NS_MAIN) && (count(explode('/', $title->getDBkey())) == 1) );
	}

	/**
	 * True means that: <b>isInWikiplace()</b> OR <b>isPublic()</b> OR <b>isAdmin()</b>
	 * To really know if element should belong to a wikiplace,
	 * call isInWikiplace($namespace, $db_key) instead.
	 * @param int $namespace
	 * @return boolean
	 */
	public static function isInWikiplaceNamespaces($namespace) {
		if (!is_int($namespace)) {
			throw new MWException('Invalid namespace argument.');
		}

		global $wgWikiplaceNamespaces;

		return in_array($namespace, $wgWikiplaceNamespaces);
	}

	/**
	 *
	 * @param int $namespace
	 * @param string $db_key
	 * @return boolean
	 */
	public static function isInWikiplace($namespace, $db_key) {
		if (!is_int($namespace)) {
			throw new MWException('Invalid namespace argument.');
		}

		global $wgWikiplaceNamespaces;

		if (!in_array($namespace, $wgWikiplaceNamespaces)) {
			return false;
		}
		return (!WpPage::isPublic($namespace, $db_key) && !WpPage::isAdmin($namespace, $db_key) );
	}

	/**
	 *
	 * @param int $namespace
	 * @param string $db_key
	 * @return boolean 
	 */
	public static function isPublic($namespace, $db_key) {
		if (!($namespace == NS_FILE) && !($namespace == NS_FILE_TALK)) {
			return false;
		}
		$exploded = WpWikiplace::explodeWikipageKey($db_key, $namespace);
		return ( $exploded[0] == WP_PUBLIC_FILE_PREFIX );
	}

	/**
	 *
	 * @param int $namespace
	 * @param string $db_key
	 * @return boolean 
	 */
	public static function isAdmin($namespace, $db_key) {
		if (!($namespace == NS_FILE) && !($namespace == NS_FILE_TALK)) {
			return false;
		}
		$exploded = WpWikiplace::explodeWikipageKey($db_key, $namespace);
		return ( $exploded[0] == WP_ADMIN_FILE_PREFIX );
	}

	/**
	 * count all pages owned by user
	 * @param type $user_id
	 * @return int the number of pages 
	 */
	public static function countPagesOwnedByUser($user_id) {

		if (($user_id === null) || !is_int($user_id) || ($user_id < 1)) {
			throw new MWException('Invalid user identifier.');
		}

		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow(
				array('wp_wikiplace', 'wp_page'), array('count(*) as total'), array('wpw_owner_user_id' => $user_id), __METHOD__, array(), array('wp_page' => array('INNER JOIN', 'wpw_id = wppa_wpw_id')));

		if ($result === null) {
			return 0;
		}

		return intval($result->total);
	}

	/**
	 * Get current diskpace usage
	 * @param type $user_id
	 * @return int The diskpace usage <b>in MB</b>
	 */
	public static function countDiskspaceUsageByUser($user_id) {

		if (($user_id === null) || !is_int($user_id) || ($user_id < 1)) {
			throw new MWException('Invalid user identifier.');
		}

		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow(
				array('wp_wikiplace', 'wp_page', 'page', 'image'), array(
			'sum(img_size) >> 20  as total'
				), array(
			'wpw_owner_user_id' => $user_id,
			'page_namespace' => NS_FILE,
				), __METHOD__, array(), array(
			'wp_page' => array('INNER JOIN', 'wpw_id = wppa_wpw_id'),
			'page' => array('INNER JOIN', 'wppa_page_id = page_id'),
			'image' => array('INNER JOIN', 'page_title = img_name'),
				));

		if ($result === null) {
			return 0;
		}

		return intval($result->total);
	}

	/**
	 * Create a new WpPage record, which will associate a MediaWiki page to a Wikiplace.
	 * @param int $article_id the WikiPage/Article id
	 * @param int $wikiplace_id the WpWikiplace id
	 * @return WpPage The newly created WpPage, or null if a db error occured
	 */
	public static function create($article_id, $wikiplace_id) {

		if (!is_int($article_id) || !is_int($wikiplace_id) ||
				($article_id < 1) || ($wikiplace_id < 1)) {
			throw new MWException('Cannot create wikiplace page, invalid argument.');
		}

		$dbw = wfGetDB(DB_MASTER);

		$dbw->begin();

		// With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
		$id = $dbw->nextSequenceValue('wppa_id');

		$success = $dbw->insert('wp_page', array(
			'wppa_id' => $id,
			'wppa_wpw_id' => $wikiplace_id,
			'wppa_page_id' => $article_id
				), __METHOD__);

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();

		$dbw->commit();

		if (!$success) {
			return null;
		}

		$wpp = new self($id, $wikiplace_id, $article_id);

		return $wpp;
	}

	/**
	 * Delete the association of an article to a wikiplace.
	 * @param int $article_id page_id, usually $title->getArticle()->getId()
	 * @return boolean
	 * @throws MWException 
	 */
	public static function delete($article_id) {

		if (!is_int($article_id) || ( $article_id < 1 )) {
			throw new MWException('Cannot delete wikiplace page, invalid wikipage identifier.');
		}

		$dbw = wfGetDB(DB_MASTER);

		$dbw->begin();

		$success = $dbw->delete('wp_page', array('wppa_page_id' => $article_id), __METHOD__);

		$dbw->commit();

		return $success;
	}

	/**
	 *
	 * @param int $wikiplace_id 
	 */
	public function setWikiplaceId($wikiplace_id) {

		if (!is_int($wikiplace_id) || ($wikiplace_id < 0)) {
			throw new MWException('Invalid Wikiplace identifier.');
		}

		$this->wppa_wpw_id = intval($wikiplace_id);
		$this->wikiplace = null;

		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		$success = $dbw->update(
				'wp_page', array('wppa_wpw_id' => $this->wppa_wpw_id), array('wppa_id' => $this->wppa_id));

		$dbw->commit();

		if (!$success) {
			throw new MWException('Error while updating Wikiplace page to database.');
		}
	}

	/**
	 *
	 * @param int $pageId Article id
	 */
	public function setPageId($pageId) {

		if (!is_int($pageId) || ($pageId < 1)) {
			throw new MWException('Invalid article identifier.');
		}

		$this->wppa_page_id = intval($pageId);

		$this->page_namespace = null;
		$this->page_title = null;

		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		$success = $dbw->update(
				'wp_page', array('wppa_page_id' => $this->wppa_page_id), array('wppa_id' => $this->wppa_id));

		$dbw->commit();

		if (!$success) {
			throw new MWException('Error while updating Wikiplace page article identifier to database.');
		}
	}

	/**
	 * Test if the user is the owner of the page.
	 * If the page is not found, user is owner if she has WP_ADMIN_RIGHT right
	 * @param $article_id The Mediawiki article identifier (primary key in page table), NOT the WpPage
	 * table primary key. Can be $title->getArticleID().
	 * @param User $user
	 * @return boolean true = user is owner, false = not
	 */
	public static function isOwner($article_id, $user) {

		$owner = self::findOwnerUserIdByArticleId($article_id);

		return ( ( $owner == 0 && $user->isAllowed(WP_ADMIN_RIGHT) )
				||
				( $owner == $user->getId() ) );
	}

	/**
	 * Find the owner "user id" of a wikiplace page
	 * @param int $article_id
	 * @return int the user id, 0 if the page is not a wikiplace page
	 */
	public static function findOwnerUserIdByArticleId($article_id) {

		if (!is_int($article_id) || ($article_id < 0)) {
			throw new MWException('Cannot find the owner, invalid argument.');
		}

		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectField(
				array('wp_page', 'wp_wikiplace'), 'wpw_owner_user_id', array(
			'wppa_page_id' => $article_id,
			'wppa_wpw_id = wpw_id'), __METHOD__);

		if ($result === false) {
			// not found, so return null
			return 0;
		}

		return intval($result);
	}

}
