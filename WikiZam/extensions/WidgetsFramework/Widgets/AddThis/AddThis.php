<?php

namespace WidgetsFramework;

class AddThis extends ParserFunction {
    
    protected $pubid;
    protected $big;
    protected $left;
    protected $right;
    protected $vertical;
    protected $long;
    protected $counter;
       
    
    protected function declareParameters() {
               
        $this->long = new Boolean('long');
        $this->addParameter($this->long);
        
        
        $this->big = new Boolean('big');
        $this->addParameter($this->big);
        
        
        $this->counter = new Boolean('counter');
        $this->addParameter($this->counter);
        
        
        $this->vertical = new Boolean('vertical');
        $this->addParameter($this->vertical);
        
        
        $float = new XorParameter('float');
        
        $this->left = new Boolean('left');
        $float->addParameter($this->left);        
        
        $this->right = new Boolean('right');
        $float->addParameter($this->right);
        
        $this->addParameter($float);
        
        
        $this->pubid = new String('pubid');
        $this->pubid->setDefaultValue('ra-4fdafa43072e511d');
        $this->pubid->setEscapeMode('urlpathinfo');
        $this->addParameter($this->pubid);
                
    }

    
    
    protected function getOutput() {
        
        $classes = $this->big->getValue() ? ' addthis_32x32_style' : ' addthis_16x16_style';


        if ($this->left->getValue()) {
            $classes .= ' left';
            
        } elseif ($this->right->getValue()) {
            $classes .= ' right';
            
        } // else: nothing added
        
        
        if ( $this->vertical->getValue() ) {
            
            $classes .= ' vertical ';
            
        } else { // not vertical, so accepting long and counter options
            
            if ($this->long->getValue()) {
                $classes .= ' long';
            }
                        
            if ($this->counter->getValue()) {
                $classes .= ' counter';
            }
        }
        
        
        $buttons = '<a class="addthis_button_preferred_1"></a><a class="addthis_button_preferred_2"></a>';
        
        if ($this->long->getValue()) {
            $buttons .= '<a class="addthis_button_preferred_3"></a><a class="addthis_button_preferred_4"></a>';
        }
        
        
        $extra_bubble_button = '';
        if ( $this->counter->getValue() && !$this->vertical->getValue() ) {
            $extra_bubble_button = '<a class="addthis_counter addthis_bubble_style"></a>';
        }
        
        
        $pubid = $this->pubid->getOutput();
        
        return
        
        '<div class="addthis_toolbox addthis_default_style'.$classes.'">
            '.$buttons.'
            <a class="addthis_button_compact"></a>
            '.$extra_bubble_button.'
        </div>
        <script type="text/javascript">'
                ."var addthis_config={data_ga_property:'UA-32666889-1',data_ga_social:true,services_exclude:'print'};".'</script>
        <script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid='.$pubid.'"></script>'
            
            ;
        
    }
    
}