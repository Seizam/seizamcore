<?php
/**
 * Internationalisation file for the AuthorProtect extension
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 @author Ryan Schmidt
 */
$messages['en'] = array(
	'setpermissions'            => 'Set permissions',
	'setpermissions-desc'       => 'Based on AuthorProtect v1.2 and AuthorRestriction v2.0 extensions, it allows the owner of a page or file to set rights for other users',
    'setpermissions-intro'      => 'Use this form to set this page read/write rights from non-owners',
    
	'setpermissions-notowner'	=> 'This page does\'nt belong to you.',
	'setpermissions-confirm'    => 'Change protection levels',
    
	'setpermissions-edit'       => 'No one else can edit (make it read only)',
	'setpermissions-read'       => 'No one else can read (make it private)',
    
	'setpermissions-move'       => 'No one else can move',
	'setpermissions-upload'     => 'No one else can upload',
	'setpermissions-create'     => 'No one else can create',
    
	'setpermissions-expiry'     => 'Expires:',
	'setpermissions-reason'     => 'Reason:',

	'setpermissions-success'    => 'Protection successful!',
	'setpermissions-failure'    => 'Protection unsuccessful',
	
	'right-setpermissions'      => 'Set permissions',
	
	'protect-level-owner'		=> 'Owner only',
	'restriction-level-owner'	=> 'Owner-protected',
	
	'protect-level-artists'		=> 'All artists',
	'restriction-level-artists'	=> 'Artists-protected',
	
	//'protect-level-autoconfirmed'		=> 'All autoconfirmed users',
	
	'setpermissions-everyone'	=> 'Everyone',
	'setpermissions-artists'	=> 'All artists',
	'setpermissions-owner-me'	=> 'Me',
	
	'setpermissions-whocan-read'	=> 'Who can read?',
	'setpermissions-whocan-edit'	=> 'Who can edit?',
	'setpermissions-whocan-move'	=> 'Who can move? (renaming)',
	
);

/** Message documentation (Message documentation)
 * @author Darth Kule
 * @author Purodha
 * @author Raymond
 */
$messages['qqq'] = array(
	'setpermissions-desc' => 'Extension description displayed on [[Special:Version]].',
	'setpermissions-expiry' => '{{Identical/Expires}}',
	'setpermissions-reason' => '{{Identical|Reason}}',
	'right-setpermissions' => '{{doc-right|setpermissions}}',
);

/** French (Français)
 * @author Crochet.david
 * @author Grondin
 * @author Peter17
 */
$messages['fr'] = array(
	'setpermissions'			=> 'Protéger la page des non-propriétaires',
	'setpermissions-desc'		=> 'Permet à l’auteur d’une page de la protéger des autres utilisateurs',
	'setpermissions-notowner'	=> 'Cette page ne vous appartient pas.',
	'setpermissions-confirm'	=> 'Modifier le niveau de protection',
	'setpermissions-edit'		=> 'Restreindre l’édition aux auteurs',
	'setpermissions-move'		=> 'Restreindre le renommage aux auteurs',
	'setpermissions-expiry'		=> 'Expire :',
	'setpermissions-reason'		=> 'Motif :',
	'setpermissions-intro'		=> 'Utilisez ce formulaire pour verrouiller cette pages des non-auteurs',
	'setpermissions-success'	=> 'Protection réussie !',
	'setpermissions-failure'	=> 'Échec de la protection',
	'protect-level-owner'		=> 'Protéger des non-propriétaires',
	'restriction-level-owner'	=> 'Propirétaire-protégé',
	'right-setpermissions'		=> 'Protéger la page des auteurs',
);


