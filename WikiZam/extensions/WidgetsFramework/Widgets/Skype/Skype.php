<?php

namespace WidgetsFramework;

class Skype extends ParserFunction {
    
    /** @var String */
    protected $name;
    /** @var Boolean */
    protected $status;
    /** @var Option */
    protected $right;
    /** @var Option */
    protected $left;
    
    /**
     * Declares the widget's parameters:
     * <ul>
     * <li>instanciates Parameter objects,</li>
     * <li>configures them and</li>
     * <li>calls addParameter() for each of them.</li>
     * </ul>
     * 
     * @return void
     */
    public function declareParameters() {
        
        $this->name = new String('name');
        $this->name->setRequired();
        $this->addParameter($this->name);
        
        
        $this->status = new Boolean('status');
        $this->addParameter($this->status);
        
        
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
    protected function getClass() {
     
        if ($this->right->getValue()) {
            return 'class="wfmk_right" ';
        } elseif ($this->left->getValue()) {
            return 'class="wfmk_left" ';
        } else {
            return '';
        }
    }
    
    protected function getImg() {
        if ($this->status->getValue()) {
            // live status image
           return '<img src="http://mystatus.skype.com/bigclassic/' . Tools::Escape($this->name->getOutput(), 'url') . '" style="border: none;" width="182" height="44" alt="My status" />';
        } else {
            // "call me" image
            return  '<img src="http://download.skype.com/share/skypebuttons/buttons/call_blue_white_124x52.png" style="border: none;" width="124" height="52" alt="Skype Me™!" />';
        }
    }
    
    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string raw HTML
     */
    public function getOutput() {
        
        //$output = '<div class="' . $this->getCSSClasses() . '">';
        $this->setBlock(false);
        $output = '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>';
        $output .= '<a '. $this->getClass() . 'href="skype:' . Tools::Escape($this->name->getOutput(), 'htmlall') .'?call">';
        $output .= $this->getImg();        
        $output .= '</a>';
        return $output;
        
    }

}
