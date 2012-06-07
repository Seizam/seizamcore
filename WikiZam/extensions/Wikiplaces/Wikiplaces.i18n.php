<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
    /* Backend registration */
    'specialpages-group-wikiplace' => 'Wikiplaces',
    'wp-desc' => 'Enables artists to create WikiPlaces, places of Art & Freedom within Mediawiki.',
    /* Special Pages */
    'wikiplacesadmin' => 'Wikiplaces Administration',
    'subscriptions' => 'My Subscriptions',
    'wikiplaces' => 'My Wikiplaces',
    'offers' => 'Our Offers',
    /* Group: Artists */
    'group-artist' => 'Artists',
    'group-artist-member' => 'artist',
    'grouppage-artist' => '{{ns:project}}:Artists',
    /* Generic keywords */
    'wp-wikiplace' => 'Wikiplace',
    'wp-wikiplaces' => 'Wikiplaces',
    'wp-homepage' => 'Homepage',
    'wp-items' => 'Items',
    'wp-subpage' => 'Subpage',
    'wp-subpages' => 'Subpages',
    'wp-redirect' => 'Redirect',
    'wp-name' => 'Name',
    'wp-hits' => 'hits',
    'wp-Hits' => 'Hits',
    'wp-bandwidth' => 'Bandwidth',
    'wp-monthly_hits' => '{{int:wp-Hits}}',
    'wp-monthly_bandwidth' => '{{int:wp-bandwidth}}',
    'wp-plan_name' => 'Plan name',
    'wp-diskspace' => 'Diskspace',
    'wp-nswp' => 'Setting',
    'wp-nswp-talk' => 'Setting talk',
    'wp-max_wikiplaces' => '{{int:wp-wikiplaces}}',
    'wp-max_pages' => '{{int:wp-subpages}}',
    /* Actions */
    'wp-seeall' => 'see all',
    'wp-subscribe' => 'Subscribe',
    'wp-subscribe-change' => 'Change subscription',
    'wp-subscribe-renew' => 'Renew',
    'wp-create' => 'Create!',
    /* Tips */
    'wp-url-tip' => 'Your Wikiplace is available at [[$1|<u>http://www.<b>seizam.com/$1</b></u>]].',
    'wp-suburl-tip' => 'Your Page is available at [[$1|<u>http://www.<b>seizam.com/$1/$2</b></u>]].',
    'wp-link-tip' => 'Type <b><nowiki>[[$1]]</nowiki></b> anywhere to make a link like: [[$1]].',
    'wp-linkalias-tip' => 'Type <b><nowiki>[[$1|$2]]</nowiki></b> to make a link like: [[$1|$2]].',
    'wp-sublink-tip' => 'Type <b><nowiki>[[$1/$2]]</nowiki></b> anywhere to make a link like: [[$1/$2]].',
    'wp-sublinkalias-tip' => 'Type <b><nowiki>[[$1/$2|$2]]</nowiki></b> to make a link like: [[$1/$2|$2]].',
    'wp-action-tip' => 'Do not forget to checkout the <b>actions menu</b> (top right of every page) to find settings  like <b>Protect</b> or <b>Watch</b>.',
    'wp-subpage-tip' => 'To create a subpage, click [[Special:Wikiplaces/CreatePage:$1|here]] or visit [[$1/SubpageName|<u>www.seizam.com/<b>$1/SubpageName</b></u>]].',
    /* TablePage: Wikiplace */
    'wp-list-header' => 'Here are your Wikiplaces.',
    'wp-list-footer' => '==={{int:statistics}}===
* Diskspace usage total: $1
* Pages total: $2',
    'wp-consult-header' => 'Here are all the pages within <b>[[$1]]</b>.',
    'wp-consult-footer' => '==={{int:tipsntricks}}===
