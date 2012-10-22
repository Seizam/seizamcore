<?php

/**
 * MediaWiki CarZam extension v.1.0
 *
 * Based on example code from
 * http://www.mediawiki.org/wiki/Manual:Extending_wiki_markupeunet.yu>
 * (with modified parser callback and attribute additions)
 *
 * Code released under GPL v3.0
 * @author Clément Dietshcy for Seizam.com
 * 
 */
$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'CarZam',
    'author' => array('[http://www.seizam.com/User:Bedhed Clément Dietschy]'),
    'url' => 'http://www.seizam.com',
    'version' => '1.0',
    'descriptionmsg' => 'carzam-desc',
);


$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['CarZamCarrousel'] = $dir . 'CarZam.Carrousel.php';
$wgAutoloadClasses['CarZamSlideshow'] = $dir . 'CarZam.Slideshow.php';
$wgAutoloadClasses['CarZamHooks'] = $dir . 'CarZam.hooks.php';


// Load JS Resources
$wgHooks['BeforePageDisplay'][] = 'CarZamHooks::beforePageDisplay';
// Add Tag to parser
$wgHooks['ParserFirstCallInit'][] = 'CarZamHooks::onParserFirstCallInit';

$wgExtensionMessagesFiles['CarZam'] = $dir . 'CarZam.i18n.php';

// JS Resources Declaration
$carZamResourceTemplate = array(
    'localBasePath' => $dir . 'modules',
    'remoteExtPath' => 'ParserExtensions/CarZam/modules',
    'group' => 'ext.carzam'
);

$wgResourceModules += array(
    'ext.carzam.carrousel' => $carZamResourceTemplate + array(
            'scripts' => 'ext.carzam.carrousel.js',
            'styles' => 'ext.carzam.carrousel.css',
            'position' => 'bottom'
        ));




