<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
    /* Backend registration */
    'specialpages-group-wikiplace' => 'WikiPlaces',
    'wp-desc' => 'Enables artists to create WikiPlaces, places of Art & Freedom within Mediawiki.',
    /* Special Pages */
    'wikiplacesadmin' => 'WikiPlaces Administration',
    'subscriptions' => 'My Subscriptions',
    'wikiplaces' => 'My WikiPlaces',
    /* Group: Artists */
    'group-artist' => 'Artists',
    'group-artist-member' => 'artist',
    'grouppage-artist' => '{{ns:project}}:Artists',
    /* Generic keywords */
    'wp-wikiplace' => 'WikiPlace',
    'wp-wikiplaces' => 'WikiPlaces',
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
    'wp-duration' => 'Duration',
    'notemplate' => 'Empty',
    'wp-default-talk' => 'Default talk',
    /* Actions */
    'wp-seeall' => 'see all',
    'wp-subscribe' => 'Subscribe',
    'wp-subscribe-change' => 'Change subscription',
    'wp-subscribe-renew' => 'Renew',
    'wp-create' => 'Create!',
    'wp-create-page' => 'Create',
    'wp-create-page-long' => 'Create a new Page',
    'wp-create-wikiplace' => 'Create',
    'wp-create-wikiplace-long' => 'Create a new WikiPlace',
    /* Tips */
    'wp-url-tip' => 'Your WikiPlace is available at [[$1|<u>http://www.<b>seizam.com/$1</b></u>]].',
    'wp-suburl-tip' => 'Your Page is available at [[$1/$2|<u>http://www.<b>seizam.com/$1/$2</b></u>]].',
    'wp-link-tip' => 'Type <b><nowiki>[[$1]]</nowiki></b> anywhere to make a [[Help:Links|link]] like: [[$1]].',
    'wp-linkalias-tip' => 'Type <b><nowiki>[[$1|$2]]</nowiki></b> to make a [[Help:Links|link]] like: [[$1|$2]].',
    'wp-sublink-tip' => 'Type <b><nowiki>[[$1/$2]]</nowiki></b> anywhere to make a [[Help:Links|link]] like: [[$1/$2]].',
    'wp-sublinkalias-tip' => 'Type <b><nowiki>[[$1/$2|$2]]</nowiki></b> to make a [[Help:Links|link]] like: [[$1/$2|$2]].',
    'wp-action-tip' => 'Do not forget to checkout the <b>actions menu</b> (top right of every page) to find settings  like <b>Protect</b> or <b>Watch</b>.',
    'wp-subpage-tip' => 'To create a subpage, click [[Special:WikiPlaces/CreatePage:$1|here]] or visit [[$1/SubpageName|<u>www.seizam.com/<b>$1/SubpageName</b></u>]].',
    'wp-background-tip' => 'To install a [[Help:Background|background image]], input the link to the image on [[{{ns:Wikiplace}}:$1/'.WPBACKGROUNDKEY.']].',
    'wp-navigation-tip' => 'To install the [[Help:WikiPlace Navigation|navigation menu]], input a list of links on [[{{ns:Wikiplace}}:$1/'.WPNAVIGATIONKEY.']].',
    /* TablePage: Wikiplace */
    'wp-list-header' => '<span style="float:right">See : <b>[[Help:WikiPlaces|Help]]</b></span>Here are your WikiPlaces.',
    'wp-list-footer' => '<h3>{{int:statistics}}</h3>
* Diskspace usage total: $1
* Pages total: $2',
    'wp-consult-header' => '<span style="float:right">See <b>[[Help:WikiPlaces|Help]]</b> | Manage <b>[[Special:WikiPlaces/Members:$1|Members]]</b></span>Here are all the pages within <b>[[$1]]</b>.',
    'wp-consult-footer' => '<h3>{{int:tipsntricks}}</h3>