* {{int:wp-url-tip|$1}}
* {{int:wp-link-tip|$1}}
* {{int:wp-sublink-tip|$1|{{int:wp-subpage}}}}',
    /* Form: Wikiplace */
    'wp-create-header' => 'Please fill the form below to create a new Wikiplace.',
    'wp-create-section' => 'Create a Wikiplace',
    'wp-name-field' => 'Name:',
    'wp-template-field' => 'Template :',
    'wp-create-template-help' => 'Sélectionnez ici le modèle utilisé pour cette page. Modifiez par la suite ce patron générique pour personaliser votre wikiplace.',  
    'wp-license-field' => '{{int:license}}',
    'wp-create-license-help' => 'Select here the license you wish to associate with this page. Your work is to be released under this license.',
    'wp-create-name-help' => 'Type here the Name of your new Wikiplace. It will be available at <u>www.seizam.com/<b>Name</b></u> and its Subpages will be at <u>www.seizam.com/<b>Name</b>/Subpage</u>. <b>Advice:</b> Make it short, easy to remember and easy to type!',
    'wp-createpage-header' => 'Please fill the form below to create a new page within one of your Wikiplaces.',
    'wp-createpage-section' => 'Create a Page',
    'wp-wikiplace-field' => 'Parent Wikiplace:',
    'wp-createpage-wikiplace-help' => 'Select here the parent Wikiplace. Your Page will be available at <u>www.seizam.com/<b>Parent</b>/Name</u>.',
    'wp-createpage-name-help' => 'Type here the Name of your Page. It will be available at <u>www.seizam.com/Parent/<b>Name</b></u>. <b>Advice:</b> Make it short, easy to remember and easy to type!',
    /* Disclaimer: Wikiplace */
    'wp-create-wp-success' => '==Congratulation!==

Your new Wikiplace <b>[[$1]]</b> has been successfully created! {{int:wp-url-tip|$1}} You can administrate it from <b>[[Special:Wikiplaces/Consult:$1|{{int:wikiplaces}}]]</b>.

==={{int:tipsntricks}}===
* {{int:wp-link-tip|$1}}
* {{int:wp-linkalias-tip|$1|My Homepage!}}
* {{int:wp-subpage-tip|$1}}
* {{int:wp-action-tip}}',
    'wp-create-sp-success' => '==Congratulation!==

Your new Subpage <b>[[$1/$2|$2]]</b> has been successfully created in [[$1]]! {{int:wp-suburl-tip|$1|$2}} You can administrate [[$1]] from <b>[[Special:Wikiplaces/Consult:$1|{{int:wikiplaces}}]]</b>.

===Tips & Tricks===
* {{int:wp-sublink-tip|$1|$2}}
* {{int:wp-sublinkalias-tip|$1|$2}}
* {{int:wp-action-tip}}',
    'copyrightwarning3' => "Please note that all contributions to Wikiplaces on {{SITENAME}} might be edited or altered depending on the level of protection set by the Wikiplace owner (see [[Help:Protection]] for details).<br />
You are also promising us that you wrote this yourself, or copied it from a public domain or similar free resource (see $1 for details).
'''Do not submit copyrighted work without permission!'''",
    /* Warning: Wikiplace */
    'wp-invalid-name' => 'This name is invalid.',
    'wp-name-already-exists' => 'This name already exists. Please retry with a different name.',
    'wp-create-wp-first' => 'You need to create a Wikiplace first.',
	'wp-duplicate-exists' => 'A page with the same name but different case ([[$1]]) already exists.',
	'wp-duplicate-related' => 'A related page with the same name but different case ([[$1]]) exists. This page has to be named [[$2]].',
	'wp-no-container-found' => 'This page should belong to a Wikiplace. Retry with a name starting by a Wikiplace name.',
	'wp-not-owner' => 'You are not the owner of this Wikiplace.',
	'wp-notloggedin' => 'You must be logged in to perform this action. Please [[Special:UserLogin/signup|create an account]] or [[Special:UserLogin|log in]].',
    'wp-nosub' => 'Wikiplaces are only available upon subscription. [[Special:Subscriptions/new|Click here to subscribe !]]',
/* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Here are your active subscriptions.',
    'wp-subscriptionslist-noactive-header' => '{{int:wp-sub-noactive}} [[Special:Subscriptions/new|Click here to subscribe !]]',
    'wp-subscriptionslist-pending-header' => 'Your subscription is pending because it has not been paid for yet. [[Special:ElectronicPayment|Please click here to credit your account.]]',
    'wp-subscriptionslist-footer' => 'Would you like to setup your [[Special:Subscriptions/renew|subscription renewal]] plan? Or perhaps [[Special:Subscriptions/change|change your plan]] right now?',
    /* Form: Subscription */
    'wp-sub-new-header' => 'Please fill the form below to subscribe to Seizam.',
    'wp-sub-new-section' => 'Subscribe',
    'wp-planfield' => 'Plan:',
    'wp-planfield-help' => 'Select the Seizam Plan you wish to subscribe to from this dropdown list. More details [[Special:Offers|here]].',
    'wp-checkfield' => 'I read, understood and agree with Seizam General Terms and Conditions of Use ([[Project:GTCU|GTCU]]) <b>AND</b> Seizam Artist Specific Terms and Conditions of Use ([[Project:ASTCU|ASTCU]]).',
    'wp-checkfield-unchecked' => 'You need to agree with our Terms and Conditions to subscribe. Please check the box above.',
    'wp-plan-desc-short' => '$1: $2 $3 for $4 months.',
    'wp-plan-subscribe-go' => 'Subscribe!',
    'wp-sub-renew-header' => 'Please fill the form below to setup your renewal plan.',
    'wp-sub-renew-section' => 'Renew my subscription',
    'wp-do-not-renew' => 'Do not renew',
    'wp-plan-renew-go' => 'Set as my next plan',
    /* Disclaimer: Subscription */
    'wp-new-success-ok' => 'Thank you! Your subscription has been recorded and everything is in order. What about checking [[Special:MySeizam|{{int:myseizam}}]] out now?',
    'wp-renew-success' => 'Your next plan has been selected. Thanks!',
    'wp-sub-noactive' => 'You do not have any active subscription.',
    /* Warning: Subscription */
    'wp-no-active-sub' => 'You need an active subscription to perform this action. [[Special:Subscriptions/new|Click here to subscribe!]]',
    'wp-change-plan-required' => 'You need to upgrade your plan to perform this action. [[Special:Subscriptions/change|Click here to change plan!]]',
    'wp-wikiplace-quota-exceeded' => 'Your Wikiplace creation quota is exceeded. {{int:wp-change-plan-required}}',
    'wp-page-quota-exceeded' => 'Your page creation quota is exceeded.  {{int:wp-change-plan-required}}',
    'wp-diskspace-quota-exceeded' => 'Your file upload quota is exceeded.  {{int:wp-change-plan-required}}',
    'wp-subscribe-already' => 'You already have an active or pending subscription.',
    'wp-subscribe-email' => 'Before taking a subscription, you need to validate your e-mail address. [[Special:Preferences#mw-htmlform-email|Click here to setup and validate your e-mail address!]]',
    'wp-subscribe-change' => 'You can select another plan to start from the end of your current subscription through [[Special:Subscriptions/renew|the subscription renewal page]]. Please [[Project:Contact|contact us]] if you need to switch to another plan right now. {{int:sz-asap}}',
	'wp-insufficient-quota' => 'Insufficient quota (this plan is too small for your usage).',
	'wp-plan-not-available-renewal' => 'Plan not available (we do not offer it anymore).',
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
    /* Backend registration */
    'specialpages-group-wikiplace' => 'Wikiplaces',
    'wp-desc' => 'Permets aux artistes de créer des Wikiplaces, endroits d\'art et de liberté au sein de Mediawiki.',
    /* Special Pages */
    'wikiplacesadmin' => 'Administration de Wikiplaces',
    'subscriptions' => 'Mes Abonnements',
    'wikiplaces' => 'Mes Wikiplaces',
    'offers' => 'Nos Offres',
    /* Group: Artists */
    'group-artist' => 'Artistes',
    'group-artist-member' => 'artiste',
    'grouppage-artist' => '{{ns:project}}:Artists/fr',
    /* Generic keywords */
    'wp-wikiplace' => 'Wikiplace',
    'wp-wikiplaces' => 'Wikiplaces',
    'wp-homepage' => 'Page d\'accueil',
    'wp-items' => 'Objets',
    'wp-subpage' => 'Sous-page',
    'wp-subpages' => 'Sous-pages',
    'wp-redirect' => 'Redirection',
    'wp-name' => 'Nom',
    'wp-hits' => 'visites',
    'wp-Hits' => 'Visites',
    'wp-bandwidth' => 'Bande passante',
    'wp-monthly_hits' => '{{int:wp-Hits}}',
    'wp-monthly_bandwidth' => '{{int:wp-bandwidth}}',
    'wp-plan_name' => 'Nom de l\'offre',
    'wp-diskspace' => 'Espace disque',
    'wp-nswp' => 'Réglage',
    'wp-max_wikiplaces' => '{{int:wp-wikiplaces}}',
    'wp-max_pages' => '{{int:wp-subpages}}',
    /* Actions */
    'wp-seeall' => 'voir tout',
    'wp-subscribe' => 'Abonnement',
    'wp-subscribe-change' => 'Changer d\'Abonnement',
    'wp-subscribe-renew' => 'Renouveler',
    'wp-create' => 'Créer !',
    /* Tips */
    'wp-url-tip' => 'Votre Wikiplace est disponnible depuis [[$1|<u>http://www.<b>seizam.com/$1</b></u>]].',
    'wp-suburl-tip' => 'Votre page est disponnible depuis [[$1|<u>http://www.<b>seizam.com/$1/$2</b></u>]].',
    'wp-link-tip' => 'Saisissez <b><nowiki>[[$1]]</nowiki></b> n\'importe où pour faire un lien du type : [[$1]].',
    'wp-linkalias-tip' => 'Saisissez <b><nowiki>[[$1|$2]]</nowiki></b> n\'importe où pour faire un lien du type : [[$1|$2]].',
    'wp-sublink-tip' => 'Saisissez <b><nowiki>[[$1/$2]]</nowiki></b> n\'importe où pour faire un lien du type : [[$1/$2]].',
    'wp-sublinkalias-tip' => 'Saisissez <b><nowiki>[[$1/$2|$2]]</nowiki></b> n\'importe où pour faire un lien du type : [[$1/$2|$2]].',
    'wp-action-tip' => 'N\'oubliez pas de consulter le <b>menu actions</b> (en haut à droite de chaque page) où se trouvent des réglages comme <b>Protéger</b> ou <b>Suivre</b>.',
    'wp-subpage-tip' => 'Pour créer une sous-page, cliquez [[Special:Wikiplaces/CreatePage:$1|ici]] ou visitez [[$1/NomSousPage|<u>www.seizam.com/<b>$1/NomSousPage</b></u>]].',
    /* TablePage: Wikiplace */
    'wp-list-header' => 'Voici vos Wikiplaces.',
    'wp-list-footer' => '==={{int:statistics}}===
