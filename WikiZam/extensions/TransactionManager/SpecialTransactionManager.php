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
        parent::__construct('TransactionManager', 'user');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $output = $this->getOutput();
        $user = $this->getUser();

        $this->setHeaders();

        $tmr = array(
            # Params related to Message
            'tmr_type' => 'sale', # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
            # Paramas related to User
            'tmr_user_id' => 1, # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
            'tmr_mail' => $user->getEmail(), # tinyblob COMMENT 'User''s Mail',
            'tmr_ip' => IP::sanitizeIP(wfGetIP()), # tinyblob COMMENT 'User''s IP'
            # Params related to Record
            'tmr_amount' => 1.56, # decimal(9,2) NOT NULL COMMENT 'Record Amount',
            'tmr_currency' => 'EUR', # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
            'tmr_desc' => 'sale', # varchar(64) NOT NULL COMMENT 'Record Description',
            'tmr_status' => 'OK' # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding, TEst)',
        );

        wfRunHooks('CreateTransaction', array(&$tmr));

        //$tmrs = TMRecord::getAllOwnedByUserId($user->getId(), array('tmr_status'=>'PE'));
        
        /*$output->addHTML('<pre>');
        $output->addHTML(print_r($tmr, true));
        $output->addHTML('</pre>');*/
        
        

        # Building the transaction table and sum
        if ($user->isLoggedIn()) {
            $table = new TransactionsTablePager();
            $table->setSelectFields(array('tmr_id', 'tmr_desc', 'tmr_date_created', 'tmr_amount', 'tmr_currency', 'tmr_status'));
            $table->setSelectConds(array('tmr_user_id' => $user->getId(), 'tmr_currency' => 'EUR'));
            $table->setHeader(wfMessage('tm-balance', TMRecord::getTrueBalanceFromDB($user->getId()))->parse() . ' ' . wfMessage('tm-table-desc')->parse());
            $output->addHtml($table->getWholeHtml());
        } else {
            $output->addWikiText(wfMessage('tm-desc')->text());
            $output->addWikiText(wfMessage('resetpass-no-info')->text());
        }
    }

}