* {{int:wp-url-tip|$1}}
* {{int:wp-background-tip|$1}}
* {{int:wp-navigation-tip|$1}}',
    /* Form: Wikiplace */
    'wp-create-header' => 'Fill the form below to create a new WikiPlace.',
    'wp-create-section' => 'Create a WikiPlace',
    'wp-name-field' => 'Name:',
    'wp-template-field' => 'Template:',
    'wp-create-template-help' => 'Select here the template to use for this page. Then edit this generic layout to make this page yours. See all templates at [[:Category:Page_Templates]].',  
    'wp-license-field' => 'License:',
    'wp-create-license-help' => 'Select here the license you wish to associate with this page. Your work is to be released under this license.',
    'wp-create-name-help' => 'Type here the Name of your new WikiPlace. It will be available at <u>www.seizam.com/<b>Name</b></u> and its Subpages will be at <u>www.seizam.com/<b>Name</b>/Subpage</u>. <b>Advice:</b> Make it short, easy to remember and easy to type!',
    'wp-createpage-header' => 'Fill the form below to create a new page within one of your WikiPlaces.',
    'wp-createpage-section' => 'Create a Page',
    'wp-parent-wikiplace-field' => 'Parent WikiPlace:',
    'wp-createtalk-field' => 'Also create and open the associated Talk for this page.',
    'wp-createpage-wikiplace-help' => 'Select here the parent WikiPlace. Your Page will be available at <u>www.seizam.com/<b>Parent</b>/Name</u>.',
    'wp-createpage-name-help' => 'Type here the Name of your Page. It will be available at <u>www.seizam.com/Parent/<b>Name</b></u>. <b>Advice:</b> Make it short, easy to remember and easy to type!',
    'wp-createpage-template-help' => '{{int:wp-create-template-help}}',
    'wp-createpage-license-help' => 'Select here the type of Intellectual Property Licensing this page is released under. We advise free licensing but that is your call.',
    'wp-createpage-createtalk-help' => 'Check this box if you wish to open the Talk for this page. This second talk page will be available at <u>www.seizam.com/<b>Talk:</b>Parent/Name</u>.',
    'wp-createtalk-help' => 'Check this box if you wish to open the Talk for this page. This second talk page will be available at <u>www.seizam.com/<b>Talk:</b>Name</u>.',
    
    'wp-subpage-talk-default' => '{{Subst:Default_Talk}}',
    'wp-homepage-talk-default' => '{{Subst:Default_Talk}}',
    /* Disclaimer: Wikiplace */
    'wp-create-wp-success' => '<h2>Congratulations!</h2>

Your new WikiPlace <b>[[$1]]</b> has been successfully created! {{int:wp-url-tip|$1}} You can administrate it from <b>[[Special:WikiPlaces/Consult:$1|{{int:wikiplaces}}]]</b>.

<h3>{{int:tipsntricks}}</h3>
* {{int:wp-link-tip|$1}}
* {{int:wp-linkalias-tip|$1|My Homepage!}}
* {{int:wp-subpage-tip|$1}}
* {{int:wp-action-tip}}',
    'wp-create-sp-success' => '<h2>Congratulations!</h2>

Your new Subpage <b>[[$1/$2|$2]]</b> has been successfully created in [[$1]]! {{int:wp-suburl-tip|$1|$2}} You can administrate [[$1]] from <b>[[Special:WikiPlaces/Consult:$1|{{int:wikiplaces}}]]</b>.

<h3>{{int:tipsntricks}}</h3>
* {{int:wp-sublink-tip|$1|$2}}
* {{int:wp-sublinkalias-tip|$1|$2}}
* {{int:wp-action-tip}}',
    'copyrightwarning3' => "Please note that all contributions to WikiPlaces on {{SITENAME}} might be edited or altered depending on the level of protection set by the WikiPlace owner (see [[Help:Protection]] for details).<br />
