<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Implements Special:Welcome
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
class SpecialMainpage extends SpecialPage {

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('Welcome');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $output = $this->getOutput();
        $output->setArticleRelated( false );
		$output->setPageTitle( $this->getDescription() );
        
        $user = $this->getUser();

        $output->addHTML($this->displaySlideshow());
        if ($user->isLoggedIn())
            $output->addHTML($this->displayOffers());
        else
            $output->addHTML($this->displayForm());

        $output->addHTML($this->displayTriptic());
    }

    private function displaySlideshow() {
        # block_medium
        $html = Xml::openElement('div', array('class' => 'block block_medium'));
        $html .= Xml::element('h3', array('class' => 'title'), wfMessage('sz-mp-ourfreedoms')->text());
        # inside
        $html .= Xml::openElement('div', array('class' => 'inside'));
        # slideshow
        $html .= Xml::openElement('div', array('class' => 'slideshow'));
        $html .= Xml::openElement('ul');
        
        $slide = 0;
        $key = 'sz-slideshow'.$slide;
        $h4 = wfMessage($key);
        
        while ($h4->exists()) {
            $html .= Xml::openElement('li');
            $html .= Xml::openElement('a', array('href' => wfMessage($key.'-href')->text()));
            # wrapper
            $html .= Xml::openElement('div', array('class'=>'wrapper'));
            $html .= Xml::element('img', array('src' => wfMessage($key.'-src')->text(),'alt' => $h4, 'width' => 497, 'height' => 188));
            # /wrapper
            $html .= Xml::closeElement('div');
            # caption
            $html .= Xml::openElement('div', array('class'=>'caption'));
            $html .= Html::rawElement('h4', array(), $h4);
            $html .= Html::rawElement('p', array(), wfMessage($key . '-body')->parse());
            # /caption
            $html .= Xml::closeElement('div');
            $html .= Xml::closeElement('a');
            $html .= Xml::closeElement('li');
            $slide++;
            $key = 'sz-slideshow'.$slide;
            $h4 = wfMessage($key);
        };


        $html .= Xml::closeElement('ul');
        # /slideshow
        $html .= Xml::closeElement('div');
        # /inside
        $html .= Xml::closeElement('div');
        # /block_medium
        $html .= Xml::closeElement('div');

        return $html;
    }

    private function displayForm() {
        if (session_id() == '') {
            wfSetupSession();
        }

        if (!LoginForm::getCreateaccountToken()) {
            LoginForm::setCreateaccountToken();
        }
        $token = LoginForm::getCreateaccountToken();

        # block_join
        $html = Xml::openElement('div', array('class' => 'block block_join'));
        $html .= Xml::element('h3', array('class' => 'title'), wfMessage('sz-mp-joinus')->text());
        # inside
        $html .= Xml::openElement('div', array('class' => 'inside'));
        global $wgServer;
        $html .= Xml::openElement('form', array(
                    'id' => 'userloginS',
                    'action' => $wgServer.'/index.php?title=Special:UserLogin&action=submitlogin&type=signup',
                    'method' => 'post',
                    'name' => 'userlogin'
                ));

        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for' => 'wpNameS', 'class' => 'sread'), wfMessage('yourname')->text());
        $html .= Xml::element('input', array('id' => 'wpNameS', 'name' => 'wpName', 'placeholder' => wfMessage('sz-mp-yourname')->text()));
        $html .= Xml::closeElement('p');

        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for' => 'wpPasswordS', 'class' => 'sread'), wfMessage('yourpassword')->text());
        $html .= Xml::element('input', array('id' => 'wpPasswordS', 'type' => 'password', 'name' => 'wpPassword', 'placeholder' => wfMessage('sz-mp-yourpassword')->text()));
        $html .= Xml::closeElement('p');

        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for' => 'wpRetypeS', 'class' => 'sread'), wfMessage('yourpasswordagain')->text());
        $html .= Xml::element('input', array('id' => 'wpRetypeS', 'type' => 'password', 'name' => 'wpRetype', 'placeholder' => wfMessage('sz-mp-yourpasswordagain')->text()));
        $html .= Xml::closeElement('p');

        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for' => 'wpEmailS', 'class' => 'sread'), wfMessage('youremail')->text());
        $html .= Xml::element('input', array('id' => 'wpEmailS', 'name' => 'wpEmail', 'placeholder' => wfMessage('sz-mp-youremail')->text()));
        $html .= Xml::closeElement('p');

        $html .= Xml::openElement('p', array('class' => 'submit'));
        $html .= Linker::link(SpecialPage::getTitleFor('UserLogin'), wfMessage('login')->text());
        $html .= Xml::element('label', array('for' => 'wpCreateaccountS', 'class' => 'sread'), wfMessage('createaccount')->text());
        $html .= Xml::element('input', array('id' => 'wpCreateaccountS', 'name' => 'wpCreateaccount', 'type' => 'submit', 'value' => wfMessage('sz-mp-enter')->text()));
        $html .= Xml::closeElement('p');

        $html .= Xml::element('input', array('name' => 'wpCreateaccountToken', 'type' => 'hidden', 'value' => $token));

        $html .= Xml::closeElement('form');
        # /inside
        $html .= Xml::closeElement('div');
        # /block
        $html .= Xml::closeElement('div');

        return $html;
    }

    private function displayOffers() {
        # block_join
        $html = Xml::openElement('div', array('class' => 'block block_join'));
        
        if (is_null(WpSubscription::newActiveByUserId($this->getUser()->getId())))
            $blockjoin = array('sz-blockjoin0','sz-blockjoin1','sz-blockjoin2');
        else
            $blockjoin = array('sz-blockjoin0b','sz-blockjoin1','sz-blockjoin2');

        foreach ($blockjoin as $box) {
            $html .= Xml::openElement('a', array('class'=>'fade','href' => wfMessage($box.'-href')->text()));
            $html .= Xml::element('span', array(), wfMessage($box)->text());
            $html .= Html::rawElement('small', array(), wfMessage($box.'-catch')->parse());
            $html .= Xml::closeElement('a');
        }

        # /block_join
        $html .= Xml::closeElement('div');

        return $html;
    }

    private function displayTriptic() {
        # block_full
        $html = Xml::openElement('div', array('class' => 'block block_full'));
        $html .= Xml::element('h3', array('class' => 'title'), wfMessage('sz-mp-triptic')->text());
        # inside
        $html .= Xml::openElement('div', array('class' => 'inside'));

        $triptic = array('sz-triptic0','sz-triptic1','sz-triptic2');
        
        foreach ($triptic as $ptic) {
            # third_parts
            $html .= Xml::openElement('div', array('class' => 'third_parts'));
            $html .= Xml::element('h4', array(), wfMessage($ptic)->text());
            $html .= Xml::openElement('a', array('href' => wfMessage($ptic.'-href')->text()));
            $html .= Xml::openElement('figure');
            $html .= Xml::element('img', array('src' => wfMessage($ptic.'-src')->text(), 'alt' => wfMessage($ptic)->text(),'width' => 241, 'height' => 133));
            $html .= Html::rawElement('figcaption', array(), wfMessage($ptic.'-caption')->parse());
            $html .= Xml::closeElement('figure');
            $html .= Xml::closeElement('a');
            # /third_parts
            $html .= Xml::closeElement('div');
        }

        $html .= Xml::openElement('div', array('class' => 'clearfix'));
        $html .= Xml::closeElement('div');
        # /inside
        $html .= Xml::closeElement('div');
        # /block_full
        $html .= Xml::closeElement('div');

        return $html;
    }

}