<?php

namespace WidgetsFramework;

class YouTube extends ParserFunction {

    protected $id;
    protected $playlist;
    protected $width;
    protected $height;
    protected $right;
    protected $left;

    protected function declareParameters() {
        
        global $wgWFMKMaxWidth;

        $this->id = new String('id');
        $this->id->setEscapeMode('urlpathinfo');

        $this->playlist = new String('playlist');
        $this->playlist->setEscapeMode('urlpathinfo');

        $source = new XorParameter('source');
        $source->addParameter($this->id);
        $source->addParameter($this->playlist);
        $source->setRequired(); // user need to set one of these parameter
        $source->setDefaultParameter($this->id); // user don't need to type "id=xxx", just "xxx" at right position
        $this->addParameter($source);


        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue($wgWFMKMaxWidth);
        $this->width->setMin(0);
        $this->width->setMax($wgWFMKMaxWidth);
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

        $classes[] = 'youtube';
        $classes[] = 'wfmk_block';
        $classes[] = 'wfmk_frame';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }

    public function getIframeSrc() {
        $src = 'http://www.youtube.com/embed/';

        // the "source" xorparameter is required,
        // so: parameter 'id' is set, or parameter 'playlist' is set
        if ($this->playlist->hasBeenSet()) {
            $src .= 'listType=playlist&list=' . $this->playlist->getOutput();
        } else { // $this->id has been set
            $src .= $this->id->getOutput();
        }

        return $src;
    }

    protected function getOutput() {
        return '<iframe
                    class="' . $this->getCSSClasses() . '"
                    width="' . $this->width->getOutput() . '"
                    height="' . $this->height->getOutput() . '"
                    src="' . $this->getIframeSrc() . '"
                    frameborder="0"
                    allowfullscreen>
                </iframe>';
    }

}