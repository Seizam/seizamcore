<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified pages listing. 
 */
class WpInvitationsTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array('wp_invitation');
    protected $selectFields = array(
        'wpi_code',
		'wpi_to_email',
		// 'wpi_from_user_id',
		'wpi_date_created',
		// 'wpi_date_last_used',
		//'wpi_counter',
		//'wpi_wpic_id',
		);
    protected $defaultSort = 'wpi_date_created';
    public $mDefaultDirection = true; // true = DESC
    public $forceDefaultLimit = 20;
    protected $tableClasses = array('WpInvitation'); # Array
    protected $messagesPrefix = 'wp-';

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
            default:
                return htmlspecialchars($value);
        }
    }
	
	function getFieldNames() {
        $fieldNames = parent::getFieldNames();
		$fieldNames['wpi_code'] = wfMessage ('wpi_code')->text ();
		$fieldNames['wpi_to_email'] = wfMessage ('wpi_to_email')->text ();
		$fieldNames['wpi_date_created'] = wfMessage ('wpi_date_created')->text ();
        return $fieldNames;
    }

}