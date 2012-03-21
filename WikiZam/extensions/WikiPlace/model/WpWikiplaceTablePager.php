<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpWikiplaceTablePager extends SkinzamTablePager {
	
	
    # Fields for default behavior
    protected $selectTables = array ( 'wp_wikiplace', 'wp_page', 'page' , 'wp_usage' );
	
	protected $selectJoinConditions = array( 
		'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id'),
		'page' => array('INNER JOIN','wpw_home_page_id = page_id'),
		'wp_usage' => array('INNER JOIN','wpu_wpw_id = wppa_wpw_id') );
    protected $selectFields = array(
		'page_title' ,
		'count(*)',
		'wpu_monthly_page_hits',
		'wpu_monthly_bandwidth',
		'wpu_updated',
		'wpu_end_date' );
	protected $selectOptions = array( 'GROUP BY' => 'wpw_id');
    protected $defaultSort = 'page_title';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpWikiplace'); # Array
    protected $messagesPrefix = 'wpwtp';
	
	protected $selectConds = array ( 'wpu_active' => 1 );
	
	
	/**
	 * Contruct a list of wikiplace
	 * @param type $wikiplace_name
	 */
	public function __construct( $conditions = array() ) {
		parent::__construct();
		if ( !is_array($conditions) ) {
			throw new MWException('Cannot construct the TablePager with this conditions, invalid argument');
		}
		$this->selectConds = array_merge( $this->selectConds , $conditions );
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
        global $wgLang;
        switch ($name) {
			
			case 'page_title':
				return Linker::linkKnown( 
						SpecialPage::getTitleFor('Wikiplace', SpecialWikiplace::ACTION_CONSULT_WP), // where to go
						Title::makeTitle(WP_PAGE_NAMESPACE, $value)->getPrefixedText(), // the link text
						array(),
						array( 'wikiplace' => $value) ); // an argument
			case 'count(*)':
			case 'wpu_monthly_page_hits':
			case 'wpu_monthly_bandwidth':			
				return $value;	
			case 'wpu_updated':
			case 'wpu_end_date':
				return ($value === null) ? '-' : $wgLang->timeanddate($value, true);
            default:
                throw new MWException( 'Unknown data name "'.$name.'"');
        }
    }
	

}