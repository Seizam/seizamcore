<?php

namespace WidgetsFramework;

class Facebook extends ParserFunction {

    /** @var String */
    protected $profile;
    /** @var IntegerInPixel */
    protected $width;
    /** @var IntegerInPixel */
    protected $height;
    /** @var Boolean */
    protected $faces;
    /** @var Boolean */
    protected $stream;
    /** @var Boolean */
    protected $force_wall;
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
        
        $this->profile = new String('profile');
        $this->profile->setRequired();
        $this->addParameter($this->profile);


        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue($wgWFMKMaxWidth);
        $this->width->setMin(0);
        $this->width->setMax($wgWFMKMaxWidth);
        $this->addParameter($this->width);


        $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(556);
        $this->height->setMin(0);
        $this->addParameter($this->height);


        $this->faces = new Boolean('faces');
        $this->faces->setDefaultValue(true);
        $this->addParameter($this->faces);


        $this->stream = new Boolean('stream');
        $this->stream->setDefaultValue(true);
        $this->addParameter($this->stream);


        $this->force_wall = new Boolean('force_wall');
        $this->addParameter($this->force_wall);


        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    /**
     * Checks parameters requirements (required, min, max,...).
     * Updates default values of some parameters according the other parameters
     * values.
     * 
     * @throws UserError When a parameter fails its validate.
     */
    protected function validate() {

        parent::validate(); // Checks parameters requirements (required, min, max,...).

        $faces = $this->faces->getValue();
        $stream = $this->stream->getValue();

        if ($faces && $stream) {
            $this->height->setDefaultValue(556);
        } elseif ($stream) { // && !$faces
            $this->height->setDefaultValue(395);
        } elseif ($faces) { // && !$stream
            $this->height->setDefaultValue(258);
        } else { // !$faces && !$stream
            $this->height->setDefaultValue(63);
        }
        
    }

    /**
     * 
     * @return string
     */
    protected function getCSSClasses() {
        $classes = array();

        $classes[] = 'facebook';
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
     * 
     * @return string
     */
    protected function getIframeSrc() {

        $src = 'http://www.facebook.com/plugins/likebox.php?href=';
        $src .= $this->profile->getOutput();
        $src .= '&amp;width=';
        $src .= $this->width->getOutput();
        $src .= '&amp;height=';
        $src .= $this->height->getOutput();
        $src .= '&amp;colorscheme=light&amp;show_faces=';
        $src .= $this->faces->getOutput();
        $src .= '&amp;stream=';
        $src .= $this->stream->getOutput();
        $src .= '&amp;header=false';

        if ($this->force_wall->getValue()) {
            $src .= '&amp;force_wall=true';
        }

        $src .= '&amp;border_color=white';

        return $src;
    }

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string raw HTML
     */
    protected function getOutput() {
        return '<iframe
                    class="' . $this->getCSSClasses() . '"
                    src="' . $this->getIframeSrc() . '"
                    scrolling="no"
                    frameborder="0"
                    style="
                        overflow:hidden;
                        width:' . $this->width->getOutput() . 'px;
                        height:' . $this->height->getOutput() . 'px"
                    allowTransparency="true">
                </iframe>';
    }

}