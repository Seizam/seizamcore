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
        global $wgRequest, $wgOut, $wgUser;

        $this->setHeaders();
        
        $message = new EPMessage($wgRequest->getText('amount'));
        
        $wgOut->addWikiText('ref: ' . $message->epm_o_reference);
        
        $wgOut->addWikiText('ram: ' . $message->epm_o_raw_amount);
        
        $wgOut->addWikiText('cur: ' . $message->epm_o_currency);
        
        $wgOut->addWikiText('amo: ' . $message->epm_o_amount);

        $wgOut->addWikiText('tex: ' . $message->epm_o_free_text);

        // transaction date : format d/m/y:h:m:s
        //$sDate = date("d/m/Y:H:i:s");
        $wgOut->addWikiText('dat: ' . $message->epm_o_date);
        
        $wgOut->addWikiText('lan: ' . $message->epm_o_language);
        
        $wgOut->addWikiText('mai: ' . $message->epm_o_mail);

        // Control String for support
        $wgOut->addWikiText('ctl: ' . $message->CtlHmac);

        // Data to certify
        $wgOut->addWikiText('dat: ' . $message->PHP1_FIELDS);
        
        $wgOut->addWikiText('sMAC: ' . $message->epm_o_mac);

        $output = '<h1>Connexion au serveur de paiement / <span class="anglais">Connection to the payment server</span></h1>
<div id="presentation">
	<p>
	Cette page g&eacute;n&egrave;re le formulaire de paiement avec des donn&eacute;es arbitraires.<br />
	<span class="anglais">This page generates the payment form with some arbitrary data.</span>
	</p>
</div>

<div id="frm">
<p>
    	Cliquez sur le bouton ci-dessous pour vous connecter au serveur de paiement.<br />
	<span class="anglais">Click on the following button to be redirected to the payment server.</span>
</p>
<!-- FORMULAIRE TYPE DE PAIEMENT / PAYMENT FORM TEMPLATE -->
<form action="'.$message->oTpe->sUrlPaiement.'" method="post" id="PaymentRequest">
<p>
	<input type="hidden" name="version"             id="version"        value="'.$message->oTpe->sVersion.'" />
	<input type="hidden" name="TPE"                 id="TPE"            value="'.$message->oTpe->sNumero.'" />
	<input type="hidden" name="date"                id="date"           value="'.$message->epm_o_date.'" />
	<input type="hidden" name="montant"             id="montant"        value="'.$message->epm_o_amount.'" />
	<input type="hidden" name="reference"           id="reference"      value="'.$message->epm_o_reference.'" />
	<input type="hidden" name="MAC"                 id="MAC"            value="'.$message->epm_o_mac.'" />
	<input type="hidden" name="url_retour"          id="url_retour"     value="'.$message->oTpe->sUrlKO.'" />
	<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="'.$message->oTpe->sUrlOK.'" />
	<input type="hidden" name="url_retour_err"      id="url_retour_err" value="'.$message->oTpe->sUrlKO.'" />
	<input type="hidden" name="lgue"                id="lgue"           value="'.$message->oTpe->sLangue.'" />
	<input type="hidden" name="societe"             id="societe"        value="'.$message->oTpe->sCodeSociete.'" />
	<input type="hidden" name="texte-libre"         id="texte-libre"    value="'.HtmlEncode($message->epm_o_free_text).'" />
	<input type="hidden" name="mail"                id="mail"           value="'.$message->epm_o_mail.'" />
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
	<input type="submit" name="bouton"              id="bouton"         value="Connexion / Connection" />
</p>
</form>
<!-- FIN FORMULAIRE TYPE DE PAIEMENT / END PAYMENT FORM TEMPLATE -->
</div>
<div>
	<p>
	Cha&icirc;ne de contr&ocirc;le &agrave; fournir au support en cas de probl&egrave;mes <br />
	<span class="anglais">Control string needed by support in case of problems</span>
	</p>
	<pre>'.$message->CtlHmac.'</pre>
	<p>
	Cha&icirc;ne utilis&eacute;e pour le calcul du sceau HMAC <br />
	Num&eacute;ro de TPE*date*montant*r&eacute;f&eacute;rence*texte libre*version*code langue*code soci&eacute;t&eacute;*email*nombre &eacute;ch&eacute;ance*date &eacute;ch&eacute;ance1*montant &eacute;ch&eacute;ance1*date &eacute;ch&eacute;ance2*montant &eacute;ch&eacute;ance2*date &eacute;ch&eacute;ance3*montant &eacute;ch&eacute;ance3*date &eacute;ch&eacute;ance4*montant &eacute;ch&eacute;ance4*options<br />
	<span class="anglais">String used to generate the HMAC<br />
	TPE number*date*amount*reference*free text*version*language code*company code*e-mail*nombre echéance*date échéance1*montant échéance1*date échéance2*montant échéance2*date échéance3*montant échéance3*date échéance4*montant échéance4*options</span>
	</p>
	<pre>'.$message->PHP1_FIELDS.'</pre>
</div>
<div>
        <p>
	Cette page est fournie comme un exemple d\'impl&eacute;mentation de CM-CIC p@iement.<br />
	Elle n\'a pas pour but de r&eacute;pondre &agrave; toutes les configurations existantes. &copy; 2009 Euro Informations.<br />
	<span class="anglais">This page is just an example of the use of CM-CIC p@aiement.<br />
	Its main purpose is not to give an answer to every existing configurations. &copy; 2009 Euro Informations</span>
	</p>
</div>';



        $wgOut->addHTML($output);
    }

}