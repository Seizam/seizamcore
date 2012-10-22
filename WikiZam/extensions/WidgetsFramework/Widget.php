<?php

namespace WidgetsFramework; // need to be declared at the very begining of the file

interface Widget {

    /**
     * Called by MediaWiki hook ParserFirstCallInit.
     * Should be used to register the widget to the parser.
     * @param Parser $parser
     * @return boolean true to continue hook processing
     * (or false to abort this parser hook, but will probably breaks other extensions)
     */
    public static function Register($parser);
}

