<?php

class WikiplaceUpload {

	private static $USER_IS_WP_ADMIN = false;
	private static $FILE_PREFIXES = array(); // db_key => text form
	private static $FILE_PREFIXES_DEFAULT = null;
//	private static $FORCE_LICENSE = null;
	
	// set to true if handler has been succesfully installed
	// otherwise, it doesn't alter the original upload form in
	// order to have upload correctly working
	private static $WIKIPLACE_UPLOAD_INSTALLED = false; 

	/**
	 * Tell MediaWiki to use our file upload handler (wrapper) instead of default ones.
	 * When UploadBase::createFromRequest has been called.
	 * Used to change the class name of the UploadBase sub-class. By default, MediaWiki
	 * use "UploadFrom$type". 
	 * By default, MediaWiki 
	 * @param string $type the requested upload type (File or Stash or Url)
	 * @param string $className the class name of the Upload instance to be created
	 */
	public static function installWikiplaceUploadHandler($type, &$className) {
		
		self::$WIKIPLACE_UPLOAD_INSTALLED = true;
		
		if ( $type == 'File' ) {
			
			$className = 'WpUploadFromFile';
			
		} elseif ( $type == 'Stash') {
			
			$className = 'WpUploadFromStash';	
			
		} elseif ( $type == 'Url') {
			
			$className = 'WpUploadFromUrl';	
			
		} else {
			
			wfDebugLog( 'wikiplaces-upload', 'installWikiplaceUploadHandler('.$type.'), WARNING, unrecognized upload type, standard upload form will be used');
			self::$WIKIPLACE_UPLOAD_INSTALLED = false;
			
		}
		
		return false; // ensure that no other hooks can override this
		
	}

	/**
	 * Fetch informations about the user uploading, prepare prefixes list content, and
	 * set the default item to select. It also updates the special page DestFileName 
	 * attribute. Attached to hooks:
	 * <ul>
	 * <li>$wgHooks['UploadForm:initial']</li>
	 * <li>$wgHooks['UploadForm:BeforeProcessing']</li>
	 * </ul>
	 * @param SpecialPage $specialUploadObj current SpecialUpload page object
	 * @todo Maybe we can know wich user is uploading in onUploadFormSourceDescriptors()
	 * using a nicer way ?
	 */
	public static function fetchRequestInformations($specialUploadObj) {

		$user = $specialUploadObj->getUser();
		self::$USER_IS_WP_ADMIN = $user->isAllowed(WP_ADMIN_RIGHT);

		if ( self::$USER_IS_WP_ADMIN || !self::$WIKIPLACE_UPLOAD_INSTALLED ) {
			return true; // no informations to fetch and nothing to prepare
		}
		
		$request =  $specialUploadObj->getRequest();
		
		// search an argument, as seen in SpecialPageFactory around line 408
		$db_key = $specialUploadObj->getFullTitle()->getDBkey();
		$bits = explode( '/', $db_key, 2 );
		$param = null;
		if ( isset( $bits[1] ) ) { 
			$param = $bits[1];
		}
		
		// is there a param wikiplace=public ?
		if ( $param === str_replace(' ', '_', WP_PUBLIC_FILE_PREFIX) )  { 
			
			// if public, there is only one choice in the listbox
			wfDebugLog( 'wikiplaces-upload', 'fetchRequestInformations: only public prefix will be visible');
			self::$FILE_PREFIXES[$param] = WP_PUBLIC_FILE_PREFIX;
			self::$FILE_PREFIXES_DEFAULT = $param;

		} else {

			// prepare prefixes list
			$wikiplaces = WpWikiplace::getAllOwnedByUserId($user->getId());
			foreach ($wikiplaces as $wikiplace) {
				$wpw_name = $wikiplace->get('name');

				// as $wikiplace->get('name') return the text form, we convert it as Title would does
				// ( str_replace as seen in Title.php line 302 )
				self::$FILE_PREFIXES[str_replace(' ', '_', $wpw_name)] = $wpw_name;
			}

			// do we have to set a default value ?
			if (($param != null) && array_key_exists($param, self::$FILE_PREFIXES)) {
				self::$FILE_PREFIXES_DEFAULT = $param;
			}
			
		}
		
		// update special page DestFileName attribute
		$name = self::getDestinationFileName($request);
		if ($name != null) {	
			$specialUploadObj->mDesiredDestName = $name;
			wfDebugLog( 'wikiplaces-upload', 'fetchRequestInformations, mDesiredDestName set to "'.$name.'"');
		}
		
		return true; // continue hook processing
	}
	
