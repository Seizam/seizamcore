<?php

namespace WidgetsFramework;

class WidgetStripper {
    
    protected static $MARKER_PREFIX = "";
    protected static $MARKER_COUNT = 1;
    
    protected static $STRIPPED_ITEMS = array();
    
    /**
     * Return the stripped item to give to the parser
     * @param string $item
     * @return string
     */
    public static function StripItem($item) {
        
        // inspired from insertStripItem() in Parser.php around line 800       
        //$uniq_text = dechex( mt_rand( 0, 0x7fffffff ) ) . dechex( mt_rand( 0, 0x7fffffff ) );                    
        $stripped = 'WidgetStripper'. self::$MARKER_COUNT . 'EndOfWidgetStripper';
        
        self::$STRIPPED_ITEMS[self::$MARKER_COUNT] = $item;
        
        wfDebugLog('WidgetsFramework', 'WidgetStripper stripped item to id '.self::$MARKER_COUNT);
            
        self::$MARKER_COUNT++;
        
        return $stripped;
        
    }
    
    /**
     * Should be attached to hook ParserAfterTidy
     * @param type $out
     * @param type $text
     * @return boolean
     */
    public static function UnstripItems(&$out, &$text) {
# PATCH
        $text = preg_replace(
                '/\<p\>(WidgetStripper[0-9]*EndOfWidgetStripper)\n\<\/p\>/', "$1\n", $text
        );
# /PATCH
        
        $text = preg_replace_callback(
            '/WidgetStripper([0-9]*)EndOfWidgetStripper?/',
            "WidgetsFramework\\WidgetStripper::UnstripItem",
            $text);

        return true;
    }
    
    public static function UnstripItem($matches) {
        wfDebugLog('WidgetsFramework', 'WidgetStripper unstripped item '.$matches[1]);
        return (self::$STRIPPED_ITEMS[$matches[1]]);
    } 

}


