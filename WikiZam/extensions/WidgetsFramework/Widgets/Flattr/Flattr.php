<?php

namespace WidgetsFramework;

class Flattr extends ParserFunction {

    /** @var String */
    protected $url;

    /** @var String */
    protected $uid;

    /** @var String */
    protected $title;

    /** @var String */
    protected $description;

    /** @var XorParameter */
    protected $category;

    /** @var String */
    protected $language;

    /** @var String */
    protected $tags;

    /** @var Option */
    protected $compact;

    /** @var Option */
    protected $nopopout;

    /** @var Option */
    protected $hidden;

    /** @var Option */
    protected $right;

    /** @var Option */
    protected $left;

    /** @var Option */
    protected $inline;
    
    

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
    public function declareParameters() {

        $this->uid = new String('uid');
        $this->addParameter($this->uid);

        $this->url = new String('url');
        $this->url->setDefaultValue($this->parser->getTitle()->getCanonicalURL());
        $this->addParameter($this->url);

        $this->title = new String('title');
        $this->title->setDefaultValue($this->parser->getTitle()->getText());
        $this->title->setMinimalLength(5);
        $this->title->setMaximalLength(100);
        $this->addParameter($this->title);

        $this->description = new String('description');
        $this->description->setDefaultValue($this->parser->getTitle()->getText());
        $this->description->setMinimalLength(5);
        $this->description->setMaximalLength(1000);
        $this->addParameter($this->description);

        $this->category = new XorParameter('category');
        $this->category->addParameter(new Option('text'));
        $this->category->addParameter(new Option('images'));
        $this->category->addParameter(new Option('video'));
        $this->category->addParameter(new Option('audio'));
        $this->category->addParameter(new Option('software'));
        $this->category->addParameter(new Option('people'));
        $this->category->addParameter(new Option('rest'));
        $this->addParameter($this->category);

        $this->tags = new String('tags');
        $this->addParameter($this->tags);

        $this->compact = new Option('compact');
        $this->addParameter($this->compact);

        $this->nopopout = new Option('nopopout');
        $this->addParameter($this->nopopout);

        $this->hidden = new Option('hidden');
        $this->addParameter($this->hidden);

        $float = new XorParameter('float');
        $this->right = new Option('right');
        $float->addParameter($this->right);
        $this->left = new Option('left');
        $float->addParameter($this->left);
        $this->addParameter($float);

        $this->language = new String('language');
        $this->addParameter($this->language);
        
        
    }

    /**
     * @return string 
     */
    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'flattr';

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
     * @return string raw HTML
     */
    public function getOutput() {
        $output = "<a class=\"FlattrButton\" style=\"display:none;\" href=\"{$this->url->getOutput()}\" title=\"{$this->title->getOutput()}\"";    
        if ($this->uid->hasBeenSet()) $output .= " data-flattr-uid=\"{$this->uid->getOutput()}\"";
        if ($this->category->hasBeenSet()) $output .= " data-flattr-category=\"{$this->category->getOutput()}\"";
        if ($this->language->hasBeenSet()) $output .= " data-flattr-language=\"{$this->language->getOutput()}\"";
        if ($this->tags->hasBeenSet()) $output .= " data-flattr-tags=\"{$this->tags->getOutput()}\"";
        if ($this->compact->hasBeenSet()) $output .= " data-flattr-button=\"compact\"";
        if ($this->nopopout->hasBeenSet()) $output .= " data-flattr-popout=\"0\"";
        if ($this->hidden->hasBeenSet()) $output .= " data-flattr-hidden=\"1\"";
        $output .= ">";
        $output .= $this->description->getOutput();
        $output .= "</a>";
        $output .= "<script type=\"text/javascript\" src=\"http://api.flattr.com/js/0.6/load.js?mode=auto\"></script>";
        
        if ($this->left->hasBeenSet() || $this->right->hasBeenSet() ) {
            $output = "<div class=\"{$this->getCSSClasses()}\">$output</div>";
        }

        return $output;
    }

}
