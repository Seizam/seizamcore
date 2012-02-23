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
        parent::__construct('ElectronicPayment');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $request = $this->getRequest();
        $output = $this->getOutput();

        switch ($request->getText('action')) {
            # Sysop can read an order from table (providing id)
            case 'read' :
                $this->setHeaders();
                $this->constructAndDisplayRead();
                break;
            # Coming back to Seizam (payment failed/cancelled)
            case 'fail' :
                $this->setHeaders();
                $output->addWikiText(wfMesage('ep-fail')->text());
                break;
            # Coming back to Seizam (payment succeeded)
            case 'success' :
                $this->setHeaders();
                $output->addWikiText(wfMessage('ep-success')->text());
                break;
            # Validation Interface (not for humans)
            case 'EPTBack' :
                $this->displayEPTBack($this->constructEPTBack($request));
                break;
            # Welcome & Init Order
            default :
                $this->setHeaders();
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
        $tmp_o_date_bank_format = $request->getText('date');
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

        return new EPMessage('in', $epm);
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
        $epm['epm_date_message'] = date("Y-m-d:H:i:s");
        $epm['epo_amount'] = $alldata['amount']; #How much?
        $epm['epo_currency'] = 'EUR'; #Of what
        $epm['epo_mail'] = $user->getEmail();
        if ($epm['epo_mail'] == '')
            $epm['epo_mail'] = $alldata['mail'];
        $epm['epm_ip'] = IP::sanitizeIP(wfGetIP());
        $epm['epo_language'] = $this->assignEPTLanguage();
        $epm['epo_status'] = 'PE';

        return new EPMessage('out', $epm);
    }

    # Display the form to be sent to the bank

    private function displayAttempt(EPMessage $epmessage) {
        $output = $this->getOutput();

        $output->addWikiText(wfMsg('ep-action', $epmessage->order->epo['epo_amount']) . $epmessage->order->epo['epo_currency']);

        # The form that is gonna send the user to the bank payment interface
        $html = '

<div id="frm">
<!-- FORMULAIRE TYPE DE PAIEMENT / PAYMENT FORM TEMPLATE -->
<form action="' . $epmessage->oTpe->sUrlPaiement . '" method="post" id="PaymentRequest">
<p>
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
	<!-- Uniquement pour le Paiement fractionné -->
	<input type="hidden" name="nbrech"              id="nbrech"         value="" />
	<input type="hidden" name="dateech1"            id="dateech1"       value="" />
	<input type="hidden" name="montantech1"         id="montantech1"    value="" />
	<input type="hidden" name="dateech2"            id="dateech2"       value="" />
	<input type="hidden" name="montantech2"         id="montantech2"    value="" />
	<input type="hidden" name="dateech3"            id="dateech3"       value="" />
	<input type="hidden" name="montantech3"         id="montantech3"    value="" />
	<input type="hidden" name="dateech4"            id="dateech4"       value="" />
	<input type="hidden" name="montantech4"         id="montantech4"    value="" />
	<!-- -->
	<input type="submit" name="bouton"              id="bouton"         value="' . wfMsg('ep-connect') . '" />
</p>
</form>
<!-- FIN FORMULAIRE TYPE DE PAIEMENT / END PAYMENT FORM TEMPLATE -->
</div>
';
        $output->addHTML($html);
    }

    # Instanciate and print Order id=?

    private function constructAndDisplayRead() {
        global $wgOut;
        $epm['epm_id'] = $wgRequest->getText('id');
        $message = new EPMessage('read', $epm);
        $wgOut->addHTML('<pre>' . print_r($message->epm, true) . '</pre>');
    }

    # Form to input order (amount, email)

    private function constructDefault() {
        $user = $this->getUser();

        # We are using the HTMLForm Helper
        # That's the way to create a form
        $formDescriptor = array(
            'amount' => array(
                'label-message' => 'ep-fd-amountlabel',
                'type' => 'float',
                'required' => 'true',
                'help-message' => 'ep-help-amount',
                'min' => 0,
                'filter-callback' => array($this, 'filterAmount')
            ),
            'mail' => array(
                'label-message' => 'youremail',
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


        $htmlForm = new HTMLFormS($formDescriptor, 'ep-fd');
        $htmlForm->setSubmitText(wfMsg('next'));
        $htmlForm->setTitle($this->getTitle());
        $htmlForm->setSubmitCallback(array($this, 'initAttempt'));

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
            return $matches['Y'] . '-' . $matches['m'] . '-' . $matches['d'] . ':' . $matches['H'] . ':' . $matches['i'] . ':' . $matches['s'];
        } else
            return "\nbankStringToMySqlTime Error\n";
    }

    # Reads $input like '1234.5678EUR' and puts 1234.5678 into epo_amount and EUR into epo_currency.

    private function ReadAmountAndCurrencyFromString($input) {
        $matches = array();
        $pattern = "/^(?P<A>[0-9\.]+)(?P<C>[A-Z]{3})$/";
        if (preg_match($pattern, $input, $matches) == 1) {
            $epm['epo_amount'] = $matches['A'];
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

    # Just an array print fonction

    private function sayIt($in) {
        $output = $this->getOutput();
        $output->addHTML('<pre>');
        $output->addHTML(print_r($in, true));
        $output->addHTML('</pre>');
    }

}