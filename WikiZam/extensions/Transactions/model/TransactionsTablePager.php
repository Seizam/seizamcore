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
    protected $selectFields = array('tmr_id AS id', 'tmr_desc AS description', 'tmr_date_created AS date_created', 'tmr_date_modified AS date_modified', 'tmr_amount AS amount', 'tmr_currency AS currency', 'tmr_status AS status');
    protected $defaultSort = 'date_created';
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
            case 'type':
                return wfMessage('tm-' . $value)->text();
            case 'date_created':
            case 'date_modified':
                return $wgLang->timeanddate($value, true);
            case 'desc':
                return wfMessage($value)->text();
            case 'status':
                return wfMessage('status-' . $value)->text();
            case 'amount':
                if ($this->mCurrentRow->currency == 'EUR')
                    $cur = ' €';
                else $cur = $this->row->currency;
                $cur = Xml::element('span', array('class'=>'currency'), $cur);
                return ($value > 0 ? '+' . $value : $value).$cur ;
            default:
                return $value;
        }
    }
    
    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['currency']);
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

        if (isset($row->status))
            $classes[] = $row->status;

        if ($row->amount > 0)
            $classes[] = 'positive';

        return $classes;
    }

}