<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file

class Vimeo extends ParserFunction {
    
    // call it using wikitext {{#vimeo:}}
    protected static $NAME = 'vimeo';
    //protected static $FLAGS = 0; 

    protected $id;
    protected $float;
    protected $height;
    protected $width;
    


    public function declareParameters() {
        
        wfDebugLog('WidgetsFramework', "Vimeo->declareParameters()");      
        
        $this->id = new String('id');
        $this->id->setEscapeMode('urlpathinfo');
        $this->addParameter($this->id);
        
       
        $this->float = new XorParameter('float');
        
        $left = new Boolean('left');
        $this->float->addParameter($left);        
        $this->float->setDefaultParameter($left);
        
        $right = new Boolean('right');
        $this->float->addParameter($right);
        
        $this->addParameter($this->float);
        
        
        $this->height = new PixelSize('height');
        $this->height->setMin(100);
        $this->height->setMax(200);
        $this->addParameter($this->height);
        
        
        $this->width = new PixelSize('width');
        $this->width->setMin(300);
        $this->width->setMax(400);
        $this->addParameter($this->width);
        
    }

    public function render() {
        
        wfDebugLog('WidgetsFramework', "Vimeo->render()");

        return '<iframe 
                    class="vimeo '.$this->float->getHtml().'"
                    allowfullscreen=""
                    frameborder="0"
                    height="'.$this->height->getHtml().'"
                    src="http://player.vimeo.com/video/'. $this->id->getHtml().'?title=0&amp;byline=0&amp;portrait=0"
                    webkitallowfullscreen=""
                    width="'.$this->width->getHtml().'">
                </iframe>';
        
        /*
        $class = 'vimeo';
        if (isset($right)) {
            $class .= ' right';
        } elseif (isset($left)) {
            $class .= ' left';
        }
        
        $height = 441;
        if (isset($height)) {
            $height = html_escape($height);
        }
        
        $id = escape_urlpathinfo($id);
        
        $width = 784;
        if (isset($width)) {
            $width = html_escape($width);
        }
        
     */   
        
    }

}

