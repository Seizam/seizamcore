<?php

namespace WidgetsFramework;

class Flickr extends ParserFunction {

    protected $user;
    protected $tag;
    protected $nolink;
    protected $photostream;
    protected $count;
    protected $random; // only when user all or user tag
    protected $size;
    protected $right;
    protected $left;
    protected $width;
    protected $height;

    protected function declareParameters() {

        $this->user = new String('user');
        $this->addParameter($this->user);


        $this->tag = new String('tag');
        $this->addParameter($this->tag);


        // hide link to fickr.com on top, and to photostream on bottom
        $this->nolink = new Boolean('nolink');
        $this->addParameter($this->nolink);


        $this->photostream = new OptionString('photostream');
        $this->photostream->setOptionValue('photostream...');
        $this->addParameter($this->photostream);


        $this->count = new Integer('count');
        $this->count->setDefaultValue(3);
        $this->count->setMin(1);
        $this->count->setMax(10);
        $this->addParameter($this->count);


        $this->random = new Boolean('random'); // default = false = the most recent
        $this->addParameter($this->random);


        $square = new Option('square');
        $square->setOutputOnTrue('s');

        $thumbnail = new Option('thumbnail');
        $thumbnail->setOutputOnTrue('t');

        $medium = new Option('medium');
        $medium->setOutputOnTrue('m');

        $this->size = new XorParameter('size');
        $this->size->addParameter($square);
        $this->size->addParameter($thumbnail);
        $this->size->addParameter($medium);
        $this->size->setDefaultParameter($square); // size's default output is square's default output
        $square->setDefaultValue(true); // default value of square is true = square's default output is 's'
        $this->addParameter($this->size);
        

        $this->width = new IntegerInPixel('width');
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

    protected function validate() {
        parent::validate();
        
        if ($this->size->getOutput() == 's') {
            $this->count->setDefaultValue(9);
        }
    }
    
    protected function getSourceOutput() {

        $user_is_set = $this->user->hasBeenSet();
        $tag_is_set = $this->tag->hasBeenSet();

        if ($user_is_set && !$tag_is_set) { // user
            return "source=user&user=" . $this->user->getOutput();
        } elseif ($user_is_set) { // user & tag
            return "source=user_tag&user=" . $this->user->getOutput() . "&tag=" . $this->tag->getOutput();
        } elseif ($tag_is_set) { // tag
            return "source=all_tag&tag=" . $this->tag->getOutput();
        } else { // public
            return "source=all";
        }
    }

    protected function getFlickrLinkOutput() {

        if ($this->nolink->getValue()) {
            return '';
        } else {
            return '
    <div class="flickr_www">
        <a href="http://www.flickr.com">
            www.<strong style="color:#3993ff">flick<span style="color:#ff1c92">r</span></strong>.com
        </a>
    </div>';
        }
    }

    protected function getPhotostreamOutput() {

        if (!$this->photostream->hasBeenSet()) {
            return '';
        }

        $back = '<div class="flickr_photostream"><a href="';

        $user_is_set = $this->user->hasBeenSet();
        $tag_is_set = $this->tag->hasBeenSet();

        if ($user_is_set && !$tag_is_set) { // user
            $back .= 'http://www.flickr.com/photos/' . $this->user->getOutput() . '/';
        } elseif ($user_is_set) { // user & tag
            $back .= 'http://www.flickr.com/photos/' . $this->user->getOutput() . '/tags/' . $this->tag->getOutput() . '/';
        } elseif ($tag_is_set) { // tag
            $back .= 'http://www.flickr.com/photos/tags/' . $this->tag->getOutput() . '/';
        } else { // public
            $back .= 'http://www.flickr.com/photos/';
        }

        $back .= '">' . $this->photostream->getOutput() . '</a></div>';

        return $back;
    }

    protected function getWidthStyleOutput() {

        $style = 'style="';

        if ($this->width->hasBeenSet()) {
            
            if ( ($this->size->getOutput() == 's') && ($this->width->getValue() < 10) ){ 
                // images are squares of 75x75px, and user wrote size in number of columns
                $style .= 'width: ' . ( 12 + ( 85 * $this->width->getValue() ) ) . 'px;';
            } else {
                $style .= 'width: ' . $this->width->getOutput() . 'px;';
            }
        }

        if ($this->height->hasBeenSet()) {
            $style .= 'height: ' . $this->height->getOutput() . 'px;';
        }

        return $style . '"';
    }

    protected function getCSS() {
        return '.flickr_badge_wrapper { display: inline-block; } 
                .flickr_badge_container { border: 1px solid #d8d7d7; padding: 10px 0 0 10px; }
                .flickr_badge_image, .flickr_photostream{ display: inline-block; padding: 0 10px 10px 0; vertical-align: middle;} 
                .flickr_badge_image img { display: block; margin: 0 auto; }
                .flickr_www, .flickr_photostream{ display: block; text-align:center;  color:#3993ff; }';
    }

    protected function getCSSClasses() {

        $classes = array();
        $classes[] = 'wfmk_block';
        $classes[] = 'flickr_badge_wrapper';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    protected function getOutput() {

        $show_name = $this->nolink->getValue() ? '' : 'show_name=1&'; // seems to be useless
        $random_or_recent = $this->random->getValue() ? 'random' : 'recent';

        return '<!-- Start of Flickr Badge -->  
                <style type="text/css">' . $this->getCSS() . '</style>
                <div class="' . $this->getCSSClasses() . '" ' . $this->getWidthStyleOutput() . '>
                    ' . $this->getFlickrLinkOutput() . '
                    <div class="flickr_badge_container">
                        <script 
                            type="text/javascript" 
                            src="http://www.flickr.com/badge_code_v2.gne?' . $show_name . 'count=' . $this->count->getOutput() . '&display=' . $random_or_recent . '&size=' . $this->size->getOutput() . '&layout=x&' . $this->getSourceOutput() . '">
                        </script>
                        ' . $this->getPhotostreamOutput() . '
                    </div>
                </div>
                <!-- End of Flickr Badge -->';
    }

}