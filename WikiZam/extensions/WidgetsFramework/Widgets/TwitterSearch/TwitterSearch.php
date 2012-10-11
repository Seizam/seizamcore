<?php

namespace WidgetsFramework;

class TwitterSearch extends ParserFunction {

    protected $left;
    protected $right;
    protected $height;
    protected $width;
      
    protected $count;
    protected $query;
    protected $title;
    protected $subject;
    
    protected $scrollbar;
    protected $loop;
    protected $live;
    protected $all;
     
    
    protected function declareParameters() {
        
        
        $this->query = new String('query');
        $this->query->setRequired();
        $this->query->setEscapeMode('quotes');
        $this->addParameter($this->query);
        
                
        $this->title = new String('title');
        $this->title->setEscapeMode('quotes');
        $this->addParameter($this->title);
        
        
        $this->subject = new String('subject');
        $this->subject->setEscapeMode('quotes');
        $this->addParameter($this->subject);
        
        
        $this->width = new IntegerInPixel('width');
        $this->width->setMin(0);
        $this->width->setMax(784);
        $this->width->setDefaultValue(784);
        $this->addParameter($this->width);
        
        
        $this->height = new IntegerInPixel('height');
        $this->height->setMin(0);
        $this->height->setDefaultValue(441);
        $this->addParameter($this->height);
        

        $this->count = new Integer('count');
        $this->count->setMin(0);
        $this->count->setMax(30); 
        $this->count->setDefaultValue(5);       
        $this->addParameter($this->count);

        
        $this->scrollbar = new Boolean('scrollbar');
        $this->addParameter($this->scrollbar);
        
        $this->loop = new Boolean('loop');
        $this->addParameter($this->loop);
        
        $this->live = new Boolean('live');
        $this->addParameter($this->live);
        
        $this->all = new Boolean('all');
        $this->addParameter($this->all);

        
        $float = new XorParameter('float');
        
        $this->left = new Boolean('left');
        $float->addParameter($this->left);        
        
        $this->right = new Boolean('right');
        $float->addParameter($this->right);
        
        $this->addParameter($float);
        
    }

    protected function getOutput() {
        
        $class = '';
        
        if ($this->left->getValue()) {
            $class .= ' left';
            
        } elseif ($this->right->getValue()) {
            $class .= ' right';
            
        } // else: nothing added
        
        $width = $this->width->getOutput();        
        $height = $this->height->getOutput(); 
        
        $count = $this->count->getOutput();        
        $query = $this->query->getOutput();       
        $title = $this->title->getOutput();        
        $subject = $this->subject->getOutput();
        
        $scrollbar = $this->scrollbar->getOutput();        
        $loop = $this->loop->getOutput();       
        $live = $this->live->getOutput();
        
        $all = $this->all->getValue() ? 'all' : 'default';
        
        return 
        '<div class="twitter'.$class.'">
            <script charset="utf-8" src="http://widgets.twimg.com/j/2/widget.js"></script>
            <script>'."
            new TWTR.Widget({
              version: 2,
              type: 'search',
              rpp: '".$count."',
              search: '".$query."',
              interval: 6000,
              title: '".$title."',
              subject: '".$subject."',
              width: ".$width.",
              height: ".$height.",
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
                scrollbar: ".$scrollbar.",
                loop: ".$loop.",
                live: ".$live.",
                behavior: '".$all."'
              }
            }).render().start();".'
            </script>
        </div>' ;
    }
}