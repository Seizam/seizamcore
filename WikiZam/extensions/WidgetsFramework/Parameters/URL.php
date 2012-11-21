<?php

/**
 * Parameter of type URL.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class URL extends String {
    
    /**
     * <ul>
     * <li>The default value is the empty string</li>
     * <li>URL content is escaped for output.</li>
     * <li>Validates only URL values</li>
     * <li>Minimal length is 0 (accepts empty string)</li>
     * <li>Maximal length is 1024</li>
     * <li>The parameter is not required</li>
     * </ul>
     * 
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {
        parent::__construct($name);
        $this->setEscapeMode('url');
        $this->setValidateType('url');
    }
    
}