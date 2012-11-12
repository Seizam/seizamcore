<?php

namespace WidgetsFramework;

class Bandcamp extends ParserFunction {

    public static $BACKGROUND_COLOR = 'f2f2f2'; // without #
    public static $LINK_COLOR = 'e22c2e'; // without #
    public static $SIZES = array(
        // name => array( width, height)
        'venti' => array(400, 100),
        'grande' => array(300, 100),
        'grande2' => array(300, 355),
        'grande3' => array(300, 410),
        'tall' => array(150, 295),
        'tall2' => array(150, 450),
        'short' => array(46, 23),
    );
    protected $source;
    /**
     * @var XorParamter 
     */
    protected $size;
    protected $float;

    protected function declareParameters() {

        $track = new String('track');
        $track->setValidateType('int');

        $album = new String('album');
        $album->setValidateType('int');

        $this->source = new XorParameter('source');
        $this->source->addParameter($track);
        $this->source->addParameter($album);
        $this->source->setRequired(); // user need to set one of theses parameters
        $this->addParameter($this->source);


        $this->size = new XorParameter('size');
        $size_names = array_keys(self::$SIZES);
        foreach ($size_names as $size_name) {
            $this->size->addParameter(new Option($size_name));      
        }
        $this->size->setDefaultValue($size_names[0]);
        $this->addParameter($this->size);
        
        
        $this->float = new XorParameter('float');
        $this->float->addParameter(new Option('right'));
        $this->float->addParameter(new Option('left'));
        $this->addParameter($this->float);
    }
    
    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'bandcamp';
        $classes[] = 'wfmk_block';

        $float = $this->float->getOutput();
        if ( $float == 'right') {
            $classes[] = 'wfmk_right';
        } elseif ( $float == 'left') {
            $classes[] = 'wfmk_left';
        }
        
        if ($this->size->getOutput() != 'short') {
            $classes[] = 'wfmk_frame';
        }

        return Tools::arrayToCSSClasses($classes);
    }
    
    protected function getCSSStyle() {
        
        $styles = array();
        
        $size = $this->size->getOutput();
        
        list($width, $height) = self::$SIZES[$size];
        
        $styles[] = "width:{$width}px";
        $styles[] = "height:{$height}px";
        
        if ($size != 'short') {
            $styles[] = "padding:8px";
        } 
        return Tools::arrayToCSSStyle($styles);
    }
    
    protected function getOutput() {

        $size = $this->size->getOutput();
        list($width, $height) = self::$SIZES[$size];

        $source = $this->source->getParameter();
        $source_type = $source->getName();
        $source_id = $source->getValue();
        $link_col = self::$LINK_COLOR;

        return '<iframe
            class="'.$this->getCSSClasses().'"
            style="'.$this->getCSSStyle().'"
            width="' . $width . '"
            height="' . $height . '"
            src="http://bandcamp.com/EmbeddedPlayer/v=2/' . $source_type . '=' . $source_id . '/size=' . $size . '/linkcol=' . $link_col . '/transparent"
            allowtransparency="true"
            frameborder="0"></iframe>';
    }

}