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

        if ($this->right->getValue()) {
            $classes[] = 'right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'left';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    protected function getOutput() {
        return '<audio
                    class="'.$this->getCSSClasses().'"
                    src="'.$this->url->getOutput().'"
                    controls
                    preload >
                </audio>';
    }

}