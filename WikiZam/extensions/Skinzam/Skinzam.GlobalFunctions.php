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
    if ($size > 1024) {
        $size = $size / 1024;
        // For GB and bigger two decimal places are smarter
        $size = round($size, 2);
        return wfMessage('size-gigabytes', $wgLang->formatNum($size))->text();
    } else if ($size < 1) {
        return '< '.wfMessage('size-megabytes', $wgLang->formatNum(1))->text();
    } else {
        return wfMessage('size-megabytes', $wgLang->formatNum($size))->text();
    }
}

/**
 * Format a number with K, M, G
 *
 * @param $size number
 * @return string Plain text (not HTML)
 */
function wgFormatNumber($number) {
    global $wgLang;
    $unit = '';
    if ($number >= 10000) {
        $number = $number / 1000;
        $unit = 'k';
        if ($number >= 10000) {
            $number = $number / 1000;
            $unit = 'M';
            if ($number >= 10000) {
                $number = $number / 1000;
                $unit = 'G';
                if ($number >= 10000) {
                    $number = $number / 1000;
                    $unit = 'T';
                }
            }
        }
    }
    $number = intval($number);
    return $wgLang->formatNum($number) . $unit;
}

//
/**
 * special backtrace method
 * original from http://php.net/manual/en/function.debug-backtrace.php
 * by diz at ysagoon dot com 
 * 
 * @return string A pretty summarized backtrace (filenames, lines, functions names, ...)
 */
function wfGetPrettyBacktrace()
{

    $backtrace = debug_backtrace();
    $output = null;

    foreach ($backtrace as $bt) {
        $args = '';
        foreach ($bt['args'] as $a) {
            if (!empty($args)) {
                $args .= ', ';
            }
            switch (gettype($a)) {
            case 'integer':
            case 'double':
                $args .= $a;
                break;
            case 'string':
                $a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
                $args .= "\"$a\"";
                break;
            case 'array':
                $args .= 'Array('.count($a).')';
                break;
            case 'object':
                $args .= 'Object('.get_class($a).')';
                break;
            case 'resource':
                $args .= 'Resource('.strstr($a, '#').')';
                break;
            case 'boolean':
                $args .= $a ? 'True' : 'False';
                break;
            case 'NULL':
                $args .= 'Null';
                break;
            default:
                $args .= 'Unknown';
            }
        }
	
	if ($output==null)
	    $output = '';
	else
	    $output.="\n" ;
	
        $output .= " file: ".( isset($bt['file']) ? $bt['file'] : '?' ).
		" line: ".( isset($bt['line']) ? $bt['line'] : '?' ) ;
        $output .= " call: ".( isset($bt['class']) ? $bt['class'] : '' ). 
		(isset($bt['type']) ? $bt['type'] : '' ) .
		(isset($bt['function']) ? $bt['function'] : '') ."($args)";;
    }

    return $output;
    
}


