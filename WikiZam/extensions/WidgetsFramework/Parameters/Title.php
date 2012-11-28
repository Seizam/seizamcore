<?php

namespace WidgetsFramework;

class Title extends Parameter {

    /**
     * <ul>
     * <li>The default value is <i>null</i></li>
     * <li>The parameter is not required</li>
     * </ul>  
     * @param string $name The parameter name, case insensitive
     * @throws \MWException When $name not set
     */
    public function __construct($name) {
        parent::__construct($name);

        $this->default_value = null;
    }

    /**
     * Transforms from string to MediaWiki Title object.
     * Require a value.
     * 
     * @param string|boolean $value A string or boolean <i>true</i>
     * @return \Title
     * @throws UserError When value is not a signed integer.
     */
    public function parse($value) {

        if ($value === true) {
            // parameter specified without value
            Tools::ThrowUserError(wfMessage('wfmk-value-required', $this->getName()));
        }

        $title = \Title::newFromText($value);
        if (is_null($title)) {
            Tools::ThrowUserError(wfMessage('wfmk-title-syntax', $this->getName()));
        }

        return $title;
    }

    public function getOutput() {
        /** @var \Title */
        $value = $this->getValue();
        return $value->getFullURL();
    }

}