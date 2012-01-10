<?php
/*****************************************************************************
 *
 * "Open source" kit for CM-CIC P@iement (TM)
 *
 * File "Phase1Aller.php":
 *
 * Author   : Euro-Information/e-Commerce (contact: centrecom@e-i.com)
 * Version  : 1.04
 * Date     : 01/01/2009
 *
 * Copyright: (c) 2009 Euro-Information. All rights reserved.
 * License  : see attached document "License.txt".
 *
 *****************************************************************************/

// TPE Settings
// Warning !! CMCIC_Config contains the key, you have to protect this file with all the mechanism available in your development environment.
// You may for instance put this file in another directory and/or change its name. If so, don't forget to adapt the include path below.
require_once("CMCIC_Config.php");

// PHP implementation of RFC2104 hmac sha1 ---
require_once("CMCIC_Tpe.inc.php");

$sOptions = "";

// ----------------------------------------------------------------------------
//  CheckOut Stub setting fictious Merchant and Order datas.
//  That's your job to set actual order fields. Here is a stub.
// -----------------------------------------------------------------------------

// Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max
$sReference = "ref" . date("His");

// Amount : format  "xxxxx.yy" (no spaces)
$sMontant = 1.01;

// Currency : ISO 4217 compliant
$sDevise  = "EUR";

// free texte : a bigger reference, session context for the return on the merchant website
$sTexteLibre = "Texte Libre";

// transaction date : format d/m/y:h:m:s
$sDate = date("d/m/Y:H:i:s");

// Language of the company code
$sLangue = "FR";

// customer email
$sEmail = "test@test.zz";

// ----------------------------------------------------------------------------

// between 2 and 4
//$sNbrEch = "4";
$sNbrEch = "";

// date echeance 1 - format dd/mm/yyyy
//$sDateEcheance1 = date("d/m/Y");
$sDateEcheance1 = "";

// montant échéance 1 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance1 = "0.26" . $sDevise;
$sMontantEcheance1 = "";

// date echeance 2 - format dd/mm/yyyy
$sDateEcheance2 = "";

// montant échéance 2 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance2 = "0.25" . $sDevise;
$sMontantEcheance2 = "";

// date echeance 3 - format dd/mm/yyyy
$sDateEcheance3 = "";

// montant échéance 3 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance3 = "0.25" . $sDevise;
$sMontantEcheance3 = "";

// date echeance 4 - format dd/mm/yyyy
$sDateEcheance4 = "";

// montant échéance 4 - format  "xxxxx.yy" (no spaces)
//$sMontantEcheance4 = "0.25" . $sDevise;
$sMontantEcheance4 = "";

// ----------------------------------------------------------------------------

$oTpe = new CMCIC_Tpe($sLangue);     		
$oHmac = new CMCIC_Hmac($oTpe);      	        

// Control String for support
$CtlHmac = sprintf(CMCIC_CTLHMAC, $oTpe->sVersion, $oTpe->sNumero, $oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $oTpe->sVersion, $oTpe->sNumero)));

// Data to certify
$PHP1_FIELDS = sprintf(CMCIC_CGI1_FIELDS,     $oTpe->sNumero,
                                              $sDate,
                                              $sMontant,
                                              $sDevise,
                                              $sReference,
                                              $sTexteLibre,
                                              $oTpe->sVersion,
                                              $oTpe->sLangue,
                                              $oTpe->sCodeSociete, 
                                              $sEmail,
                                              $sNbrEch,
                                              $sDateEcheance1,
                                              $sMontantEcheance1,
                                              $sDateEcheance2,
                                              $sMontantEcheance2,
                                              $sDateEcheance3,
                                              $sMontantEcheance3,
                                              $sDateEcheance4,
                                              $sMontantEcheance4,
                                              $sOptions);

// MAC computation
$sMAC = $oHmac->computeHmac($PHP1_FIELDS);

// --------------------------------------------------- End Stub ---------------


// ----------------------------------------------------------------------------
// Your Page displaying payment button to be customized  
// ----------------------------------------------------------------------------
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
<meta http-equiv="cache-control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<title>Connexion au serveur de paiement</title>
<link type="text/css" rel="stylesheet" href="CMCIC.css" />
</head>

<body>
<div id="header">
        <a href="http://www.cmcicpaiement.fr"><img src="logocmcicpaiement.gif" alt="CM-CIC P@iement" title="CM-CIC P@iement" /></a>
