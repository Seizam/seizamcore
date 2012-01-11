<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Implements Special:EPTBack
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
class SpecialEPTBack extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('EPTBack');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        global $wgRequest, $wgOut, $wgUser;

        // Disable the regular OutputPage stuff -- we're taking over output!
        $wgOut->disable();

        // Set the content type.
        header("Pragma: no-cache");
        header("Content-type: text/plain");

        # Get request data from, e.g.
        $param = $wgRequest->getText('param');

        // Begin Main : Retrieve Variables posted by CMCIC Payment Server 
        $CMCIC_bruteVars = getMethode();
        
        print_r($CMCIC_bruteVars);
        print_r($wgRequest);

// TPE init variables
        $oTpe = new CMCIC_Tpe();
        $oHmac = new CMCIC_Hmac($oTpe);

// Message Authentication
        $cgi2_fields = sprintf(CMCIC_CGI2_FIELDS, $oTpe->sNumero, $CMCIC_bruteVars["date"], $CMCIC_bruteVars['montant'], $CMCIC_bruteVars['reference'], $CMCIC_bruteVars['texte-libre'], $oTpe->sVersion, $CMCIC_bruteVars['code-retour'], $CMCIC_bruteVars['cvx'], $CMCIC_bruteVars['vld'], $CMCIC_bruteVars['brand'], $CMCIC_bruteVars['status3ds'], $CMCIC_bruteVars['numauto'], $CMCIC_bruteVars['motifrefus'], $CMCIC_bruteVars['originecb'], $CMCIC_bruteVars['bincb'], $CMCIC_bruteVars['hpancb'], $CMCIC_bruteVars['ipclient'], $CMCIC_bruteVars['originetr'], $CMCIC_bruteVars['veres'], $CMCIC_bruteVars['pares']
        );


        if ($oHmac->computeHmac($cgi2_fields) == strtolower($CMCIC_bruteVars['MAC'])) {
            switch ($CMCIC_bruteVars['code-retour']) {
                case "Annulation" :
                    // Payment has been refused
                    // put your code here (email sending / Database update)
                    // Attention : an autorization may still be delivered for this payment
                    break;

                case "payetest":
                    // Payment has been accepeted on the test server
                    // put your code here (email sending / Database update)
                    break;

                case "paiement":
                    // Payment has been accepted on the productive server
                    // put your code here (email sending / Database update)
                    break;


                /*                 * * ONLY FOR MULTIPART PAYMENT ** */
                case "paiement_pf2":
                case "paiement_pf3":
                case "paiement_pf4":
                    // Payment has been accepted on the productive server for the part #N
                    // return code is like paiement_pf[#N]
                    // put your code here (email sending / Database update)
                    // You have the amount of the payment part in $CMCIC_bruteVars['montantech']
                    break;

                case "Annulation_pf2":
                case "Annulation_pf3":
                case "Annulation_pf4":
                    // Payment has been refused on the productive server for the part #N
                    // return code is like Annulation_pf[#N]
                    // put your code here (email sending / Database update)
                    // You have the amount of the payment part in $CMCIC_bruteVars['montantech']
                    break;
            }

            $receipt = CMCIC_CGI2_MACOK;
        } else {
            // your code if the HMAC doesn't match
            $receipt = CMCIC_CGI2_MACNOTOK . $cgi2_fields;
        }

//-----------------------------------------------------------------------------
// Send receipt to CMCIC server
//-----------------------------------------------------------------------------
        printf(CMCIC_CGI2_RECEIPT, $receipt);

// Copyright (c) 2009 Euro-Information ( mailto:centrecom@e-i.com )
// All rights reserved. ---
    }

}