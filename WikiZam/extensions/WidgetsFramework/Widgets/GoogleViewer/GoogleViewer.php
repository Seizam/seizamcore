<?php

/**
 * WidgetsFramework extension -- Google Viewer widget
 * 
 * The WidgetsFramework extension provides a php base for widgets to be easily added to the parser.
 * 
 * @see http://www.mediawiki.org/wiki/Extension:WidgetsFramework
 * @see http://www.seizam.com/Help:Widgets
 * 
 * This widget was created by the Yellpedia.com team continuing the excellant work done by
 * Clément Dietschy <clement@seizam.com> & Yann Missler <yann@seizam.com> in creating the WdigetFramework extension.
 * @license GPL v3 or later
 * @version 0.3
 */

namespace WidgetsFramework; 

class GoogleViewer extends ParserFunction {

    /** @var String */
    protected $url;
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

        $this->url = new String('url');
        $this->url->setEscapeMode('urlpathinfo');
        $this->addParameter($this->url);
		
		$this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue($wgWFMKMaxWidth);
        $this->width->setMax($wgWFMKMaxWidth);
        $this->addParameter($this->width);

        $this->height = new IntegerInPixel('height');
		$this->height->setDefaultValue(700);
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

        $classes[] = 'wufoo';
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
		return '<iframe src="http://docs.google.com/viewer?url='.$this->url->getOutput().'&embedded=true" style="border:none;" width="'.$this->width->getOutput().'" height="'.$this->height->getOutput().'"></iframe>';
    }

}

