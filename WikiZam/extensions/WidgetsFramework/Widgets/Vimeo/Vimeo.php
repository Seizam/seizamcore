<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

class Vimeo extends ParserFunction {

    protected $id;
    protected $width;
    protected $height;
    protected $right;
    protected $left;

    protected function declareParameters() {

        $this->id = new String('id');
        $this->id->setRequired();
        $this->id->setEscapeMode('urlpathinfo');
        $this->addParameter($this->id);


        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue(784);
        $this->width->setMin(0);
        $this->width->setMax(784);
        $this->addParameter($this->width);


        $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(441);
        $this->height->setMin(0);
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

        $classes[] = 'vimeo';
        $classes[] = 'wfmk_block';
        $classes[] = 'wfmk_frame';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    public function getOutput() {

        return '<iframe 
                    class="' . $this->getCSSClasses() . '"
                    allowfullscreen=""
                    frameborder="0"
                    height="' . $this->height->getOutput() . 'px"
                    src="http://player.vimeo.com/video/' . $this->id->getOutput() . '?title=0&amp;byline=0&amp;portrait=0"
                    webkitallowfullscreen=""
                    width="' . $this->width->getOutput() . 'px">
                </iframe>';
    }

}

