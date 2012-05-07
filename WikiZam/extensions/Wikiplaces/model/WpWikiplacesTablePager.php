<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Wikiplaces listing. 
 */
class WpWikiplacesTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array('wp_wikiplace', 'wp_page', 'page');
    protected $selectJoinConditions = array(
        'wp_page' => array('LEFT JOIN', 'wpw_id = wppa_wpw_id'),
        'page' => array('INNER JOIN', 'wpw_home_page_id = page_id'));
    protected $selectFields = array(
        'page_title',
        'page_namespace',
        'count(*)',
        'wpw_monthly_page_hits',
        'wpw_monthly_bandwidth',
        'wpw_report_updated',
        'wpw_date_expires');
    protected $selectOptions = array('GROUP BY' => 'wpw_id');
    protected $defaultSort = 'page_title';
    public $mDefaultDirection = true; // true = DESC
    protected $tableClasses = array('WpWikiplace'); # Array
    protected $messagesPrefix = 'wpwtp';
    protected $selectConds = array();

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

            case 'page_title':
                $title = Title::makeTitle($this->mCurrentRow->page_namespace, $value);
                return Linker::linkKnown($title, $title->getPrefixedText());
            case 'count(*)':
                $html = '<b>'.$value.'</b> '.  wfMessage('wp-items');
                $html .= '<ul>';
                $html .= '<li>'
						. SpecialWikiplaces::getLinkConsultWikiplace( $this->mCurrentRow->page_title )
                        . '</li>';
                $html .= '<li>'
                        . SpecialWikiplaces::getLinkCreateSubpage( $this->mCurrentRow->page_title )
                        . '</li>';
                $html .= '</ul>';
                return $html;
            case 'wpw_monthly_page_hits':
                return wgformatNumber($value).' hits';
            case 'wpw_monthly_bandwidth':
                if (intval($value) < 1)
                    return '< ' . wgformatSizeMB(1);
                else
                    return wgformatSizeMB($value);
            case 'wpw_report_updated':
            case 'wpw_date_expires':
                return ($value === null) ? '-' : $wgLang->timeanddate($value, true);
            default:
                return htmlspecialchars($value);
        }
    }

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['page_namespace']);
        unset($fieldNames['wpw_report_updated']);
        unset($fieldNames['wpw_date_expires']);
        return $fieldNames;
    }

    function getEndBody() {
        $colums = count($this->getFieldNames());
        
        if ($this->even)
            $class = 'mw-line-even';
        else $class = 'mw-line-odd';
        $this->even = !$this->even;
        
        $html = "<tr class=\"$class\"><td colspan=\"$colums\">";
        $html .= SpecialWikiplaces::getLinkCreateWikiplace();
        $html .= "</td></tr>";
        $html .= "</tbody></table>\n";
		return $html;
	}
}