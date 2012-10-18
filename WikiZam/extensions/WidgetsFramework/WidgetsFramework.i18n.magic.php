<?php

// This files create magic words automatically for widgets that do not have their own *.magic.php file

$magicWords = array();

global $wgWidgetsFrameworkNeedMagicWords;
 
foreach ($wgWidgetsFrameworkNeedMagicWords as $widget) {
    wfDebugLog('WidgetsFramework', 'Adding automatic magic word for widget '.$widget);
    $magicWords['en'][$widget] = array(0, strtolower($widget));
}




