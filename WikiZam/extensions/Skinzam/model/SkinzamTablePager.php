<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class SkinzamTablePager extends TablePager {
    # Fields to be redeclared for default behavior

    protected $selectTable; # String
    protected $selectFields = '*'; # Array
    protected $selectConds = array(); # Array
    protected $sortableFields = '*'; # Array
    protected $tableClasses = array(); # Array
    protected $defaultSort; # String
    public $mDefaultDirection = false; # Boolean
    protected $messagesPrefix = 'sz'; # String
    
    public $mLimit = 20;
    
    # Do not redeclare
    private $defaultTableClasses = array('TablePager');
    private $even = true;
    private $header = '';
    private $footer = '';

    /**
     * ABSTRACT! Can be redeclared, don't forget to call parent and merge results.
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
        return $value;
    }

    /**
     * SEMI-ABSTRACT! Can be redeclared, don't forget to call parent and merge results.
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

        return array('class' => implode(' ', $classes));
    }

    /**
     * SEMI-ABSTRACT! Can be redeclared, don't forget to call parent and merge results.
     * Get class names to be applied to the given row.
     *
     * @param $row Object: the database result row
     * @return Array
     */
    function getRowAttrs($row) {
        $classes = array();

        # Apply class="even" to every even row (theader included)
        if ($this->even)
            $classes[] = 'even';
        $this->even = !$this->even;

        return array('class' => implode(' ', $classes));
    }

    /**
     * Use this function to pass the name of db table
     * 
     * @param string $selectTable 
     */
    public function setSelectTable($selectTable) {
        $this->selectTable = $selectTable;
    }

    /**
     * Use this function to pass an array of fields to be displayed ('*' for all).
     * 
     * @param array $selectFields 
     */
    public function setSelectFields($selectFields) {
        $this->selectFields = $selectFields;
        ;
    }

    /**
     * Use this function to pass an array of SQL conditions for the query.
     * 
     * @param array $selectConds 
     */
    public function setSelectConds($selectConds) {
        $this->selectConds = $selectConds;
    }

    /**
     * Use this function to pass an array of fields to be made sortable ('*' for all).
     *
     * @param array $sortableFields 
     */
    public function setFieldSortable($sortableFields) {
        $this->sortableFields = $sortableFields;
    }

    /**
     * Determine if $field should be sortable
     * 
     * @param string $field
     * @return boolean 
     */
    function isFieldSortable($field) {
        if (!is_array($this->sortableFields)) {
            if ($this->sortableFields === '*') {
                return true;
            } else {
                return false;
            }
        } else if (in_array($field, $this->sortableFields))
            return true;
    }

    /**
     * This function should be overridden to provide all parameters
     * needed for the main paged query. It returns an associative
     * array with the following elements:
     *    tables => Table(s) for passing to Database::select()
     *    fields => Field(s) for passing to Database::select(), may be *
     *    conds => WHERE conditions
     *    options => option array
     *    join_conds => JOIN conditions
     *
     * @return Array
     */
    function getQueryInfo() {
        $infos = array();
        $infos['tables'] = $this->selectTable;
        $infos['fields'] = $this->selectFields;
        $infos['conds'] = $this->selectConds;
        return $infos;
    }

    /**
     * Get class names to be applied to table
     * 
     * @return string 
     */
    function getTableClass() {
        return implode(' ', array_merge($this->tableClasses, $this->defaultTableClasses));
    }

    /**
     * Set class names to be applied to table
     * 
     */
    public function setTableClass($classes) {
        if (!is_array($classes))
            $classes = explode(' ', $classes);

        $this->tableClasses = array_merge($this->tableClasses, $classes);
    }

    /**
     * Get default field for sorting
     * 
     */
    function getDefaultSort() {
        return $this->defaultSort;
    }

    /**
     * Set default field for sorting
     * 
     */
    public function setDefaultSort($field) {
        $this->defaultSort = $field;
    }

    /**
     * Get array of field names to be displayed in thead
     * 
     */
    function getFieldNames() {
        $fieldNames = array();
        foreach ($this->selectFields as $field)
            $fieldNames[$field] = wfMessage($this->messagesPrefix . '-' . $field)->text();
        return $fieldNames;
    }

    /**
     * Set prefix for i18n messages
     * 
     */
    public function setMessagesPrefix($prefix) {
        $this->messagesPrefix = $prefix;
    }
    
    /**
     * Set Header Text
     *
     * @param string $html 
     */
    public function setHeader($html) {
        $this->header = Html::rawElement('div', array('class' => 'table_header informations'), $html);
    }
    
    /**
     * Set Footer Text
     *
     * @param string $html 
     */
    public function setFooter($html) {
        $this->footer = Html::rawElement('div', array('class' => 'table_footer informations'), $html);
    }
    
    /**
     *
     * @return string 
     */
    public function getHeader() {
        return $this->header;
    }
    
    /**
     *
     * @return string 
     */
    public function getFooter() {
        return $this->footer;
    }
    

    /**
     * Easy Form Printout
     *
     * @return string 
     */
    public function getWholeHtml() {
        return $this->getHeader()
                . $this->getBody()
                . $this->getNavigationBar()
                . $this->getFooter();
    }

}