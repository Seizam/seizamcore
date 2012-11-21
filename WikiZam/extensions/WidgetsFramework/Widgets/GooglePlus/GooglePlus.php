<?php

namespace WidgetsFramework;

class GooglePlus extends ParserFunction {

    /** @var XorParameter */
    protected $target;
    /** @var XorParameter */
    protected $size;
    /** @var XorParameter */
    protected $annotation; // used for +1
    /** @var Boolean */
    protected $icon;
    /** @var IntegerInPixel */
    protected $width; // used for +1 with annotation=inline
    /** @var Option */
    protected $right;
    /** @var Option */
    protected $left;
    /**
     * internal var computed during validate() and used during getOutput()
     * @var int 
     */
    protected $height_value;

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
        
        global $wgWFMKMaxWidth;

        // the identifier is a numeric value, but very long
        // handling it as an Integer could break when MediaWiki runs on 32bits servers
        // the safer is to handle it as string, and validate only digits
        $user = new String('user');
        $user->setValidateType('digits');

        // a +1 take an url as value, the default is the current page
        $url = new String('url');
        $url->setValidateType('url');
        $url->setEscapeMode('none');

        $this->target = new XorParameter('target');
        $this->target->addParameter($url);
        $this->target->addParameter($user);
        $this->target->setDefaultParameter($url); // parse as "target = URL_VALUE" by default
        $this->addParameter($this->target);


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


        $none = new Option('none');
        $bubble = new Option('bubble');
        $inline = new Option('inline');

        $this->annotation = new XorParameter('annotation'); // used for +1
        $this->annotation->addParameter($none);
        $this->annotation->addParameter($bubble);
        $this->annotation->addParameter($inline);

        $this->annotation->setDefaultParameter($none); // size's default output is none's default output
        $none->setDefaultValue(true); // default value of none is true = none's default output is 'none'
        $this->addParameter($this->annotation);


        $this->icon = new Boolean('icon');
        $this->addParameter($this->icon);


        $this->width = new IntegerInPixel('width'); // used for +1 with annotation=inline
        $this->width->setMax($wgWFMKMaxWidth);
        $this->width->setDefaultValue(300);
        $this->addParameter($this->width);


        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    /**
     * Checks parameters requirements (required, min, max,...).
     * Updates default values of some parameters according the other parameters
     * values.
     * 
     * @throws UserError When a parameter fails its validate.
     */
    protected function validate() { // 100 - 180
        // set default url (default parameter is url)
        $this->target->setDefaultValue($this->parser->getTitle()->getCanonicalUrl());

        if ($this->target->getParameter()->getName() == 'url') {

            if ($this->annotation->getOutput() == 'inline') { // displaying a +1 button inline
                // google documentation about width: min = 120, default (when unspecified) = 450
                $this->width->setMin(120);
            }
        } else { // $this->target->getParameter()->getName() = 'user'
            if (!$this->icon->getValue()) { // displaying a badge, not just an icon
                if ($this->size->getOutput() == 'small') {
                    $this->height_value = 69;
                    $this->width->setMin(170); // google documentation             
                } else {
                    $this->height_value = 131;
                    $this->width->setMin(100); // google documentation
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

    /**
     * 
     * @return string
     */
    protected function getDataWidthOutput() {
        if (( $this->target->getParameter()->getName() == 'user' ) || ( $this->annotation->getOutput() == 'inline' )) {
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

    /**
     * 
     * @return string
     */
    protected function getTargetOutput() {
        $target = $this->target->getOutput();
        if ($this->target->getParameter()->getName() == 'user') {
            $target = "https://plus.google.com/" . $target;
        }
        return $target;
    }

    /**
     * 
     * @return string
     */
    protected function getDataHrefOutput() {
        return 'data-href="' . $this->getTargetOutput() . '"';
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
     * 
     * @return string
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

    /**
     * 
     * @return string
     */
    protected function getFloatClass() {
        if ($this->right->getValue()) {
            return 'wfmk_right';
        } elseif ($this->left->getValue()) {
            return 'wfmk_left';
        }
    }

    /**
     * 
     * @return string
     */
    protected function getOutputAsPlusOneButton() {
        return '<div class="g-plusone"
                    ' . $this->getDataSizeOutput() . '
                    ' . $this->getDataAnnotationOutput() . '
                    ' . $this->getDataWidthOutput() . '
                    ' . $this->getDataHrefOutput() . ' >
                </div>     
                ' . $this->getAsynchronousJavaScript();
    }

    /**
     * 
     * @return string
     */
    protected function getOutputAsIcon() {
        $this->setBlock(false);
        $icon_size = $this->getIconSize();

        return '<a href="' . $this->getTargetOutput() . '"
                   rel="publisher"
                   style="text-decoration:none;">
                       <img src="//ssl.gstatic.com/images/icons/gplus-' . $icon_size . '.png"
                            alt="Google+"
                            style="border:0;width:' . $icon_size . 'px;height:' . $icon_size . 'px;"/>
                </a>';
    }

    /**
     * 
     * @return string
     */
    protected function getOutputAsBadge() {
        return '<div class="g-plus"
                    ' . $this->getDataWidthOutput() . '
                    ' . $this->getDataHeightOutput() . '
                    ' . $this->getDataHrefOutput() . ' >
                </div>
                ' . $this->getAsynchronousJavaScript();
    }

    /**
     * 
     * @param string $content
     * @return string
     */
    protected function wrapFloatDiv($content) {
        $class = $this->getFloatClass();
        if (!empty($class)) {
            return '<div class="' . $class . '">' . $content . '</div>';
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

    /**
     * Called after arguments have been parsed, parameters are set and validated.
     * 
     * Returns the output as raw HTML.
     * 
     * @return string raw HTML
     */
    protected function getOutput() {

        if ($this->target->getParameter()->getName() == 'url') {
            $content = $this->getOutputAsPlusOneButton();
        } elseif ($this->icon->getValue()) {
            $content = $this->getOutputAsIcon();
        } else {
            $content = $this->getOutputAsBadge();
        }

        return $this->wrapFloatDiv($content);
    }

}
