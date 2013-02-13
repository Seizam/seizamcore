<?php

/**
 * WidgetsFramework extension -- Google Doc widget
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

class GoogleDoc extends ParserFunction {

    /** @var String */
    protected $id;
	/** @var String **/
	protected $oldkey;
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
        $this->id->setEscapeMode('urlpathinfo');
        $this->addParameter($this->id);
		
		$this->oldkey = new String('oldkey');
        $this->oldkey->setEscapeMode('urlpathinfo');
        $this->addParameter($this->oldkey);
		
		$this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue($wgWFMKMaxWidth);
        $this->width->setMax($wgWFMKMaxWidth);
        $this->addParameter($this->width);

        $this->height = new IntegerInPixel('height');
		$this->height->setDefaultValue(300);
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
	
	
	protected function get_id_key() {

        $id_is_set = $this->id->hasBeenSet();
		$key_is_set = $this->oldkey->hasBeenSet();

        if ($id_is_set) { // user
            return 'document/pub?id='.$this->id->getOutput().'&amp;embedded=1';
        } elseif($key_is_set) {
			return 'View?docID='.$this->oldkey->getOutput().'&hgd=1';
		}
    }
	
    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string Raw HTMl
     */
    public function getOutput() {
		return '<iframe width="'.$this->width->getOutput().'" height="'.$this->height->getOutput().'" frameborder="1" src="http://docs.google.com/'.$this->get_id_key().'"></iframe>';
    }

}

