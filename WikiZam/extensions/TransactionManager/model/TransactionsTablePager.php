<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class TransactionsTablePager extends SkinzamTablePager {
    # Fields for default behavior
    protected $selectTable = 'tm_record'; # String
    protected $selectFields = array('tmr_id', 'tmr_desc', 'tmr_date_created', 'tmr_date_modified', 'tmr_amount', 'tmr_currency', 'tmr_status');
    protected $defaultSort = 'tmr_date_created';
    public $mDefaultDirection = true;
    protected $tableClasses = array('TMRecord'); # Array
    protected $messagesPrefix = 'tm';

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
            case 'tmr_type':
                return wfMessage('tm-type-' . $value)->text();
            case 'tmr_date_created':
            case 'tmr_date_modified':
                return $wgLang->timeanddate($value, true);
            case 'tmr_desc':
                return wfMessage($value)->text();
            case 'tmr_status':
                return wfMessage('tm-status-' . $value)->text();
            case 'tmr_amount':
                return $value > 0 ? '+' . $value : $value;
            default:
                return $value;
        }
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