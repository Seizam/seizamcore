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
        global $wgRequest, $wgOut;

        switch ($wgRequest->getText('action')) {
            # Sysop can read an order from table (providing id)
            case 'read' :
                $this->setHeaders();
                self::constructRead();
                break;
            # Coming back to Seizam (payment failed/cancelled)
            case 'fail' :
                $this->setHeaders();
                $wgOut->addWikiText(wfMsg('ep-fail'));
                break;
            # Coming back to Seizam (payment succeeded)
            case 'success' :
                $this->setHeaders();
                $wgOut->addWikiText(wfMsg('ep-success'));
                break;
            # Validation Interface (not for humans)
            case 'EPTBack' :
                $message = new EPMessage('in');
                $wgOut->disable();
                header("Pragma: no-cache");
                header("Content-type: text/plain");
                printf(CMCIC_CGI2_RECEIPT, $message->tmp_o_receipt);
                break;
            # Welcome & Init Order
            default :
                $this->setHeaders();
                self::constructDefault();
                break;
        }
    }

    # Construct the form to be sent to the bank

    static function constructAttempt() {
        global $wgOut;
        $message = new EPMessage('out');

        $wgOut->addWikiText(wfMsg('ep-action', $message->epm['epm_o_amount']) . $message->epm['epm_o_currency']);

        # The form that is gonna send the user to the bank payment interface
        $output = '

<div id="frm">
<!-- FORMULAIRE TYPE DE PAIEMENT / PAYMENT FORM TEMPLATE -->
<form action="' . $message->oTpe->sUrlPaiement . '" method="post" id="PaymentRequest">
<p>
	<input type="hidden" name="version"             id="version"        value="' . $message->oTpe->sVersion . '" />
	<input type="hidden" name="TPE"                 id="TPE"            value="' . $message->oTpe->sNumero . '" />
	<input type="hidden" name="date"                id="date"           value="' . $message->mySqlStringToBankTime($message->epm['epm_o_date']) . '" />
	<input type="hidden" name="montant"             id="montant"        value="' . $message->epm['epm_o_amount'] . $message->epm['epm_o_currency'] . '" />
	<input type="hidden" name="reference"           id="reference"      value="' . $message->epm['epm_o_reference'] . '" />
	<input type="hidden" name="MAC"                 id="MAC"            value="' . $message->epm['epm_o_mac'] . '" />
	<input type="hidden" name="url_retour"          id="url_retour"     value="' . $message->oTpe->sUrlKO . '" />
	<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="' . $message->oTpe->sUrlOK . '" />
	<input type="hidden" name="url_retour_err"      id="url_retour_err" value="' . $message->oTpe->sUrlKO . '" />
	<input type="hidden" name="lgue"                id="lgue"           value="' . $message->oTpe->sLangue . '" />
	<input type="hidden" name="societe"             id="societe"        value="' . $message->oTpe->sCodeSociete . '" />
	<input type="hidden" name="texte-libre"         id="texte-libre"    value="' . HtmlEncode($message->epm['epm_o_free_text']) . '" />
	<input type="hidden" name="mail"                id="mail"           value="' . $message->epm['epm_o_mail'] . '" />
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
        $wgOut->addHTML($output);
    }

    # Instanciate and print Order id=?

    static function constructRead() {
        global $wgOut;
        $message = new EPMessage('read');
        $wgOut->addHTML('<pre>' . print_r($message->epm, true) . '</pre>');
    }

    # Form to input order (amount, email)

    static function constructDefault() {
        global $wgRequest, $wgUser;

        # We are using the HTMLForm Helper
        # That's the way to create a form
        $formDescriptor = array(
            'amount' => array(
                'label-message' => 'ep-fd-amountlabel',
                'type' => 'float',
                'required' => 'true'
            ),
            'mail' => array(
                'label-message' => 'youremail',
                'type' => 'email',
                'validation-callback' => array('SpecialElectronicPayment', 'validateEmail'),
                'required' => 'true'
            )
        );

        # If user has an email registered, don't let him change it
        if (!(($mail = $wgUser->getEmail()) == '')) {
            $formDescriptor['mail']['default'] = $wgUser->getEmail();
            $formDescriptor['mail']['disabled'] = true;
        }


        $htmlForm = new HTMLForm($formDescriptor, 'ep-fd');
        $htmlForm->setSubmitText(wfMsg('next'));
        $htmlForm->setTitle(SpecialPage::getTitleFor('ElectronicPayment'));
        $htmlForm->setSubmitCallback(array('SpecialElectronicPayment', 'initOrder'));

        $htmlForm->show();
    }

    # Called after constructDefault's form has been validated

    static function initOrder() {
        global $wgRequest;
        SpecialElectronicPayment::constructAttempt();
        return true;
    }

    # Validate Email String provided in constructDefault

    static function validateEmail($email, $alldata) {
        if ($email && !Sanitizer::validateEmail($email)) {
            return wfMsgExt('invalidemailaddress', 'parseinline');
        } else if ($email == '') {
            return wfMsgExt('htmlform-required', 'parseinline');
        }
        return true;
    }

    # Just an array print fonction

    static function sayIt($in) {
        global $wgOut;
        $wgOut->addHTML('<pre>');
        $wgOut->addHTML(print_r($in, true));
        $wgOut->addHTML('</pre>');
    }

}