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
	
	public function addCondition( $cond = array() ) {
		if ( !is_array($cond) ) {
			throw new MWException('Cannot add condition, invalid argument');
		}
		$this->selectConds = array_merge( $this->selectConds , $cond );
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
				return Title::makeTitle(WP_PAGE_NAMESPACE, $value)->getPrefixedText();
			case 'count(*)':
			case 'wpu_monthly_page_hits':
			case 'wpu_monthly_bandwidth':
			case 'wpu_updated':
			case 'wpu_end_date':
				return $value;
            default:
                throw new MWException( 'Unknown data name "'.$name.'"');
        }
    }
	

}