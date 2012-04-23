<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Implements Special:Skinzam
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
class SpecialMySeizam extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('MySeizam');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $this->setHeaders();
        $user = $this->getUser();
        $output = $this->getOutput();

        if ($user->isAnon()) {
            $link = Linker::linkKnown(
                            SpecialPage::getTitleFor('Userlogin'), wfMessage('wp-nlogin-link-text')->text(), array(), array('returnto' => $this->getTitle()->getPrefixedText())
            );
            $output->addHTML('<p>' . wfMessage('wp-nlogin-text')->rawParams($link)->parse() . '</p>');
            $output->addHTML('<p>' . wfMessage('myseizam')->text() . ': ' . wfMessage('ms-myseizam-desc')->text() . '</p>');
            return;
        }

        if (!$this->userCanExecute($user)) {
            $this->displayRestrictionError();
            return;
        }

        $output->addHTML('MySeizam');
    }

    # Just an array print fonction

    static function sayIt($in) {
        global $wgOut;
        $wgOut->addHTML('<pre>');
        $wgOut->addHTML(print_r($in, true));
        $wgOut->addHTML('</pre>');
    }

}