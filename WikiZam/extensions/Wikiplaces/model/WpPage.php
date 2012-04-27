<?php

class WpPage {  

	private	$wppa_id,				// int(10) unsigned NOT NULL AUTO_INCREMENT 
	        $wppa_wpw_id,			// int(10) unsigned
	        $wppa_page_id;			// int(10) unsigned
				
	private $page_namespace;	// int(11)
	private $page_title;
	private $wikiplace;
		
	private function __construct( $id, $wikiplaceId, $pageId ) {

		$this->wppa_id      = $id;
		$this->wppa_wpw_id  = $wikiplaceId;
		$this->wppa_page_id = $pageId;

	}
	
	private function fetchWikiplace($databaseRow = null) {
		
		if ($databaseRow !== null) {
			
			$wikiplace = WpWikiplace::constructFromDatabaseRow($databaseRow);
			
			if ($this->wikiplace->get('wpw_id') != $this->wppa_wpw_id) {
				throw new MWException('The wikiplace given is not the one the page belongs to.');
				
			} else {
				$this->wikiplace = $wikiplace;
			}
			
		} else {
			
			$this->wikiplace = WpWikiplace::getById($this->wppa_wpw_id);
			
			if ($this->wikiplace === null) {
				// there is a big problem... the page belongs to nothing!
				throw new MWException('Unknown wikiplace.');
			} 
		}
		
	}
	
	/**
	 * Fetch the page
	 * @param StdClass/Title/WikiPage $data if null, this function will query the database
	 */
	private function fetchPage( $data = null ) {
		
		if ($data === null) {
			$dbr = wfGetDB(DB_SLAVE);
			$data = $dbr->selectRow( 
					'page', 
					array('page_id', 'page_namespace','page_title'),
					array( 'page_id' =>  $this->wppa_page_id ),
					__METHOD__ );
		}
		
		if ( $data instanceof WikiPage ) {
			$data = $data->getTitle();
		}
		
		if ( $data instanceof Title) {
			
			if ( $data->getArticleID() != $this->wppa_page_id) {
				throw new MWException('Cannot fetch page, the Title object does not match with the current page.');
			} 
			$this->page_namespace = $data->getNamespace();	
			$this->page_title     = $data->getText();
			
		} elseif ( isset($data->page_id) && isset($data->page_title) && isset($data->page_namespace) ) {
			
			if ($data->page_id != $this->wppa_page_id) {
				throw new MWException('Cannot fetch page, the database row does not match with the current page.');
			} 
			$this->page_namespace = $data->page_namespace;	
			$this->page_title     = $data->page_title;
			
		} else {
			
			throw new MWException('Cannot fetch page.');
			
		}

	}
	
		/**
	 *
	 * @param type $attribut_name
	 * @return type 
	 */
	public function get($attribut_name) {
		switch ($attribut_name) {
			case 'wppa_id':
			case 'wppa_wpw_id':
			case 'wppa_page_id':
				return intval($this->$attribut_name);
				break;
			case 'page_namespace':
			case 'page_title':
				if ($this->$attribut_name === null) {
					$this->fetchPage();
				}
				return $this->$attribut_name;
				break;
		}
		throw new MWException('Unknown attribut '.$attribut_name);
	}
	
	
	/**
	 * Get the Wikiplace instance from a SQL row
	 * @param ResultWrapper $row
	 * @return self 
	 */
	private static function constructFromDatabaseRow( $row ) {
			
		if ( $row === null ) {
			throw new MWException( 'Cannot construct the page, no databse row given.' );
		}
		
		if ( !isset($row->wppa_id) || !isset($row->wppa_wpw_id) || !isset($row->wppa_page_id) ) {
			throw new MWException( 'Cannot construct the page from the supplied row, missing field.' );
		}
			
		return new self ( intval($row->wppa_id) , intval($row->wppa_wpw_id) ,  intval($row->wppa_page_id) );
		
	}
	
