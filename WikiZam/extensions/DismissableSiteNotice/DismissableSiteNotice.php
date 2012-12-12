<?php
if ( !defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'DismissableSiteNotice',
	'author' => 'Brion Vibber',
	'descriptionmsg' => 'sitenotice-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:DismissableSiteNotice',
);

$wgExtensionMessagesFiles['DismissableSiteNotice'] = __DIR__ . '/DismissableSiteNotice.i18n.php';

// No dismissal for anons
$wgSiteNoticeNoDismissalforAnon = true;

function wfDismissableSiteNotice( &$notice ) {
	global $wgMajorSiteNoticeID, $wgUser, $wgContLang, $wgSiteNoticeNoDismissalforAnon;

	if ( !$notice ) {
		return true;
	}

	$floatSide = $wgContLang->alignEnd();
	$oppositeFloatSide = $wgContLang->alignStart();
	$encNotice = Xml::escapeJsString($notice);
	$encClose = Xml::escapeJsString( wfMessage( 'sitenotice_close' )->text() );
	$id = intval( $wgMajorSiteNoticeID ) . "." . intval( wfMessage( 'sitenotice_id' )->inContentLanguage()->text() );

	// Dismissal for anons
	if ( $wgSiteNoticeNoDismissalforAnon && $wgUser->isAnon() ) {
		$notice = <<<HTML
<script type="text/javascript">
/* <![CDATA[ */
document.writeln("<div id=\"mw-dismissable-notice\">$encNotice</div>");
/* ]]> */
</script>
HTML;
		return true;
	}

	$notice = <<<HTML
<script type="text/javascript">
/* <![CDATA[ */
var cookieName = "dismissSiteNotice=";
var cookiePos = document.cookie.indexOf(cookieName);
var floatSide = "$floatSide";
var oppositeFloatSide = "$oppositeFloatSide";
var siteNoticeID = "$id";
var siteNoticeValue = "$encNotice";
var cookieValue = "";
var msgClose = "$encClose";

if (cookiePos > -1) {
	cookiePos = cookiePos + cookieName.length;
	var endPos = document.cookie.indexOf(";", cookiePos);
	if (endPos > -1) {
		cookieValue = document.cookie.substring(cookiePos, endPos);
	} else {
		cookieValue = document.cookie.substring(cookiePos);
	}
}
if (cookieValue != siteNoticeID) {
	function dismissNotice() {
		var date = new Date();
		date.setTime(date.getTime() + 30*86400*1000);
		document.cookie = cookieName + siteNoticeID + "; expires="+date.toGMTString() + "; path=/";
		var element = document.getElementById('mw-dismissable-notice');
		element.parentNode.removeChild(element);
	}
	document.writeln('<div id="mw-dismissable-notice">'
			+ '<div class="mw-dismissable-notice-text">' + siteNoticeValue + '</div>'
			+ '<div class="mw-dismissable-notice-close">[<a href="javascript:dismissNotice();">' + msgClose + '</a>]</div>'
		+ '</div>'
	);
}
/* ]]> */
</script>
HTML;
	// Compact the string a bit
	/*
	$notice = strtr( $notice, array(
		"\r\n" => '',
		"\n" => '',
		"\t" => '',
		'cookieName' => 'n',
		'cookiePos' => 'p',
		'siteNoticeID' => 'i',
		'siteNoticeValue' => 'sv',
		'cookieValue' => 'cv',
		'msgClose' => 'c',
		'endPos' => 'e',
	));*/
	return true;
}

$wgHooks['SiteNoticeAfter'][] = 'wfDismissableSiteNotice';

$wgMajorSiteNoticeID = 1;
