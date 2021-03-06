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
class SpecialElectronicPayment extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('ElectronicPayment', EP_ACCESS_RIGHT);
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $this->setHeaders();
        $request = $this->getRequest();

        if (isset($par) && $par != '') {
            $action = $par;
        } else {
            $action = $request->getText('action');
        }

        if ($action == 'EPTBack') {
            # Validation Interface (not for humans)
            $this->displayEPTBack($this->constructEPTBack($request));
            return;
        }

        $output = $this->getOutput();
        $user = $this->getUser();

        // Check rights
        if (!$this->userCanExecute($user)) {
            
            // MAINTENANCE SCRUB
            //$output->addHTML(wfMessage('sz-maintenance'));
            //return;
            
            // If anon, redirect to login
            if ($user->isAnon()) {
                $output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto' => $this->getFullTitle())), '401');
                return;
            }
            // Else display an error page.
            $this->displayRestrictionError();
            return;
        }


        switch ($action) {
            # Coming back to Seizam (payment failed/cancelled)
            case 'fail' :
                $this->constructDefault('fail');
                break;
            # Coming back to Seizam (payment succeeded)
            case 'success' :
                $output->redirect($this->getTitleFor('Transactions')->getLocalURL(array('msgkey' => 'ep-success', 'msgtype' => 'success')));
                break;
            # Welcome & Init Order
            default :
                $this->constructDefault();
                break;
        }
    }

    # Validation Interface (not for humans)

    private function constructEPTBack($request) {
        # Some params are read from free-text, free-text has to be set first.
        $epm['epm_free_text'] = $request->getText('texte-libre');

        # Msg related fields
        $epm['epo_user_id'] = $this->valueFromFreeText('user', $epm['epm_free_text']);

        # Order related fields
        $epm['epm_ept'] = $request->getText('TPE');
        $epm['epm_date_message_bank_format'] = $request->getText('date');
        $epm['epm_date_message'] = $this->bankStringToMySqlTime($epm['epm_date_message_bank_format']);
        $epm['epo_id'] = $request->getText('reference');

        # Money Issues
        $epm = array_merge($epm, $this->ReadAmountAndCurrencyFromString($request->getText('montant')));

        # User related data
        $epm['epo_mail'] = $this->valueFromFreeText('mail', $epm['epm_free_text']);
        $epm['epo_language'] = $this->valueFromFreeText('lang', $epm['epm_free_text']);
        $epm['epm_ip'] = $request->getText('ipclient');

        # Order Validation
        $epm['epm_mac'] = strtolower($request->getText('MAC'));

        # Order Confirmation
        $epm['epm_return_code'] = $request->getText('code-retour');
        $epm['epm_cvx'] = $request->getText('cvx');
        $epm['epm_vld'] = $request->getText('vld');
        $epm['epm_brand'] = $request->getText('brand');
        $epm['epm_status3ds'] = $request->getText('status3ds');
        $epm['epm_numauto'] = $request->getText('numauto');
        $epm['epm_whyrefused'] = $request->getText('motifrefus');
        $epm['epm_originecb'] = $request->getText('originecb');
        $epm['epm_bincb'] = $request->getText('bincb');
        $epm['epm_hpancb'] = $request->getText('hpancb');
        $epm['epm_originetr'] = $request->getText('originetr');
        $epm['epm_veres'] = $request->getText('veres');
        $epm['epm_pares'] = $request->getText('pares');
        $epm['epm_filtercause'] = $request->getText('filtragecause');
        $epm['epm_filtervalue'] = $request->getText('filtragevaleur');

        return EPMessage::create('in', $epm);
    }

    private function displayEPTBack(EPMessage $epmessage) {
        $output = $this->getOutput();
        $output->disable();
        header("Pragma: no-cache");
        header("Content-type: text/plain");
        printf(CMCIC_CGI2_RECEIPT, $epmessage->epm_receipt);
    }

    # Construct the data to be sent to the bank

    private function constructAttempt($alldata) {
        $user = $this->getUser();

        $epm['epo_user_id'] = $user->getId();
        $epm['epm_date_message'] = date("Y-m-d H:i:s");
        $epm['epo_amount'] = $epm['epo_amount_bank_format'] = number_format($alldata['amount'], 2, '.', ''); #How much?
        $epm['epo_currency'] = 'EUR'; #Of what
        $epm['epo_mail'] = $user->getEmail();
        if ($epm['epo_mail'] == '')
            $epm['epo_mail'] = $alldata['mail'];
        $epm['epm_ip'] = IP::sanitizeIP(wfGetIP());
        $epm['epo_language'] = $this->assignEPTLanguage();
        $epm['epo_status'] = 'PE';

        return EPMessage::create('out', $epm);
    }

    # Display the form to be sent to the bank

    private function displayAttempt(EPMessage $epmessage) {
        $output = $this->getOutput();
        $lang = $this->getLanguage();
        # The form that is gonna send the user to the bank payment interface
        $html = '<form  class="visualClear" action="' . $epmessage->oTpe->sUrlPaiement . '" method="post" id="PaymentRequest">
                    <div class="form_header informations">' . wfMessage('ep-attempt-formheader', $lang->formatNum($epmessage->order->epo['epo_amount']) )->parse() . '</div>
                    <div class="edit_col_1">
                        <fieldset>
                            <legend>' . wfMessage('ep-section2')->text() . '</legend>
                            <div id="mw-htmlform-you" class="content_block">
                                <p class="mw-htmlform-field-HTMLFloatField ">
                                    <label for="mw-input-wpamount">' . wfMessage('ep-cd-amountlabel')->text() . '</label>
                                    <input id="mw-input-wpamount" class="disabled" disabled="" value="' . $lang->formatNum($epmessage->order->epo['epo_amount']) . '" size="27" name="wpamount">
                                    <span class="sread help htmlform-tip">' . wfMessage('ep-help-amount')->text() . '</span>
                                </p>
                                <p class="mw-htmlform-field-HTMLTextField ">
                                    <label for="mw-input-wpemail">' . wfMessage('youremail')->text() . '</label>
                                    <input id="mw-input-wpemail" class="disabled" disabled="" value="' . $epmessage->order->epo['epo_mail'] . '" size="27" name="wpemail">
                                    <span class="sread help htmlform-tip">' . wfMessage('ep-help-mail')->text() . '</span>
                                </p>
                            </div>
                        </fieldset>
                        <input type="hidden" name="version"             id="version"        value="' . $epmessage->oTpe->sVersion . '" />
                        <input type="hidden" name="TPE"                 id="TPE"            value="' . $epmessage->oTpe->sNumero . '" />
                        <input type="hidden" name="date"                id="date"           value="' . $epmessage->epm_date_message_bank_format . '" />
                        <input type="hidden" name="montant"             id="montant"        value="' . $epmessage->order->epo['epo_amount'] . $epmessage->order->epo['epo_currency'] . '" />
                        <input type="hidden" name="reference"           id="reference"      value="' . $epmessage->epm['epm_epo_id'] . '" />
                        <input type="hidden" name="MAC"                 id="MAC"            value="' . $epmessage->epm['epm_mac'] . '" />
                        <input type="hidden" name="url_retour"          id="url_retour"     value="' . $epmessage->oTpe->sUrlKO . '" />
                        <input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="' . $epmessage->oTpe->sUrlOK . '" />
                        <input type="hidden" name="url_retour_err"      id="url_retour_err" value="' . $epmessage->oTpe->sUrlKO . '" />
                        <input type="hidden" name="lgue"                id="lgue"           value="' . $epmessage->oTpe->sLangue . '" />
                        <input type="hidden" name="societe"             id="societe"        value="' . $epmessage->oTpe->sCodeSociete . '" />
                        <input type="hidden" name="texte-libre"         id="texte-libre"    value="' . HtmlEncode($epmessage->epm['epm_free_text']) . '" />
                        <input type="hidden" name="mail"                id="mail"           value="' . $epmessage->order->epo['epo_mail'] . '" />
                        <p class="submit">
                            <input type="submit" name="bouton" class="mw-htmlform-submit" id="bouton"  value="' . wfMsg('ep-connect') . '" />
                        </p>
                    </div>
                    <div class="edit_col_2">
                        <div id="help_zone" class="content_block">
                            <h4>' . wfMessage('sz-htmlform-helpzonetitle')->text() . '</h4>
                            <p>' . wfMessage('sz-htmlform-helpzonedefault')->parse() . '</p>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="form_footer informations">' . wfMessage('ep-attempt-formfooter')->parse() . '</div>
                </form>';
        $output->addHTML($html);
    }

    # Form to input order (amount, email)

    private function constructDefault($errorKey = null) {
        $user = $this->getUser();
        $output = $this->getOutput();

        $balance = 0;
        if ($user->isLoggedIn()) {
            $balance = TMRecord::getTrueBalanceFromDB($user->getId());
        }

        # Set Minimum payment value (regarding banking fees)
        $min = max(-$balance, 5);

        $defaultAmount = '';

        # Building the pending transaction table and sum
        if ($balance < 0) {
            $defaultAmount = $min;
            $table = new TransactionsTablePager();
            $table->setSelectFields(array('tmr_desc', 'tmr_date_created', 'tmr_amount', 'tmr_currency'));
            $table->setSelectConds(array('tmr_user_id' => $user->getId(), 'tmr_status' => 'PE', 'tmr_amount < 0', 'tmr_currency' => 'EUR'));
            $table->setFieldSortable(false);
            $tableHtml = $table->getBody();
        }

        # We check if the user has some pending transaction to pay
        # We are using the HTMLFormS Helper
        # That's the way to create a form
        $formDescriptor = array(
            'amount' => array(
                'label-message' => 'ep-cd-amountlabel',
                'section' => 'section1',
                'type' => 'float',
                'required' => 'true',
                'help-message' => array('ep-help-amount', $min),
                'min' => $min,
                'max' => 9000000,
                'default' => $defaultAmount,
                'filter-callback' => array($this, 'filterAmount')
            ),
            'mail' => array(
                'label-message' => 'youremail',
                'section' => 'section1',
                'type' => 'email',
                'required' => 'true',
                'validation-callback' => array($this, 'validateEmail'),
                'help-message' => 'ep-help-mail'
            )
        );

        # If user has an email registered, don't let him change it
        if (!(($mail = $user->getEmail()) == '')) {
            $formDescriptor['mail']['default'] = $user->getEmail();
            #@FIXME: If form disabled
            $formDescriptor['mail']['disabled'] = true;
        }


        $htmlForm = new HTMLFormS($formDescriptor, 'ep-cd');
        $htmlForm->setSubmitText(wfMsg('next'));
        $htmlForm->setTitle($this->getTitle());
        $htmlForm->setSubmitCallback(array($this, 'initAttempt'));
        if ($balance < 0) {
            $htmlForm->addHeaderText(wfMessage('ep-default-formheader') . ' ' . wfMessage('ep-default-formheader-pending', $this->getLanguage()->formatNum(-$balance), 'cur-euro') . $tableHtml);
            $htmlForm->addFooterText(wfMessage('ep-default-formfooter-pending'));
        } else {
            $htmlForm->addHeaderText(wfMessage('ep-default-formheader'));
        }

        if (isset($errorKey))
            $output->addHTML($htmlForm->getErrors(wfMessage("ep-$errorKey")));

        $htmlForm->show();
    }

    # Called after constructDefault's form has been validated

    public function initAttempt($alldata) {
        $this->displayAttempt($this->constructAttempt($alldata));
        return Status::newGood();
    }

    # Validate Email String provided in constructDefault

    public function validateEmail($email, $alldata) {
        if ($email && !Sanitizer::validateEmail($email)) {
            return wfMsgExt('invalidemailaddress', 'parseinline');
        } else if ($email == '') {
            return wfMsgExt('htmlform-required', 'parseinline');
        }
        return true;
    }

    # Filter Amount field ( ","->".") provided in constructDefault

    public function filterAmount($amount, $alldata) {
        return preg_replace('/,/', '.', $amount);
    }

    # Pick a language for the external payment interface (FR EN DE IT ES NL PT SV availabe) (EN default)

    private function assignEPTLanguage() {
        if ($this->getLang()->getCode() == 'fr')
            return 'FR';
        else
            return 'EN';
    }

    # Takes $time like 'dd/mm/YYYY_a_HH:ii:ss' and outputs 'YYYY-mm-dd:HH:ii:ss'.

    private function bankStringToMySqlTime($time) {
        $matches = array();
        $pattern = "/^(?P<d>[0-9]{2})\/(?P<m>[0-9]{2})\/(?P<Y>[0-9]{4})_a_(?P<H>[0-9]{2}):(?P<i>[0-9]{2}):(?P<s>[0-9]{2})$/";
        if (preg_match($pattern, $time, $matches) == 1) {
            return $matches['Y'] . '-' . $matches['m'] . '-' . $matches['d'] . ' ' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nbankStringToMySqlTime Error\n";
    }

    # Reads $input like '1234.5678EUR' and puts 1234.5678 into epo_amount and EUR into epo_currency.

    private function ReadAmountAndCurrencyFromString($input) {
        $matches = array();
        $pattern = "/^(?P<A>[0-9\.]+)(?P<C>[A-Z]{3})$/";
        if (preg_match($pattern, $input, $matches) == 1) {
            $epm['epo_amount_bank_format'] = $matches['A'];
            $epm['epo_amount'] = number_format($matches['A'], 2, '.', '');
            $epm['epo_currency'] = $matches['C'];
            return $epm;
        } else
            return false;
    }

    # Extract 'value' corresponding to $key in $string.
    # Ex: $string = '(user: <1> ip: <127.0.0.1> mail: <contact@seizam.com>  lang: <EU>)'
    # valueFromFreeText('ip') returns 127.0.0.1

    private function valueFromFreeText($key, $string) {
        $matches = array();
        $pattern = "/" . $key . ": <([\d\D]*?)>/";
        if (preg_match($pattern, $string, $matches) == 1) {
            return $matches[1];
        } else
            return "null";
    }

    /* An attempt to put the form pointing to CIC bank in the HTMLForm Helper
     * 
      private function displayAttempt2(EPMessage $epmessage) {
      $attemptFormDescriptor = array(
      'version' => array(
      'type' => 'hidden',
      'name' => 'version',
      'default' => $epmessage->oTpe->sVersion
      ),
      'TPE' => array(
      'type' => 'hidden',
      'name' => 'TPE',
      'default' => $epmessage->oTpe->sNumero
      ),
      'date' => array(
      'type' => 'hidden',
      'name' => 'date',
      'default' => $epmessage->epm_date_message_bank_format
      ),
      'montant' => array(
      'type' => 'hidden',
      'name' => 'montant',
      'default' => $epmessage->order->epo['epo_amount'] . $epmessage->order->epo['epo_currency']
      ),
      'reference' => array(
      'type' => 'hidden',
      'name' => 'reference',
      'default' => $epmessage->epm['epm_epo_id']
      ),
      'MAC' => array(
      'type' => 'hidden',
      'name' => 'MAC',
      'default' => $epmessage->epm['epm_mac']
      ),
      'url_retour' => array(
      'type' => 'hidden',
      'name' => 'url_retour',
      'default' => $epmessage->oTpe->sUrlKO
      ),
      'url_retour_ok' => array(
      'type' => 'hidden',
      'name' => 'url_retour_ok',
      'default' => $epmessage->oTpe->sUrlOK
      ),
      'url_retour_err' => array(
      'type' => 'hidden',
      'name' => 'url_retour_err',
      'default' => $epmessage->oTpe->sUrlKO
      ),
      'lgue' => array(
      'type' => 'hidden',
      'name' => 'lgue',
      'default' => $epmessage->oTpe->sLangue
      ),
      'societe' => array(
      'type' => 'hidden',
      'name' => 'societe',
      'default' => $epmessage->oTpe->sCodeSociete
      ),
      'texte-libre' => array(
      'type' => 'hidden',
      'name' => 'texte-libre',
      'default' => HtmlEncode($epmessage->epm['epm_free_text'])
      ),
      'mail' => array(
      'type' => 'hidden',
      'name' => 'mail',
      'default' => $epmessage->order->epo['epo_mail']
      ),
      );


      $htmlForm = new HTMLFormS($attemptFormDescriptor, 'ep-pr');
      $htmlForm->setSubmitText(wfMsg('next'));
      $htmlForm->setTitle($epmessage->oTpe->sUrlPaiement);
      $htmlForm->setSubmitCallback(array($this, 'initAttempt'));

      $htmlForm->show();
      } */
}

