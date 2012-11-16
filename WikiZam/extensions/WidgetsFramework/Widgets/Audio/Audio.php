<?php

namespace WidgetsFramework;

class Audio extends ParserFunction {

    protected $url;
    protected $right;
    protected $left;

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

    protected function getOutput() {
        return '<audio
                    class="' . $this->getCSSClasses() . '"
                    src="' . $this->url->getOutput() . '"
                    controls
                    preload >
                </audio>';
    }

}