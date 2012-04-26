<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Transactions listing. 
 */
class WpPageTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array(
        'wp_wikiplace',
        'homepage' => 'page',
        'wp_page',
        'subpage' => 'page');
    protected $selectJoinConditions = array(
        'homepage' => array('INNER JOIN', 'wpw_home_page_id = homepage.page_id'),
        'wp_page' => array('INNER JOIN', 'wpw_id = wppa_wpw_id'),
        'subpage' => array('INNER JOIN', 'wppa_page_id = subpage.page_id'));
    protected $selectFields = array('subpage.page_title AS subpage_title', 'subpage.page_namespace AS subpage_namespace', 'homepage.page_title AS homepage_title');
    protected $selectOptions = array('ORDER BY' => 'subpage.page_title');
    protected $defaultSort = 'subpage_title';
    public $mDefaultDirection = false; // true = DESC
    protected $tableClasses = array('WpPage'); # Array
    protected $messagesPrefix = 'wppatp';

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
        switch ($name) {
            case 'subpage_title':
                $to = Title::makeTitle($this->mCurrentRow->subpage_namespace, $value);
                return Linker::linkKnown($to, $to->getPrefixedText());
            case 'subpage_namespace':
                return $this->formatNamespace($value);
            default:
                return htmlspecialchars($value);
        }
    }
    
    function formatNamespace($value) {
        global $wgLang;
        switch ($value) {
            case NS_MAIN :
                if ($this->mCurrentRow->subpage_title === $this->mCurrentRow->homepage_title)
                    return wfMessage('wp-homepage')->text();
                else
                    return wfMessage('wp-subpage')->text();
            default :
                return $wgLang->getNsText($value);
                
        }
    }
    
    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['homepage_title']);
        return $fieldNames;
    }
}