<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
	/* General */
    'subscriptions' => 'My Subscriptions',
    /* Actions */
    'wp-subscribe' => 'Subscribe',
    'wp-subscribe-change' => 'Change subscription',
    'wp-subscribe-renew' => 'Renew',
    /* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Here are your active subscriptions.',
    'wp-subscriptionslist-noactive-header' => '{{int:wp-sub-noactive}} [[Special:Subscriptions/New|Click here to subscribe !]]',
    'wp-subscriptionslist-pending-header' => 'Your subscription is pending because it has not been paid for yet. [[Special:ElectronicPayment|Click here to credit your account.]]',
    'wp-subscriptionslist-footer' => 'Would you like to setup your [[Special:Subscriptions/Renew|subscription renewal]] plan? Or perhaps [[Special:Subscriptions/Change|change your plan]] right now?',
    /* Form: Subscription */
    'wp-sub-new-header' => 'Fill the form below to subscribe to Seizam. If you have an invitation, [[Special:Invitation|click here]].',
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
    'wp-wikiplace-no-active-sub' => 'This WikiPlace has no active subscription associated. Its owner needs to (re)subscribe to let you perform this action.',
    'wp-change-plan-required' => 'You need to upgrade your plan to perform this action. [[Special:Subscriptions/Change|Click here to change plan!]]',
    'wp-owner-change-plan-required' => 'To let you perform this action, the owner of this WikiPlace needs to upgrade his/her plan.',
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
    'subscriptions' => 'Mes abonnements',
    /* Actions */
    'wp-subscribe' => 'Abonnement',
    'wp-subscribe-change' => 'Changer d\'Abonnement',
    'wp-subscribe-renew' => 'Renouveler',
    /* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Voici vos abonnements actifs.',
    'wp-subscriptionslist-noactive-header' => '{{int:wp-sub-noactive}} [[Special:Subscriptions/New|Cliquez ici pour vous abonner !]]',
    'wp-subscriptionslist-pending-header' => 'Votre abonnement est en attente car il n\'a pas encore été payé. [[Special:ElectronicPayment|Cliquez ici pour créditer votre compte.]]',
    'wp-subscriptionslist-footer' => 'Désirez vous paramétrer le [[Special:Subscriptions/Renew|renouvellement de votre abonnement]] ? Ou peut-être [[Special:Subscriptions/Change|changer d\'abonnement]] immédiatement ?',
    /* Form: Subscription */
    'wp-sub-new-header' => 'Remplissez ce formulaire pour vous abonner à Seizam. Si vous avez une invitation, [[Special:Invitation|cliquez ici]].',
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
    'wp-wikiplace-no-active-sub' => 'Cette WikiPlace n\'a pas d\'abonnement actif associé. Son/sa propriétaire doit s\'abonner pour vous permettre de réaliser cette action.',
    'wp-change-plan-required' => 'Vous devez changer d\'abonnement pour réaliser cette action. [[Special:Subscriptions/Change|Cliquez ici pour changer d\'abonnement !]]',
    'wp-owner-change-plan-required' => 'Pour vous permettre de réaliser cette action, le propriétaire de ce WikiPlace doit changer d\'abonnement.',
    'wp-wikiplace-quota-exceeded' => 'Votre quota de création de WikiPlaces est dépassé. {{int:wp-change-plan-required}}',
    'wp-wikiplace-page-quota-exceeded' => 'Le quota de création de page de cette WikiPlace est dépassé.  {{int:wp-owner-change-plan-required}}',
    'wp-page-quota-exceeded' => 'Votre quota de création de pages est dépassé. {{int:wp-change-plan-required}}',
    'wp-diskspace-quota-exceeded' => 'Votre quota d\'importation de fichiers est dépassé.  {{int:wp-change-plan-required}}',
    'wp-wikiplace-diskspace-quota-exceeded' => 'Le quota d\'importation de fichiers de cette WikiPlace est dépassé.  {{int:wp-owner-change-plan-required}}',
    'wp-subscribe-already' => 'Vous avez déjà un abonnement actif ou en attente.',
    'wp-subscribe-email' => 'Avant de vous abonner, vous devez valider votre adresse de courriel. Allez dans votre boîte de réception et cliquez sur le <u>lien de confirmation</u> que nous vous avons envoyé ([[Special:Preferences#mw-htmlform-email|réenvoyer]]). Rechargez ensuite cette page.',
    'wp-subscribe-change' => 'Vous pouvez sélectionner un abonnement qui succédera à l\'actuel depuis [[Special:Subscriptions/Renew|la page de renouvellement d\'abonnement]]. [[Project:Contact/fr|Contactez-nous]] si vous avez besoin de changer d\'abonnement immédiatement. {{int:sz-asap}}',
    'wp-insufficient-quota' => 'Quota insuffisant (cette offre est trop petite pour votre usage).',
	'wp-plan-not-available-renewal' => 'Offre indisponnible (cette offre n\'est plus proposée).',
);
