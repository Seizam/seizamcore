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
    
    protected function declareParameters() {    
        
        $this->id = new String('id');
        $this->id->setRequired();
        $this->id->setEscapeMode('urlpathinfo');
        $this->addParameter($this->id);
        
        
        $this->width = new PixelSize('width');
        $this->width->setDefaultValue(784);
        $this->width->setMin(320);
        $this->width->setMax(784);
        $this->addParameter($this->width);
        
        
        $this->height = new PixelSize('height');
        $this->height->setDefaultValue(441);
        $this->height->setMin(180);
        $this->height->setMax(441);
        $this->addParameter($this->height);
        
       
        $this->float = new XorParameter('float');
        
        $left = new Option('left');
        $this->float->addParameter($left);        
        $this->float->setDefaultParameter($left);
        
        $right = new Option('right');
        $this->float->addParameter($right);
        
        $this->addParameter($this->float);
        
        

        
    }

    public function getOutput() {
        
        $float = $this->float->getOutput();
        $height = $this->height->getOutput();
        $id = Tools::Escape($this->id->getOutput(),'urlpathinfo');
        $width = $this->width->getOutput();

        return '<iframe 
                    class="vimeo '.$float.'"
                    allowfullscreen=""
                    frameborder="0"
                    height="'.$height.'"
                    src="http://player.vimeo.com/video/'.$id.'?title=0&amp;byline=0&amp;portrait=0"
                    webkitallowfullscreen=""
                    width="'.$width.'">
                </iframe>';
        
    }

}

