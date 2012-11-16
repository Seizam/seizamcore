<?php

namespace WidgetsFramework;

class Facebook extends ParserFunction {

    protected $profile;
    protected $width;
    protected $height;
    protected $faces;
    protected $stream;
    protected $force_wall;
    protected $right;
    protected $left;

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

    protected function validate() {

        parent::validate();

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