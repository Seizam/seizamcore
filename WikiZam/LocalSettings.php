<?php

# Config file for WikiZam
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# http://www.mediawiki.org/wiki/Manual:Configuration_settings

# Some local configuration has been deported to ServerSettings.php

# Table of content:
# * SHOULD BE IN SERVERSETTINGS.PHP
# * GENERAL
# * USER INTERFACE
# * RIGHTS & SECURITY
# * UPLOADS
# * ABUSE CONTROL
# * PARSER FEATURES
# * MISCELLENAOUS
# * WIKIZAM CORE

# Protect against web entry
if (!defined('MEDIAWIKI')) {
    exit; 
}

# Include of ServerSetting.php where Server Side settings are stored
require_once("$IP/ServerSettings.php");

# ===============================
# SHOULD BE IN SERVERSETTINGS.PHP
# ===============================

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Experimental charset support for MySQL 4.1/5.0.
$wgDBmysql5 = false;

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

# Query string length limit for ResourceLoader. You should only set this if
# your web server has a query string length limit (then set it to that limit),
# or if you have suhosin.get.max_value_length set in php.ini (then set it to
# that value)
$wgResourceLoaderMaxQueryLength = 1024;

# See line 286 (regex memory limit)


# ===============
#     GENERAL
# ===============

$wgSitename = "Seizam";
$wgMetaNamespace = "Project";

# Site language code, should be one of ./languages/Language(.*).php
$wgLanguageCode = "en";

# Password reminder name
$wgPasswordSenderName = 'Seizam Mail';

# UPO means: this is also a user preference option
$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO
$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = true; // all e-mail functions (except requesting a password reminder e-mail) only work for authenticated (confirmed) e-mail addresses

# Ensure to clear cache when modifications occur on this file
$wgInvalidateCacheOnLocalSettingsChange = true;

# Widget namespace
define( 'NS_WIDGET', 274 );
$wgExtraNamespaces[NS_WIDGET] = 'Widget';

# Widget_talk namespace
define( 'NS_WIDGET_TALK', NS_WIDGET + 1 );
$wgExtraNamespaces[NS_WIDGET_TALK] = 'Widget_talk';

# Enable subpages
$wgNamespacesWithSubpages = array(
    NS_MAIN => true,
    NS_TALK => true,
    NS_USER => true,
    NS_USER_TALK => true,
    NS_PROJECT => true,
    NS_PROJECT_TALK => true,
    NS_FILE_TALK => true,
    NS_MEDIAWIKI => true,
    NS_MEDIAWIKI_TALK => true,
    NS_TEMPLATE_TALK => true,
    NS_HELP => true,
    NS_HELP_TALK => true,
    NS_CATEGORY_TALK => true,
    NS_SPECIAL => true,
    NS_WIDGET => true,
    NS_WIDGET_TALK => true,
);

# FEEDS
$wgAdvertisedFeedTypes = array('rss', 'atom');



# ===============
# USER INTERFACE
# ===============

# The relative URL path to the skins directory
$wgStylePath = "$wgScriptPath/skins";

# The relative URL path to the logo.
$wgLogo = "$wgStylePath/skinzam/images/seizam_logo_square.png";

# Where is the favicon ?
$wgFavicon = "$wgStylePath/common/images/favicon.ico";

# Default skin
$wgDefaultSkin = "skinzam";
# To remove various skins from the User Preferences choices
$wgSkipSkins = array("chick", "cologneblue", "nostalgia", "simple", "standard", "monobook", "myskin", "modern");
# =[ VECTOR (the extension) ]= Usability extension for Vector (base of Skinzam)
require_once( "$IP/extensions/Vector/Vector.php" );
$wgVectorFeatures['collapsiblenav']['user'] = false;
$wgVectorFeatures['editwarning']['global'] = true;
$wgVectorFeatures['simplesearch']['user'] = false;


$wgVectorUseSimpleSearch = true;
//$wgEnableMWSuggest = true; # Enable AJAX autocomplete search suggestions (autosuggest) while typing in search boxes
// Breaks on the absolute footer

# UI Elements extension for Seizam's skin
# MUST be declared before LanguageSelector due to hack on UserGetLanguageObject Hook.
require_once( "$IP/extensions/Skinzam/Skinzam.php" );

