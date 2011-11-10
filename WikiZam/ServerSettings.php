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
$wgEmergencyContact = "sysadmin@seizam.com";
$wgPasswordSender   = "serveur@seizam.com";

## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "wikizam";
$wgDBuser           = "root";
$wgDBpassword       = "root";

# MySQL specific settings
$wgDBprefix         = "";

$wgSecretKey = "a99b97286c3e606e27464d7df07c64faabffa80f3f0d73a71e5470b29be82e2c";

$wgUpgradeKey = "1abd89e1c9307b07";

$wgSMTP = array(
        'host' => 'ssl://smtp.gmail.com',
        'IDHost' => 'mydomain.com',
        'port' => 465,
        'username' => 'serveur@seizam.com', ## or info@mydomain.com, or whatever email account you've set up for your Mediawiki installation
        'password' => 'mv74KLp1',
        'auth' => true
     );


?>
