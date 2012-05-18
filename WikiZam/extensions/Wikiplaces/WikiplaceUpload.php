<?php

class WikiplaceUpload {

	
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
	
	// hooks
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
	 * When UploadBase::createFromRequest has been called.
	 * Used to change the class name of the UploadBase sub-class. By default, MediaWiki
	 * use "UploadFrom$type". 
	 * By default, MediaWiki 
	 * @param string $type the requested upload type (File or Stash or Url)
	 * @param string $className the class name of the Upload instance to be created
	 */
	public static function onUploadCreateFromRequest($type, &$className) {
		wfDebugLog( 'wikiplaces-upload', 'upload 0 ::onUploadCreateFromRequest');
		return true;
	}
	
	/**
	 * Called just before the upload form is generated
	 * ( just before $this->showUploadForm( $this->getUploadForm() call )
	 * @param HTMLForm $uploadFormObj current UploadForm object
	 */
	public static function onUploadFormInitial($uploadFormObj) {
		wfDebugLog( 'wikiplaces-upload', 'upload 1 ::onUploadFormInitial');
		return true;
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
	 * Occurs after the descriptor for the upload form as been assembled.
	 * @param array $descriptor the HTMLForm descriptor 
	 */
	public static function onUploadFormInitDescriptor( $descriptor ) {
		wfDebugLog( 'wikiplaces-upload', 'upload 3 ::onUploadFormInitDescriptor');
		return true;
	}

	/**
	 * Called just before the upload data, like wpUploadDescription, are processed, so extensions get a chance to manipulate them. 
	 * @param HTMLForm $uploadFormObj current UploadForm object
	 */
	public static function onUploadFormBeforeProcessing($uploadFormObj) {
		wfDebugLog( 'wikiplaces-upload', 'upload 4 ::onUploadFormBeforeProcessing');
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

}