<?php

/**
 * Parameter is an option with a string as default output that can be set.
 * Can be see like a string that can be true.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

class OptionString extends String {

    protected $option_value;

    /**
     * <ul>
     * <li>The default (OFF) value is empty string</li>
     * <li>The ON value is empty string</li>
     * <li>HTML content is escaped for output.</li>
     * <li>No validation of the content of the value</li>
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
        $this->option_value = '';
    }

    /**
     * Sets the value when option is ON (without value)
     * 
     * @param string $option_value Will be parsed, except if $do_parse is false
     * @param boolean $do_parse do parse the $option_value (default is true, safer) 
     * @return void
     * @throws \MWException When $option_value cannot be parsed
     */
    public function setONValue($option_value, $do_parse = true) {

        if ($do_parse && is_string($option_value)) {
            try {

                $option_value = $this->parse($option_value); // can throw UserError Exception
            } catch (UserError $e) {
                throw new \MWException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->option_value = $option_value;
    }

    /**
     * Accepts every string.
     * If parameter is set without value (has an option), returns the value set
     * with setONValue()
     * 
     * @param string|boolean $value The value
     * @return string
     */
    public function parse($value) {

        if ($value === true) {
            $value = $this->option_value;
        }

        return $value;
    }

}
