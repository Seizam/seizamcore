<?php

namespace WidgetsFramework;

class SoundCloud extends ParserFunction {

    protected $source;
    protected $width;
    protected $height;
    protected $autoplay;
    protected $artwork;
    protected $comments;
    protected $playcount;
    protected $like;
    protected $right;
    protected $left;

    protected function declareParameters() {
        
        global $wgWFMKMaxWidth;

        $track = new Integer('track');
        $track->setMin(0);

        $user = new Integer('user');
        $user->setMin(0);

        $playlist = new Integer('playlist');
        $playlist->setMin(0);

        $this->source = new XorParameter('source');
        $this->source->addParameter($track);
        $this->source->addParameter($user);
        $this->source->addParameter($playlist);
        $this->source->setRequired(); // user need to set one of these parameter
        $this->source->setDefaultParameter($track); // user don't need to type "track=xxx", just "xxx" at right position
        $this->addParameter($this->source);

        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue($wgWFMKMaxWidth);
        $this->width->setMax($wgWFMKMaxWidth);
        $this->addParameter($this->width);

        $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(450); // updated later to 166 if source is "track"
        $this->addParameter($this->height);

        $this->autoplay = new Boolean('autoplay');
        $this->addParameter($this->autoplay);

        $this->artwork = new Boolean('artwork');
        $this->addParameter($this->artwork);

        $this->comments = new Boolean('comments');
        $this->addParameter($this->comments);

        $this->playcount = new Boolean('playcount');
        $this->addParameter($this->playcount);

        $this->like = new Boolean('like');
        $this->addParameter($this->like);

        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    protected function validate() {

        parent::validate();

        if ($this->source->getParameter()->getName() == 'track') {
            $this->height->setDefaultValue(166);
        }
    }
    
    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'soundcloud';
        $classes[] = 'wfmk_block';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }

    protected function getOutput() {

        // source is required, at this point, we are sure that one of the subparameters has been set
        $source = $this->source->getParameter();

        $source_type = $source->getName() . 's';
        $source_id = $source->getOutput();

        return '<iframe
                    class="' . $this->getCSSClasses() . '"
                    width="' . $this->width->getOutput() . '"
                    height="' . $this->height->getOutput() . '"
                    scrolling="no"
                    frameborder="no"
                    src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2F' . $source_type . '%2F' . $source_id . '&amp;auto_play=' . $this->autoplay->getOutput() . '&amp;show_artwork=' . $this->artwork->getOutput() . '&amp;color=e22c2e&amp;show_comments=' . $this->comments->getOutput() . '&amp;show_playcount=' . $this->playcount->getOutput() . '&amp;liking=' . $this->like->getOutput() . '">
                </iframe>';
    }

}