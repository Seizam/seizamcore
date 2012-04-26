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
class SpecialTransactions extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('Transactions', 'user');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $output = $this->getOutput();
        $user = $this->getUser();

        $this->setHeaders();

        if (!$user->isLoggedIn()) {
            $output->addWikiText(wfMessage('tm-desc')->text());
            $output->addWikiText(wfMessage('resetpass-no-info')->text());
            return;
        }
        
        $table = new TransactionsTablePager();
        $table->setSelectFields(array('tmr_id', 'tmr_desc', 'tmr_date_created', 'tmr_amount', 'tmr_currency', 'tmr_status'));
        $table->setSelectConds(array('tmr_user_id' => $user->getId(), 'tmr_currency' => 'EUR'));
        $table->setHeader(wfMessage('tm-balance', TMRecord::getTrueBalanceFromDB($user->getId()))->parse() . ' ' . wfMessage('tm-table-desc')->parse());
        $output->addHtml($table->getWholeHtml());
    }

}
