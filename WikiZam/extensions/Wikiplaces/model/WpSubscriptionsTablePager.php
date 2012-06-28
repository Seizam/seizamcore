<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpSubscriptionsTablePager extends SkinzamTablePager {
	
	
    # Fields for default behavior
    protected $selectTables = array ( 'wp_subscription', 'wp_plan');
	
	protected $selectJoinConditions = array( 'wp_plan' => array('INNER JOIN','wps_wpp_id = wpp_id') );
    protected $selectFields = array(	
		'wps_active',
		'wpp_name',
		'wps_start_date',			// when the sub starts
		'wps_end_date',				// subscribed plan name
		'wpp_nb_wikiplaces',
		'wpp_nb_wikiplace_pages',
		'wpp_diskspace',
		'wps_tmr_status',
		);
    protected $defaultSort = 'wps_start_date';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpSubscription'); # Array
    protected $messagesPrefix = 'wp-';
	

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
			
			case 'wps_start_date':
			case 'wps_end_date':
				return ($value === null) ? '-' : $wgLang->date($value, true);
			case 'wpp_name':
				return  wfMessage('wpp-' . $value)->text();
			case 'wps_tmr_status':
				return wfMessage("status-$value")->text() ;
			case 'wpp_nb_wikiplaces':
			case 'wpp_nb_wikiplace_pages':
				return wfFormatNumber($value);
			case 'wpp_monthly_bandwidth':
			case 'wpp_diskspace':
				return wfFormatSizeMB($value);
            default:
                return htmlspecialchars($value);
        }
    }
    
    

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['wps_active']);
        
        if (isset($fieldNames['wpp_name']))
            $fieldNames['wpp_name'] = wfMessage ('wp-name')->text ();
        
        if (isset($fieldNames['wps_start_date']))
            $fieldNames['wps_start_date'] = wfMessage ('start_date')->text ();
        
        if (isset($fieldNames['wps_end_date']))
            $fieldNames['wps_end_date'] = wfMessage ('end_date')->text ();
        
        if (isset($fieldNames['wpp_nb_wikiplaces']))
            $fieldNames['wpp_nb_wikiplaces'] = wfMessage ('wp-max_wikiplaces')->text ();
        
        if (isset($fieldNames['wpp_nb_wikiplace_pages']))
            $fieldNames['wpp_nb_wikiplace_pages'] = wfMessage ('wp-max_pages')->text ();
        
        if (isset($fieldNames['wpp_diskspace']))
            $fieldNames['wpp_diskspace'] = wfMessage ('wp-diskspace')->text ();
        
        if (isset($fieldNames['wps_tmr_status']))
            $fieldNames['wps_tmr_status'] = wfMessage ('status')->text ();
        
        return $fieldNames;
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

        if ($row->wps_active == '1')
            $classes[] = 'active';
		
		if ( $row->wps_tmr_status == 'PE' )
            $classes[] = 'pending';
        else if ($row->wps_tmr_status == 'KO')
            $classes[] = 'canceled';
		
        return array('class' => implode(' ', $classes));
    }

}