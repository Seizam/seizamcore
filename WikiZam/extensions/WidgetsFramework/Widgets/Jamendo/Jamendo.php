<?php

namespace WidgetsFramework;

class Jamendo extends ParserFunction {
    
    /** @var XorParameter */
    protected $source;
    /** @var Boolean */
    protected $autoplay;
    /** @var XorParameter */
    protected $lang;
    /** @var Option */
    protected $right;
    /** @var Option */
    protected $left;
    
    public function declareParameters() {
        
        $track = new String('track');
        $track->setValidateType('int');

        $album = new String('album');
        $album->setValidateType('int');

        $this->source = new XorParameter('source');
        $this->source->addParameter($track);
        $this->source->addParameter($album);
        $this->source->setRequired();
        $this->source->setDefaultParameter($track);
        $this->addParameter($this->source);
        
        
        $this->autoplay = new Boolean('autoplay');
        $this->addParameter($this->autoplay);
        
        
        $this->lang = new XorParameter('lang');
        $this->lang->addParameter(new Option('en'));
        $this->lang->addParameter(new Option('fr'));            
        $this->lang->setDefaultValue('en');
        $this->addParameter($this->lang);
        
        
        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    /**
     * 
     * @return string
     */
    protected function getCSSClasses() {

        $classes = array();

        $classes[] = 'jamendo';
        $classes[] = 'wfmk_block';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }    
    
    protected function getOutput() {
        
        $source = $this->source->getParameter()->getName(); // 'track' or 'album'
        $id = $this->source->getOutput();
        $lang = $this->lang->getOutput();
        $autoplay = $this->autoplay->getValue() ? 'playerautoplay=1&amp;' : '';
        
        return '
            <div class="'.$this->getCSSClasses().'">
            <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" height="300" width="200" align="middle">
            <param name="allowScriptAccess" value="always">
            <param name="wmode" value="transparent">
            <param name="movie" value="http://widgets.jamendo.com/'.$lang.'/'.$source.'/?'.$source.'_id='.$id.'&amp;'.$autoplay.'playertype=2008">
            <param name="quality" value="high"><param name="bgcolor" value="#FFFFFF">
            <embed src="http://widgets.jamendo.com/'.$lang.'/'.$source.'/?'.$source.'_id='.$id.'&amp;'.$autoplay.'playertype=2008" quality="high" wmode="transparent" bgcolor="#FFFFFF" allowscriptaccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" height="300" width="200" align="middle">
            </object>
            </div>';
        
    }
 
    
}