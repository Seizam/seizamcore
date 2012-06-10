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
    const TITLE_NAME = 'Transactions';

    const ACTION_BILL = 'bill';
    const ACTION_LIST = 'list';

    private $action = self::ACTION_LIST;
    private $id = null;
    private $msgType = null;
    private $msgKey = null;

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct(self::TITLE_NAME, TM_ACCESS_RIGHT);
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
                $output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto' => $this->getFullTitle())), '401');
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

        // Reading parameter from request
        if (isset($par) & $par != '') {
            $explosion = explode(':', $par);
            if (count($explosion) == 1) {
                $this->action = $explosion[0];
                $this->id = null;
            } else if (count($explosion) == 2) {
                $this->action = $explosion[0];
                $this->id = $explosion[1];
            }
        } else {
            $this->action = $request->getText('action', null);
            $this->id = $request->getText('id', null);
        }
        $this->msgType = $request->getText('msgtype', $this->msgType);
        $this->msgKey = $request->getText('msgkey', $this->msgKey);

        $this->display();
    }

    private function display() {
        $output = $this->getOutput();

        // Top Infobox Messaging
        if ($this->msgType != null) {
            $msg = wfMessage($this->msgKey);
            if ($msg->exists()) {
                $output->addHTML(Html::rawElement('div', array('class' => "informations $this->msgType"), $msg->parse()));
            }
        }

        switch ($this->action) {
            case self::ACTION_BILL:
                $this->displayBill();
                break;
            case self::ACTION_LIST:
            default:
                $this->displayList();
                break;
        }
    }

    private function displayList() {
        $output = $this->getOutput();
        $user = $this->getUser();

        $table = new TransactionsTablePager();
        $table->setSelectFields(array('tmr_id', 'tmr_desc', 'tmr_date_created', 'tmr_amount', 'tmr_currency', 'tmr_status'));
        $table->setSelectConds(array('tmr_user_id' => $user->getId(), 'tmr_currency' => 'EUR'));
        $table->setHeader(wfMessage('tm-balance', $this->getLanguage()->formatNum(TMRecord::getTrueBalanceFromDB($user->getId())), 'cur-euro')->parse() . ' ' . wfMessage('tm-table-desc')->parse());
        $output->addHtml($table->getWholeHtml());
    }

    private function displayBill() {
        $output = $this->getOutput();
        $user = $this->getUser();

        $tmRecord = TMRecord::getById((int) $this->id);
        $tmr = $tmRecord->getTMR();

        if ($user->getId() != $tmRecord->getUserId() || $tmr['tmr_amount'] >= 0 || $tmr['tmr_status'] == 'KO') {
            $this->action = self::ACTION_LIST;
            $this->msgKey = 'sz-invalid-request';
            $this->msgType = 'error';
            $this->display();
            return;
        }

        $tmUser = User::newFromId($tmRecord->getUserId());

        $output->disable();
        header("Pragma: no-cache");
        header("Content-type: text/html");

        print_r($this->constructHtmlHead()
                . $this->constructHtmlBody($tmr, $tmUser));
    }

    private function constructHtmlHead() {
        $html = '<!DOCTYPE html><html><head><title>Seizam - Facture' . $this->id . '</title><meta charset="UTF-8"><meta content="noindex,nofollow" name="robots">';
        $html .= '<style type="text/css">
html {background-color:#e5e5e5; padding: 0;}
body {position: absolute; width: 19cm; height: 29cm; padding: 1cm; margin: 0; background-color:white; border: 1px solid #CCCCCC;}
#client {float: right; width: 35%;}
h1 {text-align: center; clear: both;}
h2, h3, th {color: #CCCCCC; margin: 0;}
p {margin-top: 0;}
table {width:100%; border-collapse: collapse; margin-bottom: 1em;}
td, th {border: 1px solid #CCCCCC; text-align: right; padding: 0.5em;}
#pied {position: absolute; bottom: 1cm; text-align: center; margin: 0; width: 19cm; font-size: 0.8em;}
</style>';
        $html .= '</head>';
        return $html;
    }

    /**
     *
     * @param TmRecord $tmRecord
     * @param User $tmUser
     * @return string 
     */
    private function constructHtmlBody($tmr, $tmUser) {
        $lang = Language::factory('fr');

        $realname = $tmUser->getRealName();

        $q = 1;
        $ttc = -$tmr['tmr_amount'];
        $ht = $this->getHTFromTTC($ttc);
        $tttc = $ttc * $q;
        $tht = $ht * $q;

        $html = '<body>';
        $html .= '<h2>Seizam</h2>';
        $html .= '<p>Société à responsabilité limitée (Sàrl)<br/>Capital : 10000€</p>';

        $html .= '<h3>Adresse :</h3>';
        $html .= '<p>24 avenue de Bâle<br/>68300 Saint-Louis<br/>France</p>';

        $html .= '<div id="client"><h3>Client :</h3>';
        $html .= '<p>' . $tmUser->getName() . '<br/>';
        if ($realname != '')
            $html .= '(' . $realname . ')<br/>';
        $html .= $tmUser->getEmail() . '</p></div>';

        $html .= '<h3>Immatriculation :</h3>';
        $html .= '<p>SIREN : 537 537 045<br/>RCS Mulhouse (68100)</p>';

        $html .= '<h1><u>Facture ' . $this->id . '</u> émise le ' . $lang->date($tmr['tmr_date_created']) . '</h1>';

        $html .= '<table><tr><th style="text-align:left">Désignation</th><th>Quantité</th><th>PU HT (€)</th><th>Total HT (€)</th><th>Total TTC (€)</th></tr>';
        $html .= '<tr>';
        $html .= '<td style="text-align:left">' . wfMessage($tmr['tmr_desc'])->inLanguage($lang)->text() . '</td>';
        $html .= '<td>' . $q . '</td>';
        $html .= '<td>' . $ht . '</td>';
        $html .= '<td>' . $tht . '</td>';
        $html .= '<td>' . $tttc . '</td>';
        $html .= '</tr>';
        $html .= '<tr><th colspan="4">TOTAL HT (€)</th><td>' . $tht . '</td></tr>';
        $html .= '<tr><th colspan="4">TOTAL TTC (€)</th><td>' . $tttc . '</td></tr>';
        $html .= '</table>';

        $html .= '<p style="text-align:center;">';
        if ($tmr['tmr_status'] == 'PE')
            $html .= 'À payer sous 30 jours.';
        elseif ($tmr['tmr_status'] == 'OK')
            $html .= 'Payée le ' . $lang->timeanddate($tmr['tmr_date_created']) . ' GMT.';
        $html .= '</p>';

        $html .= '<p id="pied"><b>Seizam - Sàrl au capital de 10000€</b>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;24 avenue de Bâle - 68300 - Saint-Louis&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;SIREN : 537 537 045 - RCS Mulhouse (68100)</p>';
        $html .= '</body></html>';
        return $html;
    }

    private function getHTFromTTC($ttc) {
        return round(1000 / 1196 * $ttc, 2);
    }
    
    public static function getLinkBill($id = null, $text = 'bill') {
		$params = array('action' => self::ACTION_BILL);
		if ($id != null) {
			$params['id'] = $id;
		}
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_BILL . ':' . $id), $text);
	}

}

