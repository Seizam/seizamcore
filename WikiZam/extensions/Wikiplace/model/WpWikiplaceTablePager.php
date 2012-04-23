<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpWikiplaceTablePager extends SkinzamTablePager {
	
	
    # Fields for default behavior
    protected $selectTables = array ( 'wp_wikiplace', 'wp_page', 'page' );
	
	protected $selectJoinConditions = array( 
		'wp_page' => array('LEFT JOIN','wpw_id = wppa_wpw_id'),
		'page' => array('INNER JOIN','wpw_home_page_id = page_id') );
    protected $selectFields = array(
		'page_title' ,
		'page_namespace',
		'count(*)',
		'wpw_monthly_page_hits',
		'wpw_monthly_bandwidth',
		'wpw_report_updated',
		'wpw_date_expires' );
	protected $selectOptions = array( 'GROUP BY' => 'wpw_id');
    protected $defaultSort = 'page_title';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpWikiplace'); # Array
    protected $messagesPrefix = 'wpwtp';
	
	protected $selectConds = array ();



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
						Title::makeTitle($this->mCurrentRow->page_namespace, $value)->getPrefixedText(), // the link text
						array(),
						array( 'wikiplace' => $value) ); // an argument
			case 'count(*)':
			case 'wpw_monthly_page_hits':
				return $value;
			case 'wpw_monthly_bandwidth':			
				return "$value Mb";	
			case 'wpw_report_updated':
			case 'wpw_date_expires':
				return ($value === null) ? '-' : $wgLang->timeanddate($value, true);
			default:
                throw new MWException( 'Unknown data name "'.$name.'"');
            
        }
    }
	
	function getFieldNames() {
        $fieldNames = parent::getFieldNames();
		unset($fieldNames['page_namespace']);
        return $fieldNames;
    }
	

}