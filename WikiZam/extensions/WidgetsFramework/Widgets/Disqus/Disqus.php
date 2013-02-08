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
 * @version 0.3
 */
 
 namespace WidgetsFramework; 

class Disqus extends ParserFunction {

    /** @var String */
    protected $shortname;
	/** @var String **/
	protected $uid;
    /** @var String */
    protected $url;
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

        $this->shortname = new String('shortname');
        $this->shortname->setRequired();
        $this->shortname->setEscapeMode('urlpathinfo');
        $this->addParameter($this->shortname);
		
		$this->uid = new String('uid');
        $this->uid->setEscapeMode('urlpathinfo');
        $this->addParameter($this->uid);

        $this->url = new String('url');
        $this->url->setDefaultValue($this->parser->getTitle()->getCanonicalURL());
        $this->addParameter($this->url);

        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    /**
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
	
	
	protected function getUid() {
        if ($this->uid->hasBeenSet()) { // user
            return "var disqus_identifier ='{$this->uid->getOutput()}';";
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
		return '<div id="disqus_thread"></div>
				<script type="text/javascript">
					var disqus_shortname = \'' . $this->shortname->getOutput() . '\';
                    '.$this->getUid().'
                    '.$this->getURL().'
					(function() {
						var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;
						dsq.src = \'http://\' + disqus_shortname + \'.disqus.com/embed.js\';
						(document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);
					})();
				</script>
				<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
				<a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>';
    }

}

