<?php

namespace WidgetsFramework; 

class Vimeo extends ParserFunction {

    /** @var String */
    protected $id;
    /** @var IntegerInPixel */
    protected $width;
    /** @var IntegerInPixel */
    protected $height;
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
        
        global $wgWFMKMaxWidth;

        $this->id = new String('id');
        $this->id->setRequired();
        $this->id->setEscapeMode('urlpathinfo');
        $this->addParameter($this->id);


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

    /**
     * 
     * @return string
     */
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

        return Tools::ArrayToCSSClasses($classes);
    }

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string Raw HTMl
     */
    public function getOutput() {

        return '<iframe 
                    class="' . $this->getCSSClasses() . '"
                    allowfullscreen=""
                    frameborder="0"
                    width="' . $this->width->getOutput() . 'px"
                    height="' . $this->height->getOutput() . 'px"
                    src="http://player.vimeo.com/video/' . $this->id->getOutput() . '?title=0&amp;byline=0&amp;portrait=0"
                    webkitallowfullscreen="">
                </iframe>';
    }

}

