<?php

namespace WidgetsFramework;

/**
 * Users errors when they use a widget.
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