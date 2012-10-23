<?php

namespace WidgetsFramework;

class Flickr extends ParserFunction {

    protected $user;
    protected $tag;
    protected $link;
    protected $count;
    protected $random; // only when user all or user tag
    protected $size;
    protected $orientation;
    protected $align;
    protected $width;

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


        $vertical = new Option('vertical');

        $horizontal = new Option('horizontal');

        $this->orientation = new XorParameter('orientation');
        $this->orientation->addParameter($vertical);
        $this->orientation->addParameter($horizontal);
        $this->addParameter($this->orientation);


        $left = new Option('left');

        $right = new Option('right');

        $center = new Option('center');

        $this->align = new XorParameter('align');
        $this->align->addParameter($left);
        $this->align->addParameter($right);
        $this->align->addParameter($center);
        $this->addParameter($this->align);


        $this->width = new IntegerInPixel('width');
        $this->width->setMax(784);
        $this->addParameter($this->width);
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

        $back = '<div class="flickr_badge_photostream">';

        $user_is_set = $this->user->hasBeenSet();
        $tag_is_set = $this->tag->hasBeenSet();

        if ($user_is_set && !$tag_is_set) { // user
            $back .= '
<span class="flickr_badge_photostream_txt">
    <nobr>Go to</nobr> <a href="http://www.flickr.com/photos/' . $this->user->getOutput() . '/">user\'s photostream</a>
</span>';
        } elseif ($user_is_set) { // user & tag
            $back .= '
<span class="flickr_badge_photostream_txt">
    <nobr>More of</nobr> <a href="http://www.flickr.com/photos/' . $this->user->getOutput() . '/tags/' . $this->tag->getOutput() . '/">user\'s stuff tagged with ' . $this->tag->getOutput() . '</a>
</span>';
        } elseif ($tag_is_set) { // tag
            $back .= '
<span class="flickr_badge_photostream_txt">
    More <a href="http://www.flickr.com/photos/tags/' . $this->tag->getOutput() . '/">photos or video tagged with ' . $this->tag->getOutput() . '</a> on Flickr
</span>';
        } else { // public
            $back .= '
<span class= id="flickr_badge_photostream_txt">
    More <a href="http://www.flickr.com/photos/">photos and video</a> on Flickr
</span>';
        }

        $back .= '</div>';

        return $back;
    }

    protected function getCSSClasses() {

        $classes = array();
        $classes[] = 'flickr_badge_wrapper';

        $classes[] = $this->orientation->getOutput() == 'horizontal' ? "flickr_horizontal" : "flickr_vertical";

        $align = $this->align->getOutput(); // 'left' or 'right' or 'center' or ''
        if (( $align == 'left' ) || ( $align == 'right' )) {
            $classes[] = $align;
        } else {
            $classes[] = 'flickr_center';
        }

        return Tools::arrayToCSSClasses($classes);
    }

    protected function getCSS() {
        return '
<style type="text/css">

    .flickr_badge_wrapper {  }   
    .flickr_badge_wrapper a:hover,
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
    .flickr_vertical .flickr_badge_image { display: block; }
    .flickr_center .flickr_badge_container { display: block; margin: 0 auto; }
    

/**    .flickr_badge_image { border: 1px solid blue; }
    .flickr_badge_image img { border: 1px solid green; } 
    .flickr_badge_image a { border: 1px solid red; } */

</style>';
    }

    protected function getWidthStyleOutput() {
        if ($this->width->hasBeenSet()) {
            return 'style="width: ' . $this->width->getOutput() . 'px;"';
        } else {
            return '';
        }
    }

    protected function getOutput() {

        return '<!-- Start of Flickr Badge -->
    
' . $this->getCSS() . '

<div class="' . $this->getCSSClasses() . '" ' . $this->getWidthStyleOutput() . '>
    
    <a href="http://www.flickr.com" class="flickr_www">
        www.<strong style="color:#3993ff">flick<span style="color:#ff1c92">r</span></strong>.com
    </a>
    
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