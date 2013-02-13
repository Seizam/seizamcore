<?php

/**
 * WidgetsFramework extension -- Disqus widget
 * 
 * The WidgetsFramework extension provides a php base for widgets to be easily added to the parser.
 * 
 * @see http://www.mediawiki.org/wiki/Extension:WidgetsFramework
 * @see http://www.seizam.com/Help:Widgets
 * 
 * This widget was created by the Yellpedia.com team continuing the excellant work done by
 * Clément Dietschy <clement@seizam.com> & Yann Missler <yann@seizam.com> in creating the WdigetFramework extension.
 * @license GPL v3 or later
 * @version 1.1
 */
 
 namespace WidgetsFramework; 

class Disqus extends ParserFunction {

    /** @var String */
    protected $shortname;
	/** @var String **/
	protected $id;
    /** @var String */
    protected $url;
    /** @var String */
    protected $title;
    /** @var IntegerInPixel */
    protected $width;
    /** @var IntegerInPixel */
    protected $height;
    /** @var Option */
    protected $frame;
    /** @var Option */
    protected $right;
    /** @var Option */
    protected $left;
    /** @var Option */
    protected $dev;
    
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
        
        // Set $wgDisqusShortName in LocalSettings as you discus account name
        global $wgDisqusShortName, $wgWFMKMaxWidth;

        $this->shortname = new String('shortname');
        $this->shortname->setEscapeMode('javascript');
        $this->shortname->setDefaultValue($wgDisqusShortName);
        $this->addParameter($this->shortname);
		
		$this->id = new String('id');
        $this->id->setEscapeMode('javascript');
        $this->addParameter($this->id);

        $this->url = new String('url');
        $this->url->setEscapeMode('javascript');
        $this->url->setDefaultValue($this->parser->getTitle()->getCanonicalURL());
        $this->addParameter($this->url);
        
        $this->title = new String('title');
        $this->title->setEscapeMode('javascript');
        $this->title->setDefaultValue($this->parser->getTitle()->getFullText());
        $this->addParameter($this->title);
        
        $this->width = new IntegerInPixel('width');
        $this->width->setMax($wgWFMKMaxWidth);
        $this->width->setMin(0);
        $this->addParameter($this->width);
        
        $this->height = new IntegerInPixel('height');
        $this->height->setMin(0);
        $this->addParameter($this->height);
        
        $this->frame = new Option('frame');
        $this->addParameter($this->frame);

        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
        
        $this->dev = new Option('dev');
        $this->addParameter($this->dev);
    }

    /**
     * @return string
     */
    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'disqus';
        $classes[] = 'wfmk_block';
        if ($this->frame->getValue()) {
            $classes[] = 'wfmk_frame';
        }

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }
	
	
	protected function getId() {
        if ($this->uid->hasBeenSet()) { // user
            return "var disqus_identifier ='{$this->id->getOutput()}';";
        } else {
            return '';
        }
        
    }
	
	protected function getURL() {
            return "var disqus_url  ='{$this->url->getOutput()}';";
    }
	
    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string Raw HTMl
     */
    public function getOutput() {
        
        $output = "<div id=\"disqus_thread\" class=\"{$this->getCSSClasses()}\" style=\"";
        if ($this->width->hasBeenSet()) $output .= "width:{$this->width->getOutput()}px;";
        if ($this->height->hasBeenSet()) $output .= "height:{$this->height->getOutput()}px;";
        $output .= "\">";
        $output .= "\n<script type=\"text/javascript\">";
        $output .= "\nvar disqus_shortname='{$this->shortname->getOutput()}';";
        if ($this->id->hasBeenSet()) $output .= "\nvar disqus_identifier='{$this->id->getOutput()}';";
        $output .= "\nvar disqus_title='{$this->title->getOutput()}';";
        $output .= "\nvar disqus_url='{$this->url->getOutput()}';";
        if ($this->dev->hasBeenSet()) $output .= "\nvar disqus_developer=1;";
        
        $output .= "\n(function() {
var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
})()";
        $output .= "</script>";
        $output .= "<noscript>Please enable JavaScript to view the <a href=\"http://disqus.com/?ref_noscript\">comments powered by Disqus.</a></noscript>";
        // $output .= "<a href=\"http://disqus.com\" class=\"dsq-brlink\">comments powered by <span class=\"logo-disqus\">Disqus</span></a>";
        
        return $output;
    }

}