You are also promising us that you wrote this yourself, or copied it from a public domain or similar free resource (see $1 for details).
'''Do not submit copyrighted work without permission!'''",
    /* Warning: Wikiplace */
    'wp-invalid-name' => 'This name is invalid.',
    'wp-name-already-exists' => 'This name already exists. Retry with a different name.',
    'wp-create-wp-first' => 'You need to create a WikiPlace first.',
	'wp-duplicate-exists' => 'A page with the same name but different case ([[:$1]]) already exists.',
	'wp-duplicate-related' => 'A related page with the same name but different case ([[$1]]) exists. This page has to be named [[$2]].',
	'wp-no-container-found' => 'This page should belong to a WikiPlace. Retry with a name starting by a WikiPlace name.',
	'wp-not-owner' => 'You are not the owner of this WikiPlace.',
    'wp-not-owner-or-member' => 'You are neither the owner nor a member of this WikiPlace.',
	'wp-notloggedin' => 'You must be logged in to perform this action. Please [[Special:UserLogin/signup|create an account]] or [[Special:UserLogin|log in]].',
    'wp-nosub' => 'WikiPlaces are only available upon subscription. [[Special:Subscriptions/New|Click here to subscribe !]]',
    /* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Here are your active subscriptions.',
    'wp-subscriptionslist-noactive-header' => '{{int:wp-sub-noactive}} [[Special:Subscriptions/New|Click here to subscribe !]]',
    'wp-subscriptionslist-pending-header' => 'Your subscription is pending because it has not been paid for yet. [[Special:ElectronicPayment|Click here to credit your account.]]',
    'wp-subscriptionslist-footer' => 'Would you like to setup your [[Special:Subscriptions/Renew|subscription renewal]] plan? Or perhaps [[Special:Subscriptions/Change|change your plan]] right now?',
    /* Form: Subscription */
    'wp-sub-new-header' => 'Fill the form below to subscribe to Seizam.',
    'wp-sub-new-section' => 'Subscribe',
    'wp-planfield' => 'Plan:',
    'wp-planfield-help' => 'Select the Seizam Plan you wish to subscribe to from this dropdown list. More details [[Project:Plans|here]].',
    'wp-checkfield' => 'I read, understood and agree with Seizam General Terms and Conditions of Use ([[Project:GTCU|GTCU]]) <b>AND</b> Seizam Artist Specific Terms and Conditions of Use ([[Project:STCUA|STCUA]]).',
    'wp-checkfield-unchecked' => 'You need to agree with our Terms and Conditions to subscribe. Please check the box above.',
    'wp-plan-desc-short' => '$1 for $2€',
    'wp-plan-subscribe-go' => 'I subscribe',
    'wp-sub-renew-header' => 'Fill the form below to setup your renewal plan.',
    'wp-sub-renew-section' => 'Renew my subscription',
    'wp-do-not-renew' => 'Do not renew',
    'wp-plan-renew-go' => 'Set as my next plan',
    /* Disclaimer: Subscription */
    'wp-new-success-ok' => 'Thank you! Your subscription has been recorded and everything is in order. What about checking [[Special:MySeizam|{{int:myseizam}}]] out now?',
    'wp-renew-success' => 'Your next plan has been selected. Thanks!',
    'wp-cancel-success' => 'Your pending subscription has been cancelled.',
    'wp-sub-noactive' => 'You do not have any active subscription.',
    /* Warning: Subscription */
    'wp-no-active-sub' => 'You need an active subscription to perform this action. [[Special:Subscriptions/New|Click here to subscribe!]]',
    'wp-wikiplace-no-active-sub' => 'This WikiPlace has no active subscription associated. Its owner need an active subscription to let you perform this action.',
    'wp-change-plan-required' => 'You need to upgrade your plan to perform this action. [[Special:Subscriptions/Change|Click here to change plan!]]',
    'wp-owner-change-plan-required' => 'Its owner need to upgrade the plan to let you perform this action.',
    'wp-wikiplace-quota-exceeded' => 'Your WikiPlace creation quota is exceeded. {{int:wp-change-plan-required}}',
    'wp-page-quota-exceeded' => 'Your page creation quota is exceeded.  {{int:wp-change-plan-required}}',
    'wp-wikiplace-page-quota-exceeded' => 'This WikiPlace page creation quota is exceeded.  {{int:wp-owner-change-plan-required}}',
    'wp-diskspace-quota-exceeded' => 'Your file upload quota is exceeded.  {{int:wp-change-plan-required}}',
    'wp-wikiplace-diskspace-quota-exceeded' => 'This WikiPlace file upload quota is exceeded.  {{int:wp-owner-change-plan-required}}',
    'wp-subscribe-already' => 'You already have an active or pending subscription.',
    'wp-subscribe-email' => 'Before taking a subscription, you need to validate your e-mail address. Go to your inbox and click the <u>confirmation link</u> we just sent to you ([[Special:Preferences#mw-htmlform-email|resend]]). Then reload this page.',
    'wp-subscribe-change' => 'You can select another plan to start from the end of your current subscription through [[Special:Subscriptions/Renew|the subscription renewal page]]. [[Project:Contact|Contact us]] if you need to switch to another plan right now. {{int:sz-asap}}',
	'wp-insufficient-quota' => 'Insufficient quota (this plan is too small for your usage).',
	'wp-plan-not-available-renewal' => 'Plan not available (we do not offer it anymore).',
    
    'forbidden-admin-action' => 'The action you are attempting is reserverd to administrators only. {{int:sz-report}}',
    
    /* Background action */
    'wp-background-action' => 'Background',
    'wp-wikiplace-field' => 'WikiPlace:',
    'wp-filename-field' => 'Filename:',
    'wp-setbackground-wikiplace-help' => 'Select here the [[Help:WikiPlaces|WikiPlace]] you want to install the background on.',
    'wp-setbackground-filename-help' => 'Type here the name of an [[Help:Images|image]] [[Help:Files|file]]. Make sure it does not have [[Help:Protection|"read" protection]] and you have the right to use it.',
    'wp-setbackground-go' => 'Install',
    'wp-invalid-background' => 'This file is invalid. It cannot be installed as a background.',
    'wp-setbackground-header' => '<span style="float:right">See : <b>[[Help:Background|Help]]</b></span>Fill the form below to install a background image on your WikiPlace.',
    'wp-setbackground-section' => 'Install a background',
    'wp-setbackground-success' => 'The background has been installed on your WikiPlace!',
	    
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
    'specialpages-group-wikiplace' => 'WikiPlaces',
    'wp-desc' => 'Permets aux artistes de créer des WikiPlaces, endroits d\'art et de liberté au sein de Mediawiki.',
    /* Special Pages */
    'wikiplacesadmin' => 'Administration de WikiPlaces',
    'subscriptions' => 'Mes abonnements',
    'wikiplaces' => 'Mes WikiPlaces',
    /* Group: Artists */
    'group-artist' => 'Artistes',
    'group-artist-member' => 'artiste',
    'grouppage-artist' => '{{ns:project}}:Artists/fr',
    /* Generic keywords */
    'wp-wikiplace' => 'WikiPlace',
    'wp-wikiplaces' => 'WikiPlaces',
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
    'wp-duration' => 'Durée',
    'notemplate' => 'Vide',
    'wp-default-talk' => 'Discussion par défaut',
    /* Actions */
    'wp-seeall' => 'voir tout',
    'wp-subscribe' => 'Abonnement',
    'wp-subscribe-change' => 'Changer d\'Abonnement',
    'wp-subscribe-renew' => 'Renouveler',
    'wp-create' => 'Créer !',
    'wp-create-page' => 'Créer',
    'wp-create-page-long' => 'Créer une nouvelle Page',
    'wp-create-wikiplace-long' => 'Créer',
    'wp-create-wikiplace-long' => 'Créer une nouvelle WikiPlace',
    /* Tips */
    'wp-url-tip' => 'Votre WikiPlace est disponnible depuis [[$1|<u>http://www.<b>seizam.com/$1</b></u>]].',
    'wp-suburl-tip' => 'Votre page est disponnible depuis [[$1/$2|<u>http://www.<b>seizam.com/$1/$2</b></u>]].',
    'wp-link-tip' => 'Saisissez <b><nowiki>[[$1]]</nowiki></b> n\'importe où pour faire un [[Help:Liens|lien]] du type : [[$1]].',
    'wp-linkalias-tip' => 'Saisissez <b><nowiki>[[$1|$2]]</nowiki></b> n\'importe où pour faire un [[Help:Liens|lien]] du type : [[$1|$2]].',
    'wp-sublink-tip' => 'Saisissez <b><nowiki>[[$1/$2]]</nowiki></b> n\'importe où pour faire un [[Help:Liens|lien]] du type : [[$1/$2]].',
    'wp-sublinkalias-tip' => 'Saisissez <b><nowiki>[[$1/$2|$2]]</nowiki></b> n\'importe où pour faire un [[Help:Liens|lien]] du type : [[$1/$2|$2]].',
    'wp-action-tip' => 'N\'oubliez pas de consulter le <b>menu actions</b> (en haut à droite de chaque page) où se trouvent des réglages comme <b>Protéger</b> ou <b>Suivre</b>.',
    'wp-subpage-tip' => 'Pour créer une sous-page, cliquez [[Special:WikiPlaces/CreatePage:$1|ici]] ou visitez [[$1/NomSousPage|<u>www.seizam.com/<b>$1/NomSousPage</b></u>]].',
    'wp-background-tip' => 'Pour installer l\'[[Help:Arrière-plan|arrière-plan]], saisissez un lien vers l\'image sur [[{{ns:Wikiplace}}:$1/'.WPBACKGROUNDKEY.']].',
    'wp-navigation-tip' => 'Pour installer le [[Help:Navigation pour WikiPlace|menu de navigation]], saisissez une liste de liens sur [[{{ns:Wikiplace}}:$1/'.WPNAVIGATIONKEY.']].',
    /* TablePage: Wikiplace */
    'wp-list-header' => '<span style="float:right">Voir : <b>[[Help:WikiPlaces/fr|Aide]]</b></span>Voici vos WikiPlaces.',
    'wp-list-footer' => '<h3>{{int:statistics}}</h3>
