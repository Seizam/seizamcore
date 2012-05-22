<?php

class WikiplaceUpload {

	private static $USER_IS_WP_ADMIN = false;
	private static $FILE_PREFIXES = array(); // db_key => text form
	private static $FILE_PREFIXES_DEFAULT = null;
//	private static $FORCE_LICENSE = null;
	
	

	/*
	public static function setup() {
		$wgHooks['UploadForm:initial'][] = 'WikiplaceUpload::onUploadForminitial';
		$wgHooks['UploadFormSourceDescriptors'][] = 'WikiplaceUpload::onUploadFormSourceDescriptors';
		$wgHooks['UploadFormInitDescriptor'][] = 'WikiplaceUpload::onUploadFormInitDescriptor';
		$wgHooks['UploadCreateFromRequest'][] = 'WikiplaceUpload::onUploadCreateFromRequest';
		$wgHooks['UploadForm:BeforeProcessing'][] = 'WikiplaceUpload::onUploadFormBeforeProcessing';
		$wgHooks['UploadVerifyFile'][] = 'WikiplaceUpload::onUploadVerifyFile';
		$wgHooks['UploadVerification'][] = 'WikiplaceUpload::onUploadVerification';
		$wgHooks['UploadComplete'][] = 'WikiplaceUpload::onUploadComplete';
		$wgHooks['SpecialUploadComplete'][] = 'WikiplaceUpload::onSpecialUploadComplete';
	}
	*/
	
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

	/**
	 * Tell MediaWiki to use our file upload wrappers instead of default ones.
	 * When UploadBase::createFromRequest has been called.
	 * Used to change the class name of the UploadBase sub-class. By default, MediaWiki
	 * use "UploadFrom$type". 
	 * By default, MediaWiki 
	 * @param string $type the requested upload type (File or Stash or Url)
	 * @param string $className the class name of the Upload instance to be created
	 */
	public static function onUploadCreateFromRequest($type, &$className) {
		wfDebugLog( 'wikiplaces-upload', 'upload 0 ::onUploadCreateFromRequest('.$type.')');
		if ( $type == 'File' ) {
			$className = 'WpUploadFromFile';
		} elseif ( $type == 'Stash') {
			$className = 'WpUploadFromStash';	
		}
		return false; // ensure that no other hooks can override this
	}
	
	/**
	 * Store some informations about the user uploading (used by onUploadFormSourceDescriptors()
	 *  to generate a correct prefix listbox)
	 * Called just before the upload form is generated ( just before 
	 * $this->showUploadForm( $this->getUploadForm() call )
	 * @param SpecialPage $specialUploadObj current SpecialUpload page object
	 * @todo Maybe we can know wich user is uploading in onUploadFormSourceDescriptors()
	 * using a nicer way ?
	 */
	public static function onUploadFormInitial($specialUploadObj) {
		wfDebugLog( 'wikiplaces-upload', 'upload 1 ::onUploadFormInitial');
		self::fetchRequestInformations($specialUploadObj);
		return true;
	}
	
	/**
	 * Fetch informations about the user uploading, prepare prefixes list content, and
	 * set the default item to select.
	 * @param SpecialPage $specialUploadObj current SpecialUpload page object
	 * @todo Use constant to define public file license to force to
	 */
	public static function fetchRequestInformations($specialUploadObj) {

		$user = $specialUploadObj->getUser();
		self::$USER_IS_WP_ADMIN = $user->isAllowed(WP_ADMIN_RIGHT);

		if (self::$USER_IS_WP_ADMIN == true) {
			return; // admin uses standard upload form, so no informations to fetch
		}
		
		// search an argument called "wikiplace"
		$param = $specialUploadObj->getRequest()->getText('wikiplace', '');
		
		// public ?
		if ( ($param != null) && ($param === str_replace(' ', '_', WP_PUBLIC_FILE_PREFIX)) ) { // if wikiplace=public
			self::$FILE_PREFIXES[$param] = WP_PUBLIC_FILE_PREFIX;
			self::$FILE_PREFIXES_DEFAULT = $param;
//			self::$FORCE_LICENSE = 'seizam-public-file-license';
			return; // nothing else to do
		}

		// prepare prefixes list
		$wikiplaces = WpWikiplace::getAllOwnedByUserId($user->getId());
		foreach ($wikiplaces as $wikiplace) {
			$wpw_name = $wikiplace->get('name');

			// as $wikiplace->get('name') return the text form, we convert it as Title would does
			// ( str_replace as seen in Title.php line 302 )
			self::$FILE_PREFIXES[str_replace(' ', '_', $wpw_name)] = $wpw_name;
		}

		// set a default value ?
		if ( ($param != null) && array_key_exists($param, self::$FILE_PREFIXES) ) {
			self::$FILE_PREFIXES_DEFAULT = $param;
		}
	}

