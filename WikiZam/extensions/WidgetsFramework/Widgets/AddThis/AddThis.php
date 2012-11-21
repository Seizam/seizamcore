<?php

namespace WidgetsFramework;

class AddThis extends ParserFunction {

    /** @var Boolean */
    protected $long;
    /** @var Boolean */
    protected $big;
    /** @var Boolean */
    protected $counter;    
    /** @var Boolean */
    protected $vertical;
    /** @var URL */
    protected $url;    
    /** @var String */    
    protected $pubid;
    /** @var Option */
    protected $left;
    /** @var Option */
    protected $right;
    
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

        $this->long = new Boolean('long');
        $this->addParameter($this->long);

        $this->big = new Boolean('big');
        $this->addParameter($this->big);


        $this->counter = new Boolean('counter');
        $this->addParameter($this->counter);


        $this->vertical = new Boolean('vertical');
        $this->addParameter($this->vertical);
        
        $this->url = new String('url');
        $this->url->setDefaultValue($this->parser->getTitle()->getCanonicalURL());
        $this->addParameter($this->url);


        $this->pubid = new String('pubid');
        $this->pubid->setDefaultValue('ra-4fdafa43072e511d');
        $this->pubid->setEscapeMode('urlpathinfo');
        $this->addParameter($this->pubid);


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
    protected function getCSSClasses() {

        $classes = array();
        $classes[] = 'addthis_toolbox';
        $classes[] = 'addthis_default_style';

        if ($this->big->getValue()) {
            $classes[] = 'addthis_32x32_style';
        } else {
            $classes[] = 'addthis_16x16_style';
        }
        
        $classes[] = 'wfmk_block';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        if ($this->vertical->getValue()) {
            $classes[] = 'vertical ';
        } else { // not vertical, so accepting long and counter options
            if ($this->long->getValue()) {
                $classes[] = 'long';
            }
            if ($this->counter->getValue()) {
                $classes[] = 'counter';
            }
        }

        return Tools::ArrayToCSSClasses($classes);
    }

    /**
     * 
     * @return string
     */
    protected function getButtons() {
        $howmany = 2;
        if ($this->long->getValue()) {
            $howmany = 4;
        }
        
        $buttons = "";
        $index = 1;
        
        for ($index; $index <= $howmany; $index++) {
            $buttons .= "<a class=\"addthis_button_preferred_{$index}\"></a>\n";
        }

        return $buttons;
    }
    
    /**
     * 
     * @return string
     */
    protected function getExtraBubbleButton() {

        if ($this->counter->getValue() && !$this->vertical->getValue()) {
            return '<a class="addthis_counter addthis_bubble_style"></a>';
        }
        // else
        return '';
    }
    
    /**
     * 
     * @global string $wgGAnalyticsPropertyID
     * @return string
     */
    protected function getConfigurationJSVars() {
        global $wgGAnalyticsPropertyID;
        
        $js = "";
        
        $vars = array();
        
        if (isset ($wgGAnalyticsPropertyID)) {
            $vars[] = "data_ga_property:'$wgGAnalyticsPropertyID'";
            $vars[] = "data_ga_social:true";
        }
        
        /**
         * @TODO Enable ConfigurationVars settings from parameter
         */
        $vars[] = "services_exclude:'print'";
        $vars[] = "data_track_clickback:false";
        
        if (!empty($vars))
            $js = "\n\tvar addthis_config={".  implode(", ", $vars)."};";
        
        return $js;
    }
    
    /**
     * 
     * @return string
     */
    protected function getShareJSVars() {
        
        $js = "";
        
        $vars = array();
        
        /**
         * @TODO Enable ShareVars settings from parameter
         */
        
        if (!empty($vars))
            $js = "\n\tvar addthis_share={".  implode(", ", $vars)."};";
        
        return $js;
    }
    
    /**
     * 
     * @return type
     */
    protected function getHtmlShareVars() {
        
        $vars = array();
        
        /**
         * @TODO Enable ShareVars settings from parameter
         */
        $vars[] = "addthis:url=\"{$this->url->getOutput()}\"";
        
        return implode(' ', $vars);
        
    }
    
    /**
     * 
     * @return string
     */
    protected function getJSVars() {
        $js = "";
        $js .= $this->getConfigurationJSVars().$this->getShareJSVars();
        if ($js != "")
            $js = "\n<script type=\"text/javascript\">".$js."\n</script>";
        return $js;
    }

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string raw HTML
     */
    protected function getOutput() {

        return "<div class=\"{$this->getCSSClasses()}\" {$this->getHtmlShareVars()}>"
                    . $this->getButtons()
                    . "<a class=\"addthis_button_compact\"></a>"
                    . $this->getExtraBubbleButton()
                . "</div>"
                . $this->getJSVars()
                . "<script type=\"text/javascript\" src=\"http://s7.addthis.com/js/250/addthis_widget.js#pubid={$this->pubid->getOutput()}\">
                </script>";
    }

}