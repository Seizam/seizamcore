<?php

/**
 * Internationalisation file for the ProtectOwn extension
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();

/** English
 */
$messages['en'] = array(
    'po-desc' => 'Enable protection/openness options for the owner of a resource',
    'protectown' => 'Protect Own',
    'collaborate' => 'Collaborate',
    'collaborate-title' => 'Change collaboration level for "$1"',
    'po-success' => 'Collaboration has been successfully set!',
    'po-failure' => 'Collaboration failed. {{int:sz-report}}',
    'po-locked' => 'Collaboration locked. {{int:sz-report}}',
    'po-notowner' => 'This page does not belong to you. {{int:sz-report}}',
    'po-submit' => 'Setup Collaboration',
    'po-header' => "<span style=\"float:right\">See <b>[[Help:Collaboration|Help]]</b> | Manage <b>[[Special:WikiPlaces/Members:$2|Members]]</b></span>You can change the collaboration level for the page '''$1''' here.",
    'po-legend' => 'Collaborate',
    'po-whocan-read' => 'Who can read?',
    'po-help-read' => 'Click one radio button to select the group of user allowed to read this page/this file. Eg: Select "<b>{{int:po-owner}}</b>" to forbid access to everyone.',
    'po-whocan-edit' => 'Who can edit?',
    'po-help-edit' => 'Click one radio button to select the group of user allowed to edit this page/this file. Eg: Select "<b>{{int:po-everyone}}</b>" to allow the entire world.',
    'po-whocan-upload' => 'Who can upload?',
    'po-help-upload' => 'Click one radio button to select the group of user allowed to re-upload this file. Eg: Select "<b>{{int:po-artist}}</b>" to allow Seizam subscribers only.',
    'po-everyone' => 'Everyone on the planet',
    'po-user' => 'Registered Users',
    'po-artist' => 'Subscribed Artists',
	'po-member' => 'Authorized Members',
    'po-owner' => 'Only Me',
	'badaccess-groups' => '{{int:badaccess-group0}}',
    'restriction-level-owner' => 'Owner only',
    'restriction-level-user' => 'Registered Users',
    'restriction-level-artist' => 'Subscribed Artists',
    'restriction-read' => 'Read',
    'tooltip-ca-setprotection' => 'Define access rights for this page',
    'accesskey-ca-setprotection' => 'p'
    
);

/** French
 */
$messages['fr'] = array(
    'po-desc' => 'Active les options de protection/ouverture pour le propriétaire d\'une ressource',
    'protectown' => 'Protect Own',
    'collaborate' => 'Collaborer',
    'collaborate-title' => 'Changer le niveau de collaboration sur "$1"',
    'po-success' => 'Collaboration mise en place avec succès !',
    'po-failure' => 'Collaboration ratée.  {{int:sz-report}}',
    'po-locked' => 'Collaboration bloquée. {{int:sz-report}}',
    'po-notowner' => 'Cette page ne vous appartient pas. {{int:sz-report}}',
    'po-submit' => 'Paramétrer la collaboration',
    'po-header' => "<span style=\"float:right\">Voir <b>[[Help:Collaboration/fr|l'aide]]</b> | Gérer <b>[[Special:WikiPlaces/Members:$2|les membres]]</b></span>Vous pouvez modifier le niveau de collaboration sur la page '''$1''' ici.",
    'po-legend' => 'Collaborer',
    'po-whocan-read' => 'Qui peut lire ?',
    'po-help-read' => 'Cliquez un boutton radio pour sélectionner le groupe d\'utilisateurs autorisé à consulter cette page/ce fichier. Ex : Sélectionnez "<b>{{int:po-owner}}</b>" pour interdire l\'accès à tout le monde.',
    'po-whocan-edit' => 'Qui peut modifier ?',
    'po-help-edit' => 'Cliquez un boutton radio pour sélectionner le groupe d\'utilisateurs autorisé à modifier cette page/ce fichier. Ex : Sélectionnez "<b>{{int:po-everyone}}</b>" pour autoriser la terre entière.',
    'po-whocan-upload' => 'Qui peut importer ?',
    'po-help-upload' => 'Cliquez un boutton radio pour sélectionner le groupe d\'utilisateurs autorisé à ré-importer ce fichier. Ex : Sélectionnez ""<b>{{int:po-artist}}</b>" pour autoriser uniquement les abonnés à Seizam.',
    'po-everyone' => 'Le monde entier',
    'po-user' => 'Les utilisateurs enregistrés',
    'po-artist' => 'Les artistes abonnés',
	'po-member' => 'Les membres autorisés',
    'po-owner' => 'Seulement moi',
    'restriction-level-owner' => 'Propriétaire seulement',
    'restriction-level-user' => 'Utilisateurs enregistrés',
    'restriction-level-artist' => 'Artistes abonnés',
    'restriction-read' => 'Lire',
    'tooltip-ca-setprotection' => 'Définir les droits d\'accès à cette page',
);