	/**
	 *
	 * @param type $conds
	 * array( 'page_title' =>  $subpage_name , 'page_namespace' => WP_PAGE_NAMESPACE , 'wppa_wpw_id' => $wikiplace_id ),
	 * @param type $multiple
	 * @return type 
	 */
	private static function getFromDb($conds, $multiple = false) {
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$tables = array ( 'wp_page', 'page' );
		$vars = array( 'wppa_id','wppa_wpw_id', 'wppa_page_id',    'page_id', 'page_namespace','page_title');
		$fname = __METHOD__;
		$options = array();
		$join_conds = array( 'page' => array('INNER JOIN','wppa_page_id = page_id') );
		
		if ($multiple) {
			
			$results = $dbr->select($tables, $vars, $conds, $fname, $options, $join_conds);
			$pages = array();
			foreach ( $results as $row ) {
				$page = self::constructFromDatabaseRow($row);
				$page->fetchPage($row);
				$pages[] = $page;
			}
			$dbr->freeResult( $results );
			return $pages;
			
		} else {
			
			$result = $dbr->selectRow($tables, $vars, $conds, $fname, $options, $join_conds);
			if ( $result === false ) {
				// not found, so return null
				return null;
			}
			return self::constructFromDatabaseRow($result);
			
		}
				
	}
	
	
	/**
	 * Restore from DB, using id
	 * @param int $id 
	 * @return WpWikiplace if found, or null if not
	 */
	public static function getById($id) {
				
		if ( ($id === null) || !is_int($id) || ($id < 1) ) {
			throw new MWException( 'Cannot search page, invalid identifier.' );
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 'wp_page', '*',	array( 'wppa_id' =>  $id ), __METHOD__ );
		
		if ( $result === false ) {
			// not found, so return null
			return null;
		}
		
		return self::constructFromDatabaseRow($result);

	}

	
	public static function getByName($name) {
				
		if ( ($name === null) || !is_string($name) ) {
			throw new MWException( 'Cannot search page, invalid name.' );
		}
		
		return self::getFromDb(array('page_title' => $name));

	}
	
	
	/**
	 * Trigger MediaWiki article creation using template "Wikiplace homepage" as content
	 * @param string $new_wikiplace_name The new 'wikiplace_name'
	 * @return Title/string The created homepage if ok, string message if an error occured 
	 * <ul>
	 * <li><b>wp-bad-title</b> MediaWiki is unable to create this homepage. Title may contain bad characters.</li>
	 * <li><b<wp-title-already-exists</b> This homepage already exists</li>
	 * <li><b>wp-internal-error</b> if Mediawiki returned an error while creating the article:
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
	public static function createHomepage( $new_wikiplace_name ) {
		
		if ( ($new_wikiplace_name===null) || ( ! is_string($new_wikiplace_name)) ) {
			throw new MWException( 'Cannot create homepage, invalid name.' );
		}
		
		$title = Title::newFromText( $new_wikiplace_name );
		
		if (!($title instanceof Title)) {
			return 'wp-bad-title';
		}
		
		if ($title->isKnown()) {
			return 'wp-title-already-exists';
		}
		
		$text = '{{subst:Wikiplace homepage}}';

		// now store the new page in mediawiki, this will trigger a hook that will really create the WpPage
		$article = new Article($title);
		$status = $article->doEdit($text, '',EDIT_NEW);
		
		if ( ! $status->isgood()) {
			return 'wp-internal-error';
		}
		
		return $title;
		
	}
	
	/**
	 * Trigger MediaWiki article creation of the wikiplace subpage. Use template "Wikiplace subpage" as content.
	 * @param WpWikiplace $wikiplace
	 * @param string $new_page_name
	 * @return Title/string the created Title, or string message if an error occured
	 * <ul>
	 * <li><b>wp-bad-title</b> MediaWiki is unable to create this page. Title may contain bad characters.</li>
	 * <li><b<wp-title-already-exists</b> This page already exists</li>
	 * <li><b>wp-internal-error</b> if Mediawiki returned an error while creating the article:
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
	public static function createSubpage( $wikiplace, $new_page_name ) {
		
		if ( ($wikiplace === null) || !($wikiplace instanceof WpWikiplace) ||
				( $new_page_name === null) || !is_string($new_page_name) ) {
			throw new MWException( 'Cannot create Wikiplace page (wrong argument)' );
		}
		
		$title = Title::newFromText( $wikiplace->get('name') . '/' . $new_page_name );
		
		// $title can be Title, or null on an error.
		
		if (!($title instanceof Title)) {
			return 'wp-bad-title';
		}
		
		if ($title->isKnown()) {
			return 'wp-title-already-exists';
		}
		
		$text = '{{subst:Wikiplace subpage}}';
		
		// now store the new page in mediawiki, this will trigger the WikiplaceHook, wich will 
		// allow the page saving
		$article = new Article($title);
		$status = $article->doEdit($text, '',EDIT_NEW);
		
		if ( ! $status->isgood()) {
			return 'wp-internal-error';
		}
		
		return $title;
		
	}
	
	/**
	 * Check that the $title is a homepage and not a subpage. The test is performed using
	 * the MediaWiki Title object of the page, and doesn't ensure that the corresponding wikiplace already exists.
	 * @param Title $title
	 * @return boolean 
	 */
	public static function isHomepage($title) {
		if ( !($title instanceof Title) ) {
			throw new MWException( 'Argument is not a MediaWiki Title.' );
		}
		return ( ($title->getNamespace() == NS_MAIN) && (count(explode( '/', $title->getDBkey() )) == 1) );
	}
	
    /**
	 * 
	 * @param int $namespace
	 * @return boolean
	 */
	public static function isInWikiplaceNamespaces($namespace) {
		
		if ( !is_int($namespace) ) {
			throw new MWException( 'Invalid namespace argument.' );
		}
		
		return in_array( $namespace, array(
			NS_MAIN,
			NS_TALK,
			NS_FILE,
			NS_FILE_TALK,
		));
	}
	


