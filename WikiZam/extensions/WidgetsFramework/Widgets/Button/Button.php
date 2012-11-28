<?php

namespace WidgetsFramework;

class Button extends ParserFunction {

    /** @var Title */
    protected $title;

    /** @var Wikitext */
    protected $caption;

    /** @var XorParameter */
    protected $color;

    /** @var IntegerInPixel */
    protected $width;

    /** @var IntegerInPixel */
    protected $height;

    /** @var Option */
    protected $right;

    /** @var Option */
    protected $left;

    protected function declareParameters() {

        global $wgWFMKMaxWidth;

        $this->title = new Title('title');
        $this->title->setRequired();
        $this->addParameter($this->title);


        $this->caption = new Wikitext('caption');
        $this->caption->setParser($this->getParser());
        $this->caption->setRequired();
        $this->addParameter($this->caption);


        $this->color = new XorParameter('color');

        $red = new Option('red');
        $this->color->addParameter($red);

        $grey = new Option('grey');
        $this->color->addParameter($grey);

        $this->color->setDefaultValue('red');

        $this->addParameter($this->color);


        $this->width = new IntegerInPixel('width');
        $this->width->setMax($wgWFMKMaxWidth);
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

    /**
     * 
     * @return string
     */
    protected function getClass() {
        $classes = array();
        $classes[] = 'wfmk_button';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        $classes[] = 'wfmk_button_' . $this->color->getOutput();
        $classes = Tools::ArrayToCSSClasses($classes);

        return 'class="' . $classes . '"';
    }

    protected function getStyle() {
        $styles = array();
        if ($this->width->hasBeenSet()) {
            $styles[] = 'width:' . $this->width->getOutput() . 'px;';
        }
        if ($this->height->hasBeenSet()) {
            $styles[] = 'height:' . $this->height->getOutput() . 'px;';
        }
        $styles = Tools::ArrayToCSSStyle($styles);
        return empty($styles) ? '' : 'style="' . $styles . '"';
    }

    protected function getOutput() {

        $this->setBlock(false);

        return '<a
            ' . $this->getClass() . '
            ' . $this->getStyle() . '
            href="' . $this->title->getOutput() . '">' .
                $this->caption->getOutput() .
                '</a>';
    }

}