# For attaching licensing metadata to pages, and displaying an
# appropriate copyright notice / icon. GNU Free Documentation
# License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl  = "";
$wgRightsText = "";
$wgRightsIcon = "";
# $wgRightsCode = ""; # Not yet used

# if true, displays only 1 link for login/signup
$wgUseCombinedLoginLink = false;

# =[ Polyglot ]= (auto select page version regarding user language)
require_once( "$IP/extensions/PolyglotS/PolyglotS.php" );
# Supported languages
$wgPolyglotLanguages = null ;
# Enable redirect on target page (eg. MainPage -> MainPage/fr -> Accueil)
$wgPolyglotFollowRedirects = true;

# =[ Language Selector ]= (auto select user language and drop down menu)
require_once( "$IP/extensions/LanguageSelector/LanguageSelector.php" );
# Supported languages
$wgLanguageSelectorLanguages = array('ar','de','en','fr','es','it','ja','pl','pt','zh');
# Displayed languages
$wgLanguageSelectorLanguagesShorthand = array('en','fr');
# Method of language selection
$wgLanguageSelectorDetectLanguage = LANGUAGE_SELECTOR_PREFER_CLIENT_LANG; #Automatic selection regarding browser
# Where to put the language selection dropdown menu
$wgLanguageSelectorLocation = LANGUAGE_SELECTOR_MANUAL; #Hard integrated for Skinzam

# =[ Contact Page ]=
require_once( "$IP/extensions/ContactPage/ContactPage.php" );
$wgContactUser = 'WikiSysop';
$wgUserEmailUseReplyTo = true;
$wgContactRequireAll = true;

# =[ WikiEditor ]=
require_once( "$IP/extensions/WikiEditor/WikiEditor.php" );
$wgWikiEditorFeatures['toolbar']['global'] = true;
$wgWikiEditorFeatures['toolbar']['user'] = false;
$wgWikiEditorFeatures['dialogs']['global'] = true;
$wgWikiEditorFeatures['dialogs']['user'] = false;
$wgWikiEditorFeatures['preview']['user'] = false;
$wgWikiEditorFeatures['publish']['user'] = false;
$wgWikiEditorFeatures['toc']['user'] = false;


# =[ MediaWiki & SkinZam ]=
$wgDefaultUserOptions['usebetatoolbar'] = 1;
$wgDefaultUserOptions['usebetatoolbar-cgd'] = 1;
$wgDefaultUserOptions['wikieditor-preview'] = 0;
$wgDefaultUserOptions['rememberpassword'] = 1; // Remember my login on this browser for a maximum of X days

# =================
# RIGHTS & SECURITY
# =================

# password criteria
$wgMinimalPasswordLength = 4;

# define auto confirmed users as "email confirmed" users
$wgAutopromote['autoconfirmed'] = APCOND_EMAILCONFIRMED;

# Browser Blacklist for unicode non compliant browsers ('/^Lynx/' retrieve from wikimedia.org CommonSettings.php)
$wgBrowserBlackList[] = '/^Lynx/';

# Read WhiteList
$wgWhitelistRead = array("Main Page", "Special:UserLogin", "Special:UserLogout");

# =[ ProtectOwn ]=
require_once( "$IP/extensions/ProtectOwn/ProtectOwn.php" );
# available restriction level/group via SetPermissions form
# ($wgRestrictionLevels will be updated in order for theses level to be accessed via protect
$wgProtectOwnGroups = array('', 'user', 'artist', 'owner');
$wgGroupPermissions['bot'][PROTECTOWN_BYPASS] = true;

# remove the 'move' restriction (so it does not appear in protectOwn)
unset($wgRestrictionTypes[array_search('move', $wgRestrictionTypes)]);

# everyone can edit, even anons, but, there can be per-page restrctions
# by default 'user' is allowed to edit, even if '*' is not.
$wgGroupPermissions['*']['read'] = true; // ProtectOwn override this if protection set on page
$wgGroupPermissions['*']['edit'] = true; // ProtectOwn override this if protection set on page

# bot can edit all protected pages (to sysop level) without cascade protection enabled
$wgGroupPermissions['bot']['editprotected'] = true;

