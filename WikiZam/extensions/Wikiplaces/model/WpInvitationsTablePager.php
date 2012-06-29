<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Use TablePager for prettified pages listing. 
 */
class WpInvitationsTablePager extends SkinzamTablePager {
    # Fields for default behavior

    protected $selectTables = array('wp_invitation','wp_invitation_category','wp_subscription','user');
    protected $selectFields = array(
        'wpi_code',
		'wpic_desc',
		'wpi_to_email',
		// 'wpi_from_user_id',
		'wpi_date_created',
		'wpi_date_last_used',
		'wpi_counter',
        'user_name'
		);
    protected $selectJoinConditions = array(
        'wp_invitation_category' => array('INNER JOIN', 'wpi_wpic_id = wpic_id'),
        'wp_subscription' => array('LEFT JOIN', 'wps_wpi_id = wpi_id'),
        'user' => array('LEFT JOIN', 'user_id = wps_buyer_user_id'));
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
			case 'wpi_date_created':
				return $wgLang->date($value, true);
			case 'wpi_counter':
				if ( $value < 0) {
					return wfMessage ('wpi-unlimited',-($value+1))->text();
				} elseif ( $value == 0) {
					return wfMessage ('wpi-used', $this->mCurrentRow->user_name, $wgLang->date($this->mCurrentRow->wpi_date_last_used, true))->parse();
				} elseif ($value == 1) {
					return wfMessage ('status-PE')->text();
				} else {
                    return wfMessage ('wpi-remaining',  wfFormatNumber($value))->text();
                }
            case 'wpi_to_email':
                if (empty ($value))
                    return '-';
                else
                    return htmlspecialchars($value);
            case 'wpic_desc':
                return wfMessage('wpi-'.$value)->text();
            default:
                return htmlspecialchars($value);
        }
    }
	
	function getFieldNames() {
        $fieldNames = parent::getFieldNames();
		unset($fieldNames['wpi_date_last_used']);
		unset($fieldNames['user_name']);
		$fieldNames['wpi_code'] = wfMessage ('wpi-code')->text ();
		$fieldNames['wpic_desc'] = wfMessage('wpi-type')->text();
		$fieldNames['wpi_to_email'] = wfMessage ('wpi-to-email')->text ();
		$fieldNames['wpi_date_created'] = wfMessage ('date_created')->text ();
		$fieldNames['wpi_counter'] = wfMessage ('status')->text ();
        return $fieldNames;
    }
	
	    /**
     * Get a class name to be applied to the given row.
     *
     * @param $row Object: the database result row
     * @return array
     */
    function getRowClasses($row) {
        $classes = array();

        if ($row->wpi_counter == 1)
            $classes[] = 'pending';

        return $classes;
    }

}