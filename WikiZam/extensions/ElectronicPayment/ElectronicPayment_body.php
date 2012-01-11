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

        # Get request data from, e.g.
        $param = $wgRequest->getText('param');

        // Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max
        $sReference = "ref" . date("His");
        $wgOut->addWikiText('ref: ' . $sReference);

        // Amount : format  "xxxxx.yy" (no spaces)
        if (($sMontant = $wgRequest->getText('amount'))<=0) {
            if (($sMontant = $par)<=0) {
                $sMontant=7.77;
            }
        }
        
        $wgOut->addWikiText('amo: ' . $sMontant);

        // Currency : ISO 4217 compliant
        $sDevise = "EUR";
        $wgOut->addWikiText('cur: ' . $sDevise);

        // free texte : a bigger reference, session context for the return on the merchant website
        $sTexteLibre = "ElectronicPayment Test";
        $wgOut->addWikiText('tex: ' . $sTexteLibre);

        // transaction date : format d/m/y:h:m:s
        $sDate = date("d/m/Y:H:i:s");
        $wgOut->addWikiText('dat: ' . $sDate);

        // Language of the company code
        $sLangue = "EN";
        $wgOut->addWikiText('lan: ' . $sLangue);

        // customer email
        $sEmail = $wgUser->getEmail();
        $wgOut->addWikiText('mai: ' . $sEmail);

        $oTpe = new CMCIC_Tpe($sLangue);
        $oHmac = new CMCIC_Hmac($oTpe);

        // Control String for support
        $CtlHmac = sprintf(CMCIC_CTLHMAC, $oTpe->sVersion, $oTpe->sNumero, $oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $oTpe->sVersion, $oTpe->sNumero)));
        $wgOut->addWikiText('ctl: ' . $CtlHmac);

        // Empty fields for kit compatibility
        $sNbrEch = "";
        $sDateEcheance1 = "";
        $sMontantEcheance1 = "";
        $sDateEcheance2 = "";
        $sMontantEcheance2 = "";
        $sDateEcheance3 = "";
        $sMontantEcheance3 = "";
        $sDateEcheance4 = "";
        $sMontantEcheance4 = "";
        $sOptions = "";


        // Data to certify
        $PHP1_FIELDS = sprintf(CMCIC_CGI1_FIELDS, $oTpe->sNumero, $sDate, $sMontant, $sDevise, $sReference, $sTexteLibre, $oTpe->sVersion, $oTpe->sLangue, $oTpe->sCodeSociete, $sEmail, $sNbrEch, $sDateEcheance1, $sMontantEcheance1, $sDateEcheance2, $sMontantEcheance2, $sDateEcheance3, $sMontantEcheance3, $sDateEcheance4, $sMontantEcheance4, $sOptions);
        $wgOut->addWikiText('dat: ' . $PHP1_FIELDS);

        $sMAC = $oHmac->computeHmac($PHP1_FIELDS);
        $wgOut->addWikiText('sMAC: ' . $sMAC);

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
<form action="'.$oTpe->sUrlPaiement.'" method="post" id="PaymentRequest">
<p>
	<input type="hidden" name="version"             id="version"        value="'.$oTpe->sVersion.'" />
	<input type="hidden" name="TPE"                 id="TPE"            value="'.$oTpe->sNumero.'" />
	<input type="hidden" name="date"                id="date"           value="'.$sDate.'" />
	<input type="hidden" name="montant"             id="montant"        value="'.$sMontant . $sDevise.'" />
	<input type="hidden" name="reference"           id="reference"      value="'.$sReference.'" />
	<input type="hidden" name="MAC"                 id="MAC"            value="'.$sMAC.'" />
	<input type="hidden" name="url_retour"          id="url_retour"     value="'.$oTpe->sUrlKO.'" />
	<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="'.$oTpe->sUrlOK.'" />
	<input type="hidden" name="url_retour_err"      id="url_retour_err" value="'.$oTpe->sUrlKO.'" />
	<input type="hidden" name="lgue"                id="lgue"           value="'.$oTpe->sLangue.'" />
	<input type="hidden" name="societe"             id="societe"        value="'.$oTpe->sCodeSociete.'" />
	<input type="hidden" name="texte-libre"         id="texte-libre"    value="'.HtmlEncode($sTexteLibre).'" />
	<input type="hidden" name="mail"                id="mail"           value="'.$sEmail.'" />
	<!-- Uniquement pour le Paiement fractionné -->
	<input type="hidden" name="nbrech"              id="nbrech"         value="'.$sNbrEch.'" />
	<input type="hidden" name="dateech1"            id="dateech1"       value="'.$sDateEcheance1.'" />
	<input type="hidden" name="montantech1"         id="montantech1"    value="'.$sMontantEcheance1.'" />
	<input type="hidden" name="dateech2"            id="dateech2"       value="'.$sDateEcheance2.'" />
	<input type="hidden" name="montantech2"         id="montantech2"    value="'.$sMontantEcheance2.'" />
	<input type="hidden" name="dateech3"            id="dateech3"       value="'.$sDateEcheance3.'" />
	<input type="hidden" name="montantech3"         id="montantech3"    value="'.$sMontantEcheance3.'" />
	<input type="hidden" name="dateech4"            id="dateech4"       value="'.$sDateEcheance4.'" />
	<input type="hidden" name="montantech4"         id="montantech4"    value="'.$sMontantEcheance4.'" />
	<!-- -->
	<input type="submit" name="bouton"              id="bouton"         value="Connexion / Connection" />
