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
        parent::__construct('Transactions', TM_ACCESS_RIGHT);
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $output = $this->getOutput();
        $user = $this->getUser();
        $request = $this->getRequest();

        $this->setHeaders();

        // Check rights
        if (!$this->userCanExecute($user)) {
            // If anon, redirect to login
            if ($user->isAnon()) {
                $output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto'=>$this->getFullTitle())), '401');
                return;
            }
            // Else display an error page.
            $this->displayRestrictionError();
            return;
        }
        
        if (isset($par) & $par != '') {
            $action = $par;
        } else {
            $action = $request->getText('action');
        }
        
        $msgType = $request->getText('msgtype', null);
        if (isset($msgType)) {
            $msg = wfMessage($request->getText('msg'));
            if ($msg->exists()) {
                $output->addHTML(Html::rawElement('div', array('class'=>"informations $msgType"), $msg->parse()));
            }
        }
        
        $table = new TransactionsTablePager();
        $table->setSelectFields(array('tmr_id', 'tmr_desc', 'tmr_date_created', 'tmr_amount', 'tmr_currency', 'tmr_status'));
        $table->setSelectConds(array('tmr_user_id' => $user->getId(), 'tmr_currency' => 'EUR'));
        $table->setHeader(wfMessage('tm-balance', TMRecord::getTrueBalanceFromDB($user->getId()))->parse() . ' ' . wfMessage('tm-table-desc')->parse());
        $output->addHtml($table->getWholeHtml());
    }

}

