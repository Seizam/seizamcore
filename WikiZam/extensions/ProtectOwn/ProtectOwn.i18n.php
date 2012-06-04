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
    'po-desc' => 'Enable protection options for the owner of a resource',
    'protectown' => 'Protect Own',
    'po-success' => 'Protection has been successfully set!',
    'po-failure' => 'Protection failed. {{int:sz-report}}',
    'po-locked' => 'Protection locked. {{int:sz-report}}',
    'po-notowner' => 'This page does not belong to you. {{int:sz-report}}',
    'po-submit' => 'Set Protection',
    'po-legend' => 'Protect my page',
    'po-whocan-read' => 'Who can read?',
    'po-help-read' => 'Click one radio button to select the group of user allowed to read this page/this file. Eg: Select "<b>{{int:po-owner}}</b>" to forbid access to everyone.',
    'po-whocan-edit' => 'Who can edit?',
    'po-help-edit' => 'Click one radio button to select the group of user allowed to edit this page/this file. Eg: Select "<b>{{int:po-everyone}}</b>" to allow the entire world.',
    'po-whocan-upload' => 'Who can upload?',
    'po-help-upload' => 'Click one radio button to select the group of user allowed to re-upload this file. Eg: Select "<b>{{int:po-artist}}</b>" to allow Seizam subscribers only.',
    'po-everyone' => 'Everyone on the planet',
    'po-user' => 'Registered Users',
    'po-artist' => 'Subscribed Artists',
    'po-owner' => 'Only Me',
);

/** French
 */
$messages['fr'] = array(
    'po-desc' => 'Active les options de protection pour le propriétaire d\'une ressource',
    'protectown' => 'Protect Own',
    'po-success' => 'Protection mise en place avec succès !',
    'po-failure' => 'Protection ratée.  {{int:sz-report}}',
    'po-locked' => 'Protection bloquée. {{int:sz-report}}',
    'po-notowner' => 'Cette page ne vous appartient pas. {{int:sz-report}}',
    'po-submit' => 'Régler la protection',
    'po-legend' => 'Protéger ma page',
    'po-whocan-read' => 'Qui peut lire ?',
    'po-help-read' => 'Cliquez un boutton radio pour sélectionner le groupe d\'utilisateurs autorisé à consulter cette page/ce fichier. Ex : Sélectionnez "<b>{{int:po-owner}}</b>" pour interdire l\'accès à tout le monde.',
    'po-whocan-edit' => 'Qui peut modifier ?',
    'po-help-edit' => 'Cliquez un boutton radio pour sélectionner le groupe d\'utilisateurs autorisé à modifier cette page/ce fichier. Ex : Sélectionnez "<b>{{int:po-everyone}}</b>" pour autoriser la terre entière.',
    'po-whocan-upload' => 'Qui peut importer ?',
    'po-help-upload' => 'Cliquez un boutton radio pour sélectionner le groupe d\'utilisateurs autorisé à ré-importer ce fichier. Ex : Sélectionnez ""<b>{{int:po-artist}}</b>" pour autoriser uniquement les abonnés à Seizam.',
    'po-everyone' => 'Le monde entier',
    'po-user' => 'Les utilisateurs enregistrés',
    'po-artist' => 'Les artistes abonnés',
    'po-owner' => 'Seulement moi',
);