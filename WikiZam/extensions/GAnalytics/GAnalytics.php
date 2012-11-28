<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'GAnalytics',
	'version'        => '1.0',
	'author'         => array('Tim Laqua','Clément Dietschy', 'Seizam Sàrl'),
	'descriptionmsg' => 'googleanalytics-desc',
	'url'            => 'http://www.seizam.com',
);

$wgExtensionMessagesFiles['GAnalytics'] = dirname(__FILE__) . '/GAnalytics.i18n.php';

$wgHooks['BeforePageDisplay'][]  = 'efGAnalyticsHookText';

if (!isset($wgGAnalyticsPropertyID)) {
    $wgGAnalyticsPropertyID = "";
}
if (!isset($wgGAnalyticsIgnoreSysops)) {
    $wgGAnalyticsIgnoreSysops = true;
}
if (!isset($wgGAnalyticsIgnoreBots)) {
    $wgGAnalyticsIgnoreBots = true;
}
/**
 *
 * @param OutputPage $out
 * @param Skin $skin
 * @return type 
 */
function efGAnalyticsHookText(OutputPage &$out, Skin &$skin) {
	$out->addHeadItem('GAnalytics', efAddGAnalytics());
	return true;
}

function efAddGAnalytics() {
	global $wgGAnalyticsPropertyID, $wgGAnalyticsIgnoreSysops, $wgGAnalyticsIgnoreBots, $wgUser;
	if (!$wgUser->isAllowed('bot') || !$wgGAnalyticsIgnoreBots) {
		if (!$wgUser->isAllowed('protect') || !$wgGAnalyticsIgnoreSysops) {
			if ( !empty($wgGAnalyticsPropertyID) ) {
                
				$html = "<script type=\"text/javascript\">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '$wgGAnalyticsPropertyID']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>";
			} else {
				$html = "<!-- Set \$wgGAnalyticsPropertyID to in localSettings to the ID provided by Google Analytics. -->\n";
			}
		} else {
			$html = "<!-- GAnalytics tracking is disabled for users with 'protect' rights (I.E. sysops) -->\n";
		}
	} else {
		$html = "<!-- GAnalytics tracking is disabled for bots -->\n";
	}

	return $html;
}
