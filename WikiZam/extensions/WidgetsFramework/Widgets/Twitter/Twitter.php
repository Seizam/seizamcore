<?php

namespace WidgetsFramework;

class Twitter extends ParserFunction {

    protected $source; // xorparameter, contains "user" and "query"
    protected $faves; // in xorparameter "mode"
    protected $list; // in xorparameter "mode"
    protected $follow; // in xorparameter "mode"
    protected $title;
    protected $subject;
    protected $width;
    protected $height;
    protected $count;
    protected $scrollbar;
    protected $live;
    protected $loop;
    protected $all;
    protected $right;
    protected $left;

    protected function declareParameters() {
        
        global $wgWFMKMaxWidth;

        $user = new String('user');
        $user->setEscapeMode('quotes');

        $search = new String('search');
        $search->setEscapeMode('quotes');

        $this->source = new XorParameter('source');
        $this->source->addParameter($user);
        $this->source->addParameter($search);
        $this->source->setRequired(); // one of theses parameters has to be set
        $this->source->setDefaultParameter($user); // user don't need to type "user=xxx", just "xxx" at right position
        $this->addParameter($this->source);


        $this->faves = new Option('faves');

        $this->list = new String('list');
        $this->list->setEscapeMode('quotes');

        $this->follow = new Option('follow');

        $mode = new XorParameter('mode');
        $mode->addParameter($this->faves);
        $mode->addParameter($this->list);
        $mode->addParameter($this->follow);
        $this->addParameter($mode);


        $this->title = new String('title');
        $this->title->setEscapeMode('quotes');
        $this->addParameter($this->title);


        $this->subject = new String('subject');
        $this->subject->setEscapeMode('quotes');
        $this->addParameter($this->subject);


        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue($wgWFMKMaxWidth);
        $this->width->setMin(0);
        $this->width->setMax($wgWFMKMaxWidth);
        $this->addParameter($this->width);


        $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(441);
        $this->height->setMin(0);
        $this->addParameter($this->height);


        $this->count = new Integer('count');
        $this->count->setDefaultValue(5);
        $this->count->setMin(0);
        $this->count->setMax(30);
        $this->addParameter($this->count);


        $this->scrollbar = new Boolean('scrollbar');
        $this->addParameter($this->scrollbar);


        $this->live = new Boolean('live');
        $this->addParameter($this->live);


        $this->loop = new Boolean('loop');
        $this->addParameter($this->loop);


        $this->all = new Boolean('all');
        $this->addParameter($this->all);


        $float = new XorParameter('float');

        $this->right = new Option('right');
        $float->addParameter($this->right);

        $this->left = new Option('left');
        $float->addParameter($this->left);

        $this->addParameter($float);
    }

    public function getCSSClasses() {

        $classes = array();

        $classes[] = 'twitter';
        $classes[] = 'wfmk_block';

        if ($this->right->getValue()) {
            $classes[] = 'wfmk_right';
        } elseif ($this->left->getValue()) {
            $classes[] = 'wfmk_left';
        }

        return Tools::ArrayToCSSClasses($classes);
    }

    protected function getType() {

        // "source" parameter is required ; at this point, source->getParameter() will return parameter "source" or "search'
        $source = $this->source->getParameter();

        if ($source->getName() == "user") {

            if ($this->faves->getValue()) {
                return 'faves';
            } elseif ($this->list->getValue()) {
                return 'list';
            } elseif ($this->follow->getValue()) {
                return 'follow';
            } else {
                return 'profile';
            }
        } else { // $source->getName() == "search"
            return 'search';
        }
    }

    /**
     * 
     * @param 'faves'|'list'|'profile'|'search' $type
     * @return type
     */
    protected function getJSWidgetStartCall($type) {

        switch ($type) {
            case 'profile' :
            case 'faves' :
                return ".render().setUser('" . $this->source->getOutput() . "').start()"; // source = user
            case 'list' :
                return ".render().setList('" . $this->source->getOutput() . "','" . $this->list->getOutput() . "').start()"; // source = user
            case 'search' :
                return ".render().start()";
        }
    }

    protected function getFollowButton() {
        $user = $this->source->getOutput();
        return '<a href="https://twitter.com/' . $user . '" class="twitter-follow-button" data-show-count="false" data-dnt="true">
                    Follow @' . $user . '
                </a>
                <script>
                    !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");
                </script>';
    }

    protected function getOutput() {

        $type = $this->getType();

        if ($type == 'follow') {
            return $this->getFollowButton();
        } elseif ($type == 'search') {
            $search = "search: '" . $this->source->getOutput() . "',"; // source = search
        } else {
            $search = "";
        }

        return "<div class=\"" . $this->getCSSClasses() . "\">
                    <script
                        charset=\"utf-8\"
                        src=\"http://widgets.twimg.com/j/2/widget.js\">
                    </script>
                    <script>
                        new TWTR.Widget({
                          version: 2,
                          type: '" . $type . "',
                          rpp: '" . $this->count->getOutput() . "',
                          " . $search . "
                          interval: 6000,
                          title: '" . $this->title->getOutput() . "',
                          subject: '" . $this->subject->getOutput() . "',
                          width: " . $this->width->getOutput() . ",
                          height: " . $this->height->getOutput() . ",
                          theme: {
                            shell: {
                              background: '#dad9d9',
                              color: '#ffffff'
                            },
                            tweets: {
                              background: '#fcfcfc',
                              color: '#4d4e4f',
                              links: '#e22c2e'
                            }
                          },
                          features: {
                            scrollbar: " . $this->scrollbar->getOutput() . ",
                            loop: " . $this->loop->getOutput() . ",
                            live: " . $this->live->getOutput() . ",
                            behavior: '" . ( $this->all->getValue() ? 'all' : 'default' ) . "',
                          }
                        })" . $this->getJSWidgetStartCall($type) . ";
                    </script>
                </div>";
    }

}