</p>
</form>
<!-- FIN FORMULAIRE TYPE DE PAIEMENT / END PAYMENT FORM TEMPLATE -->
</div>
<div id="source">
	<h2>Uniquement pour le d&eacute;bogage / <span class="anglais">For debug purpose only</span></h2>
        <p>
	Code source du formulaire.  <br />
	<span class="anglais">Form source code.</span>
       </p>
<pre>
&lt;form <span class="name">action</span>="<span class="value">'.$oTpe->sUrlPaiement.'"</span> method="post" id="PaymentRequest"&gt;
&lt;input type="hidden" name="<span class="name">version</span>"          value="<span class="value">'.$oTpe->sVersion.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">TPE</span>"              value="<span class="value">'.$oTpe->sNumero.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">date</span>"             value="<span class="value">'.$sDate.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">montant</span>"          value="<span class="value">'.$sMontant . $sDevise.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">reference</span>"        value="<span class="value">'.$sReference.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">MAC</span>"              value="<span class="value">'.$sMAC.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">url_retour</span>"       value="<span class="value">'.$oTpe->sUrlKO.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">url_retour_ok</span>"    value="<span class="value">'.$oTpe->sUrlOK.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">url_retour_err</span>"   value="<span class="value">'.$oTpe->sUrlKO.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">lgue</span>"             value="<span class="value">'.$oTpe->sLangue.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">societe</span>"          value="<span class="value">'.$oTpe->sCodeSociete.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">texte-libre</span>"      value="<span class="value">'.HtmlEncode($sTexteLibre).'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">mail</span>"             value="<span class="value">'.$sEmail.'</span>" /&gt;
&lt;!-- Uniquement pour le Paiement fractionn&eacute; --&gt;
&lt;input type="hidden" name="<span class="name">nbrech</span>"           value="<span class="value">'.$sNbrEch.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech1</span>"         value="<span class="value">'.$sDateEcheance1.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech1</span>"      value="<span class="value">'.$sMontantEcheance1.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech2</span>"         value="<span class="value">'.$sDateEcheance2.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech2</span>"      value="<span class="value">'.$sMontantEcheance2.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech3</span>"         value="<span class="value">'.$sDateEcheance3.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech3</span>"      value="<span class="value">'.$sMontantEcheance3.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech4</span>"         value="<span class="value">'.$sDateEcheance4.'</span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech4</span>"      value="<span class="value">'.$sMontantEcheance4.'</span>" /&gt;
&lt;!-- --&gt;
&lt;input type="submit" name="<span class="name">bouton</span>"           value="<span class="value">Connexion / Connection</span>" /&gt;
&lt;/form&gt;
</pre>
</div>
<div>
	<p>
	Cha&icirc;ne de contr&ocirc;le &agrave; fournir au support en cas de probl&egrave;mes <br />
	<span class="anglais">Control string needed by support in case of problems</span>
	</p>
	<pre>'.$CtlHmac.'</pre>
	<p>
	Cha&icirc;ne utilis&eacute;e pour le calcul du sceau HMAC <br />
	Num&eacute;ro de TPE*date*montant*r&eacute;f&eacute;rence*texte libre*version*code langue*code soci&eacute;t&eacute;*email*nombre &eacute;ch&eacute;ance*date &eacute;ch&eacute;ance1*montant &eacute;ch&eacute;ance1*date &eacute;ch&eacute;ance2*montant &eacute;ch&eacute;ance2*date &eacute;ch&eacute;ance3*montant &eacute;ch&eacute;ance3*date &eacute;ch&eacute;ance4*montant &eacute;ch&eacute;ance4*options<br />
	<span class="anglais">String used to generate the HMAC<br />
	TPE number*date*amount*reference*free text*version*language code*company code*e-mail*nombre echéance*date échéance1*montant échéance1*date échéance2*montant échéance2*date échéance3*montant échéance3*date échéance4*montant échéance4*options</span>
	</p>
	<pre>'.$PHP1_FIELDS.'</pre>
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