	/**
	 * count all pages owned by user
	 * @param type $user_id
	 * @return int the number of pages 
	 */
	public static function countPagesOwnedByUser($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Invalid user identifier.' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 
				'wp_wikiplace',
				array(
					'count(*) as total'
					),
				array( 'wpw_owner_user_id' =>  $user_id ),
				__METHOD__,
				array(),
				array( 
					'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id'),
					'page' => array('INNER JOIN','wppa_page_id = page_id'),
					) );
		
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
	public static function getDiskspaceUsageByUser($user_id) {
				
		if ( ($user_id === null) || !is_int($user_id) || ($user_id < 1) ) {
			throw new MWException( 'Invalid user identifier.' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectRow( 
				array('wp_wikiplace','wp_page','page','image'),
				array(
					'sum(img_size) >> 20  as total'
					),
				array(
					'wpw_owner_user_id' =>  $user_id,
					'page_namespace' => NS_FILE,
					),
				__METHOD__,
				array(),
				array( 
					'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id'),
					'page' => array('INNER JOIN','wppa_page_id = page_id'),
					'image' => array('INNER JOIN','page_title = img_name'),
					) );
		
		if ($result === null) {
			return 0;
		}
		
		return intval($result->total);
		
	}
	
	/**
	 * Check the user has an active subscription and page creation quota is not exceeded
	 * @param int $user_id
	 * @return boolean/string True if user can, string message explaining why she can't
	 * <ul>
	 * <li><b>wp-no-active-sub</b> user has no active subscription</li>
	 * <li><b>wp-page-quota-exceeded</b> page quota exceeded</li>
	 * </ul>
	 */
	public static function userCanCreateNewPage($user_id) {
		
		$sub = WpSubscription::getActiveByUserId($user_id);

		if ($sub === null) { 
			return 'wp-no-active-sub';
		}

		$max_pages = $sub->get('plan')->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = self::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return 'wp-page-quota-exceeded';
		}

		return true;
		
	}
	
	/**
	 * Check the user has an active subscription, page creation quota is not exceeded and
	 * diskpace quota is not exceeded.
	 * @param int $user_id
	 * @return boolean/string True if user can, string message explaining why she can't
	 * <ul>
	 * <li><b>wp-no-active-sub</b> user has no active subscription</li>
	 * <li><b>wp-page-quota-exceeded</b> page quota exceeded</li>
	 * <li><b>wp-diskspace-quota-exceeded</b> diskspace quota exceeded</li>
	 * </ul>
	 */
	public static function userCanUploadNewFile($user_id) {
		
		$sub = WpSubscription::getActiveByUserId($user_id);

		if ($sub === null) { 
			return 'wp-no-active-sub';
		}

		$max_pages = $sub->get('plan')->get('wpp_nb_wikiplace_pages');
		$user_pages_nb = self::countPagesOwnedByUser($user_id);

		if ($user_pages_nb >= $max_pages) { 
			return 'wp-page-quota-exceeded';
		}
		
		$max_diskspace = $sub->get('plan')->get('wpp_diskspace');
		$user_diskspace_usage = self::getDiskspaceUsageByUser($user_id);

		if ($user_diskspace_usage >= $max_diskspace) { 
			return 'wp-diskspace-quota-exceeded';
		}

		return true;
		
	}
	
	
	/**
	 * Create a new WpPage record, which will associate a MediaWiki page to a Wikiplace.
	 * @param WikiPage $wikipage
	 * @param WpWikiplace $wikiplace
	 * @return WpPage The newly created WpPage, or null if a db error occured
	 */
	public static function create( $wikipage, $wikiplace ) {
		
		if ( !($wikiplace instanceof WpWikiplace) ) {
			throw new MWException( 'Cannot create wikiplace page, invalid wikiplace argument.' );
		}
		
		if ( !($wikipage instanceof WikiPage) ) {
			throw new MWException( 'Cannot create wikiplace page, invalid wikipage argument.' );
		}
						
		$pageId = $wikipage->getTitle()->getArticleID();
		$wikiplaceId = $wikiplace->get('wpw_id');
		
		$dbw = wfGetDB(DB_MASTER);
		
		$dbw->begin();
		
        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wppa_id');
		
        $success = $dbw->insert('wp_page', array(
			'wppa_id'             => $id,
			'wppa_wpw_id'         => $wikiplaceId,
			'wppa_page_id'        => $pageId
		));

		// Setting id from auto incremented id in DB
		$id = $dbw->insertId();
		
		$dbw->commit();
		
		if ( !$success ) {
			return null;
		}		
				
		$wpp = new self( $id, $wikiplaceId, $pageId );
		$wpp->fetchPage( $wikipage );
		
		return $wpp;
			
	}
	
	/**
	 * Find the owner "user id" of a wikiplace page
	 * @param int $article_id
	 * @return int the user id, 0 if the page is not a wikiplace page
	 */
	public static function findOwnerUserIdByArticleId( $article_id ) {
		
		if ( ! is_int($article_id) || ($article_id < 0) ) {
			throw new MWException('Cannot find the owner, invalid argument.');
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->selectField( 
				array( 'wp_page', 'wp_wikiplace' ),
				'wpw_owner_user_id',
				array ( 
					'wppa_page_id' => $article_id,
					'wppa_wpw_id = wpw_id' ),
				__METHOD__ );
	
		if ( $result === false ) {
			// not found, so return null
			return 0;
		}
		
		return intval($result);
		
	}
	
}