* Espace disque utilisé : $1
* Total de pages : $2',
    'wp-consult-header' => '<span style="float:right">Voir <b>[[Help:WikiPlaces/fr|l\'aide]]</b> | Gérer <b>[[Special:WikiPlaces/Members:$1|les membres]]</b></span>Voici toutes les pages dans <b>[[$1]]</b>.',
    /* Form: Wikiplace */
    'wp-create-header' => 'Remplisser ce formulaire pour créer une nouvelle WikiPlace.',
    'wp-create-section' => 'Créer une WikiPlace',
    'wp-name-field' => 'Nom :',
    'wp-template-field' => 'Modèle :',
    'wp-license-field' => 'Licence :',
    'wp-create-template-help' => 'Sélectionnez ici le modèle à utiliser pour cette page. Modifiez par la suite ce patron générique pour la personaliser. Voir [[:Category:Modèles_de_pages|tous les modèles]].',
    'wp-create-license-help' => 'Sélectionnez ici la licence que vous souhaitez associer à cette page. Votre travail sera soumis à cette licence.',
    'wp-create-name-help' => 'Saisissez ici le nom de votre nouvelle WikiPlace. Il sera disponnible depuis <u>www.seizam.com/<b>Nom</b></u> et toutes ses sous-pages seront depuis <u>www.seizam.com/<b>Name</b>/Subpage</u>. <b>Conseil :</b> Choisissez un nom court, facile à retenir et facile à saisir !',
    'wp-createpage-header' => 'Remplissez ce formulaire pour créer une nouvelle page dans l\'une de vos WikiPlaces',
    'wp-createpage-section' => 'Créer une page',
    'wp-parent-wikiplace-field' => 'WikiPlace parente :',
    'wp-createpage-wikiplace-help' => 'Sélectionnez ici la WikiPlace parente. Votre page sera disponnible depuis <u>www.seizam.com/<b>Parent</b>/Nom</u>.',
    'wp-createpage-name-help' => 'Saisissez ici le nom de votre page. Elle sera disponnible depuis <u>www.seizam.com/Parent/<b>Nom</b></u>. <b>Conseil :</b> Choisissez un nom court, facile à retenir et facile à saisir !',
    'wp-createtalk-field' => 'Créer et ouvrir la page de discussion associée.',
    'wp-createpage-license-help' => 'Sélectionnez ici le type de licence (Propriété Intellectuelle) sous laquelle votre page sera diffusée. Nous conseillons une license libre, mais c\'est votre choix.',
    'wp-createpage-createtalk-help' => 'Cochez cette case si vous désirez ouvrir la Discussion pour votre page. Cette seconde page de discussion sera disponnible depuis <u>www.seizam.com/<b>Talk:</b>Parent/Nom</u>.',
    'wp-createtalk-help' => 'Cochez cette case si vous désirez ouvrir la Discussion pour votre page. Cette seconde page de discussion sera disponnible depuis <u>www.seizam.com/<b>Talk:</b>Nom</u>.',
    /* Disclaimer: Wikiplace */
    'wp-create-wp-success' => '<h2>Félicitations !</h2>

