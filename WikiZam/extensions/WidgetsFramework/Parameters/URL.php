<?php

namespace WidgetsFramework;

class URL extends String {
    
    /**
     * Default behavior:
     * <ul>
     * <li>value not set</li>
     * <li>default value is empty string</li>
     * <li>parameter is not required</li>
     * <li>escape mode: <i>url</i></li>
     * <li>validate type: <i>url</i></li>
     * <li>minimal length: <i>1 character</i></li>
     * <li>maximal length: <i>1024 characters</i></li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException if $name not specified
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->setEscapeMode('url');
        $this->setValidateType('url');
    }
    
}