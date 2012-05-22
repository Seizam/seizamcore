<?php
/**
 * MySeizam extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @author Clément Dietschy <clement@seizam.com>
 * 
 * @license GPL v2 or later
 * @version 0.1.0
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

/* Setup */

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'MySeizam',
	'author' => array( 'Clément Dietschy', 'Seizam'),
	'version' => '0.1.0',
	'url' => 'http://www.seizam.com/',
	'descriptionmsg' => 'ms-myseizam-desc',
);

define('MS_ACCESS_RIGHT', 'msaccess');
$wgAvailableRights[] = MS_ACCESS_RIGHT; 
$wgGroupPermissions['user'][MS_ACCESS_RIGHT] = true;

$dir = dirname(__FILE__) . '/';

$wgExtensionMessagesFiles['MySeizam'] = $dir . 'MySeizam.i18n.php';
$wgExtensionAliasesFiles['MySeizam'] = $dir . 'MySeizam.alias.php';

# Special MySeizam
$wgAutoloadClasses['SpecialMySeizam'] = $dir . 'SpecialMySeizam.php';

$wgSpecialPages['MySeizam'] = 'SpecialMySeizam';

$wgSpecialPageGroups['MySeizam'] = 'users';