</div>
<h1>Connexion au serveur de paiement / <span class="anglais">Connection to the payment server</span></h1>
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
<form action="<?php echo $oTpe->sUrlPaiement;?>" method="post" id="PaymentRequest">
<p>
	<input type="hidden" name="version"             id="version"        value="<?php echo $oTpe->sVersion;?>" />
	<input type="hidden" name="TPE"                 id="TPE"            value="<?php echo $oTpe->sNumero;?>" />
	<input type="hidden" name="date"                id="date"           value="<?php echo $sDate;?>" />
	<input type="hidden" name="montant"             id="montant"        value="<?php echo $sMontant . $sDevise;?>" />
	<input type="hidden" name="reference"           id="reference"      value="<?php echo $sReference;?>" />
	<input type="hidden" name="MAC"                 id="MAC"            value="<?php echo $sMAC;?>" />
	<input type="hidden" name="url_retour"          id="url_retour"     value="<?php echo $oTpe->sUrlKO;?>" />
	<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="<?php echo $oTpe->sUrlOK;?>" />
	<input type="hidden" name="url_retour_err"      id="url_retour_err" value="<?php echo $oTpe->sUrlKO;?>" />
	<input type="hidden" name="lgue"                id="lgue"           value="<?php echo $oTpe->sLangue;?>" />
	<input type="hidden" name="societe"             id="societe"        value="<?php echo $oTpe->sCodeSociete;?>" />
	<input type="hidden" name="texte-libre"         id="texte-libre"    value="<?php echo HtmlEncode($sTexteLibre);?>" />
	<input type="hidden" name="mail"                id="mail"           value="<?php echo $sEmail;?>" />
	<!-- Uniquement pour le Paiement fractionné -->
	<input type="hidden" name="nbrech"              id="nbrech"         value="<?php echo $sNbrEch;?>" />
	<input type="hidden" name="dateech1"            id="dateech1"       value="<?php echo $sDateEcheance1;?>" />
	<input type="hidden" name="montantech1"         id="montantech1"    value="<?php echo $sMontantEcheance1;?>" />
	<input type="hidden" name="dateech2"            id="dateech2"       value="<?php echo $sDateEcheance2;?>" />
	<input type="hidden" name="montantech2"         id="montantech2"    value="<?php echo $sMontantEcheance2;?>" />
	<input type="hidden" name="dateech3"            id="dateech3"       value="<?php echo $sDateEcheance3;?>" />
	<input type="hidden" name="montantech3"         id="montantech3"    value="<?php echo $sMontantEcheance3;?>" />
	<input type="hidden" name="dateech4"            id="dateech4"       value="<?php echo $sDateEcheance4;?>" />
	<input type="hidden" name="montantech4"         id="montantech4"    value="<?php echo $sMontantEcheance4;?>" />
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
&lt;form <span class="name">action</span>="<span class="value"><?php echo $oTpe->sUrlPaiement;?>"</span> method="post" id="PaymentRequest"&gt;
&lt;input type="hidden" name="<span class="name">version</span>"          value="<span class="value"><?php echo $oTpe->sVersion;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">TPE</span>"              value="<span class="value"><?php echo $oTpe->sNumero;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">date</span>"             value="<span class="value"><?php echo $sDate;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montant</span>"          value="<span class="value"><?php echo $sMontant . $sDevise;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">reference</span>"        value="<span class="value"><?php echo $sReference;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">MAC</span>"              value="<span class="value"><?php echo $sMAC;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">url_retour</span>"       value="<span class="value"><?php echo $oTpe->sUrlKO;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">url_retour_ok</span>"    value="<span class="value"><?php echo $oTpe->sUrlOK;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">url_retour_err</span>"   value="<span class="value"><?php echo $oTpe->sUrlKO;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">lgue</span>"             value="<span class="value"><?php echo $oTpe->sLangue;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">societe</span>"          value="<span class="value"><?php echo $oTpe->sCodeSociete;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">texte-libre</span>"      value="<span class="value"><?php echo HtmlEncode($sTexteLibre);?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">mail</span>"             value="<span class="value"><?php echo $sEmail;?></span>" /&gt;
&lt;!-- Uniquement pour le Paiement fractionn&eacute; --&gt;
&lt;input type="hidden" name="<span class="name">nbrech</span>"           value="<span class="value"><?php echo $sNbrEch;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech1</span>"         value="<span class="value"><?php echo $sDateEcheance1;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech1</span>"      value="<span class="value"><?php echo $sMontantEcheance1;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech2</span>"         value="<span class="value"><?php echo $sDateEcheance2;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech2</span>"      value="<span class="value"><?php echo $sMontantEcheance2;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech3</span>"         value="<span class="value"><?php echo $sDateEcheance3;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech3</span>"      value="<span class="value"><?php echo $sMontantEcheance3;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">dateech4</span>"         value="<span class="value"><?php echo $sDateEcheance4;?></span>" /&gt;
&lt;input type="hidden" name="<span class="name">montantech4</span>"      value="<span class="value"><?php echo $sMontantEcheance4;?></span>" /&gt;
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
	<pre><?php echo $CtlHmac;?></pre>
	<p>
	Cha&icirc;ne utilis&eacute;e pour le calcul du sceau HMAC <br />
	Num&eacute;ro de TPE*date*montant*r&eacute;f&eacute;rence*texte libre*version*code langue*code soci&eacute;t&eacute;*email*nombre &eacute;ch&eacute;ance*date &eacute;ch&eacute;ance1*montant &eacute;ch&eacute;ance1*date &eacute;ch&eacute;ance2*montant &eacute;ch&eacute;ance2*date &eacute;ch&eacute;ance3*montant &eacute;ch&eacute;ance3*date &eacute;ch&eacute;ance4*montant &eacute;ch&eacute;ance4*options<br />
	<span class="anglais">String used to generate the HMAC<br />
	TPE number*date*amount*reference*free text*version*language code*company code*e-mail*nombre echéance*date échéance1*montant échéance1*date échéance2*montant échéance2*date échéance3*montant échéance3*date échéance4*montant échéance4*options</span>
	</p>
	<pre><?php echo $PHP1_FIELDS;?></pre>
</div>
<div id="footer">
        <p>
	Cette page est fournie comme un exemple d'impl&eacute;mentation de CM-CIC p@iement.<br />
	Elle n'a pas pour but de r&eacute;pondre &agrave; toutes les configurations existantes. &copy; 2009 Euro Informations.<br />
	<span class="anglais">This page is just an example of the use of CM-CIC p@aiement.<br />
	Its main purpose is not to give an answer to every existing configurations. &copy; 2009 Euro Informations</span>
	</p>
</div>
</body>
</html>
