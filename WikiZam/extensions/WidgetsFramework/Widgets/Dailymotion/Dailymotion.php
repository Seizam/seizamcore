<?php

namespace WidgetsFramework;

class Dailymotion extends ParserFunction {

    protected $id;
    protected $width;
    protected $height;
    protected $right;
    protected $left;

    protected function declareParameters() {

        $this->id = new String('id');
        $this->id->setEscapeMode('urlpathinfo');
        $this->id->setRequired();
        $this->addParameter($this->id);

        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue(784);
        $this->width->setMax(784);
        $this->addParameter($this->width);

        $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(441);
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

        $classes[] = 'dailymotion';

        if ($this->right->getValue()) {
            $classes[] = 'right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'left';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    protected function getOutput() {
        return '<iframe
                    class="' . $this->getCSSClasses() . '"
                    frameborder="0"
                    width="' . $this->width->getOutput() . '"
                    height="' . $this->height->getOutput() . '"
                    src="http://www.dailymotion.com/embed/video/' . $this->id->getOutput() . '">
               </iframe>';
    }

}