	/**
	 * Occurs after the standard source inputs have been added to the descriptor.
	 * @param array $descriptor The source section description of the UploadForm
	 */
	public static function onUploadFormSourceDescriptors( $descriptor ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 2 ::onUploadFormSourceDescriptors');
		
		return true;
	}
	
	/**
	 * Add a prefix listbox containing user's wikiplaces AND public ( or nothing if admin )
	 * Occurs after the descriptor for the upload form as been assembled.
	 * @param array $descriptor the HTMLForm descriptor 
	 * @todo re-develop properly the "force license" system
	 */
	public static function onUploadFormInitDescriptor( $descriptor ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 3 ::onUploadFormInitDescriptor');
		
		if ( self::$USER_IS_WP_ADMIN ) {
			return; // admin uses standard upload form
		}
		
		// build listbox
		$listbox = array(
			'type' => 'select',
			'section' => 'description',
			'id' => 'wpPrefix',
			'label-message' => 'wp-select-file-prefix',
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
			'label-message' => 'wp-destfilename-mainpart',
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
		// add our filename main part field
		$descriptor = array_merge( array ( 'DestFileNameMainPart' => $fileNameMainPart), $descriptor );
		
		// add the list box at the beginning of the descriptor ( before $fileNameMainPart )
		$descriptor = array_merge( array ( 'Prefix' => $listbox), $descriptor );
		

		return true;
	}
	
	/**
	 * HTMLform validator method, for the prefix listbox
	 * @param type $prefix
	 * @param type $allData
	 * @return boolean 
	 */
	public static function validateFilePrefix($prefix, $allData) {
		wfDebugLog( 'wikiplaces-upload', 'upload - ::validateFilePrefix('.$prefix.')');
		return array_key_exists($prefix, self::$FILE_PREFIXES);
	}

	/**
	 * Fetch informations about user uploading.
	 * Called just before the upload data, like wpUploadDescription, are processed, so extensions get a chance to manipulate them. 
	 * @param SpecialPage $specialUploadObj current SpecialUpload page object
	 * @todo if possible, onUploadFormInitial() and onUploadFormBeforeProcessing() has to be
	 * refactored to be only one function (they both do the same thing)
	 */
	public static function onUploadFormBeforeProcessing($specialUploadObj) {
		wfDebugLog( 'wikiplaces-upload', 'upload 4 ::onUploadFormBeforeProcessing');
		self::fetchRequestInformations($specialUploadObj);

		$name = self::getDestinationFileName($specialUploadObj->getRequest());
		if ($name != null) {	
			wfDebugLog( 'wikiplaces-upload', '$name='.$name);
			$specialUploadObj->mDesiredDestName = $name;
		}
		
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
	
	
	/**
	 *
	 * @param WebRequest $request
	 * @return mixed string or null if the prefix can't be read from request 
	 */
	public static function getDestinationFileName($request) {
		$prefix = $request->getText('wpPrefix');
		if (!$prefix) {
			wfDebugLog( 'wikiplaces-upload', 'no prefix');
			return null;
		}

		// chooses one of WpDestFileNameMainPart, wpUploadFile, filename in that order.
		$mainPart = $request->getText('wpDestFileNameMainPart', $request->getText('wpUploadFile', $request->getText('filename')));

		return $prefix . '.' . $mainPart;
	}

}


class WpUploadFromFile extends UploadFromFile {
	
	/**
	 *
	 * @param $request WebRequest
	 * @return type 
	 */
	function initializeFromRequest( &$request ) {
				
		$name = WikiplaceUpload::getDestinationFileName($request);
		if ($name == null) {
			// if no prefix, uses default behaviour (can be if the user is admin)
			return parent::initializeFromRequest($request);
		}

		$request->setVal('wpDestFile', $name);

		return parent::initializeFromRequest($request);
		
	}
	
}

class WpUploadFromStash extends UploadFromStash {

	public function initializeFromRequest(&$request) {

		$name = WikiplaceUpload::getDestinationFileName($request);
		if ($name == null) {
			// if no prefix, uses default behaviour (can be if the user is admin)
			return parent::initializeFromRequest($request);
		}

		$request->setVal('wpDestFile', $name);

		return parent::initializeFromRequest($request);

	}

}