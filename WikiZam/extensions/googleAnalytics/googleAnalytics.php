<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'Google Analytics Integration',
	'version'        => '2.0.2',
	'author'         => 'Tim Laqua',
	'descriptionmsg' => 'googleanalytics-desc',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:Google_Analytics_Integration',
);

$wgExtensionMessagesFiles['googleAnalytics'] = dirname(__FILE__) . '/googleAnalytics.i18n.php';

$wgHooks['SkinAfterBottomScripts'][]  = 'efGoogleAnalyticsHookText';
$wgHooks['ParserAfterTidy'][] = 'efGoogleAnalyticsASAC';

$wgGoogleAnalyticsAccount = "";
$wgGoogleAnalyticsAddASAC = false;
$wgGoogleAnalyticsIgnoreSysops = true;
$wgGoogleAnalyticsIgnoreBots = true;

function efGoogleAnalyticsASAC( &$parser, &$text ) {
	global $wgOut, $wgGoogleAnalyticsAccount, $wgGoogleAnalyticsAddASAC;

	if( !empty($wgGoogleAnalyticsAccount) && $wgGoogleAnalyticsAddASAC ) {
		$wgOut->addScript('<script type="text/javascript">window.google_analytics_uacct = "' . $wgGoogleAnalyticsAccount . '";</script>');
	}

	return true;
}

function efGoogleAnalyticsHookText($skin, &$text='') {
	$text .= efAddGoogleAnalytics();
	return true;
}

function efAddGoogleAnalytics() {
	global $wgGoogleAnalyticsAccount, $wgGoogleAnalyticsIgnoreSysops, $wgGoogleAnalyticsIgnoreBots, $wgUser;
	if (!$wgUser->isAllowed('bot') || !$wgGoogleAnalyticsIgnoreBots) {
		if (!$wgUser->isAllowed('protect') || !$wgGoogleAnalyticsIgnoreSysops) {
			if ( !empty($wgGoogleAnalyticsAccount) ) {
				$funcOutput = <<<GASCRIPT
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("{$wgGoogleAnalyticsAccount}");
pageTracker._trackPageview();
</script>
GASCRIPT;
			} else {
				$funcOutput = "\n<!-- Set \$wgGoogleAnalyticsAccount to your account # provided by Google Analytics. -->";
			}
		} else {
			$funcOutput = "\n<!-- Google Analytics tracking is disabled for users with 'protect' rights (I.E. sysops) -->";
		}
	} else {
		$funcOutput = "\n<!-- Google Analytics tracking is disabled for bots -->";
	}

	return $funcOutput;
}

///Alias for efAddGoogleAnalytics - backwards compatibility.
function addGoogleAnalytics() { return efAddGoogleAnalytics(); }
