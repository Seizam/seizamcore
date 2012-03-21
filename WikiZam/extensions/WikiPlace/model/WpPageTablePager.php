<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpPageTablePager extends SkinzamTablePager {
	
	
    # Fields for default behavior
    protected $selectTables = array (
		'wp_wikiplace',
		'homepage' => 'page',
		'wp_page',
		'pages' => 'page' );
	protected $selectJoinConditions = array( 
		'homepage' => array('INNER JOIN','wpw_home_page_id = homepage.page_id'),
		'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id'),
		'pages' => array('INNER JOIN','wppa_page_id = pages.page_id') );
    protected $selectFields = array( 'pages.page_title' );
	protected $selectOptions = array( 'ORDER BY' => 'pages.page_title');
    protected $defaultSort = 'pages.page_title';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpPage'); # Array
    protected $messagesPrefix = 'wppatp';

	
	/**
	 * Contruct a list of wikiplace
	 * @param type $wikiplace_name
	 */
	public function __construct( $wikiplace_name , $user_id) {
		parent::__construct();
		if ( !isset($wikiplace_name) || !isset($user_id) ||
				!is_string($wikiplace_name) || (!is_int($user_id) || ($user_id < 1)) ) {
			throw new MWException('Cannot construct the pages list, invalid argument');
		}
		$this->selectConds = array( 
			'homepage.page_title' => $wikiplace_name,
			'wpw_owner_user_id' => $user_id );
	}


    /**
     * Format a table cell. The return value should be HTML, but use an empty
     * string not &#160; for empty cells. Do not include the <td> and </td>.
     *
     * The current result row is available as $this->mCurrentRow, in case you
     * need more context.
     *
     * @param $name String: the database field name
     * @param $value String: the value retrieved from the database
     */
     function formatValue($name, $value) {
        switch ($name) {
			
			case 'pages.page_title':
				/** @todo: fix this:$value is null, but should not */
				$to = Title::makeTitle(WP_PAGE_NAMESPACE, $value);
				return $value;
				return Linker::linkKnown( 
						$to, // where to go
						$to->getPrefixedText(), // the link text
						array(),
						array() ); // an arg
				
            default:
                throw new MWException( 'Unknown data name "'.$name.'"');
        }
    }
	

}