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
 * English language labels for important HaloACL labels (namespaces, ,...).
 *
 * @author Facundo Ezequiel Grande / Thomas Schweitzer
 */
class HACLLanguageEs extends HACLLanguage {

	protected $mNamespaces = array(
		HACL_NS_ACL       => 'ACL',
		HACL_NS_ACL_TALK  => 'ACL_talk'
	);

	protected $mPermissionDeniedPage = "Permiso Denegado";
	
	protected $mPermissionDeniedPageContent = "No tiene permiso para realizar la accion requerida en esta pagina.\n\nVolver a [[Pagina Principal]].";
	
	protected $mParserFunctions = array(
		HACLLanguage::PF_ACCESS				=> 'acceso', 
		HACLLanguage::PF_MANAGE_RIGHTS		=> 'administrar permisos',
		HACLLanguage::PF_MANAGE_GROUP		=> 'administrar grupos',
		HACLLanguage::PF_PREDEFINED_RIGHT	=> 'permisos predefinidos',
		HACLLanguage::PF_PROPERTY_ACCESS	=> 'propiedades de acceso',
		HACLLanguage::PF_WHITELIST			=> 'lista blanca',
		HACLLanguage::PF_MEMBER				=> 'miembro'
	);
	
	protected $mParserFunctionsParameters = array(
		HACLLanguage::PFP_ASSIGNED_TO	=> 'asignado a', 
		HACLLanguage::PFP_ACTIONS		=> 'acciones', 
		HACLLanguage::PFP_DESCRIPTION	=> 'descripcion', 
		HACLLanguage::PFP_RIGHTS		=> 'permisos', 
		HACLLanguage::PFP_PAGES			=> 'paginas', 
		HACLLanguage::PFP_MEMBERS		=> 'miembros',
		HACLLanguage::PFP_NAME			=> 'nombre'		
	);
	
	protected $mActionNames = array(
		HACLRight::READ     => 'lectura',
		HACLRight::FORMEDIT => 'editar formato',
		HACLRight::WYSIWYG	=> 'wysiwyg',
		HACLRight::EDIT     => 'editar',
		HACLRight::CREATE   => 'crear',
		HACLRight::MOVE     => 'mover',
		HACLRight::ANNOTATE => 'anotar',
		HACLRight::DELETE   => 'borrar',
		HACLRight::ALL_ACTIONS => '*'
	);
	
	protected $mCategories = array(
		HACLLanguage::CAT_GROUP		=> 'Categoría:ACL/Grupo',
		HACLLanguage::CAT_RIGHT		=> 'Categoría:ACL/Permiso',
		HACLLanguage::CAT_SECURITY_DESCRIPTOR => 'Categoría:ACL/ACL',
	);
	
	protected $mWhitelist = "Lista Blanca";
	
	protected $mPetPrefixes = array(
		HACLSecurityDescriptor::PET_PAGE	  => 'Pagina',
		HACLSecurityDescriptor::PET_CATEGORY  => 'Categoría',
		HACLSecurityDescriptor::PET_NAMESPACE => 'Nombre de Espacio',
		HACLSecurityDescriptor::PET_PROPERTY  => 'Propiedad',
		HACLSecurityDescriptor::PET_RIGHT	  => 'Permiso'
	);
	
	protected $mSDTemplateName = "Template";
    protected $mPredefinedRightName = "Permiso";
    protected $mNamingConvention = array(
		HACLLanguage::NC_GROUP => 'Grupo'
	);
    
	protected $mLabelNSMain = "Principal";
	
}


