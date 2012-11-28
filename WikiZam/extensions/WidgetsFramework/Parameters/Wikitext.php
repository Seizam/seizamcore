<?php

namespace WidgetsFramework;

class Wikitext extends Parameter {

    /** @var \Parser */
    protected $parser;

    /**
     * 
     * @param Parser $parser
     */
    public function setParser($parser) {
        $this->parser = $parser;
    }

    /**
     * Transforms from wikitext string to HTML.
     * Require a value.
     * 
     * @param string|boolean $value A string or boolean <i>true</i>
     * @return string
     * @throws UserError When value is not a signed integer.
     */
    protected function parse($value) {

        if (!($this->parser instanceof \Parser)) {
            throw new \MWException('Parser must be set using setParser()');
        }

        if ($value === true) {
            // parameter specified without value
            Tools::ThrowUserError(wfMessage('wfmk-value-required', $this->getName()));
        }

        return $this->parser->recursiveTagParse($value);
    }

    public function getOutput() {
        return $this->getValue();
    }

}
