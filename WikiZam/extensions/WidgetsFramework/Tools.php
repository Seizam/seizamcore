<?php

namespace WidgetsFramework;

class Tools {
    /*
     * Smarty plugin
     * -------------------------------------------------------------
     * File:     modifier.validate.php
     * Type:     modifier
     * Name:     validate
     * Purpose:  Validates parameter format ('url' by default).
     *           Useful when you need to validate but not escape.
     * -------------------------------------------------------------
     */

    /**
     * This function is used to validate string.
     * Originally in Smarty, function named smarty_modifier_validate.<br />
     * Smarty modifier validate plugin
     * 
     * Type:     modifier<br />
     * Name:     validate<br />
     * Purpose:  Validates parameter format.<br />
     *           Useful when you need to validate but not escape.
     * @param string $string
     * @param all|url|int|boolean|float|email|ip $type Default = 'all' => validate everything
     * @return boolean True if valid, false otherwise
     */
    public static function Validate($string, $type = 'all') {

        if ($type == 'all') {
            return true;
        }

        // mapping for PHP filters (http://us2.php.net/manual/en/filter.constants.php)
        $filters = array(
            'url' => FILTER_VALIDATE_URL,
            'int' => FILTER_VALIDATE_INT,
            'boolean' => FILTER_VALIDATE_BOOLEAN,
            'float' => FILTER_VALIDATE_FLOAT,
            'email' => FILTER_VALIDATE_EMAIL,
            'ip' => FILTER_VALIDATE_IP
        );

        if (array_key_exists($type, $filters) && filter_var($string, $filters[$type]) !== FALSE) {
            return true;
        }

        // unless it matched some validation rule, it's not valid
        return false;
    }

    /**
     * Returns the escaped value, originally in Smarty, function named smarty_modifier_escape.<br />
     * Smarty escape modifier plugin
     *
     * Type:     modifier<br />
     * Name:     escape<br />
     * Purpose:  Escape the string according to escapement type
     * @link http://smarty.php.net/manual/en/language.modifier.escape.php
     *          escape (Smarty online manual)
     * @author   Monte Ohrt <monte at ohrt dot com>
     * @param string $value
     * @param html|htmlall|url|urlpathinfo|quotes|hex|hexentity|decentity|javascript|mail|nonstd $esc_type
     * @return string The escaped string
     */
    public static function Escape($string = '', $esc_type = 'html', $char_set = 'ISO-8859-1') {

        switch ($esc_type) {
            case 'html':
                return htmlspecialchars($string, ENT_QUOTES, $char_set);

            case 'htmlall':
                return htmlentities($string, ENT_QUOTES, $char_set);

            case 'url':
                return rawurlencode($string);

            case 'urlpathinfo':
                return str_replace('%2F', '/', rawurlencode($string));

            case 'quotes':
                // escape unescaped single quotes
                return preg_replace("%(?<!\\\\)'%", "\\'", $string);

            case 'hex':
                // escape every character into hex
                $return = '';
                for ($x = 0; $x < strlen($string); $x++) {
                    $return .= '%' . bin2hex($string[$x]);
                }
                return $return;

            case 'hexentity':
                $return = '';
                for ($x = 0; $x < strlen($string); $x++) {
                    $return .= '&#x' . bin2hex($string[$x]) . ';';
                }
                return $return;

            case 'decentity':
                $return = '';
                for ($x = 0; $x < strlen($string); $x++) {
                    $return .= '&#' . ord($string[$x]) . ';';
                }
                return $return;

            case 'javascript':
                // escape quotes and backslashes, newlines, etc.
                return strtr($string, array('\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', '</' => '<\/'));

            case 'mail':
                // safe way to display e-mail address on a web page
                return str_replace(array('@', '.'), array(' [AT] ', ' [DOT] '), $string);

            case 'nonstd':
                // escape non-standard chars, such as ms document quotes
                $_res = '';
                for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
                    $_ord = ord(substr($string, $_i, 1));
                    // non-standard char, escape it
                    if ($_ord >= 126) {
                        $_res .= '&#' . $_ord . ';';
                    } else {
                        $_res .= substr($string, $_i, 1);
                    }
                }
                return $_res;

            default: // from original code, changed from returning string unchanged to htmlall escaping (safer when wrong $esc_type)
                return htmlentities($string, ENT_QUOTES, $char_set);
            //return $string;
        }
    }

    /**
     * This function is used to generate an error about how the final users use the parameter
     * @param Message $message
     * @throws UserError
     */
    public static function throwUserError($message) {
        throw new UserError($message->text());
    }

    public static function arrayToCSSClasses($array) {
        $back = '';
        foreach ($array as $class) {
            if (!empty($class)) {
                if (!empty($back)) {
                    $back .= ' ';
                }
                $back .= $class;
            }
        }
        return $back;
    }

}