<?php
namespace WidgetsFramework; // need to be declared at the very begining of the file

class Vimeo extends ParserFunction {
    
    // call it using wikitext {{#vimeo:}}
    //protected static $FLAGS = 0; 

    protected $id;
    protected $left;
    protected $right;
    protected $height;
    protected $width;
    
    
    protected function declareParameters() {    
        
        $this->id = new String('id');
        $this->id->setRequired();
        $this->id->setEscapeMode('urlpathinfo');
        $this->addParameter($this->id);
        
        
        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue(784);
        $this->width->setMin(0);
        $this->width->setMax(784);
        $this->addParameter($this->width);
        
        
        $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(441);
        $this->height->setMin(0);
        $this->addParameter($this->height);
        
       
        $float = new XorParameter('float');
        
        $this->left = new Boolean('left');
        $float->addParameter($this->left);        
        
        $this->right = new Boolean('right');
        $float->addParameter($this->right);
        
        $this->addParameter($float);
              
    }

    public function getOutput() {
        
        $left_or_right = $this->right->getValue() ? 'right' : 'left';
        
        $height = $this->height->getOutput();
        
        $id = $this->id->getOutput();
        
        $width = $this->width->getOutput();

        return '<iframe 
                    class="vimeo '.$left_or_right.'"
                    allowfullscreen=""
                    frameborder="0"
                    height="'.$height.'px"
                    src="http://player.vimeo.com/video/'.$id.'?title=0&amp;byline=0&amp;portrait=0"
                    webkitallowfullscreen=""
                    width="'.$width.'px">
                </iframe>';
        
    }

}

