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
        'subpage' => array('INNER JOIN', 'wppa_page_id = subpage.page_id AND subpage.page_namespace !=1 AND subpage.page_namespace !=7 AND subpage.page_namespace !=71'));
    protected $selectFields = array('subpage.page_title',
        'subpage.page_namespace',
        'subpage.page_touched',
        'subpage.page_counter',
        'subpage.page_is_redirect');
    protected $selectOptions = array('ORDER BY' => 'subpage.page_title');
    protected $defaultSort = 'page_touched';
    public $mDefaultDirection = true; // true = DESC
    public $forceDefaultLimit = 20;
    protected $tableClasses = array('WpPage'); # Array
    protected $messagesPrefix = 'wp-';
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
            case 'page_title':
                return $this->formatPageTitle($value);
            case 'page_namespace':
                return $this->formatNamespace($value);
            case 'page_touched':
                return $wgLang->date($value, true);
            case 'page_counter':
                return wgFormatNumber($value) . ' ' . wfMessage('wp-hits')->text();
            case 'actions' :
                return $this->formatActions();
            default:
                return htmlspecialchars($value);
        }
    }

    function formatPageTitle($value) {
        $title = Title::makeTitle($this->mCurrentRow->page_namespace, $value);
        $ns = $title->getNamespace();
        $explosion = WpWikiplace::explodeWikipageKey($title->getDBkey(), $ns);
        $excount = count($explosion);
        $text = '';
        // Page is in NS_MAIN
        if ($ns == NS_MAIN || $ns == NS_WIKIPLACE) {
            if ($ns == NS_WIKIPLACE) {
                $text .= '<span class="wpp-ns">' . $title->getNsText() . ':</span>';
            }
            // Homepage
            if ($excount == 1) {
                if ($ns == NS_MAIN)
                    $text .= '<span class="wpp-hp">' . $explosion[0] . '</span>';
                else
                    $text .= '<span class="wpp-sp">' . $explosion[0] . '</span>';
                // Subpage
            } else {
                // Language variant
                if (strlen($explosion[$excount - 1]) == 2) {
                    $lang = $explosion[$excount - 1];
                    array_pop($explosion);
                }

                // Extracting wikiplace base
                $text .= '<span class="wpp-sp-hp">' . $explosion[0] . '</span>';
                array_shift($explosion);

                // Reconstructing Page title
                $text .= '<span class="wpp-sp">';
                foreach ($explosion as $atom)
                    $text .= '/' . $atom;
                $text .= '</span>';

                // Appending Lang variant
                if (isset($lang))
                    $text .= '<span class="wpp-sp-lg">/' . $lang . '</span>';
            }
            // Page is NS_FILE
        } else if ($title->getNamespace() == NS_FILE) {
            $text .= '<span class="wpp-ns">' . $title->getNsText() . ':</span>';
            // @TODO: Extract file extension and lang variant for prettyfying
            // 
            // Extracting wikiplace base
            $text .= '<span class="wpp-sp-hp">' . $explosion[0] . '</span>';
            array_shift($explosion);

            // Reconstructing Page title
            $text .= '<span class="wpp-sp">';
            foreach ($explosion as $atom)
                $text .= '.' . $atom;
            $text .= '</span>';
        } else {
            $text .= '<span class="wpp-ns">' . $title->getNsText() . ':</span>';
            $text .= '<span class="wpp-sp">' . $title->getText() . '</span>';
        }
        return Linker::linkKnown($title, $text, array(), array('redirect' => 'no'));
    }

    function formatNamespace($value) {
        global $wgLang;
        switch ($value) {
            case NS_MAIN :
                if (count(explode('/', $this->mCurrentRow->page_title)) == 1)
                    return wfMessage('wp-homepage')->text();
                else if ($this->mCurrentRow->page_is_redirect == 1)
                    return wfMessage('wp-redirect')->text();
                else
                    return wfMessage('wp-subpage')->text();
            case NS_WIKIPLACE :
                return wfMessage('wp-nswp')->text();
            case NS_WIKIPLACE_TALK :
                return wfMessage('wp-nswp-talk')->text();
            default :
                return $wgLang->getNsText($value);
        }
    }

    function formatActions() {
        $title = Title::makeTitle($this->mCurrentRow->page_namespace, $this->mCurrentRow->page_title);

        $html = '<ul>';
        $html .= '<li>'
                . Linker::linkKnown($title, wfMessage('view')->text(), array(), array('redirect' => 'no'))
                . '</li>';
        $html .= '<li>'
                . Linker::link($title->getTalkPage(), wfMessage('talk')->text(), array(), array('redirect' => 'no'))
                . '</li>';
        $html .= '<li>'
                . Linker::linkKnown($title, wfMessage('edit')->text(), array(), array('action' => 'edit'))
                . '</li>';
        /* $html .= '<li>'
          . Linker::linkKnown($title, wfMessage('history_short')->text(), array(), array('action' => 'history'))
          . '</li>'; */
        $html .= '<li>'
                . Linker::linkKnown($title, wfMessage('protect')->text(), array(), array('action' => PROTECTOWN_ACTION))
                . '</li>';
        $html .= '</ul>';
        return $html;
    }

    function setWPName($name) {
        $this->wpName = $name;
    }

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();

        unset($fieldNames['page_is_redirect']);

        $fieldNames['actions'] = '';

        if (isset($fieldNames['page_title']))
            $fieldNames['page_title'] = wfMessage('wp-name');

        if (isset($fieldNames['page_touched']))
            $fieldNames['page_touched'] = wfMessage('date_modified');

        if (isset($fieldNames['page_namespace']))
            $fieldNames['page_namespace'] = wfMessage('type');

        if (isset($fieldNames['page_counter']))
            $fieldNames['page_counter'] = wfMessage('wp-Hits');

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
        $html .= SpecialWikiplaces::getLinkCreateSubpage($this->wpName);
        $html .= "</td></tr>";
        $html .= "</tbody></table>\n";
        return $html;
    }

}