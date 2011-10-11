<?php

/**
 * @file
 * @ingroup ARCLibrary
 */
/*  Copyright 2010, ontoprise GmbH
*  This file is part of the LinkedData-Extension.
*
*   The ARCLibrary-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The ARCLibrary-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
 

/**
 * This file contains global functions that are called from the ARCLibrary
 * extension.
 *
 * @author Ingo Steinbauer
 *
 */
 
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the ARCLibrary extension. It is not a valid entry point.\n" );
}


/**
 * Switch on ARCLibrary extension. This function must be called in
 * LocalSettings.php and before other extensions that want to use the ARC
 * library are included. 
 * For readability, this is the only global function that does not adhere to the
 * naming conventions.
 *
 * This function installs the extension and sets up autoloading.
 */
function enableARCLibrary() {
    global $arclibgIP, $wgExtensionFunctions, $wgAutoloadClasses;

    $wgExtensionFunctions[] = 'arclibfSetupExtension';
    
    //Autoload the ARC library
	$wgAutoloadClasses['ARC2'] = $arclibgIP . '/libs/arc/ARC2.php';
	
	return true;
}

/**
 * Do the actual initialisation of the extension. This is just a delayed init that
 * makes sure MediaWiki is set up properly before we add our stuff.
 *
 * This method sets up the extension credits for the ARCLibrary extension.
 */
function arclibfSetupExtension() {
    wfProfileIn('arclibfSetupExtension');
    
    global $wgExtensionCredits, $wgVersion;

    //--- credits (see "Special:Version") ---
    $wgExtensionCredits['other'][]= array(
        'name'=>'ARCLibrary',
        'version'=>ARCLIB_ARCLIBRARY_VERSION,
        'author'=>"Benjamin Novack, Ingo Steinbauer",
        'url'=>'http://smwforum.ontoprise.com/smwforum/index.php/Help:ARCLibrary_extension',
        'description' => 'Provides ARC library to other MW extensions.');

    wfProfileOut('arclibfSetupExtension');
    
    return true;
}