* Espace disque utilisé : $1
* Total de pages : $2',
    'wp-consult-header' => 'Voici toutes les pages dans <b>[[$1]]</b>.',
    'wp-consult-footer' => '==={{int:tipsntricks}}===
* {{int:wp-url-tip|$1}}
* {{int:wp-link-tip|$1}}
* {{int:wp-sublink-tip|$1|{{int:wp-subpage}}}}',
    /* Form: Wikiplace */
    'wp-create-header' => 'Merci de remplir ce formulaire pour créer un nouveau Wikiplace.',
    'wp-create-section' => 'Créer un Wikiplace',
    'wp-name-field' => 'Nom :',
    'wp-template-field' => 'Modèle :',
    'wp-create-template-help' => 'Sélectionnez ici le modèle utilisé pour cette page. Modifiez par la suite ce patron générique pour personaliser votre wikiplace.',
    'wp-create-license-help' => 'Sélectionnez ici la licence que vous souhaitez associer à cette page. Votre travail sera soumis à cette licence.',
    'wp-create-name-help' => 'Saisissez ici le nom de votre nouveau Wikiplace. Il sera disponnible depuis <u>www.seizam.com/<b>Nom</b></u> et toutes ses sous-pages seront depuis <u>www.seizam.com/<b>Name</b>/Subpage</u>. <b>Conseil :</b> Choisissez un nom court, facile à retenir et facile à saisir !',
    'wp-createpage-header' => 'Merci de remplir ce formulaire pour créer une nouvelle page dans l\'un de vos Wikiplaces',
    'wp-createpage-section' => 'Créer une page',
    'wp-wikiplace-field' => 'Wikiplace parent:',
    'wp-createpage-wikiplace-help' => 'Sélectionnez ici le Wikiplace parent. Votre page sera disponnible depuis <u>www.seizam.com/<b>Parent</b>/Nom</u>.',
    'wp-createpage-name-help' => 'Saisissez ic le nom de votre page. Elle sera disponnible depuis <u>www.seizam.com/Parent/<b>Nom</b></u>. <b>Conseil :</b> Choisissez un nom court, facile à retenir et facile à saisir !',
    /* Disclaimer: Wikiplace */
    'wp-create-wp-success' => '==Félicitation !==

