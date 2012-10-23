<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

/**
 * Exception class which takes an HTML error message, and does not
 * produce a backtrace. Replacement for OutputPage::fatalError().
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