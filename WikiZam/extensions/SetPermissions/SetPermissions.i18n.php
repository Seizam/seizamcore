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
	'setpermissions-desc'       => 'Based on AuthorProtect 1.2 extension, it allows the author of a page or file to set rights for other users',
    	'setpermissions-intro'      => 'Use this form to set this page read/write rights from non-authors',
    
	'setpermissions-notauthor'  => 'You are not the author of this page',
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
	'protect-level-author'     => 'Author only',
	'restriction-level-author' => 'Author-protected',
	'right-setpermissions'      => 'Set permissions',
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
	'setpermissions' => 'Protéger la page des non-auteurs',
	'setpermissions-desc' => 'Permet à l’auteur d’une page de la protéger des autres utilisateurs',
	'setpermissions-notauthor' => 'Vous n‘êtes pas l’auteur de cette page',
	'setpermissions-confirm' => 'Modifier le niveau de protection',
	'setpermissions-edit' => 'Restreindre l’édition aux auteurs',
	'setpermissions-move' => 'Restreindre le renommage aux auteurs',
	'setpermissions-expiry' => 'Expire :',
	'setpermissions-reason' => 'Motif :',
	'setpermissions-intro' => 'Utilisez ce formulaire pour verrouiller cette pages des non-auteurs',
	'setpermissions-success' => 'Protection réussie !',
	'setpermissions-failure' => 'Échec de la protection',
	'protect-level-author' => 'Protéger des non-auteurs',
	'restriction-level-author' => 'Auteur-protégé',
	'right-setpermissions' => 'Protéger la page des auteurs',
);


