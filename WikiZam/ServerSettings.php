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
$wgScriptPath       = "/base";
$wgScriptExtension  = ".php";

## Mail
$wgEmergencyContact = "sysadmin@seizam.com";
$wgPasswordSender   = "serveur@seizam.com";

## Database settings
$wgDBtype           = "mysql";
$wgDBserver         = "localhost";
$wgDBname           = "wikidb";
$wgDBuser           = "seizam";
$wgDBpassword       = "seizam";

# MySQL specific settings
$wgDBprefix         = "";

$wgSecretKey = "1691a396b4ac35347d303a87d05249689c337de143015734992536e885ed0bce";

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
