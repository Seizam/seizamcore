<?php

namespace WidgetsFramework;

class Flickr extends ParserFunction {

    protected $user;
    protected $tag;
    protected $link;
    protected $count;
    protected $random; // only when user all or user tag
    protected $size;
    protected $horizontal;
    protected $right;
    protected $left;
    protected $width;
    protected $height;

    protected function declareParameters() {

        $this->user = new String('user');
        $this->addParameter($this->user);


        $this->tag = new String('tag');
        $this->addParameter($this->tag);


        // show a link to user name or to flickr at the bottom
        $this->link = new Option('link');
        $this->link->setOutputOnTrue('show_name=1&');
        $this->addParameter($this->link);


        $this->count = new Integer('count');
        $this->count->setDefaultValue(3);
        $this->count->setMin(1);
        $this->count->setMax(10);
        $this->addParameter($this->count);


        $this->random = new Option('random');
        $this->random->setOutputOnFalse('recent'); // default output (value=false) changed from '' to 'recent'
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
        $this->size->setDefaultParameter($square); // size's default output = square's default output
        $square->setDefaultValue(true); // default value of square is true = square's default output = 's'
        $this->addParameter($this->size);

/*
        $vertical = new Option('vertical');
        $this->orientation = new XorParameter('orientation');
        $this->orientation->addParameter($vertical);
        $this->orientation->addParameter($horizontal);
  */      
        $this->horizontal = new Option('horizontal');
        $this->addParameter($this->horizontal);
        

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

    protected function getPhotostreamOutput() {

        if (!$this->link->getValue()) {
            return '';
        }

        $back = '<div class="flickr_photostream">';

        $user_is_set = $this->user->hasBeenSet();
        $tag_is_set = $this->tag->hasBeenSet();

        $back .= '<span class="flickr_photostream_txt">';

        if ($user_is_set && !$tag_is_set) { // user
            $back .= '<nobr>Go to</nobr> <a href="http://www.flickr.com/photos/' . $this->user->getOutput() . '/">user\'s photostream</a>';
        } elseif ($user_is_set) { // user & tag
            $back .= '<nobr>More of</nobr> <a href="http://www.flickr.com/photos/' . $this->user->getOutput() . '/tags/' . $this->tag->getOutput() . '/">user\'s stuff tagged with ' . $this->tag->getOutput() . '</a>';
        } elseif ($tag_is_set) { // tag
            $back .= 'More <a href="http://www.flickr.com/photos/tags/' . $this->tag->getOutput() . '/">photos or video tagged with ' . $this->tag->getOutput() . '</a> on Flickr';
        } else { // public
            $back .= 'More <a href="http://www.flickr.com/photos/">photos and video</a> on Flickr';
        }

        $back .= '</span></div>';

        return $back;
    }

    protected function getCSSClasses() {

        $classes = array();
        $classes[] = 'wfmk_block';
        $classes[] = 'flickr_badge_wrapper';

        if ( $this->horizontal->getValue() ) {
            $classes[] = "flickr_horizontal";
        }

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    protected function old() {
        return '    .flickr_badge_wrapper a:hover,
    .flickr_badge_wrapper a:link,
    .flickr_badge_wrapper a:active,
    .flickr_badge_wrapper a:visited { text-decoration:none; background:inherit; color:#0063dc; } 
    .flickr_www { display:block; text-align:center; padding:0 10px 0 10px; color:#3993ff; }
    .flickr_badge_container { border: 1px solid #d8d7d7; }
    
    .flickr_badge_image { text-align:center; padding:0; margin:0; }
    .flickr_badge_image img { display: block; margin: 10px;} 
    .flickr_badge_image a { display: block; padding:0; margin:0; } 
    .flickr_badge_photostream { text-align:center; }
  
    .flickr_horizontal .flickr_badge_image { display: inline-block; }
    
    .flickr_vertical { dispplay: inline-block; }
    .flickr_vertical .flickr_badge_image { display: block; }
    
    .flickr_center .flickr_badge_container { display: inline-block; margin: 0 auto; }
    

/**    .flickr_badge_image { border: 1px solid blue; }
    .flickr_badge_image img { border: 1px solid green; } 
    .flickr_badge_image a { border: 1px solid red; } */';
    }
    
    protected function getCSS() {
        return '
<style type="text/css">
  .flickr_badge_wrapper { display: inline-block; }
  .flickr_badge_container { border: 1px solid #d8d7d7; padding: 10px 0 0 10px; }
  .flickr_badge_image { padding: 0 10px 10px 0; vertical-align: middle;} 
  .flickr_badge_image img { display: block; margin: 0 auto; }
  .flickr_www, .flickr_photostream{ text-align:center;  color:#3993ff; }
  
  .flickr_horizontal .flickr_badge_image { display: inline-block; }

</style>';
    }

    protected function getWidthStyleOutput() {
        
        $style = 'style="';
        
        if ($this->width->hasBeenSet()) {
            $style .= 'width: ' . $this->width->getOutput() . 'px;';
        }
        
        if ($this->height->hasBeenSet()) {
            $style .= 'height: ' . $this->height->getOutput() . 'px;';
        }

        return $style.'"';
    }

    protected function getOutput() {

        return '<!-- Start of Flickr Badge -->
    
' . $this->getCSS() . '

<div class="' . $this->getCSSClasses() . '" ' . $this->getWidthStyleOutput() . '>
    
    <div class="flickr_www">
        <a href="http://www.flickr.com">
            www.<strong style="color:#3993ff">flick<span style="color:#ff1c92">r</span></strong>.com
        </a>
    </div>
    
    <div class="flickr_badge_container">
        <script 
            type="text/javascript" 
            src="http://www.flickr.com/badge_code_v2.gne?' . $this->link->getOutput() . 'count=' . $this->count->getOutput() . '&display=' . $this->random->getOutput() . '&size=' . $this->size->getOutput() . '&layout=x&' . $this->getSourceOutput() . '">
        </script>
    </div>
    
    ' . $this->getPhotostreamOutput() . '

</div>

<!-- End of Flickr Badge -->
';
    }

}