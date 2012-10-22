<?php

namespace WidgetsFramework;

class Test extends ParserFunction {

    protected $required;
    protected $first;
    protected $second;
    protected $third;
    protected $string;

    protected function declareParameters() {


        $this->required = new Boolean('required');
        $this->required->setRequired();
        $this->addParameter($this->required);

        $this->first = new Boolean('first');
        $this->first->setDefaultValue('true');
        $this->addParameter($this->first);

        $this->second = new Integer('second');
        $this->addParameter($this->second);

        $this->third = new IntegerInPixel('third');
        $this->third->setDefaultValue('33');
        $this->addParameter($this->third);

        $this->string = new String('string');
        $this->string->setDefaultValue('nothing');
        $this->addParameter($this->string);
    }

    protected function getOutput() {

        $output = 'required (Boolean) = ' . $this->required->getOutput() . '<br/>';
        $output .= 'first (Boolean, default=true) = ' . $this->first->getOutput() . '<br/>';
        $output .= 'second (Integer) = ' . $this->second->getOutput() . '<br/>';
        $output .= 'third (IntegerInPixel) = ' . $this->third->getOutput() . '<br/>';
        $output .= 'string (String, default="nothin") = ' . $this->string->getOutput() . '<br/>';


        return $output;
    }

}