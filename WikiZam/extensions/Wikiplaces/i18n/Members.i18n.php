<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
    'wp-members' => 'Members',
    'wp-users' => 'Users',
    /* Consult members */
    'wp-members-list-header' => '<span style="float:right">See <b>[[Help:Members|Help]]</b> | Manage <b>[[Special:WikiPlaces/Consult:$1|Pages]]</b></span>Here are all the members of <b>[[$1]]</b>.',
    'wp-username' => 'Username',
    'wp-realname' => 'Real Name',
    'wp-remove' => 'Remove',
    'wp-add-member' => 'Add',
    'wp-add-member-long' => 'Add another member',
    'wp-members-list-footer' => '<h3>{{int:tipsntricks}}</h3>
* {{int:wp-url-tip|$1}}
* {{int:wp-background-tip|$1}}
* {{int:wp-navigation-tip|$1}}',
    /* Add member */
    'wp-addmember-header' => 'Fill the form below to add a member to a WikiPlace.',
    'wp-addmember-section' => 'Add a member',
    'wp-addmember-wikiplace-help' => 'Select here the [[Help:WikiPlaces|WikiPlace]] you want to add a member to.',
    'wp-addmember-username-help' => 'Type here a username. You can have up to ' . WP_MEMBERS_LIMIT . ' members per WikiPlace.',
    'wp-add' => 'Add!',
    'wp-addmember-success' => 'The member has been added successfully!',
    'wp-already-member' => 'This user is already member of this WikiPlace.',
    'wp-owner-cannot-be-member' => 'You are the owner of this WikiPlace, so you are already an implicit member.',
    'wp-members-limit-reached' => 'You have reached the maximum number of members for this WikiPlace.',
    /* Remove member */
    'wp-not-member' => 'This user is not a member of this WikiPlace.',
    'wp-remove-member-success' => 'The member has been removed successfully!',
    /* Log */
    'log-name-members' => 'Members Management Log',
    'log-description-members' => 'These events track when Members are added or removed to WikiPlaces. See [[Help:Collaboration]]',
    'logentry118-members-add' => 'added [[User:$4|$4]] as member of [[$3]]',
    'logentry118-members-remove' => 'removed [[User:$4|$4]] as member of [[$3]]',
    'logentry-members-add' => '[[User:$1|$1]] added [[User:$4|$4]] as member of [[$3]]',
    'logentry-members-remove' => '[[User:$1|$1]] removed [[User:$4|$4]] as member of [[$3]]',
);

/*
 * Message documentation
 */
$messages['qqq'] = array(
);

/*
 * French
 */
$messages['fr'] = array(
    'wp-members' => 'Membres',
    'wp-users' => 'Utilisateurs',
    /* Consult members */
    'wp-members-list-header' => '<span style="float:right">Voir <b>[[Help:Membres|l\'aide]]</b> | Gérer <b>[[Special:WikiPlaces/Consult:$1|les pages]]</b></span>Voici tous les membres de <b>[[$1]]</b>.',
    'wp-username' => 'Nom d\'utilisateur',
    'wp-realname' => 'Nom réel',
    'wp-remove' => 'Retirer',
    'wp-add-member' => 'Ajouter',
    'wp-add-member-long' => 'Ajouter un autre membre',
    /* Add member */
    'wp-addmember-header' => 'Remplisser ce formulaire pour ajouter un membre à une WikiPlace.',
    'wp-addmember-section' => 'Ajouter un membre',
    'wp-addmember-wikiplace-help' => 'Sélectionnez ici la [[Help:WikiPlaces|WikiPlace]] à laquelle vous souhaitez ajouter un membre.',
    'wp-addmember-username-help' => 'Saisissez ici le nom de l\'utilisateur. Vous pouvez avoir jusqu\'à ' . WP_MEMBERS_LIMIT . ' membres par WikiPlace.',
    'wp-add' => 'Ajouter !',
    'wp-addmember-success' => 'Le membre a été ajouté !',
    'wp-already-member' => 'Cet utilisateur est déjà membre de cette WikiPlace.',
    'wp-owner-cannot-be-member' => 'Vous êtes le propriétaire de cette WikiPlace, par conséquent vous êtes déjà un membre implicite.',
    'wp-members-limit-reached' => 'Vous avez atteint le nombre maximal de membres pour cette WikiPlace.',
    /* Remove member */
    'wp-not-member' => 'Cet utilisateur n\'est pas un membre de cette WikiPlace.',
    'wp-remove-member-success' => 'Le membre a été retiré avec succès !',
);

