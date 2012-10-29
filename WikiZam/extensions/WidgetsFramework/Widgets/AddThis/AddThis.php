<?php

namespace WidgetsFramework;

class AddThis extends ParserFunction {

    protected $long;
    protected $big;
    protected $counter;
    protected $vertical;
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

    protected function getOutput() {
        global $wgGAnalyticsPropertyID;
        if (!isset ($wgGAnalyticsPropertyID)) {
            $wgGAnalyticsPropertyID = '';
        }

        return '<div class="' . $this->getCSSClasses() . '">
                    ' . $this->getButtons() . '
                    <a class="addthis_button_compact"></a>
                    ' . $this->getExtraBubbleButton() . '
                </div>
                <script type="text/javascript">'
                . "var addthis_config={data_ga_property:'$wgGAnalyticsPropertyID',data_ga_social:true,services_exclude:'print'};" . '
                </script>
                <script
                    type="text/javascript"
                    src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=' . $this->pubid->getOutput() . '">
                </script>';
    }

}