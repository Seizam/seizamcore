<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Plans listing. 
 */
class WpPlansTablePager extends SkinzamTablePager {
	
	
    # Fields for default behavior
    protected $selectTables = array ('wp_plan');
	
    protected $selectFields = array(
        'wpp_id',
		'wpp_name',
        'wpp_period_months',
		'wpp_price',
		'wpp_currency',
        'wpp_end_date',
		'wpp_nb_wikiplaces',
		'wpp_nb_wikiplace_pages',
        'wpp_diskspace',
        'wpp_monthly_page_hits',
        'wpp_monthly_bandwidth'
		);
    protected $defaultSort = 'wpp_end_date';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpPlan'); # Array
    protected $messagesPrefix = 'wp-';
    public $forceDefaultLimit = 6; # if > 0 use instead of $wgUser->getOption( 'rclimit' )


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
			case 'wpp_name':
				return  SpecialSubscriptions::getLinkNew($value, 'wpp-'.$value);
            case 'wpp_period_months':
                return wfMessage('wp-period',$value);
			case 'wpp_nb_wikiplaces':
			case 'wpp_nb_wikiplace_pages':
				return wfFormatNumber($value);
			case 'wpp_monthly_bandwidth':
			case 'wpp_diskspace':
				return wfFormatSizeMB($value);
            case 'wpp_price':
                $cur = ' '.$this->mCurrentRow->wpp_currency;
                if ($cur == ' EUR')
                    $cur = ' '.  wfMessage('cur-euro')->text();
                $cur = Xml::element('span', array('class'=>'currency'), $cur);
                return $wgLang->formatNum($value).$cur ;
			case 'wpp_end_date':
				return $wgLang->date($value, true);
            case 'hard_quotas':
                return $this->formatHardQuotas();
            case 'monthly_quotas':
                return $this->formatMonthlyQuotas();
            default:
                return htmlspecialchars($value);
        }
    }
    
    function formatHardQuotas() {
        $html = Xml::openElement('ul');
        $html .= Html::rawElement('li', array(), '<b>'.wfFormatNumber($this->mCurrentRow->wpp_nb_wikiplaces).'</b> '.wfMessage ('wp-wikiplaces')->text());
        $html .= Html::rawElement('li', array(), '<b>'.wfFormatNumber($this->mCurrentRow->wpp_nb_wikiplace_pages).'</b> '.wfMessage ('wp-subpages')->text());
        $html .= Html::rawElement('li', array(), '<b>'.wfFormatSizeMB($this->mCurrentRow->wpp_diskspace).'</b> '.wfMessage ('wp-diskspace')->text());
        $html .= Xml::closeElement('ul');
        
        return $html;
    }
    
    function formatMonthlyQuotas() {
        $html = Xml::openElement('ul');
        $html .= Html::rawElement('li', array(), '<b>'.wfFormatNumber($this->mCurrentRow->wpp_monthly_page_hits).'</b> '.wfMessage ('wp-Hits')->text());
        $html .= Html::rawElement('li', array(), '<b>'.wfFormatSizeMB($this->mCurrentRow->wpp_monthly_bandwidth).'</b> '.wfMessage ('wp-bandwidth')->text());
        $html .= Xml::closeElement('ul');
        
        return $html;
    }
    

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        
        unset($fieldNames['wpp_id']);
        unset($fieldNames['wpp_currency']);
        unset($fieldNames['wpp_nb_wikiplaces']);
        unset($fieldNames['wpp_nb_wikiplace_pages']);
        unset($fieldNames['wpp_diskspace']);
        unset ($fieldNames['wpp_monthly_page_hits']);
        unset ($fieldNames['wpp_monthly_bandwidth']);
        
        $fieldNames['hard_quotas'] = wfMessage('wp-hard-quotas');
        $fieldNames['monthly_quotas'] = wfMessage('wp-monthly-quotas');
        
        if (isset($fieldNames['wpp_name']))
            $fieldNames['wpp_name'] = wfMessage ('wp-name')->text ();
        
        
        if (isset($fieldNames['wpp_period_months']))
            $fieldNames['wpp_period_months'] = wfMessage ('wp-duration')->text ();
        
        if (isset($fieldNames['wpp_price']))
            $fieldNames['wpp_price'] = wfMessage ('price')->text ();
        
        if (isset($fieldNames['wpp_end_date']))
            $fieldNames['wpp_end_date'] = wfMessage ('end_date')->text ();
        
        return $fieldNames;
    }
    
    function isFieldSortable($field) {
        return ($field != 'hard_quotas' && $field != 'monthly_quotas');
    }

}