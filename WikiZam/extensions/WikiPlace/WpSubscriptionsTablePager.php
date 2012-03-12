<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpSubscriptionsTablePager extends SkinzamTablePager {
    # Fields for default behavior
    protected $selectTables = array( 'wp_subscription', 'wp_plan' ); # String
    protected $selectFields = array(
		'wps_start_date',			// when the sub starts
		'wpp_name',					// subscribed plan name
		'wps_active',
		'wps_tmr_status',
		'wpp_nb_wikiplaces',
		'wpp_nb_wikiplace_pages',
		'wpp_diskspace',
		'wpp_monthly_page_hits',
		'wpp_monthly_bandwidth',
		'wps_end_date',		
		);
    protected $defaultSort = 'wps_date_created';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WPSubscription'); # Array
    protected $messagesPrefix = 'wp';
	

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
				return $wgLang->timeanddate($value, true);
				// $lang->timeanddate(wfTimestamp(TS_MW, $date, true)); ?
				break;
			case 'wpp_name':
				return  wfMessage('wp-plan-name-' . $value)->text();
				break;
			case 'wps_active':
				return wfMessage( ($value==0) ? 'wp-sub-unactive' : 'wp-sub-active' )->text() ;
				break;
			case 'wps_tmr_status':
				return wfMessage( 'wp-sub-tmrstatus-'.$value )->text() ;
				break;
			case 'wpp_nb_wikiplaces':
			case 'wpp_nb_wikiplace_pages':
			case 'wpp_diskspace':
			case 'wpp_monthly_page_hits':
			case 'wpp_monthly_bandwidth':
				return $value;
				break;
            default:
                throw new MWException( 'Unknown data name');
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