	/**
	 * Add a prefix listbox containing prefixeslist if required
	 * Occurs after the descriptor for the upload form as been assembled.
	 * @param array $descriptor the HTMLForm descriptor 
	 * @todo re-develop properly the "force license" system
	 */
	public static function installWikiplaceUploadFrontend( $descriptor ) {
		
		if ( self::$USER_IS_WP_ADMIN || !self::$WIKIPLACE_UPLOAD_INSTALLED) {
			wfDebugLog( 'wikiplaces-upload', 'installWikiplaceUploadFrontend, upload form will not be changed');
			return true; 
		}
		
		// build listbox
		$listbox = array(
			'type' => 'select',
			'section' => 'description',
			'id' => 'wpDestFilePrefix',
			'label-message' => 'wp-destfile-prefix',
			'validation-callback' => array(__CLASS__, 'validateFilePrefix'),
			'options' => array(),
		);

		// add prefixes in the listbox, with value in MediaWiki db_key format and caption in text format
		foreach (self::$FILE_PREFIXES as $db_key => $text) {
			$listbox['options'][$text] = $db_key;
		}
		
		// set default value if needed
		if ( self::$FILE_PREFIXES_DEFAULT != null ) {
			$listbox['default'] = self::$FILE_PREFIXES_DEFAULT ;
		}

		// build filename main part field
		$fileNameMainPart = array(
			'type' => 'text',
			'section' => 'description',
			'id' => 'wpDestFile', // same ID as old field to keep JS operating on our new field
			'label-message' => 'wp-destfile-mainpart',
			'size' => 60,
			'default' => '',
			'nodata' => false,
		);
		
		// hide original filename field
		$descriptor['DestFile']['id'] = 'oldWpDestFile'; // change ID in order to move its JS magic to our field
		$descriptor['DestFile']['type'] = 'hidden'; // hide it

		// force license if needed
/*		if ( self::$FORCE_LICENSE != null ) {
			// hide original license field, because it's populated by ajax, so it can't been forced from here
			$descriptor['License']['type'] = 'hidden'; // hide it
			
		}
*/

		// add the list box and filename main part field 
		$counter = 1;
		foreach ($descriptor as $key => $s) {
			if ($key == 'DestFile') {
				break;
			}
			$counter++;
		}
		$head = array_slice($descriptor, 0, $counter);
		$tail = array_slice($descriptor, $counter);
		$descriptor = array_merge( $head, array ( 
			'DestFilePrefix' => $listbox,
			'DestFileMainPart' => $fileNameMainPart ), $tail );

		return true;
	}
	
	/**
	 * HTMLform validator method, for the prefix listbox
	 * @param type $prefix
	 * @param type $allData
	 * @return boolean 
	 */
	public static function validateFilePrefix($prefix, $allData) {
		return array_key_exists($prefix, self::$FILE_PREFIXES);
	}

	
	/**
	 * Try to concat prefix + mainPart, or return the main part if prefix field not available
	 * @param WebRequest $request
	 * @return mixed string, or '' if both prefix and mainPart are not available from request object
	 */
	public static function getDestinationFileName($request) {

		// chooses one of WpDestFileNameMainPart, wpUploadFile, filename in that order.
		$mainPart = $request->getText('wpDestFileMainPart', 
				$request->getText('wpDestFile', 
						$request->getText('wpUploadFile',
								$request->getText('filename'))));

		$prefix = $request->getText('wpDestFilePrefix');
		
		return ( $prefix ? $prefix.'.'.$mainPart : $mainPart );
		
	}
	
	
	// not used hooks
	
/*	
$wgHooks['UploadFormSourceDescriptors'][] = 'WikiplaceUpload::onUploadFormSourceDescriptors';
$wgHooks['UploadVerifyFile'][] = 'WikiplaceUpload::onUploadVerifyFile';
$wgHooks['UploadVerification'][] = 'WikiplaceUpload::onUploadVerification';
$wgHooks['UploadComplete'][] = 'WikiplaceUpload::onUploadComplete';
$wgHooks['SpecialUploadComplete'][] = 'WikiplaceUpload::onSpecialUploadComplete';
*/
	/**
	 * Occurs after the standard source inputs have been added to the descriptor.
	 * @param array $descriptor The source section description of the UploadForm
	 */
	public static function onUploadFormSourceDescriptors( $descriptor ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 2 ::onUploadFormSourceDescriptors');
		return true;
	}
	
