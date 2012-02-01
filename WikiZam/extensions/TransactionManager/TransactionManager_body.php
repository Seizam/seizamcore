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
class SpecialTransactionManager extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('TransactionManager');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        global $wgRequest, $wgOut;

        $this->setHeaders();
        $wgOut->addWikiText(wfMsg('tm-desc'));
        self::sayIt($wgRequest);
        
        $input = array(
        # Params related to Message
        'tmr_type' => $wgRequest->getText('type'), # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
        # Params related to Record
        'tmr_amount' => $wgRequest->getText('amount'), # decimal(9,2) NOT NULL COMMENT 'Record Amount',
        'tmr_desc' => 'Test Special:', # varchar(64) COMMENT 'Record Desc',
        );
        
        self::sayIt($input);
        
        $tmr = new TMRecord($input);

        
        self::sayIt($tmr);
        
        
    }
    
    static function sayIt($in) {
        global $wgOut;
        $wgOut->addHTML('<pre>');
        $wgOut->addHTML(print_r($in, true));
        $wgOut->addHTML('</pre>');
    }

}