Votre nouveau Wikiplace <b>[[$1]]</b> a été créé avec succès ! {{int:wp-url-tip|$1}} Vous pouvez l\'administrer depuis <b>[[Special:Wikiplaces/Consult:$1|{{int:wikiplaces}}]]</b>.

==={{int:tipsntricks}}===
* {{int:wp-link-tip|$1}}
* {{int:wp-linkalias-tip|$1|My Homepage!}}
* {{int:wp-subpage-tip|$1}}
* {{int:wp-action-tip}}',
    'wp-create-sp-success' => '==Félicitation !==

Votre nouvelle sous-page <b>[[$1/$2|$2]]</b> a été créée avec succès dans [[$1]]! {{int:wp-suburl-tip|$1|$2}} Vous pouvez administrer [[$1]] depuis <b>[[Special:Wikiplaces/Consult:$1|{{int:wikiplaces}}]]</b>.

===Tips & Tricks===
* {{int:wp-sublink-tip|$1|$2}}
* {{int:wp-sublinkalias-tip|$1|$2}}
* {{int:wp-action-tip}}',
    'copyrightwarning3' => "Please note that all contributions to Wikiplaces on {{SITENAME}} might be edited or altered depending on the level of protection set by the Wikiplace owner (see [[Help:Protection]] for details).<br />
You are also promising us that you wrote this yourself, or copied it from a public domain or similar free resource (see $1 for details).
'''Do not submit copyrighted work without permission!'''",
    'copyrightwarning3' => "Toutes les contributions à des Wikiplaces sur {{SITENAME}} pourraient être modifiées en fonction du niveau de protection mis en place par le propriétaire du Wikiplace (voir [[Help:Protection/fr]] pour plus de détails).<br />
