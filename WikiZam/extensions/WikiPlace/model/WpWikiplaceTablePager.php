<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpWikiplaceTablePager extends SkinzamTablePager {
	
	
    # Fields for default behavior
    protected $selectTables = array ( 'wp_wikiplace', 'wp_page', 'wpu_usage');
	
	protected $selectJoinConditions = array( 
		'wp_page' => array('INNER JOIN','wpw_id = wppa_wpw_id') );
//		'page' => array('INNER JOIN','wppa_page_id = page_id') );
    protected $selectFields = array(
		'wpw_name',
		'count(wppa_id) as nb_pages',	
		);
    protected $defaultSort = 'wpw_name';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpWikiplace'); # Array
    protected $messagesPrefix = 'wpwtp';
	

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
			
			case 'wpw_name':
			case 'nb_page':
				return $value;
				break;
            default:
                throw new MWException( 'Unknown data name "'.$name.'"');
        }
    }

	
    /**
     * Add "active" class for actives rows, and "warning" for not "OK or PE" tmr
     *
     * @param $row Object: the database result row
     * @return String
     */
    function getRowAttrs($row) {
        $attrs = parent::getRowAttrs($row);
        $classes = explode(' ', $attrs['class']);

        if ($row->wps_active != '0')
            $classes[] = 'active';
		
		if ( ($row->wps_tmr_status != 'OK') && ($row->wps_tmr_status != 'PE') )
            $classes[] = 'warning';
		
        return array('class' => implode(' ', $classes));
    }

}