# define PROTECTED namespaces
$wgNamespaceProtection[NS_PROJECT] = array('editprotectedns');
$wgNamespaceProtection[NS_MEDIAWIKI] = array('editprotectedns');
# who can edit theses PROTECTED namespaces ?
$wgGroupPermissions['bureaucrat']['editprotectedns'] = true;
$wgGroupPermissions['sysop']['editprotectedns'] = true;
$wgGroupPermissions['bot']['editprotectedns'] = true;

# define LIMITED namespaces
$wgAvailableRights[] = 'editlimitedns';
$wgNamespaceProtection[NS_HELP] = array('editlimitedns');
$wgNamespaceProtection[NS_USER] = array('editlimitedns');
$wgNamespaceProtection[NS_TEMPLATE] = array('editlimitedns');
$wgNamespaceProtection[NS_HELP] = array('editlimitedns');
$wgNamespaceProtection[NS_CATEGORY] = array('editlimitedns');
$wgNamespaceProtection[NS_WIDGET] = array('editlimitedns');
# who can edit theses LIMITED namespaces ?
$wgGroupPermissions['bureaucrat']['editlimitedns'] = true;
$wgGroupPermissions['sysop']['editlimitedns'] = true;
$wgGroupPermissions['autoconfirmed']['editlimitedns'] = true;



# ===============
#     UPLOADS
# ===============

# To enable image uploads, make sure the 'images' directory
# is writable, then set this to true:
$wgEnableUploads  = true;	//true = upload enabled
$wgImgAuthPublicTest = false;	//false = bypass full public wiki
$wgUseImageMagick = true;	//true = use imagemagick library intsead of
				// internal PHP image conversion system
$wgImageMagickConvertCommand = "/usr/bin/convert"; 
$wgSVGConverters = array('ImageMagick' => '/usr/bin/convert -background none -thumbnail $widthx$height\! $input PNG:$output', );
$wgStrictFileExtensions = true; // default = true = everything not in $wgFileExtensions is forbidden
// what's allowed, default = array( 'png', 'gif', 'jpg', 'jpeg' )
$wgFileExtensions = array(
	'png','gif','jpg','jpeg', 'xcf', 'svg', // pictures
	'djvu', // image compression technology developed since 1996 at AT&T, used for scanned documents
	'mid', 'ogg', 'ogv', 'mp3', 'avi', // audio & video
	'mp4', 'webm', // HTML5 compatible video formats
	'pdf',
	'zip');
// ensure this types will never be uploaded, regarless $wgStrictFileExtensions or not
$wgFileBlacklist[] = 'txt';
$wgFileBlacklist[] = 'mht';



# ==================
#   ABUSE CONTROL
# ==================

# =[ ConfirmEdit with FancyCaptcha ]=
require( "$IP/extensions/ConfirmEdit/ConfirmEdit.php" );
require( "$IP/extensions/ConfirmEdit/FancyCaptcha.php" );
$wgCaptchaTriggers['createaccount'] = false;  // Special:Userlogin&type=signup
$wgGroupPermissions['autoconfirmed']['skipcaptcha'] = true;
$wgCaptchaClass = 'FancyCaptcha';
$wgCaptchaWhitelist = false; // Regex to whitelist URLs to known-good sites...
$wgCaptchaWhitelistIP = false; // List of IP ranges to allow to skip the captcha; (bug 23982 may require the server IP)
# load conf from ServerSettings vars
global $wmgCaptchaStorageClass, $wmgCaptchaSecret, $wmgCaptchaDirectory, $wmgCaptchaDirectoryLevels, $wmgCaptchaRegexes;
$wgCaptchaStorageClass = $wmgCaptchaStorageClass ; // 'CaptchaCacheStore'
$wgCaptchaSecret = $wmgCaptchaSecret; // "a_secret_key"
$wgCaptchaDirectory = $wmgCaptchaDirectory; // "$IP/extensions/ConfirmEdit/default_images"
$wgCaptchaDirectoryLevels = $wmgCaptchaDirectoryLevels; // 1
$wgCaptchaRegexes = $wmgCaptchaRegexes; // array()

# =[ SpamBlacklist ]=
require_once( "$IP/extensions/SpamBlacklist/SpamBlacklist.php" );
$wgSpamBlacklistFiles = array(
   "http://meta.wikimedia.org/w/index.php?title=Spam_blacklist&action=raw&sb_ver=1",
   "http://en.wikipedia.org/w/index.php?title=MediaWiki:Spam-blacklist&action=raw&sb_ver=1"
);

