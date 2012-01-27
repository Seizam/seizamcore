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

        $this->setHeaders();
        
        

        //echo 'lang: '.$wgLang->getCode();
        //$this->sayIt($wgOut);
        //$this->sayIt($wgRequest);
        //global $wgUser;
        //$this->sayIt($wgUser);

        if ($wgRequest->getText('status') == 'fail') {
            $wgOut->addWikiText(wfMsg('ep-fail'));
        } else if ($wgRequest->getText('status') == 'success') {
            $wgOut->addWikiText(wfMsg('ep-success'));
        } else if ($wgRequest->getText('status') == 'attempt') {

            $message = new EPMessage('out');


            //$this->sayIt($message->epm);
            //$wgOut->addWikiText('ref: ' . $message->epm_o_reference);
            //$wgOut->addWikiText('ram: ' . $message->epm_o_raw_amount);
            //$wgOut->addWikiText('cur: ' . $message->epm_o_currency);

            $wgOut->addWikiText(wfMsg('ep-action', $message->epm['epm_o_amount']) . $message->epm['epm_o_currency']);

            //$wgOut->addWikiText('tex: ' . $message->epm_o_free_text);
            // transaction date : format d/m/y:h:m:s
            //$sDate = date("d/m/Y:H:i:s");
            //$wgOut->addWikiText('dat: ' . $message->epm_o_date);
            //$wgOut->addWikiText('lan: ' . $message->epm_o_language);
            //$wgOut->addWikiText('mai: ' . $message->epm_o_mail);
            // Control String for support
            //$wgOut->addWikiText('ctl: ' . $message->CtlHmac);
            // Data to certify
            //$wgOut->addWikiText('dat: ' . $message->epm_o_validating_fields);
            //$wgOut->addWikiText('sMAC: ' . $message->epm['epm_o_mac']);

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
        } else if ($wgRequest->getText('status') == 'read') {
            $message = new EPMessage('read');
            $wgOut->addHTML('<pre>' . print_r($message->epm, true) . '</pre>');
        } else {
            $wgOut->addHTML('<a href="/index.php?title=Special:ElectronicPayment&status=attempt&amount=46">Click Here</a>');
        }
    }

    function sayIt($in) {
        global $wgOut;
        printf('<pre>');
        printf(print_r($in, true));
        printf('</pre>');
    }

}