Vous nous promettez aussi que vous avez écrit ceci vous-même, ou que vous l’avez copié d’une source provenant du domaine public, ou d’une ressource libre. (voir $1 pour plus de détails).
'''N'utilisez pas de travaux sous droit d'auteur sans autorisation expresse'''",
    /* Warning: Wikiplace */
    'wp-invalid-name' => 'Ce nom est invalide.',
    'wp-name-already-exists' => 'Ce nom existe déjà. Merci d\'utiliser un autre nom.',
    'wp-create-wp-first' => 'Vous devez d\'abord créer un Wikiplace.',
	'wp-duplicate-exists' => 'Une page ayant le même nom avec une casse différente ([[$1]]) existe déjà.',
	'wp-duplicate-related' => 'Une page liée ayant le même nom mais avec une casse différente ([[$1]]) existe. Cette page devra se nommer [[$2]].',
	'wp-no-container-found' => 'Cette page devrait appartenir à un Wikiplace. Rééssayez avec un nom commencant par celui d\'un Wikiplace.',
	'wp-not-owner' => 'Vous n\'êtes pas le propriétaire de ce Wikiplace.',
	'wp-notloggedin' => 'Cette action nécessite que vous soyez identifié. Vous pouvez [[Special:UserLogin/signup|créer un compte]] ou [[Special:UserLogin|vous connecter]].',
    'wp-nosub' => 'Les wikiplaces ne sont disponibles qu\'après abonnement. [[Special:Subscriptions/new|Cliquez ici pour vous abonner !]]',
    /* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Voici vos abonnements actifs.',
    'wp-subscriptionslist-noactive-header' => '{{int:wp-sub-noactive}} [[Special:Subscriptions/new|Cliquez ici pour vous abonner !]]',
    'wp-subscriptionslist-pending-header' => 'Votre abonnement est en attente car il n\'a pas encore été payé. [[Special:ElectronicPayment|Cliquez ici pour créditer votre compte.]]',
    'wp-subscriptionslist-footer' => 'Désirez vous paramétrer le [[Special:Subscriptions/renew|renouvellement de votre abonnement]] ? Ou peut-être [[Special:Subscriptions/change|changer d\'abonnement]] immédiatement ?',
    /* Form: Subscription */
    'wp-sub-new-header' => 'Merci de remplir ce formulaire pour vous abonner à Seizam.',
    'wp-sub-new-section' => 'S\'abonner',
    'wp-planfield' => 'Offre :',
    'wp-planfield-help' => 'Sélectionnez l\'offre Seizam à laquelle vous souhaitez souscrire grâce à ce menu déroulant. Plus de détails [[Special:Offers|ici]].',
    'wp-checkfield' => 'J\'ai lu, j\'ai compris et j\'approuve les Conditions Générales d’Utilisation de Seizam ([[Project:CGU|CGU]]) <b>ET</b> les Conditions Particulières d’Utilisation de Seizam par les Artistes ([[Project:CPUA|CPUA]]).',
    'wp-checkfield-unchecked' => 'Vous devez approuver nos conditions d\'utilisation pour vous abonner. Merci de cocher la case ci-dessus.',
    'wp-plan-desc-short' => '$1: $2 $3 pour $4 mois.',
    'wp-plan-subscribe-go' => 'S\'abonner !',
    'wp-sub-renew-header' => 'Merci de remplir ce formulaire pour paramétrer le renouvellement de votre abonnement.',
    'wp-sub-renew-section' => 'Renouveler mon abonnement',
    'wp-do-not-renew' => 'Ne pas renouveler',
    'wp-plan-renew-go' => 'Définir',
    /* Disclaimer: Subscription */
    'wp-new-success-ok' => 'Merci ! Votre abonnement a été enregistré et tout est en ordre. Voulez-vous accéder à [[Special:MySeizam|{{int:myseizam}}]] maintenant ?',
    'wp-renew-success' => 'Votre prochain abonnement a été sélectionné. Merci !',
    'wp-sub-noactive' => 'Vous n\'avez pas d\'abonnement actif.',
    /* Warning: Subscription */
    'wp-no-active-sub' => 'Il vous faut un abonnement actif pour réaliser cette action. [[Special:Subscriptions/new|Cliquez ici pour vous abonner !]]',
    'wp-change-plan-required' => 'Vous devez changer d\'abonnement pour réaliser cette action. [[Special:Subscriptions/change|Cliquez ici pour changer d\'abonnement !]]',
    'wp-wikiplace-quota-exceeded' => 'Votre quota de création de Wikiplaces est dépassé. {{int:wp-change-plan-required}}',
    'wp-page-quota-exceeded' => 'Votre quota de création de pages est dépassé. {{int:wp-change-plan-required}}',
    'wp-diskspace-quota-exceeded' => 'Votre quota d\'importation de fichiers est dépassé.  {{int:wp-change-plan-required}}',
    'wp-subscribe-already' => 'Vous avez déjà un abonnement actif ou en attente.',
    'wp-subscribe-email' => 'Avant de vous abonner, vous devez valider votre adresse de courriel. [[Special:Preferences#mw-htmlform-email|Cliquez ici pour parametrer et valider votre adresse de courriel !]]',
    'wp-subscribe-change' => 'Vous pouvez sélectionner un abonnement qui succédera à l\'actuel depuis [[Special:Subscriptions/renew|la page de renouvellement d\'abonnement]]. Merci de [[Project:Contact/fr|nous contacter]] si vous avez besoin de changer d\'abonnement immédiatement. {{int:sz-asap}}',
    'wp-insufficient-quota' => 'Quota insuffisant (cette offre est trop petite pour votre usage).',
	'wp-plan-not-available-renewal' => 'Offre indisponnible (cette offre n\'est plus disponnible).',
);