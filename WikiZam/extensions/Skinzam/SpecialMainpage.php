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

    private $slideShow = array(
        array(
            'key' => 'freedom1',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/slide_full_1.jpg'
        ),
        array(
            'key' => 'freedom2',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/slide_full_2.jpg'
        ),
        array(
            'key' => 'freedom3',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/slide_full_3.jpg'
        ),
        array(
            'key' => 'freedom4',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/slide_full_4.jpg'
        )
    );
    
    private $triptic = array(
        array(
            'key' => 'video',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/pres_video_1.jpg'
        ),
        array(
            'key' => 'demo',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/pres_video_2.jpg'
        ),
        array(
            'key' => 'blog',
            'href' => '#',
            'src' => 'http://localhost/WikiZam/skins/skinzam/images/demo/pres_video_3.jpg'
        )
    );

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
        $this->setHeaders();
        $output->addHTML($this->displaySlideshow());
        $output->addHTML($this->displayForm());
        $output->addHTML($this->displayTriptic());
    }

    private function displaySlideshow() {
        $html = Xml::openElement('div', array('class' => 'block block_medium'));
        $html .= Xml::element('h3', array('class' => 'title'), wfMessage('sz-ourfreedoms')->text());
        $html .= Xml::openElement('div', array('class' => 'inside'));
        $html .= Xml::openElement('div', array('class' => 'slideshow'));
        $html .= Xml::openElement('ul');

        foreach ($this->slideShow as $slide) {
            $html .= Xml::openElement('li');
            $html .= Xml::openElement('a', array('href' => $slide['href']));
            $html .= Xml::element('img', array('src' => $slide['src'], 'width' => 497, 'height' => 188));
            $html .= Xml::element('h4', array(), wfMessage('sz-' . $slide['key'])->text());
            $html .= Xml::element('p', array(), wfMessage('sz-' . $slide['key'] . '-body')->parse());
            $html .= Xml::closeElement('a');
            $html .= Xml::closeElement('li');
        }

        $html .= Xml::closeElement('ul');
        $html .= Xml::closeElement('div');
        $html .= Xml::closeElement('div');
        $html .= Xml::closeElement('div');

        return $html;
    }

    private function displayForm() {
		if ( session_id() == '' ) {
			wfSetupSession();
		}
        
        if ( !LoginForm::getCreateaccountToken() ) {
				LoginForm::setCreateaccountToken();
			}
		$token = LoginForm::getCreateaccountToken();
        
        $html = Xml::openElement('div', array('class' => 'block block_join'));
        $html .= Xml::element('h3', array('class' => 'title'), wfMessage('sz-joinus')->text());
        $html .= Xml::openElement('div', array('class' => 'inside'));
        $html .= Xml::openElement('form', array(
            'id'=>'userlogin2',
            'action'=>'/WikiZam/index.php?title=Special:UserLogin&action=submitlogin&type=signup',
            'method'=>'post',
            'name'=>'userlogin2'
        ));
        
        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for'=>'wpName2', 'class'=>'sread'),  wfMessage('yourname')->text());
        $html .= Xml::element('input', array('id'=>'wpName2', 'name'=>'wpName', 'placeholder'=>wfMessage('yourname')->text()));
        $html .= Xml::closeElement('p');
        
        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for'=>'wpPassword2', 'class'=>'sread'),  wfMessage('yourpassword')->text());
        $html .= Xml::element('input', array('id'=>'wpPassword2', 'type'=>'password', 'name'=>'wpPassword', 'placeholder'=>wfMessage('yourpassword')->text()));
        $html .= Xml::closeElement('p');
        
        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for'=>'wpRetype', 'class'=>'sread'),  wfMessage('yourpasswordagain')->text());
        $html .= Xml::element('input', array('id'=>'wpRetype', 'type'=>'password', 'name'=>'wpRetype', 'placeholder'=>wfMessage('yourpasswordagain')->text()));
        $html .= Xml::closeElement('p');
        
        $html .= Xml::openElement('p');
        $html .= Xml::element('label', array('for'=>'wpEmail', 'class'=>'sread'),  wfMessage('youremail')->text());
        $html .= Xml::element('input', array('id'=>'wpEmail', 'name'=>'wpEmail', 'placeholder'=>wfMessage('youremail')->text()));
        $html .= Xml::closeElement('p');
        
        $html .= Xml::openElement('p', array('class'=>'submit'));
        $html .= Xml::element('label', array('for'=>'wpCreateaccount', 'class'=>'sread'),  wfMessage('createaccount')->text());
        $html .= Xml::element('input', array('id'=>'wpCreateaccount', 'name'=>'wpCreateaccount', 'type'=>'submit', 'value' => wfMessage('sz-enter')->text()));
        $html .= Xml::closeElement('p');
        
        $html .= Xml::element('input', array('name'=>'wpCreateaccountToken', 'type'=>'hidden', 'value'=> $token));
         
        $html .= Xml::closeElement('form');
        $html .= Xml::closeElement('div');
        $html .= Xml::closeElement('div');
        
        return $html;
    }
    
    private function displayOffers() {
        $html = Xml::openElement('a', array('href' => $href, 'class'=>'subscription'));
        $html .= Xml::element('span', array(), wfMessage('sz-discoveroffers') );
        $html .= Xml::element('small', array(), wfMessage('sz-discoveroffers-catch'));
        $html .= Xml::closeElement('a');
        
        return $html;
    }

    private function displayTriptic() {
        $html = Xml::openElement('div', array('class' => 'block block_full'));
        $html .= Xml::element('h3', array('class' => 'title'), wfMessage('sz-triptic')->text());
        $html .= Xml::openElement('div', array('class' => 'inside'));
        
        foreach ($this->triptic as $ptic) {
        
        $html .= Xml::openElement('div', array('class' => 'third_parts'));
        $html .= Xml::element('h4', array(), wfMessage('sz-'.$ptic['key']));
        $html .= Xml::openElement('a', array('href'=> $ptic['href']));
        $html .= Xml::openElement('figure');
        $html .= Xml::element('img', array('src'=>$ptic['src'], 'width'=>241, 'height'=>133));
        $html .= Xml::element('figcaption',array(), wfMessage('sz-'.$ptic['key'].'-caption'));
        $html .= Xml::closeElement('figure');
        $html .= Xml::closeElement('a');
        $html .= Xml::closeElement('div');
        
        }
        
        $html .= Xml::element('div', array('class'=>'clearfix'));
        
        $html .= Xml::closeElement('div');
        $html .= Xml::closeElement('div');
        
        return $html;
    }

}