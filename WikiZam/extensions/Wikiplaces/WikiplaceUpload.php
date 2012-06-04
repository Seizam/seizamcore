<?php

class WikiplaceUpload {

	private static $USER_IS_WP_ADMIN = false;
	private static $FILE_PREFIXES = array(); // listbox value => listbox caption
	private static $FILE_PREFIXES_DEFAULT = false;
	private static $FILE_MAIN_PART_DEFAULT = '';
	private static $WPDESTFILE_READONLY = false;
	// set to true if handler has been succesfully installed
	// otherwise, it doesn't alter the original upload form in
	// order to have upload correctly working
	private static $DISPLAY_UPLOAD_MOD = false;

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

		self::$DISPLAY_UPLOAD_MOD = true;

		if ($type == 'File') {

			$className = 'WpUploadFromFile';
		} elseif ($type == 'Stash') {

			$className = 'WpUploadFromStash';
		} elseif ($type == 'Url') {

			$className = 'WpUploadFromUrl';
		} else {

			wfDebugLog('wikiplaces', 'installWikiplaceUploadHandler(' . $type . '), WARNING, unrecognized upload type, standard upload form will be used');
			self::$DISPLAY_UPLOAD_MOD = false;
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
	 * @todo if the user cannot upload a new file, maybe this function should
	 * however return true, as seen in SpecialUpload page code comments (but if
	 * true returned, the form is displayed with our error message on top)
	 */
	public static function fetchRequestInformations($specialUploadObj) {

		$user = $specialUploadObj->getUser();
		self::$USER_IS_WP_ADMIN = $user->isAllowed(WP_ADMIN_RIGHT);

		if (self::$USER_IS_WP_ADMIN || !self::$DISPLAY_UPLOAD_MOD) {
			return true; // no informations to fetch and nothing to prepare, using standard form
		}

		$full_title = $specialUploadObj->getFullTitle();
		$request = $specialUploadObj->getRequest();

		// is the user re uploading a new version of an existing file or followed a "upload a file with this name" link ?
		if ($request->getText('wpDestFile') && !$request->getText('wpDestFileMainPart')) {

			// ensure that user can, but we don't need explanation if the file can't be uploaded
			if (!WikiplacesHooks::wikiplaceUserCanCreate(Title::makeTitle(NS_FILE, $request->getText('wpDestFile')), $user)) {
				$specialUploadObj->getOutput()->showErrorPage('sorry', wfMessage('wp-invalid-request'));
				return false; // break SpecialUpload page init/processing
			}

			// she is reuploading or has followed a "upload a file with this name" link
			wfDebugLog('wikiplaces-debug', 'fetchRequestInformations: reuploading, so disabling mod');
			self::$WPDESTFILE_READONLY = true; // ensure that the filename field is readonly when create link followed
			self::$DISPLAY_UPLOAD_MOD = false;
			return true; // no informations to fetch and nothing to prepare
		}
		
		// update special page DestFileName attribute
		$final_wp_filename = self::getDestinationFileName($request);
		if ($final_wp_filename != null) {
			$specialUploadObj->mDesiredDestName = $final_wp_filename;
			wfDebugLog('wikiplaces-debug', 'fetchRequestInformations, mDesiredDestName set to "' . $final_wp_filename . '"');
		}

		// ( if we arrive here, we are uploading a new file )
		// is there a wikiplace specified in the url ?
		// search a GET parameter, as seen in SpecialPageFactory around line 408
		$db_key = $full_title->getDBkey();
		$bits = explode('/', $db_key, 2);
		$param = null;
		if (isset($bits[1])) {
			$param = $bits[1];
		}

		// is the user trying to upload a public file ?
		$final_wp_file_title = Title::newFromText($final_wp_filename, NS_FILE);
		if ( ($param === WP_PUBLIC_FILE_PREFIX) || ( ( $final_wp_file_title instanceof Title ) && WpPage::isPublic(NS_FILE, $final_wp_file_title->getDBkey() ) ) )  {

			// there is a "Public" param, there will be only one choice in the listbox
			wfDebugLog('wikiplaces-debug', 'fetchRequestInformations: only public prefix will be visible');
			self::$FILE_PREFIXES[$param] = WP_PUBLIC_FILE_PREFIX;
			self::$FILE_PREFIXES_DEFAULT = $param;
		} else {

			// can the user upload a new file with her subscription ?
			if (( $reason = WpSubscription::userCanUploadNewFile($user->getId())) !== true) {
				$specialUploadObj->getOutput()->showErrorPage('sorry', wfMessage($reason));  // no active subscription or quotas exceeded 
				return false; // break SpecialUpload page init/processing
			}

			// check if the user has at least one wikiplace
			$wikiplaces = WpWikiplace::factoryAllOwnedByUserId($user->getId());
			if (count($wikiplaces) == 0) {
				$specialUploadObj->getOutput()->showErrorPage('sorry', wfMessage('wp-create-wp-first'));
				return false; // break SpecialUpload page init/processing
			}

			// multiple choice: prepare full prefixes list
			foreach ($wikiplaces as $wikiplace) {
				$wpw_name = $wikiplace->getName();
				self::$FILE_PREFIXES[$wpw_name] = $wpw_name;
			}

			// do we have to set a default value ?
			if (($param != null) && array_key_exists($param, self::$FILE_PREFIXES)) {
				if (!self::$FILE_PREFIXES_DEFAULT) {
					self::$FILE_PREFIXES_DEFAULT = $param;
				}
			}
		}

		return true; // continue hook processing
	}

	/**
	 * Add a prefix listbox containing prefixeslist if required
	 * Occurs after the descriptor for the upload form as been assembled.
	 * @param array $descriptor the HTMLForm descriptor 
	 * @todo re-develop properly the "force license" system
	 */
	public static function installWikiplaceUploadFrontend($descriptor) {

		// set original filename field as readonly if needed
		if (self::$WPDESTFILE_READONLY) {
			$descriptor['DestFile']['readonly'] = true;
		}

		if (self::$USER_IS_WP_ADMIN || !self::$DISPLAY_UPLOAD_MOD) {
			wfDebugLog('wikiplaces-debug', 'installWikiplaceUploadFrontend, upload form will not be changed');
			return true;
		}

		// build listbox
		$prefixes = array(
			'type' => 'select',
			'section' => 'description',
			'id' => 'wpDestFilePrefix',
			'label-message' => 'wp-wikiplace-field',
			'validation-callback' => array(__CLASS__, 'validateFilePrefix'),
			'options' => array(),
		);

		// add prefixes in the listbox, with value in MediaWiki db_key format and caption in text format
		foreach (self::$FILE_PREFIXES as $backend_value => $text) {
			$prefixes['options'][$text] = $backend_value;
		}

		// set default value if needed
		if (self::$FILE_PREFIXES_DEFAULT != null) {
			$prefixes['default'] = self::$FILE_PREFIXES_DEFAULT;
		}

		// build filename main part field
		$main_part = array(
			'type' => 'text',
			'section' => 'description',
			'id' => 'wpDestFile', // same ID as old field to keep JS operating on our new field
			'label-message' => 'wp-name-field',
			'size' => 60,
			'default' => '',
			'nodata' => false,
		);

		// hide original filename field
		$descriptor['DestFile']['id'] = 'oldWpDestFile'; // change ID in order to move its JS magic to our field
		$descriptor['DestFile']['type'] = 'hidden'; // hide it
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
		$descriptor = array_merge($head, array(
			'DestFilePrefix' => $prefixes,
			'DestFileMainPart' => $main_part), $tail);

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
		$mainPart = $request->getText('wpDestFileMainPart', $request->getText('wpDestFile', $request->getText('wpUploadFile', $request->getText('filename'))));

		$prefix = $request->getText('wpDestFilePrefix');

		return ( $prefix ? $prefix . '.' . $mainPart : $mainPart );
	}

}

class WpUploadFromFile extends UploadFromFile {

	function initializeFromRequest(&$request) {
		$upload = $request->getUpload('wpUploadFile');
		$desiredDestName = WikiplaceUpload::getDestinationFileName($request);
		if (!$desiredDestName) {
			$desiredDestName = $upload->getName();
		}
		return $this->initialize($desiredDestName, $upload);
	}

}

class WpUploadFromStash extends UploadFromStash {

	public function initializeFromRequest(&$request) {
		$fileKey = $request->getText('wpFileKey', $request->getText('wpSessionKey'));
		$desiredDestName = WikiplaceUpload::getDestinationFileName($request);
		return $this->initialize($fileKey, $desiredDestName);
	}

}

class WpUploadFromUrl extends UploadFromUrl {

	public function initializeFromRequest(&$request) {
		$desiredDestName = WikiplaceUpload::getDestinationFileName($request);
		if (!$desiredDestName)
			$desiredDestName = $request->getText('wpUploadFileURL');
		return $this->initialize(
						$desiredDestName, trim($request->getVal('wpUploadFileURL')), false
		);
	}

}