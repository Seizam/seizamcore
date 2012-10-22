<?php

namespace WidgetsFramework;

class Video extends ParserFunction {

    protected $url;
    protected $width;
    protected $height;
    protected $right;
    protected $left;

    protected function declareParameters() {

        $this->url = new String('url');
        $this->url->setValidateType('url');
        $this->url->setRequired();
        $this->addParameter($this->url);

        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue(784);
        $this->width->setMax(784);
        $this->addParameter($this->width);

        $this->height = new IntegerInPixel('height');
        $this->addParameter($this->height);

        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'video';

        if ($this->right->getValue()) {
            $classes[] = 'right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'left';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    protected function getOutput() {
        return '<video
                    class="' . $this->getCSSClasses() . '"
                    src="' . $this->url->getOutput() . '"
                    width="' . $this->width->getOutput() . '"
                    height="' . ( $this->height->hasBeenSet() ? $this->height->getOutput() : '' ) . '"
                    controls
                    preload >
                </video>';
    }

}