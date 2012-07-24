<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
	/* General */
	'invitations' => 'My Invitations',
	'invitation' => 'My Invitation',
    /* Create Warnings */
	'wp-inv-create' => '<b>[[Special:Invitations/Create|Click here to invite somebody to Seizam!]]</b>',
	'wp-inv-limitreached' => 'Sorry, you have reached the invitation limit for the month.',
	'wp-inv-no' => 'Sorry, invitations are currently disabled.',
	'wp-inv-success' => 'Great! Your invitation was successfully created.',
	'wp-inv-success-sent' => '{{int:wp-inv-success}} It was sent to <b><nowiki>$1</nowiki></b> .',
    /* TP */
	'wp-inv-list-header' => 'Here is the list of all the invitation you created.',
	'wp-inv-list-footer' => '=== Available Invitations ===',
    'wp-inv-list-footer-li' => '<b>[[Special:Invitations/Create:$1|{{int:$2}}]]</b>: {{int:$2-desc}} (<b>$3 out of $4</b> remaining this month)',
    'wp-inv-list-help' => '=== About Invitations ===
Each month, you can generate a few invitation codes for your friends and relatives. A code provides great discounts on first subscription offers, it can be used through [[Special:Invitation/Use]].',
	'wpi-code' => 'Code',
	'wpi-to-email' => 'Sent to',
    'wpi-type' => 'Type',
	'wpi-used' => 'Used by [[User:$1|$1]]',
    'wpi-unlimited' => 'Unlimited, used $1 times.',
    'wpi-remaining' => '$1 remaining',
    /* Create Form */
    'wp-inv-create-header' => 'Please fill the form below to generate a new invitation code. If you fill up the e-mail part, the invitation will be sent automatically, otherwise you will need to transmit the generated code yourself.',
    'wp-inv-create-section' => 'Create an invitation code',
	'wp-inv-category-field' => 'Type:',
	'wp-inv-category-desc' => '$1 ($2/$3 remaining)',
	'wp-inv-category-help' => 'Select here the type of invitation you wish to send. Be careful, the best invitations unlocks great special offers but they are rare!',	
    'wp-inv-mail-section' => 'E-mail the invitation (optional)',
    'wp-inv-email-help' => 'Type here the e-mail adress of the person you are inviting. {{int:optional-field}} Leave blank if you do not want to send the invitation by mail.',
    'wp-inv-msg-help' => 'Type here a personal message you wish to send to the person you are inviting. {{int:optional-field}}',
    'wp-inv-msg-default' => 'Hello,
As promised, here is your invitation to Seizam.
Enjoy!
$1',
    'wp-inv-language-field' => 'Language:',
    'wp-inv-language-help' => 'Select here the language of the invitation e-mail. We provide this option in case the person you are inviting does not speak your language. {{int:optional-field}}',
	'wp-inv-admin-section' => 'Administration',
    'wp-inv-code-field' => 'Invitation code:',
	'wp-inv-code-help' => 'This is the code used to authenticate the invitation. You can change it and make it prettier if you wish to.',
	'wp-inv-counter-field' => 'Limit:',
	'wp-inv-counter-help' => 'How many times can the code be used. Type "-1" for unlimited.',
    
    /* Use Warnings */
	'wp-use-inv-header' => 'Please fill the form below to use an invitation code. Or check out [[Special:Subscriptions/new|our regular offers]].',
	'wp-use-inv-ok' => 'Great! Your invitation is valid. You now have access to new special offers.',
	'wp-use-inv-invalid' => 'This code is invalid.',
	'wp-use-inv-nolonger' => 'Sorry. Your invitation has expired.',
    
    /* Use Form */
    'wp-use-inv-help' => 'Type here the invitation code you received. Most likely a 12 characters string looking like "GNXHGKH79XQA".',
	'wp-use-inv-go' => 'Use this code!',
    'wp-use-inv-help' => 'This is your invitation code. Click [[Special:Invitation|here]] to try an other one.',
    'wp-inv-use-section' => '{{int:wp-sub-new-section}} (1/2)',
    
    /* Welcome Page */
    'wp-use-inv-notloggedin' => 'Sorry. You need to be logged in to use your invitation. Please  or ',
	'wp-use-inv-notloggedin' => '== Welcome on Seizam! ==
