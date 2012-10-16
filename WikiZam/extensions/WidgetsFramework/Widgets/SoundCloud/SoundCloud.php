<?php

namespace WidgetsFramework;

class SoundCloud extends ParserFunction {
    
    protected $id;
    protected $width;
    protected $height;
    
    protected function declareParameters() {
        
        $this->id = new Integer('id');
        $this->id->setRequired();
        $this->id->setMin(0);
        $this->addParameter($this->id);
        
        $this->width = new IntegerInPixel('width');
        $this->width->setDefaultValue(784);
        $this->width->setMax(784);
        $this->addParameter($this->width);
        
 /*       $this->height = new IntegerInPixel('height');
        $this->height->setDefaultValue(166);
        $this->addParameter($this->height);
 */      
    }

    protected function getOutput() {
        
        return '
            <iframe
                width="'.$this->width->getOutput().'"
                height="166"
                scrolling="no"
                frameborder="no"
                src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F'.$this->id->getOutput().'&show_artwork=true&show_artwork=true">
            </iframe>';
        
    }
    
}