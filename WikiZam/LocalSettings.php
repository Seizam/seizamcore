<?php
# This file was automatically generated by the MediaWiki 1.17.0
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# http://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

# Include of ServerSetting.php where Server Side settings are stored
require_once("$IP/ServerSettings.php");

## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename      = "Seizam";
$wgMetaNamespace = "Project";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL
## Moved to ServerSettings.php
#$wgScriptPath       = "/WikiZam";
#$wgScriptExtension  = ".php";

## The relative URL path to the skins directory
$wgStylePath        = "$wgScriptPath/skins";

## The relative URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo             = "$wgStylePath/common/images/seizam.png";

## UPO means: this is also a user preference option

$wgEnableEmail      = true;
$wgEnableUserEmail  = true; # UPO

## Moved to ServerSettings.php
#$wgEmergencyContact = "apache@localhost";
#$wgPasswordSender   = "apache@localhost";

$wgEnotifUserTalk      = true; # UPO
$wgEnotifWatchlist     = true; # UPO
$wgEmailAuthentication = true;

/**
 * Password reminder name
 */
$wgPasswordSenderName = 'Seizam Mail';


## Database settings
## Moved to ServerSettings.php
#$wgDBtype           = "mysql";
#$wgDBserver         = "localhost";
#$wgDBname           = "wikizam";
#$wgDBuser           = "root";
#$wgDBpassword       = "root";

# MySQL specific settings
#$wgDBprefix         = "";

# MySQL table options to use during installation or update
$wgDBTableOptions   = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Experimental charset support for MySQL 4.1/5.0.
$wgDBmysql5 = false;

## Shared memory settings
$wgMainCacheType    = CACHE_ACCEL;
$wgMemCachedServers = array();

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads  = true;
$wgImgAuthPublicTest = false;
#$wgUseImageMagick = true;
#$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from http://commons.wikimedia.org
$wgUseInstantCommons  = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.UTF-8";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
#$wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX           = false;

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publically accessible from the web.
#$wgCacheDirectory = "$IP/cache";

# Site language code, should be one of ./languages/Language(.*).php
$wgLanguageCode = "en";

## Moved to ServerSettings.php
#$wgSecretKey = "a99b97286c3e606e27464d7df07c64faabffa80f3f0d73a71e5470b29be82e2c";

## Site upgrade key. Must be set to a string (default provided) to turn on the
## web installer while LocalSettings.php is in place
## Moved to ServerSettings.php
# $wgUpgradeKey = "1abd89e1c9307b07";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook', 'vector':
$wgDefaultSkin = "seizam";
# To remove various skins from the User Preferences choices
$wgSkipSkins = array("chick", "cologneblue", "nostalgia", "simple", "standard", "monobook","myskin","modern");
## Usability extension for Vector (base of Seizam)
require_once( "$IP/extensions/Vector/Vector.php" );
$wgVectorUseSimpleSearch = true;
# Vector extension (improve Vector skin) options
$wgDefaultUserOptions['useeditwarning'] = 1;
$wgVectorFeatures['collapsibletabs']['global'] = false;
$wgVectorFeatures['collapsiblenav']['global'] = false;
$wgVectorFeatures['footercleanup']['global'] = false;

## UI Elements extension for Seizam's skin
require_once( "$IP/extensions/Seizam/Seizam.php" );

$wgHiddenPrefs = array('userid','underline','stubthreshold','showtoc','showjumplinks','editsection','externaldiff','externaleditor','diffonly','norollbackdiff');


## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgEnableCreativeCommonsRdf = true;
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl  = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
$wgRightsText = "Creative Commons Attribution Non-Commercial Share Alike";
$wgRightsIcon = "{$wgStylePath}/common/images/cc-by-nc-sa.png";
# $wgRightsCode = ""; # Not yet used

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";


# Read WhiteList
$wgWhitelistRead = array("Main Page", "Special:UserLogin", "Special:UserLogout");


# Query string length limit for ResourceLoader. You should only set this if
# your web server has a query string length limit (then set it to that limit),
# or if you have suhosin.get.max_value_length set in php.ini (then set it to
# that value)
$wgResourceLoaderMaxQueryLength = 1024;


# End of automatically generated settings.
# Add more configuration options below.

# Development Settings (toggling debug msg on)
# Php error displayed
error_reporting( E_ALL | E_STRICT);
ini_set( 'display_errors', 1 );
# Stack trace displayed
$wgShowExceptionDetails = true;
# SQL error displayed
$wgShowSQLErrors = true;
$wgDebugDumpSql  = true;
# For Production, remember to log into file instead
# ResourceLoader Debug mode
$wgResourceLoaderDebug = false;
# End Developement Settings


# Language Selector (auto select user language and drop down menu)
require_once( "$IP/extensions/LanguageSelector/LanguageSelector.php" );
# Supported languages
$wgLanguageSelectorLanguages = array('en','fr');
# Method of language selection
$wgLanguageSelectorDetectLanguage = LANGUAGE_SELECTOR_PREFER_CLIENT_LANG; #Automatic selection regarding browser
# Where to put the language selection dropdown menu
$wgLanguageSelectorLocation = LANGUAGE_SELECTOR_IN_TOOLBOX;#In toolbow for Vector Skin, hard integrated for Seizam Skin

# Polyglot (auto select page version regarding user language)
require_once( "$IP/extensions/Polyglot/Polyglot.php" );
# Enable redirect on target page (eg. MainPage -> MainPage/fr -> Accueil)
$wfPolyglotFollowRedirects = true;

# SeizamACL (Access Control Lists Extension for Seizam)
require_once( "$IP/extensions/SeizamACL/SeizamACL.php" );

# AuthorProtect (dev)
require_once( "$IP/extensions/AuthorProtect/AuthorProtect.php" );

# Google Analytics
require_once( "$IP/extensions/googleAnalytics/googleAnalytics.php" );
$wgGoogleAnalyticsAccount = "UA-25393782-2";

# Account confirmation necessity for Beta version
require_once("$IP/extensions/ConfirmAccount/ConfirmAccount.php");
# Set the person's bio as their userpage?
$wgMakeUserPageFromBio = false;
# Make the username of the real name?
$wgUseRealNamesOnly = false;
# How many requests can an IP make at once?
$wgAccountRequestThrottle = 3;
# Minimum biography specs
$wgAccountRequestMinWords = 0;
# Show confirmation info fields
$wgAccountRequestExtraInfo = false;

# Contact Page
require_once( "$IP/extensions/ContactPage/ContactPage.php" );
$wgContactUser='WikiSysop';
$wgContactRequireAll=true;
