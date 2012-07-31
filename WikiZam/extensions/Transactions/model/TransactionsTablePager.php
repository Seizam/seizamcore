<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class TransactionsTablePager extends SkinzamTablePager {
    # Fields for default behavior
    protected $selectTables = 'tm_record'; # String
    protected $selectFields = array('tmr_id',
        'tmr_desc',
        'tmr_date_created',
        'tmr_date_modified',
        'tmr_amount',
        'tmr_currency',
        'tmr_status',
        'tmr_tmb_id');
    protected $defaultSort = 'tmr_date_created';
    public $mDefaultDirection = true;
    protected $tableClasses = array('TMRecord'); # Array
    protected $messagesPrefix = '';

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
            case 'tmr_id':
                if (isset($this->mCurrentRow->tmr_tmb_id))
                    return SpecialBills::getLinkBill($this->mCurrentRow->tmr_tmb_id, $wgLang->formatNum($value));
                else
                    return $wgLang->formatNum($value);
            case 'tmr_type':
                return wfMessage('tm-' . $value)->text();
            case 'tmr_date_created':
            case 'tmr_date_modified':
                return $wgLang->timeanddate($value, true);
            case 'tmr_desc':
                return wfMessage($value)->text();
            case 'tmr_status':
                return wfMessage('status-' . $value)->text();
            case 'tmr_amount':
                if ($this->mCurrentRow->tmr_currency == 'EUR')
                    $cur = wfMessage('cur-euro')->text();
                else $cur = $this->mCurrentRow->tmr_currency;
                $cur = Xml::element('span', array('class'=>'currency'), $cur);
                return ($value > 0 ? '+' . $wgLang->formatNum($value) : $wgLang->formatNum($value)).$cur ;
            default:
                return htmlspecialchars($value);
        }
    }
    
    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['tmr_currency']);
        unset($fieldNames['tmr_tmb_id']);
        
        if (isset ($fieldNames['tmr_id']))
            $fieldNames['tmr_id'] = wfMessage('id')->text();
        
        if (isset ($fieldNames['tmr_desc']))
            $fieldNames['tmr_desc'] = wfMessage('description')->text();
        
        if (isset ($fieldNames['tmr_date_created']))
            $fieldNames['tmr_date_created'] = wfMessage('date_created')->text();
        
        if (isset ($fieldNames['tmr_date_modified']))
            $fieldNames['tmr_date_modified'] = wfMessage('date_modified')->text();
        
        if (isset ($fieldNames['tmr_amount']))
            $fieldNames['tmr_amount'] = wfMessage('amount')->text();
        
        if (isset ($fieldNames['tmr_status']))
            $fieldNames['tmr_status'] = wfMessage('status')->text();
        
        return $fieldNames;
    }

    /**
     * Get a class name to be applied to the given row.
     *
     * @param $row Object: the database result row
     * @return array
     */
    function getRowClasses($row) {
        $classes = array();

        if (isset($row->tmr_status))
            $classes[] = $row->tmr_status;

        if ($row->tmr_amount > 0)
            $classes[] = 'positive';

        return $classes;
    }

}