<?php

namespace WidgetsFramework;

/**
 *
 * @author yannouk
 */
class FiveHundredPixels extends ParserFunction {

    /** @var String */
    protected $user;

    /** @var String */
    protected $tag;

    /** @var XorParameter */
    protected $source;

    /** @var XorParameter */
    protected $category;

    /** @var Boolean */
    protected $slideshow;

    /** @var IntegerInPixel */
    protected $size;

    /** @var Integer */
    protected $width;

    /** @var Integer */
    protected $height;

    /** @var Boolean */
    protected $border;

    /** @var Integer */
    protected $padding;

    /** @var String */
    protected $color;

    /** @var XorParameter */
    protected $float;

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

        $this->source = new XorParameter('source');

        $this->user = new String('user');
        $this->source->addParameter($this->user);

        $this->tag = new String('tag');
        $this->source->addParameter($this->tag);

        $this->addParameter($this->source);
        $this->source->setRequired();


        $this->size = new IntegerInPixel('size');
        $this->size->setDefaultValue(80);
        $this->addParameter($this->size);


        $this->width = new Integer('width');
        $this->width->setDefaultValue(9);
        $this->width->setMin(1);
        $this->width->setMax(10);
        $this->addParameter($this->width);


        $this->height = new Integer('height');
        $this->height->setDefaultValue(1);
        $this->height->setMin(1);
        $this->height->setMax(10);
        $this->addParameter($this->height);


        $this->padding = new Integer('padding');
        $this->padding->setDefaultValue(8);
        $this->padding->setMin(0);
        $this->addParameter($this->padding);


        $this->color = new String('color');
        $this->addParameter($this->color);


        $this->slideshow = new Boolean('slideshow');
        $this->addParameter($this->slideshow);


        $this->border = new Boolean('border');
        $this->addParameter($this->border);


        $this->float = new XorParameter('float');

        $right = new Option('right');
        $this->float->addParameter($right);

        $left = new Option('left');
        $this->float->addParameter($left);

        $this->addParameter($this->float);


        $this->category = new XorParameter('category');
        $this->category->addParameter(new Option('Abstract'));
        $this->category->addParameter(new Option('Animals'));
        $this->category->addParameter(new Option('Black and White'));
        $this->category->addParameter(new Option('Celebrities'));
        $this->category->addParameter(new Option('City and Architecture'));
        $this->category->addParameter(new Option('Commercial'));
        $this->category->addParameter(new Option('Concert'));
        $this->category->addParameter(new Option('Family'));
        $this->category->addParameter(new Option('Fashion'));
        $this->category->addParameter(new Option('Film'));
        $this->category->addParameter(new Option('Fine Art'));
        $this->category->addParameter(new Option('Food'));
        $this->category->addParameter(new Option('Journalism'));
        $this->category->addParameter(new Option('Landscapes'));
        $this->category->addParameter(new Option('Macro'));
        $this->category->addParameter(new Option('Nature'));
        $this->category->addParameter(new Option('Nude'));
        $this->category->addParameter(new Option('People'));
        $this->category->addParameter(new Option('Performing Arts'));
        $this->category->addParameter(new Option('Sport'));
        $this->category->addParameter(new Option('Still Life'));
        $this->category->addParameter(new Option('Street'));
        $this->category->addParameter(new Option('Transportation'));
        $this->category->addParameter(new Option('Travel'));
        $this->category->addParameter(new Option('Underwater'));
        $this->category->addParameter(new Option('Urban Exploration'));
        $this->category->addParameter(new Option('Wedding'));
        $this->category->addParameter(new Option('Uncategorized'));
        $this->addParameter($this->category);
    }

    /**
     * 
     * @return int
     */
    protected function getLength($nb_of_thumbs) {
        $size = $this->size->getValue();
        $size += $this->border->getValue() ? 10 : 0;

        if ($this->slideshow->getValue()) {
            return $size;
        }

        return ( $size * $nb_of_thumbs ) + ( $this->padding->getValue() * ( $nb_of_thumbs - 1) );
    }

    /**
     * 
     * @return int
     */
    protected function getWidth() {
        return $this->getLength($this->width->getValue());
    }

    /**
     * 
     * @return int
     */
    protected function getHeight() {
        return $this->getLength($this->height->getValue());
    }

    /**
     * 
     * @return string
     */
    protected function getIframeSrc() {
        $src = "http://500pxwidget.com/" .
                ( $this->slideshow->getValue() ? 'sl' : 'in' ) . "/?" .
                ( $this->tag->hasBeenSet() ? 'h' : 'u' ) . "=";

        $parameters = $this->source->getOutput() . '|in|' .
                $this->size->getValue() . '|' .
                $this->width->getValue() . '|' .
                $this->height->getValue() . '|' .
                $this->color->getOutput() . '|' .
                ( $this->border->getValue() ? 'yes' : 'no' ) . '|' .
                $this->padding->getValue() . '|' .
                $this->category->getOutput();

        return $src . base64_encode($parameters) . '=';
    }

    /**
     * 
     * @return string
     */
    public function getCSSClasses() {

        $classes = array();

        $classes[] = '500px';
        $classes[] = 'wfmk_block';

        $float = $this->float->getOutput();
        if ($float == 'right') {
            $classes[] = 'wfmk_right';
        } elseif ($float == 'left') {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string raw HTML
     */
    protected function getOutput() {

        return '
            <!-- 500pxWidget -->
            <iframe class="' . $this->getCSSClasses() . '" src="' . $this->getIframeSrc() . '" allowTransparency="true" frameborder="0" scrolling="no" style="border:none; overflow:hidden; width:' . $this->getWidth() . 'px; height: ' . $this->getHeight() . 'px" ></iframe>';
    }

}