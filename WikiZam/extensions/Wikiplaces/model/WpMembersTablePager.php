<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified pages listing. 
 * 
 * `wpm_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
 * `wpm_wpw_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated WikiPlace',
 * `wpm_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated user',
 */
class WpMembersTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array(
        'wp_member',
        'user' );
    protected $selectJoinConditions = array(
        'user' => array('INNER JOIN', 'wpm_user_id = user_id') );
    protected $selectFields = array(
		'user_id',
        'user_name',
        'user_real_name');
    protected $defaultSort = 'user_name';
    public $mDefaultDirection = true; // true = DESC
    public $forceDefaultLimit = 10;
    protected $tableClasses = array('WpMember'); # Array
    protected $messagesPrefix = 'wp-';
    protected $wpNameDb = '';
    protected $wpNameText = '';

	/**
	 * 
	 * @param WpWikiplace $wikiplace
	 */
	function setWikiPlace($wikiplace) {
        $this->wpNameText = $wikiplace->getName();
		$this->wpNameDb = Title::newFromText($this->wpNameText)->getDBkey();
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
		
		switch ($name) {
            case 'user_name':
                return $this->formatUserName($value);
            case 'actions':
                return $this->formatActions();
            default:
                return htmlspecialchars($value);
        }
	}
	
	function formatUserName($username) {
		$userTitle = Title::newFromText($username, NS_USER);
        
		return Linker::link($userTitle, "<b>$username</b>");
	}

    function formatActions() {
        $userName = $this->mCurrentRow->user_name;
        $html = '<ul>';
        $html .= '<li>'
				. Linker::link(Title::newFromText($userName, NS_USER_TALK), wfMessage('talk')->text())
				. '</li>'
                . '<li>'
				. Linker::linkKnown(SpecialPage::getTitleFor('EmailUser',$userName), wfMessage('emailuser')->text())
				. '</li>'
                . '<li>'
				. Linker::linkKnown(SpecialPage::getTitleFor('Contributions',$userName), wfMessage('contributions')->text())
				. '</li>'
				. '<li><b>'
				. SpecialWikiplaces::getLinkRemoveMember($this->wpNameDb, $this->mCurrentRow->user_name)
                . '</b></li>';
        $html .= '</ul>';
        return $html;
    }

    function getFieldNames() {
        $fieldNames = parent::getFieldNames();
		
		unset($fieldNames['user_id']);
		
		if (isset($fieldNames['user_name']))
            $fieldNames['user_name'] = wfMessage('wp-username');

        if (isset($fieldNames['user_real_name']))
            $fieldNames['user_real_name'] = wfMessage('wp-realname');
		
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

        $html = "<tr class=\"$class mw-line-last\"><td colspan=\"$colums\">";
        $html .= SpecialWikiplaces::getLinkAddMember($this->wpNameDb, 'wp-add-member-long');
        $html .= "</td></tr>";
        $html .= "</tbody></table>\n";
        return $html;
    }

}