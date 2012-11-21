<?php

namespace WidgetsFramework;

/**
 * Exceptions to display syntax errors by wiki editors.
 * 
 * @ingroup Exception
 */
class UserError extends \MWException {

    function getHTML() {
        return $this->getMessage();
    }

    function getText() {
        return $this->getMessage();
    }

}