Thank you very much for visiting us! You received an invitation code for Seizam and that is great. But in order to use your invitation you need to be logged in.
=== You already have an account on Seizam ===
* That is easy, just <b>[{{canonicalurl:Special:UserLogin|returnto=Special:Invitation/Use:$1}} log in]</b>.
=== You don\'t have an account yet ===
* Do not worry. <b>[{{canonicalurl:Special:UserLogin|type=signup&returnto=Special:Invitation/Use:$1}} Create your account]</b> first.
=== You need help ===
* We will be happy to help you, whatever your request is! Simply <b>[[Project:Contact|contact us]]</b>.',
    
    /* Categories */
    'wpi-basic' => 'Basic Invitation',
    'wpi-basic-desc' => 'Exclusive Basic Plans (1 month for Free! 50% percent off basic prices!)',
    'wpi-discovery' => 'Discovery Invitation',
    'wpi-discovery-desc' => 'Special Discovery Plans (3 months for Free! 1 year for 6€!)',
    'wpi-beta' => 'Beta Tester Invitation',
    'wpi-beta-desc' => 'Free Basic Plan (1 year for free!)',
    'wpi-star' => 'Star Invitation',
    'wpi-star-desc' => 'Free Professional plan (1 year for free!)',
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
	/* General */
	'invitations' => 'Mes invitations',
	'invitation' => 'Mon invitation',
    /* Create Warnings */
	'wp-inv-create' => '<b>[[Special:Invitations/Create|Cliquez ici pour inviter quelqu\'un sur Seizam]]</b>',
	'wp-inv-limitreached' => 'Désolé, vous avez atteint votre limite d\'invitations pour ce mois.',
	'wp-inv-no' => 'Désolé, les invitations sont désactivées pour le moment.',
	'wp-inv-success' => 'Parfait ! Votre invitation a été créée avec succès.',
	'wp-inv-success-sent' => '{{int:wp-inv-success}} Elle a été envoyée à <b><nowiki>$1</nowiki></b> .',
    /* TP */
	'wp-inv-list-header' => 'Voici la liste de toutes les invitations que vous avez créées.',
	'wp-inv-list-footer' => '=== Invitations disponnibles ===',
    'wp-inv-list-footer-li' => '<b>[[Special:Invitations/Create:$1|{{int:$2}}]]</b> : {{int:$2-desc}} (<b>$3 sur $4</b> restantes ce mois)',
    'wp-inv-list-help' => '=== À propos des invitations ===
Chaque mois, vous pouvez générer quelques codes d\'invitation pour vos amis et proches. Un code débloque de grosses réductions sur les offres de premier abonnement, il peut être utilisé sur [[Special:Invitation/Use]].',
	'wpi-code' => 'Code',
	'wpi-to-email' => 'Envoyée à',
    'wpi-type' => 'Type',
	'wpi-used' => 'Utilisée par [[User:$1|$1]]',
    'wpi-unlimited' => 'Illimitée, utilisée $1 fois.',
    'wpi-remaining' => '$1 restantes',
    /* Create Form */
    'wp-inv-create-header' => 'Merci de remplir le formulaire ci-dessous pour générer un nouveau code. Si vous remplissez la section "courriel", l\'invitation sera envoyée automatiquement ; sinon, vous devrez transmettre le code vous-même.',
    'wp-inv-create-section' => 'Créer un code d\'invitation',
	'wp-inv-category-field' => 'Type :',
	'wp-inv-category-desc' => '$1 ($2/$3 restantes)',
	'wp-inv-category-help' => 'Sélectionnez ici le type d\'invitation que vous souhaitez envoyer. Soyez vigilant, les meilleures invitations débloquent les meilleures offres spéciales, mais elles sont rares !',	
    'wp-inv-mail-section' => 'Envoyer par courriel (optionel)',
    'wp-inv-email-help' => 'Saisissez ici l\'adresse de courriel de la personne que vous souhaitez inviter. {{int:optional-field}} Laissez libre si vous ne voulez pas envoyer votre invitation automatiquement.',
    'wp-inv-msg-help' => 'Saisissez ici un message personnel que vous voulez envoyer à la personne que vous invitez. {{int:optional-field}}',
    'wp-inv-msg-default' => 'Bonjour,