	/**
	 * Called when a file is uploaded, to allow extra file verification to take place
	 * @param UploadBase $upload an instance of UploadBase, with all info about the upload
	 * @param type $mime the uploaded file's mime type, as detected by MediaWiki. Handlers will typically only apply for specific mime types.
	 * @param mixed $error output: true if the file is valid. Otherwise, and indexed array representing the problem with the file, where the first element is the message key and the remaining elements are used as parameters to the message.
	 */
	public static function onUploadVerifyFile( $upload, $mime, &$error ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 5 ::onUploadVerifyFile');
		return true;
	}
	
	/**
	 * Called when a file is uploaded, to allow extra file verification to take place
	 * @param string $saveName destination file name
	 * @param string $tempName filesystem path to the temporary file for checks
	 * @param string $error output: HTML error to show if upload canceled by returning false
	 */
	public static function onUploadVerification( $saveName, $tempName, &$error ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 6 ::onUploadVerification');
		return true;
	}

	/**
	 * Called when a file upload has succesfully completed.
	 * @param UploadForm $form UploadForm object
	 */
	public static function onUploadComplete( &$form ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 7 ::onUploadComplete');
		return true;
	}
	
	/**
	 * Called after successfully uploading a file from Special:Upload
	 * @param HTMLForm $form The UploadForm object 
	 */
	public static function onSpecialUploadComplete( $form ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 8 ::onSpecialUploadComplete');
		return true;
	}
	
		// hooks call trace
	
	/*
	2012-05-18 10:38:33  wikidb: upload 0 ::onUploadCreateFromRequest
	2012-05-18 10:38:33  wikidb: upload 1 ::onUploadFormInitial
	2012-05-18 10:38:33  wikidb: upload 2 ::onUploadFormSourceDescriptors
	2012-05-18 10:38:33  wikidb: upload 3 ::onUploadFormInitDescriptor
	 * 
	2012-05-18 10:38:53  wikidb: upload 0 ::onUploadCreateFromRequest
	2012-05-18 10:38:53  wikidb: upload 4 ::onUploadFormBeforeProcessing
	2012-05-18 10:38:53  wikidb: upload 5 ::onUploadVerifyFile
	2012-05-18 10:38:53  wikidb: upload 6 ::onUploadVerification
	2012-05-18 10:38:54  wikidb: upload 7 ::onUploadComplete
	2012-05-18 10:38:54  wikidb: upload 8 ::onSpecialUploadComplete
	 */

}


class WpUploadFromFile extends UploadFromFile {
	
	function initializeFromRequest( &$request ) {				
		$upload = $request->getUpload( 'wpUploadFile' );		
		$desiredDestName = WikiplaceUpload::getDestinationFileName($request);
		if( !$desiredDestName ) {
			$desiredDestName = $upload->getName();
		}
		return $this->initialize( $desiredDestName, $upload );
	}
	
}

class WpUploadFromStash extends UploadFromStash {

	public function initializeFromRequest(&$request) {
		$fileKey = $request->getText( 'wpFileKey', $request->getText( 'wpSessionKey' ) );
		$desiredDestName = WikiplaceUpload::getDestinationFileName($request);
		return $this->initialize( $fileKey, $desiredDestName );
	}

}

class  WpUploadFromUrl extends UploadFromUrl {
	
	public function initializeFromRequest( &$request ) {
		$desiredDestName = WikiplaceUpload::getDestinationFileName($request);
		if ( !$desiredDestName )
			$desiredDestName = $request->getText( 'wpUploadFileURL' );
		return $this->initialize(
			$desiredDestName,
			trim( $request->getVal( 'wpUploadFileURL' ) ),
			false
		);
	}
	
}