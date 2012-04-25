<?php

/**
 * Global functions used everywhere for Skinzam
 * @file
 */
if (!defined('MEDIAWIKI')) {
    die("This file is part of MediaWiki, it is not a valid entry point");
}

/**
 * Format a size in bytes for output, using an appropriate
 * unit (MB or GB) according to the magnitude in question
 *
 * @param $size Size in MB to format
 * @return string Plain text (not HTML)
 */
function wgformatSizeMB($size) {
    global $wgLang;
    // For small sizes no decimal places necessary
    $round = 0;
    if ($size > 1024) {
        $size = $size / 1024;
        // For GB and bigger two decimal places are smarter
        $round = 2;
        $msg = 'size-gigabytes';
    } else {
        $msg = 'size-megabytes';
    }
    $size = round($size, $round);
    $text = $wgLang->getMessageFromDB($msg);
    return str_replace('$1', $wgLang->formatNum($size), $text);
}