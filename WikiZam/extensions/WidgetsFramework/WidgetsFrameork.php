<?php
/**
 * This extension is a simple framework for Widgets.
 * 
 * To install, put this in LocalSettings.php:
 *
 * # =[WidgetsFramework]=
 * require_once( "$IP/extensions/WidgetsFramework/WidgetsFramework.php" );
 *
 * And then copy the Widgets you want into the "Widgets" directory.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

/** Configuration */

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'WidgetsFramework',
    'author' => array('[http://www.seizam.com/User:Yannouk Yann Missler]'),
    'url' => 'http://www.seizam.com',
    'descriptionmsg' => 'widgetsframework-desc',
);

$wgExtensionMessagesFiles['WidgetsFramework'] =  dirname( __FILE__ ) . '/WidgetsFramework.i18n.php';

/**
 * A map of payload types to callbacks
 * This may be extended by plugins.
 */
$wgAntiBotPayloadTypes = array(
	'log' => array( 'AntiBot', 'log' ),
	'quiet' => array( 'AntiBot', 'quiet' ),
	'fail' => array( 'AntiBot', 'fail' ),
);

# Load plugins
foreach ( glob( dirname( __FILE__ ) . '/active/*.php' ) as $file ) {
	require( $file );
}

class AntiBot {
	static function getSecret( $name ) {
		global $wgAntiBotSecret, $wgSecretKey;
		$secret = $wgAntiBotSecret ? $wgAntiBotSecret : $wgSecretKey;
		return substr( sha1( $secret . $name ), 0, 8 );
	}

	/**
	 * Plugins should call this function when they are triggered
	 */
	static function trigger( $pluginName ) {
		global $wgAntiBotPayloads, $wgAntiBotPayloadTypes;
		$ret = 'quiet';
		if ( isset( $wgAntiBotPayloads[$pluginName] ) ) {
			$payloadChain = $wgAntiBotPayloads[$pluginName];
		} else {
			$payloadChain = $wgAntiBotPayloads['default'];
		}

		foreach ( $payloadChain as $payloadType ) {
			if ( !isset( $wgAntiBotPayloadTypes[$payloadType] ) ) {
				wfDebug( "Invalid payload type: $payloadType\n" );
				continue;
			}
			$ret = call_user_func( $wgAntiBotPayloadTypes[$payloadType], $pluginName );
		}
		return $ret;
	}

	static function log( $pluginName ) {
		global $wgRequest;
		$ip = wfGetIP();
		$action = $wgRequest->getVal( 'action', '<no action>' );
		$title = $wgRequest->getVal( 'title', '<no title>' );
		$text = $wgRequest->getVal( 'wpTextbox1' );
		if ( is_null( $text ) ) {
			$text = '<no text>';
		} else {
			if ( strlen( $text ) > 60 ) {
				$text = '"' . substr( $text, 0, 60 ) . '..."';
			} else {
				$text = "\"$text\"";
			}
		}
		$action = str_replace( "\n", '', $action );
		$title = str_replace( "\n", '', $title );
		$text = str_replace( "\n", '', $text );

		wfDebugLog( 'AntiBot', "$ip AntiBot plugin $pluginName hit: $action [[$title]] $text\n" );
	}

	static function quiet() {
		return 'quiet';
	}

	static function fail() {
		return 'fail';
	}
}