# =[ MediaWiki core spam filtering ]= 
# Any text added to a wiki page matching this localsettings.php regular expression (or "regex") 
# will be recognized as Wiki spam and the edit will be blocked.
$wgSpamRegex = "/".          // The "/" is the opening wrapper
	"s-e-x|zoofilia|sexyongpin|grusskarte|geburtstagskarten|animalsex|".
	"sex-with|dogsex|adultchat|adultlive|camsex|sexcam|livesex|sexchat|".
	"chatsex|onlinesex|adultporn|adultvideo|adultweb.|hardcoresex|hardcoreporn|".
	"teenporn|xxxporn|lesbiansex|livegirl|livenude|livesex|livevideo|camgirl|".
	"spycam|voyeursex|casino-online|online-casino|kontaktlinsen|cheapest-phone|".
	"laser-eye|eye-laser|fuelcellmarket|lasikclinic|cragrats|parishilton|".
	"paris-hilton|paris-tape|2large|fuel-dispenser|fueling-dispenser|huojia|".
	"jinxinghj|telematicsone|telematiksone|a-mortgage|diamondabrasives|".
	"reuterbrook|sex-plugin|sex-zone|lazy-stars|eblja|liuhecai|".
	"buy-viagra|-cialis|-levitra|boy-and-girl-kissing|". // These match spammy words
	"dirare\.com|".           // This matches dirare.com a spammer's domain name
	"overflow\s*:\s*auto|".   // This matches against overflow:auto (regardless of whitespace on either side of the colon)
	"height\s*:\s*[0-4]px|".  // This matches against height:0px (most CSS hidden spam) (regardless of whitespace on either side of the colon)
	"==<center>\[|".          // This matches some recent spam related to starsearchtool.com and friends
	"\<\s*a\s*href|".         // This blocks all href links entirely, forcing wiki syntax
	"display\s*:\s*none".     // This matches against display:none (regardless of whitespace on either side of the colon)
	"/i";                     // The "/" ends the regular expression and the "i" switch which follows makes the test case-insensitive
                              // The "\s" matches whitespace
                              // The "*" is a repeater (zero or more times)
                              // The "\s*" means to look for 0 or more amount of whitespace

# increase Perl Compatible Regular Expressions backtrack memory limit
ini_set( 'pcre.backtrack_limit', '8M' ); // default is often 100ko

# =[ AntiSpoof ]=
require_once( "$IP/extensions/AntiSpoof/AntiSpoof.php" );

# =[ SimpleAntiSpam ]=
require_once("$IP/extensions/SimpleAntiSpam/SimpleAntiSpam.php");

# =[ TitleBlacklist ]=
require_once( "{$IP}/extensions/TitleBlacklist/TitleBlacklist.php" );
# Uses a list from wikimedia.org and a local page (in ns MediaWiki)
$wgTitleBlacklistSources = array(
	array(// list of page titles which are blocked from creation/editing on Wikimedia wikis
		'type' => TBLSRC_URL,
		'src' => "http://meta.wikimedia.org/w/index.php?title=Title_blacklist&action=raw&tb_ver=1",
	),
	array(
		'type' => TBLSRC_LOCALPAGE,
		'src' => 'MediaWiki:Titleblacklist'
	)
);

# =[ EmailBlacklist ]=
require_once( "{$IP}/extensions/EmailBlacklist/EmailBlacklist.php" );

// Default bypass:
//   $wgGroupPermissions['sysop']['tboverride'] = true;
// Default caching params:
//   $wgTitleBlacklistCaching = array(
//	   'warningchance' => 100,
//	   'expiry' => 900,
//	   'warningexpiry' => 600,
//   );
// Warning messages can be customised via system messages:
//   MediaWiki:Titleblacklist-forbidden-edit: for page creation and editing,
//   MediaWiki:Titleblacklist-forbidden-move: for page moves,
//   MediaWiki:Titleblacklist-forbidden-upload: for image uploads.
//   MediaWiki:Titleblacklist-forbidden-new-account: for new accounts