Comme promis, voici ton invitation pour rejoindre Seizam.
À bientôt!
$1',
    'wp-inv-language-field' => 'Langue :',
    'wp-inv-language-help' => 'Sélectionnez ici la langue du courriel. Nous proposons cette option dans le cas où la personne que vous invitez ne parle pas votre langue. {{int:optional-field}}',
    'wp-inv-code-field' => 'Code d\'invitation :',
	'wp-inv-code-help' => 'Voici le code utilisé pour autentifier votre invitation. Vous pouvez le changer pour le rendre plus joli si vous le désirez.',
	'wp-inv-counter-field' => 'Limite :',
	'wp-inv-counter-help' => 'Combien de fois ce code peut-il être utilisé. Tapez "-1" pour illimité.',
    
    /* Use Warnings */
	'wp-use-inv-header' => 'Merci de remplir le formulaire ci-dessous pour utiliser votre code d\'invitation. Ou consultez [[Special:Subscriptions/new|nos offres normales]].',
	'wp-use-inv-ok' => 'Félicitation ! Votre invitation est valide. Vous avez maintenant accès à de nouvelles offres spéciales.',
	'wp-use-inv-invalid' => 'Ce code est invalide.',
	'wp-use-inv-nolonger' => 'Désolé. Votre invitation a expiré.',
    
    /* Use Form */
    'wp-use-inv-help' => 'Saisissez ici le code que vous avez reçu. Probablement une chaine de 12 caractères comme "GNXHGKH79XQA".',
	'wp-use-inv-go' => 'Utiliser ce code !',
    'wp-use-inv-help' => 'Voici votre code d\'invitation. Cliquez [[Special:Invitation|ici]] pour en essayer un autre.',
    'wp-inv-use-section' => '{{int:wp-sub-new-section}} (1/2)',
    
    /* Welcome Page */
	'wp-use-inv-notloggedin' => '== Bienvenue sur Seizam ==
Merci beaucoup pour votre visite ! Parfait, vous avez reçu un code d\'invitation pour Seizam. Mais pour utiliser votre invitation vous devez être connecté(e).
=== Vous avez déjà un compte sur Seizam ===
* Simplement, <b>[{{canonicalurl:Special:UserLogin|returnto=Special:Invitation/Use:$1}} connectez-vous]</b>.
=== Vous n\'avez pas de compte pour le moment ===
* Pas de souçi. <b>[{{canonicalurl:Special:UserLogin|type=signup&returnto=Special:Invitation/Use:$1}} Créez votre compte]</b> en premier.
=== Vous avez besoin d\'aide ===
* Nous serions heureux de vous aider, quelle que soit votre requète ! Il suffit de <b>[[Project:Contact|nous contacter]]</b>.',
    
    /* Categories */
    'wpi-basic' => 'Invitation basique',
    'wpi-basic-desc' => 'Offres basiques exclusives (1 mois gratuit ! 50% de réduction !)',
    'wpi-discovery' => 'Invitation découverte',
    'wpi-discovery-desc' => 'Offres spéciales de découverte (3 mois gratuits ! 1 année pour 6€ !)',
    'wpi-beta' => 'Invitation beta testeur',
    'wpi-beta-desc' => 'Offre basique gratuite (1 année gratuite !)',
    'wpi-star' => 'Invitation star',
    'wpi-star-desc' => 'Offre professionelle gratuite (1 année gratuite !)',
);