Votre nouvelle WikiPlace <b>[[$1]]</b> a été créée avec succès ! {{int:wp-url-tip|$1}} Vous pouvez l\'administrer depuis <b>[[Special:WikiPlaces/Consult:$1|{{int:wikiplaces}}]]</b>.

<h3>{{int:tipsntricks}}</h3>
* {{int:wp-link-tip|$1}}
* {{int:wp-linkalias-tip|$1|My Homepage!}}
* {{int:wp-subpage-tip|$1}}
* {{int:wp-action-tip}}',
    'wp-create-sp-success' => '<h2>Félicitations !</h2>

Votre nouvelle sous-page <b>[[$1/$2|$2]]</b> a été créée avec succès dans [[$1]]! {{int:wp-suburl-tip|$1|$2}} Vous pouvez administrer [[$1]] depuis <b>[[Special:WikiPlaces/Consult:$1|{{int:wikiplaces}}]]</b>.

<h3>{{int:tipsntricks}}</h3>
* {{int:wp-sublink-tip|$1|$2}}
* {{int:wp-sublinkalias-tip|$1|$2}}
* {{int:wp-action-tip}}',
    'copyrightwarning3' => "Toutes les contributions à des WikiPlaces sur {{SITENAME}} pourraient être modifiées en fonction du niveau de protection mis en place par le propriétaire du WikiPlace (voir [[Help:Protection/fr]] pour plus de détails).<br />
