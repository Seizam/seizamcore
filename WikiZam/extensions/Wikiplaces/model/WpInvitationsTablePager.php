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
		'wpi_date_last_used',
		'wpi_counter',
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
			case 'wpi_date_created':
				return $wgLang->date($value, true);
			case 'wpi_counter':
				if ( $value < 0) {
					return wfMessage ('wp_unlimited')->text ();
				} elseif ( $value == 0) {
					return wfMessage ('wp_used', $wgLang->date($this->mCurrentRow->wpi_date_last_used, true))->parse();
				} else {
					return wfMessage ('wp_not_used')->text ();
				}
            default:
                return htmlspecialchars($value);
        }
    }
	
	function getFieldNames() {
        $fieldNames = parent::getFieldNames();
		unset($fieldNames['wpi_counter']);
		unset($fieldNames['wpi_date_last_used']);
		$fieldNames['wpi_code'] = wfMessage ('wpi_code')->text ();
		$fieldNames['wpi_to_email'] = wfMessage ('wpi_to_email')->text ();
		$fieldNames['wpi_date_created'] = wfMessage ('wpi_date_created')->text ();
		$fieldNames['wpi_counter'] = wfMessage ('wpi_counter')->text ();
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

        if ($row->wpi_counter < 1)
            $classes[] = 'used';

        return $classes;
    }

}


class WpInvitationsTablePagerAdmin extends WpInvitationsTablePager {
	
	protected $selectTables = array('wp_invitation', 'wp_invitation_category');
    protected $selectFields = array(
		'wpic_desc',
        'wpi_code',
		'wpi_to_email',
		// 'wpi_from_user_id',
		'wpi_date_created',
		'wpi_date_last_used',
		'wpi_counter',
		//'wpi_wpic_id',
		);
	protected $selectJoinConditions = array(
		'wp_invitation_category' => array('LEFT JOIN', 'wpi_wpic_id = wpic_id') );
	
	function formatValue($name, $value) {
        global $wgLang;
        switch ($name) {
			case 'wpi_date_created':
				return $wgLang->date($value, true);
			case 'wpi_counter':
				if ( $value < 0) {
					return 'unlimited, already used '.(-($value+1)).' times';
				}elseif ( $value == 0) {
					return 'no longer available, last used '.$wgLang->date($this->mCurrentRow->wpi_date_last_used, true);
				}else{
					return 'can still be used '.$value.' times';
				}
            default:
                return htmlspecialchars($value);
        }
    }

	function getFieldNames() {
        $fieldNames = parent::getFieldNames();
		$fieldNames['wpic_desc'] = 'Category';
		return $fieldNames;
	}
	
}