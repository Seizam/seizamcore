<?php

/**
 * Widgets mother class.
 * 
 * @file
 * @ingroup Extensions
 */

namespace WidgetsFramework;

interface Widget {

    /**
     * hook ParserFirstCallInit of MediaWiki: called when the parser 
     * initialises for the first time.
     * 
     * Registers the widget to MediaWiki's parser.
     * 
     * @param Parser $parser Parser object
     * @return boolean Should always returns <i>true</i> (to continue hook
     * processing)
     */
    public static function Register($parser);
}