Vous nous promettez aussi que vous avez écrit ceci vous-même, ou que vous l’avez copié d’une source provenant du domaine public, ou d’une ressource libre. (voir $1 pour plus de détails).
'''N'utilisez pas de travaux sous droit d'auteur sans autorisation expresse'''",
    /* Warning: Wikiplace */
    'wp-invalid-name' => 'Ce nom est invalide.',
    'wp-name-already-exists' => 'Ce nom existe déjà. Utilisez un autre nom.',
    'wp-create-wp-first' => 'Vous devez d\'abord créer une WikiPlace.',
	'wp-duplicate-exists' => 'Une page ayant le même nom avec une casse différente ([[:$1]]) existe déjà.',
	'wp-duplicate-related' => 'Une page liée ayant le même nom mais avec une casse différente ([[$1]]) existe. Cette page devra se nommer [[$2]].',
	'wp-no-container-found' => 'Cette page devrait appartenir à une WikiPlace. Rééssayez avec un nom commencant par celui d\'une WikiPlace.',
	'wp-not-owner' => 'Vous n\'êtes pas le propriétaire de cette WikiPlace.',
    'wp-not-owner-or-member' => 'Vous n\'êtes ni le propriétaire, ni un membre de cette WikiPlace.',
	'wp-notloggedin' => 'Cette action nécessite que vous soyez identifié. Vous pouvez [[Special:UserLogin/signup|créer un compte]] ou [[Special:UserLogin|vous connecter]].',
    'wp-nosub' => 'Les WikiPlaces ne sont disponibles qu\'après abonnement. [[Special:Subscriptions/New|Cliquez ici pour vous abonner !]]',
    /* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Voici vos abonnements actifs.',
    'wp-subscriptionslist-noactive-header' => '{{int:wp-sub-noactive}} [[Special:Subscriptions/New|Cliquez ici pour vous abonner !]]',
    'wp-subscriptionslist-pending-header' => 'Votre abonnement est en attente car il n\'a pas encore été payé. [[Special:ElectronicPayment|Cliquez ici pour créditer votre compte.]]',
    'wp-subscriptionslist-footer' => 'Désirez vous paramétrer le [[Special:Subscriptions/Renew|renouvellement de votre abonnement]] ? Ou peut-être [[Special:Subscriptions/Change|changer d\'abonnement]] immédiatement ?',
    /* Form: Subscription */
    'wp-sub-new-header' => 'Remplissez ce formulaire pour vous abonner à Seizam.',
    'wp-sub-new-section' => 'S\'abonner',
    'wp-planfield' => 'Offre :',
    'wp-planfield-help' => 'Sélectionnez l\'offre Seizam à laquelle vous souhaitez souscrire grâce à ce menu déroulant. Plus de détails [[Project:Plans|ici]].',
    'wp-checkfield' => 'J\'ai lu, j\'ai compris et j\'approuve les Conditions Générales d’Utilisation de Seizam ([[Project:CGU|CGU]]) <b>ET</b> les Conditions Particulières d’Utilisation de Seizam par les Artistes ([[Project:CPUA|CPUA]]).',
    'wp-checkfield-unchecked' => 'Vous devez approuver nos conditions d\'utilisation pour vous abonner. Merci de cocher la case ci-dessus.',
    'wp-plan-desc-short' => '$1 à $2€',
    'wp-plan-subscribe-go' => 'Je m\'abonne',
    'wp-sub-renew-header' => 'Remplissez ce formulaire pour paramétrer le renouvellement de votre abonnement.',
    'wp-sub-renew-section' => 'Renouveler mon abonnement',
    'wp-do-not-renew' => 'Ne pas renouveler',
    'wp-plan-renew-go' => 'Définir',
    /* Disclaimer: Subscription */
    'wp-new-success-ok' => 'Merci ! Votre abonnement a été enregistré et tout est en ordre. Voulez-vous accéder à [[Special:MySeizam|{{int:myseizam}}]] maintenant ?',
    'wp-renew-success' => 'Votre prochain abonnement a été sélectionné. Merci !',
    'wp-cancel-success' => 'Votre abonnement en attente a été annulé.',
    'wp-sub-noactive' => 'Vous n\'avez pas d\'abonnement actif.',
    /* Warning: Subscription */
    'wp-no-active-sub' => 'Il vous faut un abonnement actif pour réaliser cette action. [[Special:Subscriptions/New|Cliquez ici pour vous abonner !]]',
    'wp-change-plan-required' => 'Vous devez changer d\'abonnement pour réaliser cette action. [[Special:Subscriptions/Change|Cliquez ici pour changer d\'abonnement !]]',
    'wp-wikiplace-quota-exceeded' => 'Votre quota de création de WikiPlaces est dépassé. {{int:wp-change-plan-required}}',
    'wp-page-quota-exceeded' => 'Votre quota de création de pages est dépassé. {{int:wp-change-plan-required}}',
    'wp-diskspace-quota-exceeded' => 'Votre quota d\'importation de fichiers est dépassé.  {{int:wp-change-plan-required}}',
    'wp-subscribe-already' => 'Vous avez déjà un abonnement actif ou en attente.',
    'wp-subscribe-email' => 'Avant de vous abonner, vous devez valider votre adresse de courriel. Allez dans votre boîte de réception et cliquez sur le <u>lien de confirmation</u> que nous vous avons envoyé ([[Special:Preferences#mw-htmlform-email|réenvoyer]]). Rechargez ensuite cette page.',
    'wp-subscribe-change' => 'Vous pouvez sélectionner un abonnement qui succédera à l\'actuel depuis [[Special:Subscriptions/Renew|la page de renouvellement d\'abonnement]]. [[Project:Contact/fr|Contactez-nous]] si vous avez besoin de changer d\'abonnement immédiatement. {{int:sz-asap}}',
    'wp-insufficient-quota' => 'Quota insuffisant (cette offre est trop petite pour votre usage).',
	'wp-plan-not-available-renewal' => 'Offre indisponnible (cette offre n\'est plus proposée).',
    
    
    'forbidden-admin-action' => 'L\'action que vous tentez est réservé aux administrateurs. {{int:sz-report}}',
    
    /* Background action */
    'wp-background-action' => 'Arrière-plan',
    'wp-wikiplace-field' => 'WikiPlace :',
    'wp-filename-field' => 'Nom du fichier :',
    'wp-setbackground-wikiplace-help' => 'Sélectionnez ici la WikiPlace sur laquelle installer l\'arrière plan',
    'wp-setbackground-filename-help' => 'Saisissez ici le nom du [[Help:Fichiers|fichier]] [[Help:Images/fr|image]]. Assurez-vous que ce fichier n\'est pas [[Help:Protection/fr|protégé en "lecture"]] et que vous avez le droit de l\'utiliser.',
    'wp-setbackground-go' => 'Installer',
    'wp-invalid-background' => 'Ce fichier ne peut pas être utilisé comme arrière-plan.',
    'wp-setbackground-header' => '<span style="float:right">Voir : <b>[[Help:Arrière-plan|Aide]]</b></span>Remplissez ce formulaire pour installer une image d\'arrière-plan à l\'une de vos WikiPlaces.',
    'wp-setbackground-section' => 'Installer un arrière-plan',
    'wp-setbackground-success' => 'L\'arrière-plan a été installé !',
    
);
