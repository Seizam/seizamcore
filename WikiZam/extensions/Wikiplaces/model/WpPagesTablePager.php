<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified pages listing. 
 */
class WpPagesTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array(
        'wp_wikiplace',
        'homepage' => 'page',
        'wp_page',
        'subpage' => 'page');
    protected $selectJoinConditions = array(
        'homepage' => array('INNER JOIN', 'wpw_home_page_id = homepage.page_id'),
        'wp_page' => array('INNER JOIN', 'wpw_id = wppa_wpw_id'),
        'subpage' => array('INNER JOIN', 'wppa_page_id = subpage.page_id AND subpage.page_namespace !=1 AND subpage.page_namespace !=7'));
    protected $selectFields = array('subpage.page_title AS subpage_title', 'subpage.page_namespace AS subpage_namespace', 'subpage.page_touched AS subpage_touched', 'subpage.page_counter AS subpage_counter');
    protected $selectOptions = array('ORDER BY' => 'subpage_title');
    protected $defaultSort = 'subpage_namespace';
    public $mDefaultDirection = false; // true = DESC
    protected $tableClasses = array('WpPage'); # Array
    protected $messagesPrefix = 'wppatp';
    protected $wpName = '';

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
            case 'subpage_title':
                return $this->formatPageTitle($value);
            case 'subpage_namespace':
                return $this->formatNamespace($value);
            case 'subpage_touched':
                return $wgLang->timeanddate($value, true);
            case 'subpage_counter':
                return wgformatNumber($value) . ' hits';
            case 'actions' :
                return $this->formatActions();
            default:
                return htmlspecialchars($value);
        }
    }

    function formatPageTitle($value) {
        $title = Title::makeTitle($this->mCurrentRow->subpage_namespace, $value);
        $text = '';
        // Page is in NS_MAIN
        if ($title->getNamespace() == NS_MAIN) {
            $explosion = explode('/', $title->getText());
            $excount = count($explosion);
            // Homepage
            if ($excount == 1) {
                $text .= '<span class="wpp-hp">' . $explosion[0] . '</span>';
                // Subpage
            } else {
                // Language variant
                if (strlen($explosion[$excount - 1]) == 2) {
                    $lang = $explosion[$excount - 1];
                    array_pop($explosion);
                }

                // Extracting wikiplace base
                $text .= '<span class="wpp-sp-hp">' . $explosion[0] . '/</span>';
                array_shift($explosion);

                // Reconstructing Page title
                $text .= '<span class="wpp-sp">';
                foreach ($explosion as $atom)
                    $text .= $atom . '/';
                $text = substr($text, 0, -1);
                $text .= '</span>';

                // Appending Lang variant
                if (isset($lang))
                    $text .= '<span class="wpp-sp-lg">/' . $lang . '</span>';
            }
            // Page is NS_FILE
        } else if ($title->getNamespace() == NS_FILE) {
            $text .= '<span class="wpp-ns">' . $title->getNsText() . ':</span>';
            $explosion = explode('.', $title->getText());
            // @TODO: Extract file extension and lang variant for prettyfying
            // 
            // Extracting wikiplace base
            $text .= '<span class="wpp-sp-hp">' . $explosion[0] . '.</span>';
            array_shift($explosion);

            // Reconstructing Page title
            $text .= '<span class="wpp-sp">';
            foreach ($explosion as $atom)
                $text .= $atom . '.';
            $text = substr($text, 0, -1);
            $text .= '</span>';
        } else {
            $text .= '<span class="wpp-ns">' . $title->getNsText() . ':</span>';
            $text .= '<span class="wpp-sp">' . $title->getText() . '</span>';
        }
        return Linker::linkKnown($title, $text);
    }

    function formatNamespace($value) {
        global $wgLang;
        switch ($value) {
            case NS_MAIN :
                if (!preg_match("/\//", $this->mCurrentRow->subpage_title))
                    return wfMessage('wp-homepage')->text();
                else
                    return wfMessage('wp-subpage')->text();
            default :
                return $wgLang->getNsText($value);
        }
    }

    function formatActions() {
        $title = Title::makeTitle($this->mCurrentRow->subpage_namespace, $this->mCurrentRow->subpage_title);

        $html = '<ul>';
        $html .= '<li class="first">'
                . Linker::linkKnown($title, wfMessage('wp-see')->text())
                . '</li>';
        $html .= '<li>'
                . Linker::link($title->getTalkPage(), wfMessage('wp-talk')->text())
                . '</li>';
        $html .= '<li>'
                . Linker::linkKnown($title, wfMessage('wp-edit')->text(), array(), array('action' => 'edit'))
                . '</li>';
        /*$html .= '<li>'
                . Linker::linkKnown($title, wfMessage('wp-history')->text(), array(), array('action' => 'history'))
                . '</li>';*/
        $html .= '<li>'
                . Linker::linkKnown($title, wfMessage('wp-restrict')->text(), array(), array('action' => RESTRICTIONS_ACTION))
                . '</li>';
        $html .= '</ul>';
        return $html;
    }

    function setWPName($name) {
        $this->wpName = $name;
    }

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
        unset($fieldNames['homepage_title']);
        $fieldNames['actions'] = '';
        return $fieldNames;
    }

    function getEndBody() {
        $colums = count($this->getFieldNames());

        if ($this->even)
            $class = 'mw-line-even';
        else
            $class = 'mw-line-odd';
        $this->even = !$this->even;

        $html = "<tr class=\"$class\"><td colspan=\"$colums\">";
        $html .= Linker::linkKnown(SpecialPage::getTitleFor('Wikiplaces'), wfMessage('wp-createpage')->text(), array(), array('action' => SpecialWikiplaces::ACTION_CREATE_WIKIPLACE_PAGE, 'name' => $this->wpName));
        $html .= "</td></tr>";
        $html .= "</tbody></table>\n";
        return $html;
    }

}