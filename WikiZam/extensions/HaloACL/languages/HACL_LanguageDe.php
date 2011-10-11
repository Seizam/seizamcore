<?php
/**
 * @file
 * @ingroup HaloACL_Language
 */

/*  Copyright 2009, ontoprise GmbH
*  This file is part of the HaloACL-Extension.
*
*   The HaloACL-Extension is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 3 of the License, or
*   (at your option) any later version.
*
*   The HaloACL-Extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $haclgIP;
include_once($haclgIP . '/languages/HACL_Language.php');
include_once($haclgIP . '/includes/HACL_Right.php');
include_once($haclgIP . '/includes/HACL_SecurityDescriptor.php');


/**
 * German language labels for important HaloACL labels (namespaces, ,...).
 *
 * @author Thomas Schweitzer
 */
class HACLLanguageDe extends HACLLanguage {

	protected $mNamespaces = array(
		HACL_NS_ACL       => 'Rechte',
		HACL_NS_ACL_TALK  => 'Rechte_Diskussion'
	);
	
	protected $mPermissionDeniedPage = "Zugriff verweigert";
	protected $mPermissionDeniedPageContent = "Sie dürfen die gewünschte Aktion auf dieser Seite nicht durchführen.\n\nZurück zur [[Hauptseite]].";
	
	protected $mParserFunctions = array(
		HACLLanguage::PF_ACCESS				=> 'Zugriff', 
		HACLLanguage::PF_MANAGE_RIGHTS		=> 'Rechte verwalten',
		HACLLanguage::PF_MANAGE_GROUP		=> 'Gruppe verwalten',
		HACLLanguage::PF_PREDEFINED_RIGHT	=> 'vordefiniertes Recht',
		HACLLanguage::PF_PROPERTY_ACCESS	=> 'Attributzugriff',
		HACLLanguage::PF_WHITELIST			=> 'Whitelist',
		HACLLanguage::PF_MEMBER				=> 'Mitglied'
	);

	protected $mParserFunctionsParameters = array(
		HACLLanguage::PFP_ASSIGNED_TO	=> 'zugewiesen', 
		HACLLanguage::PFP_ACTIONS		=> 'Aktionen', 
		HACLLanguage::PFP_DESCRIPTION	=> 'Beschreibung', 
		HACLLanguage::PFP_RIGHTS		=> 'Rechte', 
		HACLLanguage::PFP_PAGES			=> 'Seiten', 
		HACLLanguage::PFP_MEMBERS		=> 'Mitglieder', 
		HACLLanguage::PFP_NAME			=> 'Name', 
	);
	
	protected $mActionNames = array(
		HACLRight::READ     => 'lesen',
		HACLRight::FORMEDIT => 'formulareditieren',
		HACLRight::WYSIWYG  => 'wysiwyg',
		HACLRight::EDIT     => 'editieren',
		HACLRight::CREATE   => 'erzeugen',
		HACLRight::MOVE     => 'verschieben',
		HACLRight::ANNOTATE => 'annotieren',
		HACLRight::DELETE   => 'löschen',
		HACLRight::ALL_ACTIONS => '*'
	);
	
	protected $mCategories = array(
		HACLLanguage::CAT_GROUP		=> 'Kategorie:Rechte/Gruppe',
		HACLLanguage::CAT_RIGHT		=> 'Kategorie:Rechte/Recht',
		HACLLanguage::CAT_SECURITY_DESCRIPTOR => 'Kategorie:Rechte/Sicherheitsbeschreibung'
	);
	
	protected $mWhitelist = "Positivliste";
	
	protected $mPetPrefixes = array(
		HACLSecurityDescriptor::PET_PAGE	  => 'Seite',
		HACLSecurityDescriptor::PET_CATEGORY  => 'Kategorie',
		HACLSecurityDescriptor::PET_NAMESPACE => 'Namensraum',
		HACLSecurityDescriptor::PET_PROPERTY  => 'Attribut',
		HACLSecurityDescriptor::PET_RIGHT	  => 'Recht'
	);
	
	protected $mSDTemplateName = "Vorlage";
	protected $mPredefinedRightName = "Recht";
	
	protected $mNamingConvention = array(
		HACLLanguage::NC_GROUP => 'Gruppe'
	);
	
	protected $mLabelNSMain = "Hauptnamensraum";
	
}


