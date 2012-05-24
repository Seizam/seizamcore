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
    
    /* Generic keywords */
	'wp-wikiplace' => 'Wikiplace',
	'wp-wikiplaces' => 'Wikiplaces',
    'wp-homepage' => 'Homepage',
    'wp-items' => 'items',
	'wp-subpage' => 'Subpage',
	'wp-subpages' => 'Subpages',
    'wp-name' => 'Name',
	'wp-hits' => 'Hits',
	'wp-bandwidth' => 'Bandwidth',
	'wp-monthly_hits' => 'Hits',
	'wp-monthly_bandwidth' => 'Bandwidth',
    'wp-plan_name' => "Plan name",
	'wp-diskspace' => "Diskspace",
    'wp-nswp' => 'Setting',
    'wp-nswp-talk' => 'Setting talk',
    'wp-max_wikiplaces' => '{{int:wp-wikiplaces}}',
    'wp-max_pages' => '{{int:wp-subpages}}',
    
    /* Actions */
    'wp-seeall' => 'see all',
	'wp-subscribe' => 'Subscribe',
	'wp-subscribe-change' => 'Change subscription',
	'wp-subscribe-renew' => 'Renew',
    
    /* Form: Wikiplace */
    'wp-enter-new-wp-name' => 'Please enter your new Wikiplace name',
    'wp-create-wp-go' => 'Create!',
    'wp-select-wp' => 'Please select a Wikiplace',
    'wp-enter-new-sp-name' => 'Please enter your new subpage name',
    'wp-create-sp-go' => 'Create!',
    
    /* Disclaimer: Wikiplace */
    'wp-create-wp-success' => 'You successfully created your Wikiplace $1.',
    'wp-create-sp-success' => 'You successfully created your subpage $1.',
    'wp-your-total-diskspace' => 'Total diskspace usage: $1',
    
    /* Warning: Wikiplace */
    'wp-invalid-name' => 'This name is invalid.',
    'wp-name-already-exists' => 'This name already exists. Please retry with a different name.',
    'wp-create-wp-first' => 'You need to create a Wikiplace first.',
    
    /* TablePager: Subscription */
    'wp-subscriptionslist-header' => 'Here is a list of all your subscriptions through time:',
    'wp-subscriptionslist-noactive-header' => 'You do not have any active subscription. [[Special:Subscriptions/new|Click here to subscribe again!]]

{{int:wp-subscriptionslist-header}}',
    'wp-subscriptionslist-footer' => 'Would you like to setup your [[Special:Subscriptions/renew|subscription renewal]] plan? Or perhaps [[Special:Subscriptions/change|change your plan]] right now?',
    
    /* Form: Subscription */
    'wp-sub-new-header' => 'Please fill the form below to subscribe to Seizam.',
    'wp-sub-new-section' => 'Subscribe',
    'wp-planfield' => 'Plan:',
    'wp-planfield-help' => 'Select the Plan you wish to subscribe to from this dropdown list of available Plans offered by Seizam. More details [[Special:Offers|here]].',
    
    'wp-checkfield' => 'I read, understood and agree with both the General Terms and Conditions of Use ([[Project:GTCU|GTCU]]) and the Artist Specific Terms and Conditions of Use ([[Project:ASTCU|ASTCU]]) of Seizam.',
    'wp-checkfield-unchecked' => 'You need to agree with our Terms and Conditions to subscribe. Please check the box above.',
    
    'wp-plan-desc-short' => '$1: $2 $3 for $4 months',
	'wp-plan-subscribe-go' => 'Subscribe!',
	'wp-do-not-renew' => 'do not renew',
	'wp-plan-renew-go' => 'Set as my next plan',
    
    /* Disclaimer: Subscription */
	'wp-subscribe-success' => 'You successfully subscribed to our offer $1.',
	'wp-subscribe-tmr-ok' => 'Your payment has been validated, so you can start using your plan.',
	'wp-subscribe-tmr-pe' => 'Your payment is pending. [[Special:ElectronicPayment|Please credit your Seizam account]]. Once paid, your plan will be activated and you will be able to use it.',
	'wp-subscribe-tmr-other' => 'Please check [[Special:Transactions|your transactions]].',
	'wp-renew-success' => 'Next plan selected.',
	
    /* Warning: Subscription */
	'wp-no-active-sub' => 'You need an active subscription to perform this action. [[Special:Subscriptions/new|Click here to subscribe!]]',
	'wp-wikiplace-quota-exceeded' => 'Your wikiplace creation quota is exceeded. You need to upgrade your plan to perform this action. [[Special:Subscriptions/change|Click here to change plan!]]',
	'wp-page-quota-exceeded' => 'Your page creation quota is exceeded. You need to upgrade your plan to perform this action. [[Special:Subscriptions/change|Click here to change plan!]]',
	'wp-diskspace-quota-exceeded' => 'Your file upload quota is exceeded. You need to upgrade your plan to perform this action. [[Special:Subscriptions/change|Click here to change plan!]]',
    'wp-subscribe-already' => 'You already have an active subscription. [[Special:Subscriptions/change|Click here to change your subscription]], or [[Special:MySeizam|here to return to MySeizam]].',
	'wp-subscribe-email' => 'Before taking a subscription, you need to validate your e-mail address. [[Special:Preferences#mw-htmlform-email|Click here to setup and validate your e-mail address!]]',
    'wp-subscribe-change' => 'You can select another plan to start from the end of your current subscription through [[Special:Subscriptions/renew|the subscription renewal page]]. Please [[Project:Contact|contact us]] if you need to switch to another plan right now. {{int:sz-asap}}',
    
    /* Errors */
	'wp-internal-error' => 'Sorry, an internal error occured. {{int:sz-report}}',
    'wp-invalid-request' => 'Sorry, we cannot understand what you try to do OR you are not allowed to do it. {{int:sz-report}}'
	
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
    'wp-desc' => 'Permets aux artistes de créer des Wikiplaces, endroits d\'art et de liberté au sein de Mediawiki.',
	
	'subscriptions' => 'Mes Abonnements',
	'wikiplaces' => 'Mes Wikiplaces',
	'offers' => 'Nos Offres'
);