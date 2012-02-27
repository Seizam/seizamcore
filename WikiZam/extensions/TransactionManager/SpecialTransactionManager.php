<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Implements Special:ElectronicPayment
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 * @ingroup Upload
 */

/**
 * Form for handling uploads and special page.
 *
 * @ingroup SpecialPage
 * @ingroup Upload
 */
class SpecialTransactionManager extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('TransactionManager');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $output = $this->getOutput();

        $this->setHeaders();
        $output->addWikiText(wfMessage('tm-desc')->text());
        
        
        
        $output->addWikiText('Pending:');
        foreach(TMRecord::getPendingTransactions($this->getUser()->getId()) as $record)
            self::sayIt($record);
        
        $output->addWikiText('BALANCE= '.$this->getBalanceFromDB().'€');
        
        foreach($this->readUserRecordsfromDB() as $record)
            self::sayIt($record);
    }
    
    static function sayIt($in) {
        global $wgOut;
        $wgOut->addHTML('<pre>');
        $wgOut->addHTML(print_r($in, true));
        $wgOut->addHTML('</pre>');
    }
    
    public function readUserRecordsfromDB() {
        $user = $this->getUser();
        $dbr = wfGetDB(DB_SLAVE);
        return $dbr->select('tm_record', '*', array('tmr_user_id' => $user->getId()));
    }
    
    public function getBalanceFromDB() {
        $user = $this->getUser();
        $dbr = wfGetDB(DB_SLAVE);
        # @TODO: Remove TE(st) from conditions. Otherwise we count virtual money...
        $result = $dbr->select('tm_record', 'SUM(tmr_amount) AS balance', array('tmr_user_id' => $user->getId(), 'tmr_status'=>array('OK','TE')));
        return $result->current()->balance;
    }

}

/**
 * Use TablePager for prettified output. 
 */
class TransactionsTablePager extends TablePager {
    
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
    public function getQueryInfo() {
        global $wgUser;
        $infos = array();
        $infos['tables'] = 'tm_record';
        $infos['fields'] = array('tmr_id','tmr_date_created','tmr_amount','tmr_currency','tmr_desc');
        $infos['conds'] = array('tmr_user_id' => $wgUser->getId(), 'tmr_status'=>'PE','tmr_amount < 0');
        return $infos;
    }
    
    public function isFieldSortable($field) {
        return true;
    }
    
    public function formatValue($name, $value) {
        return $value;
    }
    
    public function getDefaultSort() {
        return 'tmr_date_created';
    }
    
    public function getFieldNames() {
        $fieldNames = array();
        $fieldNames['tmr_id'] = 'ID';
        $fieldNames['tmr_date_created'] = 'Creation Date';
        $fieldNames['tmr_amount'] = 'Amount';
        $fieldNames['tmr_currency'] = 'Currency';
        $fieldNames['tmr_desc'] = 'Description';
        return $fieldNames;
    }

}

