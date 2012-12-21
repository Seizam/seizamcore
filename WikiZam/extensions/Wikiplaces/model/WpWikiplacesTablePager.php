<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified Wikiplaces listing. 
 */
class WpWikiplacesTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array('wp_wikiplace', 'wp_page', 'page', 'wp_member');
    protected $selectJoinConditions = array(
        'wp_page' => array('LEFT JOIN', 'wpw_id = wppa_wpw_id'),
        'page' => array('INNER JOIN', 'wpw_home_page_id = page_id'),
        'wp_member' => array('LEFT JOIN', 'wpw_id = wpm_wpw_id') );
    protected $selectFields = array(
        'page_title',
        'page_namespace',
        'count(DISTINCT wppa_id) as count',
        'count(DISTINCT wpm_id) as members',
        'wpw_monthly_page_hits',
        'wpw_monthly_bandwidth',
        'wpw_report_updated',
        'wpw_date_expires');
    protected $selectOptions = array('GROUP BY' => 'wp_wikiplace.wpw_id');
    protected $defaultSort = 'page_title';
    public $forceDefaultLimit = 3;
    public $mDefaultDirection = false; // true = DESC
    protected $tableClasses = array('WpWikiplace'); # Array
    protected $messagesPrefix = 'wp-';
    protected $selectConds = array();
	
	function setShortDisplay() {
		if (($key = array_search('wpw_monthly_page_hits', $this->selectFields)) !== false) {
			unset($this->selectFields[$key]);
		}
		if (($key = array_search('wpw_monthly_bandwidth', $this->selectFields)) !== false) {
			unset($this->selectFields[$key]);
		}
		$this->setFieldNames(); // reset the columns names
	}

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
                return Linker::linkKnown($title, null, array(), array('redirect'=>'no'));
            case 'count':
                $html = '<b>'.$value.'</b> '.  wfMessage('wp-items');
                $html .= '<ul>';
                $html .= '<li><b>'
                        . SpecialWikiplaces::getLinkConsultWikiplace($this->mCurrentRow->page_title)
                        . '</b></li>';
                $html .= '<li>'
                        . SpecialWikiplaces::getLinkCreateSubpage( $this->mCurrentRow->page_title )
                        . '</li>';
                $html .= '</ul>';
                return $html;
            case 'members':
                $html = '<b>'.$value.'</b> '.  wfMessage('wp-users');
                $html .= '<ul>';
                $html .= '<li><b>'
						. SpecialWikiplaces::getLinkListMembers($this->mCurrentRow->page_title)
                        . '</b></li>';
                $html .= '<li>'
						. SpecialWikiplaces::getLinkAddMember( $this->mCurrentRow->page_title )
                        . '</li>';
                $html .= '</ul>';
                return $html;
            case 'wpw_monthly_page_hits':
                return wfFormatNumber($value).' '.  wfMessage('wp-hits')->text();
            case 'wpw_monthly_bandwidth':
                return wfFormatSizekB($value);
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
        unset($fieldNames['count(DISTINCT wppa_id) as count']);
        unset($fieldNames['count(DISTINCT wpm_id) as members']);
				
        $fieldNames['count'] = wfMessage('wp-subpages')->text();
		
		$fieldNames['members'] = wfMessage('wp-members')->text();
        
        if (isset ($fieldNames['page_title']))
            $fieldNames['page_title'] = wfMessage('wp-name')->text();
        
        if (isset ($fieldNames['wpw_monthly_page_hits']))
            $fieldNames['wpw_monthly_page_hits'] = wfMessage('wp-Hits')->text();
        
        if (isset ($fieldNames['wpw_monthly_bandwidth']))
            $fieldNames['wpw_monthly_bandwidth'] = wfMessage('wp-bandwidth')->text();
                
        return $fieldNames;
    }

    function getEndBody() {
        $colums = count($this->getFieldNames());
        
        if ($this->even)
            $class = 'mw-line-even';
        else $class = 'mw-line-odd';
        $this->even = !$this->even;
        
        $html = "<tr class=\"$class mw-line-last\"><td colspan=\"$colums\">";
        $html .= SpecialWikiplaces::getLinkCreateWikiplace('wp-create-wikiplace-long');
        $html .= "</td></tr>";
        $html .= parent::getEndBody();
		return $html;
	}
    
    /**
     * Determine if $field should be sortable
     * 
     * @param string $field
     * @return boolean 
     *
     */
    function isFieldSortable($field) {
        return ( $field == 'count' || $field == 'members' ) ? false : parent::isFieldSortable($field);
    }
}