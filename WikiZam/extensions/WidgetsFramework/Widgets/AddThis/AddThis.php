<?php

namespace WidgetsFramework;

class AddThis extends ParserFunction {

    protected $long;
    protected $big;
    protected $counter;
    protected $vertical;
    protected $url;
    protected $pubid;
    protected $left;
    protected $right;

    protected function declareParameters() {

        $this->long = new Boolean('long');
        $this->addParameter($this->long);


        $this->big = new Boolean('big');
        $this->addParameter($this->big);


        $this->counter = new Boolean('counter');
        $this->addParameter($this->counter);


        $this->vertical = new Boolean('vertical');
        $this->addParameter($this->vertical);
        
        $this->url = new URL('url');
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

    protected function getCSSClasses() {

        $classes = array();
        $classes[] = 'wfmk_block';
        $classes[] = 'addthis_toolbox';
        $classes[] = 'addthis_default_style';

        if ($this->big->getValue()) {
            $classes[] = 'addthis_32x32_style';
        } else {
            $classes[] = 'addthis_16x16_style';
        }

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

        return Tools::arrayToCSSClasses($classes);
    }

    protected function getButtons() {
        $buttons = '<a class="addthis_button_preferred_1"></a><a class="addthis_button_preferred_2"></a>';

        if ($this->long->getValue()) {
            $buttons .= '<a class="addthis_button_preferred_3"></a><a class="addthis_button_preferred_4"></a>';
        }

        return $buttons;
    }

    protected function getExtraBubbleButton() {

        if ($this->counter->getValue() && !$this->vertical->getValue()) {
            return '<a class="addthis_counter addthis_bubble_style"></a>';
        }
        // else
        return '';
    }
    
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
    
    protected function getShareJSVars() {
        
        $js = "";
        
        $vars = array();
        
        /**
         * @TODO Enable ShareVars settings from parameter
         */
        $vars[] = "url:'".$this->url->getOutput()."'";
        
        if (!empty($vars))
            $js = "\n\tvar addthis_share={".  implode(", ", $vars)."};";
        
        return $js;
    }
    
    protected function getJSVars() {
        $js = "";
        $js .= $this->getConfigurationJSVars().$this->getShareJSVars();
        if ($js != "")
            $js = "\n<script type=\"text/javascript\">".$js."\n</script>";
        return $js;
    }

    protected function getOutput() {
        
        global $wgGAnalyticsPropertyID;

        return '<div class="' . $this->getCSSClasses() . '">
                    ' . $this->getButtons() . '
                    <a class="addthis_button_compact"></a>
                    ' . $this->getExtraBubbleButton() . '
                </div>'
                . $this->getJSVars()
                .'<script
                    type="text/javascript"
                    src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=' . $this->pubid->getOutput() . '">
                </script>';
    }

}