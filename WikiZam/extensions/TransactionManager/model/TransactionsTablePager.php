<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class TransactionsTablePager extends TablePager {

    private $sortableFields = false;
    private $selectFields = array('tmr_id', 'tmr_desc', 'tmr_date_created', 'tmr_date_modified', 'tmr_amount', 'tmr_currency', 'tmr_status');
    private $selectConds = array();
    private $defaultSort = 'tmr_date_created';
    private $even = true;

    public function getQueryInfo() {
        global $wgUser;
        $infos = array();
        $infos['tables'] = 'tm_record';
        $infos['fields'] = $this->selectFields;
        $infos['conds'] = $this->selectConds;
        return $infos;
    }

    public function setSelectFields($selectFields) {
        $this->selectFields = $selectFields;
    }

    public function setSelectConds($selectConds) {
        $this->selectConds = $selectConds;
    }

    public function setFieldSortable($sortableFields) {
        $this->sortableFields = $sortableFields;
    }

    public function toggleListDisplay() {
        $this->listDisplay = true;
    }

    public function isFieldSortable($field) {
        if (!is_array($this->sortableFields))
            return false;
        else if (in_array($field, $this->sortableFields))
            return true;
    }

    public function formatValue($name, $value) {
        global $wgLang;
        switch ($name) {
            case 'tmr_type':
                return wfMessage('tm-type-' . $value)->text();
            case 'tmr_date_created':
            case 'tmr_date_modified':
                return $wgLang->timeanddate($value);
            case 'tmr_desc':
                return wfMessage($value)->text();
            case 'tmr_status':
                return wfMessage('tm-status-' . $value)->text();
            case 'tmr_amount':
                return $value>0 ? '+'.$value : $value;
            default:
                return $value;
        }
    }

    /**
     * Get any extra attributes to be applied to the given cell. Don't
     * take this as an excuse to hardcode styles; use classes and
     * CSS instead.  Row context is available in $this->mCurrentRow
     *
     * @param $field The column
     * @param $value The cell contents
     * @return Associative array
     */
    function getCellAttrs($field, $value) {
        $classes = array();
        $classes[] = $field;
        
        return array('class' => implode(' ',$classes));
    }

    /**
     * Get a class name to be applied to the given row.
     *
     * @param $row Object: the database result row
     * @return String
     */
    function getRowAttrs($row) {
        $classes = array();
        
        if (isset($row->tmr_status))
            $classes[] = $row->tmr_status;
        
        if ($row->tmr_amount > 0)
            $classes[] = 'positive';
        
        if ($this->even)
            $classes[] = 'even';
        
        $this->even = !$this->even;
        
        
        return array('class' => implode(' ',$classes));
    }

    function getTableClass() {
        return 'TablePager TMRecord';
    }

    public function getDefaultSort() {
        return $this->defaultSort;
    }

    public function getFieldNames() {
        $fieldNames = array();
        foreach ($this->selectFields as $field)
            $fieldNames[$field] = wfMessage('tm-' . $field)->text();
        return $fieldNames;
    }

}