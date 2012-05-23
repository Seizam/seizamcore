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
		'wps_active AS active',
		'wpp_name AS plan_name',
		'wps_start_date AS start_date',			// when the sub starts
		'wps_end_date AS end_date',				// subscribed plan name
		'wpp_nb_wikiplaces AS nb_wikiplaces',
		'wpp_nb_wikiplace_pages AS nb_wikiplace_pages',
		'wpp_diskspace AS diskspace',
		'wps_tmr_status AS status',
		);
    protected $defaultSort = 'start_date';
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
			
			case 'start_date':
			case 'end_date':
				return ($value === null) ? '-' : $wgLang->timeanddate($value, true);
			case 'plan_name':
				return  wfMessage('wp-' . $value)->text();
			case 'status':
				return wfMessage("status-$value")->text() ;
			case 'nb_wikiplaces':
			case 'nb_wikiplace_pages':
			case 'monthly_hits':
				return wgformatNumber($value);
			case 'monthly_bandwidth':
			case 'diskspace':
				return wgformatSizeMB($value);
            default:
                throw new MWException( 'Unknown data name "'.$name.'"');
        }
    }
    
    

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['active']);
        if (isset($fieldNames['start_date']))
            $fieldNames['start_date'] = wfMessage ('start_date')->text ();
        if (isset($fieldNames['end_date']))
            $fieldNames['end_date'] = wfMessage ('end_date')->text ();
        if (isset($fieldNames['status']))
            $fieldNames['status'] = wfMessage ('status')->text ();
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

        if ($row->active == '1')
            $classes[] = 'active';
		
		if ( $row->status == 'PE' )
            $classes[] = 'pending';
        else if ($row->status == 'KO')
            $classes[] = 'canceled';
		
        return array('class' => implode(' ', $classes));
    }

}