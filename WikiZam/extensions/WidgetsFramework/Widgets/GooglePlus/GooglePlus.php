<?php

namespace WidgetsFramework;

class GooglePlus extends ParserFunction {

    protected $source;
    protected $size;
    protected $annotation; // used for +1
    protected $width; // used for +1 with annotation=inline
    protected $icon;
    protected $right;
    protected $left;
    // internal var computed during validate() and used during getOutput()
    protected $height_value;

    protected function declareParameters() {

        // the identifier is a numeric value, but very long
        // handling it as an Integer could break when MediaWiki runs on 32bits servers
        // the safer is to handle it as string, and validate only digits
        $user = new String('user');
        $user->setValidateType('digits');

        // a +1 take an url as value, the default is the current page
        $url = new String('url');
        $url->setValidateType('url');
        $url->setEscapeMode('urlpathinfo');

        $this->source = new XorParameter('source');
        $this->source->addParameter($url);
        $this->source->addParameter($user);
        $this->source->setDefaultParameter($url); // parse as "source = URL_VALUE" by default
        $this->addParameter($this->source);


        $this->size = new XorParameter('size');


        $small = new Option('small');       // +1   badge   icon
        $medium = new Option('medium');     // +1
        $standard = new Option('standard'); // +1   badge   icon
        $large = new Option('large');       // +1           icon

        $this->size = new XorParameter('size');
        $this->size->addParameter($small);
        $this->size->addParameter($medium);
        $this->size->addParameter($standard);
        $this->size->addParameter($large);

        $this->size->setDefaultParameter($standard);
        $standard->setDefaultValue(true);

        $this->addParameter($this->size);


        $inline = new Option('inline');
        $bubble = new Option('bubble');
        $none = new Option('none');

        $this->annotation = new XorParameter('annotation'); // used for +1
        $this->annotation->addParameter($inline);
        $this->annotation->addParameter($bubble);
        $this->annotation->addParameter($none);
        $this->annotation->setDefaultParameter($none); // size's default output is none's default output
        $none->setDefaultValue(true); // default value of none is true = none's default output is 'none'
        $this->addParameter($this->annotation);


        $this->width = new IntegerInPixel('width'); // used for +1 with annotation=inline
        $this->width->setMax(784);
        $this->addParameter($this->width);


        $this->icon = new Boolean('icon');
        $this->addParameter($this->icon);
        
        
        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    protected function validate() { // 100 - 180

        // set default url (default parameter is url)
        $this->source->setDefaultValue($this->parser->getTitle()->getCanonicalUrl());

        if ($this->source->getParameter()->getName() == 'url') {

            if ($this->annotation->getOutput() == 'inline') { // displaying a +1 button inline
                // google documentation about width: min = 120, default (when unspecified) = 450
                $this->width->setMin(120);
                $this->width->setDefaultValue(450);
            }
        } else { // $this->source->getParameter()->getName() = 'user'
            if (!$this->icon->getValue()) { // displaying a badge, not just an icon
                
                $this->width->setDefaultValue(300);
                
                if ($this->size->getOutput() == 'small') {                  
                    $this->height_value = 69;
                    $this->width->setMin(170); // google documentation             
                } else {                 
                    $this->height_value = 131;
                    $this->width->setMin(100); // google documentation
                    $this->width->setDefaultValue(300);          
                } 
            }
        }

        parent::validate();
    }

    /**
     * Used while displaying a +1 button
     * @return string
     */
    protected function getDataSizeOutput() {

        $selected_size = $this->size->getOutput();
        switch ($selected_size) {

            case 'small':
            case 'medium':
                return 'data-size="' . $selected_size . '"';
            case 'large':
                return 'data-size="tall"';
        }
        // default: considered as no size specified
        return '';
    }

    protected function getDataWidthOutput() {
        if ( ( $this->source->getParameter()->getName() == 'user' ) || ( $this->annotation->getOutput() == 'inline' ) ) {
            return 'data-width="' . $this->width->getOutput() . '"';
        }
        // else
        return '';
    }

    /**
     * Used for displaying a badge
     * @return string
     */
    protected function getDataHeightOutput() {
        return 'data-height="' . $this->height_value . '"'; // 69 or 131
    }

    protected function getDataHrefOutput() {

        $href = $this->source->getOutput();
        
        if ($this->source->getParameter()->getName() == 'user') {
            $href = "https://plus.google.com/" . $href;
        }

        return 'data-href="' . $href . '"';
    }

    /**
     * Used when displaying a +1 button
     * @return string
     */
    protected function getDataAnnotationOutput() {

        $annotation = $this->annotation->getOutput();

        switch ($annotation) {
            case 'inline':
            case 'none':
                return 'data-annotation="' . $annotation . '" ';
            // 'bubble' is the google's default, so let's return '' for this case
        }

        return '';
    }

    /**
     * Used when displaying an icon
     */
    protected function getIconSize() {
        $selected_size = $this->size->getOutput();

        switch ($selected_size) {
            case 'small':
                return '16';
            case 'large':
                return '64';
        }

        return '32'; // standard
    }

    protected function getFloatClass() {
        if ($this->right->getValue()) {
            return 'wfmk_right';
        } elseif ($this->left->getValue()) {
            return 'wfmk_left';
        }
    }
    
    protected function getOutputAsPlusOneButton() {
        return '<div class="g-plusone"
                    ' . $this->getDataSizeOutput() . '
                    ' . $this->getDataAnnotationOutput() . '
                    ' . $this->getDataWidthOutput() . '
                    ' . $this->getDataHrefOutput() . ' >
                </div>     
                ' . $this->getAsynchronousJavaScript();
    }

    protected function getOutputAsIcon() {
        $this->setBlock(false);
        $icon_size = $this->getIconSize();

        return '<a href="//plus.google.com/101486099226952210914?prsrc=3"
                   rel="publisher"
                   style="text-decoration:none;">
                       <img src="//ssl.gstatic.com/images/icons/gplus-' . $icon_size . '.png"
                            alt="Google+"
                            style="border:0;width:' . $icon_size . 'px;height:' . $icon_size . 'px;"/>
                </a>';
    }

    protected function getOutputAsBadge() {
        return '<div class="g-plus"
                    ' . $this->getDataWidthOutput() . '
                    ' . $this->getDataHeightOutput() . '
                    ' . $this->getDataHrefOutput() . ' >
                </div>
                ' . $this->getAsynchronousJavaScript();
    }

    protected function wrapFloatDiv($content) {
        $class = $this->getFloatClass();
        if (!empty($class)) {
            return '<div class="'.$class.'">'.$content.'</div>';
        }
        // else
        return $content;
    }
    /**
     * For the best performance, place this code immediately after the last +1 button tag or Google+ badge tag on the page.
     * @return string
     */
    protected function getAsynchronousJavaScript() {
        return '<script type="text/javascript">' . "
                    (function() {
                        var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
                        po.src = 'https://apis.google.com/js/plusone.js';
                        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
                    })();
                </script>";
    }

    protected function getOutput() {

        if ($this->source->getParameter()->getName() == 'url') {
            $content = $this->getOutputAsPlusOneButton();
        } elseif ($this->icon->getValue()) {
            $content = $this->getOutputAsIcon();
        } else {
            $content = $this->getOutputAsBadge();
        }
        
        return $this->wrapFloatDiv($content);
    }

}
