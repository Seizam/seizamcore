<?php

namespace WidgetsFramework;

class Audio extends ParserFunction {

    /** @var String */
    protected $url;
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
    protected function declareParameters() {

        $this->url = new String('url');
        $this->url->setValidateType('url');
        $this->url->setRequired();
        $this->addParameter($this->url);

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
    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'audio';
        $classes[] = 'wfmk_block';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string raw HTML
     */
    protected function getOutput() {
        return '<audio
                    class="' . $this->getCSSClasses() . '"
                    src="' . $this->url->getOutput() . '"
                    controls
                    preload >
                </audio>';
    }

}