# =[ AntiBot ]= 
# a simple framework for spambot checks and trigger payloads: copy the plugins you want into the active directory
require_once( "$IP/extensions/AntiBot/AntiBot.php" );
# activated AntiBot_GenericFormEncoding plugin

# =[ AbuseFilter ]= 
# * requires Extension:AntiSpoof
# * set specific controls on actions by users, such as edits, and create automated reactions for certain behaviors
require_once( "$IP/extensions/AbuseFilter/AbuseFilter.php" );
# autoconfirmed can view summaries, sysop can view/edit filters
$wgGroupPermissions['autoconfirmed']['abusefilter-view'] = true;
$wgGroupPermissions['autoconfirmed']['abusefilter-log'] = true;
$wgGroupPermissions['sysop']['abusefilter-modify'] = true;
$wgGroupPermissions['sysop']['abusefilter-log-detail'] = true;
$wgGroupPermissions['sysop']['abusefilter-private'] = true;
$wgGroupPermissions['sysop']['abusefilter-modify-restricted'] = true;
$wgGroupPermissions['sysop']['abusefilter-revert'] = true;
// Duration of blocks made by AbuseFilter
$wgAbuseFilterBlockDuration = '2 hours';

# =[NUKE! (BABOOM)]= 
require_once( "$IP/extensions/Nuke/Nuke.php" );



# ==================
#  PARSER FEATURES
# ==================

# =[Parser Functions]=
require_once( "$IP/extensions/ParserFunctions/ParserFunctions.php");

# =[Poem]=
require_once("$IP/extensions/Poem/Poem.php");

# =[CarZam (Carrousel)]=
require_once("$IP/extensions/CarZam/CarZam.php");

# =[WidgetsFramework]=
require_once( "$IP/extensions/WidgetsFramework/WidgetsFramework.php" );
$wgWFMKMaxWidth = 784;

# =[Description2]=
require_once( "$IP/extensions/Description2/Description2.php" );
$wgEnableMetaDescriptionFunctions = true;

# =[OpenGraphMeta]=
require_once( "$IP/extensions/OpenGraphMeta/OpenGraphMeta.php" );
# PATCH1 : The Parser function {{#setmainimage:...}} is broken, we removed this feature.
# Also see PATCH2 in OpenGraphMeta.php to take $wgLogo as default mainimage.

 
# Allow external images to be displayed as <img>
$wgAllowExternalImagesFrom = array($wgServer);

# InstantCommons allows wiki to use images from http://commons.wikimedia.org
$wgUseInstantCommons = false;

# If you have the appropriate support software installed you can enable inline LaTeX equations:
$wgUseTeX = false;



# ====================
#    MISCELLENAOUS
# ====================

# =[ TitleKey ]= with this, if you type "mypage" in the search box, you will be redirected to "MyPaGe"
require_once("$IP/extensions/TitleKey/TitleKey.php");

# =[ PreventDuplicate ]= Prevents creating article with the same titles, but different cases.
require_once("$IP/extensions/PreventDuplicate/PreventDuplicate.php");

# Avoid forcing the first letter of links to capitals
# Links appearing with a capital at the beginning of a sentence will not go to 
# the same place as links in the middle of a sentence using a lowercase initial; 
# typically the former has to become a piped link. 
# After setting this to false, run cleanupCaps.php to fix the existing links 
# that will be broken
# This affect all namespaces, except: Special, MediaWiki, User
$wgCapitalLinks = false; 
# $wgCapitalLinkOverrides[ NS_TEMPLATE ] = true;

# GAnalytics
require_once( "$IP/extensions/GAnalytics/GAnalytics.php" );
$wgGAnalyticsPropertyID = "UA-32666889-1";



# ====================
#    WIKIZAM CORE
# ====================

# =[ Transaction Manager ]=
require_once( "$IP/extensions/Transactions/Transactions.php" );

# =[ Seizam's Virtual Electronic Payment Terminal ]=
require_once( "$IP/extensions/ElectronicPayment/ElectronicPayment.php" );

# =[ Wikiplaces ]=
// needs to be installed after PreventDuplicate in order to display permission errors properly
require_once( "$IP/extensions/Wikiplaces/Wikiplaces.php" ); 

# =[ MySeizam ]=
// to be inclueded after the integrated extensions
require_once( "$IP/extensions/MySeizam/MySeizam.php" );