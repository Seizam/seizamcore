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
        global $wgRequest, $wgOut;

        $message = new EPMessage('in');
        // Disable the regular OutputPage stuff -- we're taking over output!
        $wgOut->disable();

        // Set the content type.
        header("Pragma: no-cache");
        header("Content-type: text/plain");

        //-----------------------------------------------------------------------------
        // Send receipt to CMCIC server
        //-----------------------------------------------------------------------------
        printf(CMCIC_CGI2_RECEIPT, $message->tmp_o_receipt);
    }

}