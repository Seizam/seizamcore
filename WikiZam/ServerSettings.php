<?php
# Following settings are normally found in LocalSettings.
# To increase security and ease migration, server related settings have been moved here.
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

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs please see:
## http://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath       = "/WikiZam";
$wgScriptExtension  = ".php";

## Mail
$wgEmergencyContact = "apache@localhost";
$wgPasswordSender   = "apache@localhost";

## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "wikizam";
$wgDBuser           = "root";
$wgDBpassword       = "root";


?>
