<?php


$messages = array();

/*  English
 */
$messages['en'] = array(
	
	'wikiplacesadmin' => 'Wikiplaces administration',
	'subscriptions' => 'My Subscriptions',
	'wikiplaces' => 'My Wikiplaces',
	'offers' => 'Offers',
	
	'wp-sub-activation-email-subj' => 'Your subscription is now active',
	'wp-sub-activation-email-body' => '$1,
		
Your subscription to the offer "$2" is now active.
It will ends at $3.',
	'wp-sub-tmrko-email-subj' => 'Payment error',
	'wp-sub-tmrko-email-body' => '$1,
		
We inform you that your payment was rejected. Accordingly, your subscription is not active.
To continue using your services, please take out a new offer.',
	
	'wp-no-active-sub' => 'You need an active subscription to perform this action.',
	'wp-wikiplace-quota-exceeded' => 'Your wikiplace creation quota is exceeded. You need to upgrade your plan to perform this action.',
	'wp-page-quota-exceeded' => 'Your page creation quota is exceeded. You need to upgrade your plan to perform this action.',
	
	'wp-internal-error' => 'An error occured. Please try again.',
	
	'wp-nologintext' => 'You need to be <span class="plainlinks">[{{fullurl:{{#Special:UserLogin}}|returnto=$1}} logged in]</span> to continue.',

	'wp-subscribe-new' => 'Take a subscription',
	'wp-subscribe-change' => 'Change plan',
	'wp-subscribe-renew' => 'Choose renewal plan',
	'wp-subscribe-list' => 'Subscription history',
	
	'wp-subscribe-already' => 'You already subscribed to a plan.',
	'wp-subscribe-email' => 'Before taking a subscription, you need to validate your e-mail address. Please follow the instructions in the e-mail you have received when creating your account, to ensure that you can be contacted.',

	'wp-select-a-plan' => 'Please select an offer',
	'wp-invalid-plan' => 'This plan is invalid.',
	'wp-cannot-subscribe-plan' => 'You cannot subscribe to this plan. Please choose another one.',
	
	'wp-plan-desc-short' => '$1: $2 $3 for $4 months',
	'wp-plan-subscribe-go' => 'Subscribe now!',
	
	'wp-subscribe-success' => 'You successfully subscribed to our offer $1.',
	'wp-subscribe-tmr-ok' => 'Your payment has been validated, so you can start using your plan.',
	'wp-subscribe-tmr-pe' => 'Your payment is pending. [[Special:ElectronicPayment|Please credit your Seizam account]]. Once paid, your plan will be activated and you will be able to use it.',
	'wp-subscribe-tmr-other' => 'Please check [[Special:Transactions|your transactions]].',
	
	
	
	'wp-enter-new-wp-name' => 'Please enter your new Wikiplace name',
	'wp-invalid-name' => 'This name is invalid.',
	'wp-name-already-exists' => 'This name already exists. Please retry with a different name.',
	'wp-create-wp-go' => 'Create!',
	'wp-create-wp-success' => 'You successfully created your Wikiplace $1.',
	
	'wp-create-wp-first' => 'You need to create a Wikiplace first.',
	
	'wp-list-all-my-wp' => 'My Wikiplaces',
	'wp-create-subpage' => 'New Wikiplace subpage',
	
	
	'wp-select-wp' => 'Please select a Wikiplace',
	'wp-enter-new-sp-name' => 'Please enter your new subpage name',
	'wp-invalid-wp' => 'This Wikiplace is invalid.',
	'wp-create-sp-go' => 'Create!',
	'wp-create-sp-success' => 'You successfully created your subpage $1.',
	
	
	'wp-seeall' => 'see all',
    'wp-see' => 'see',
    'wp-create' => 'create',
    'wp-edit' => 'edit',
    'wp-restrict' => 'restrict',
    'wp-talk' => 'talk',
    'wp-history' => 'history',
    'wp-create-page' => 'Create a new Page',
    'wp-create-wp' => 'Create a new Wikiplace',
    'wp-items' => 'items',
    'wp-homepage' => 'Homepage',
	'wp-subpage' => 'Subpage',
	
	'wp-your-total-diskspace' => 'Total diskspace usage: $1',
	'wpwtp-page_title' => 'Wikiplace',
	'wpwtp-count(*)' => 'Subpages',
	'wpwtp-wpw_monthly_page_hits' => "Monthly hits",
	'wpwtp-wpw_monthly_bandwidth' => "Monthly bandwidth",
	
	'wpstp-wps_active' => "",
	'wpstp-wps_start_date' => "Start",
	'wpstp-wps_end_date' => "End",
	'wpstp-wpp_name' => "Plan name",
	'wpstp-wps_tmr_status' => "Paiement status",
	'wpstp-wpp_nb_wikiplaces' => "Wikiplaces",
	'wpstp-wpp_nb_wikiplace_pages' => "Pages",
	'wpstp-wpp_diskspace' => "Diskspace",
	'wpstp-wpp_monthly_page_hits' => 'Monthly page hits',
	'wpstp-wpp_monthly_bandwidth' => 'Monthly bandwidth',
	'wp-sub-unactive' => 'not active',
	'wp-sub-active' => 'active',
	'wp-sub-tmrstatus-OK' => 'paid',
	'wp-sub-tmrstatus-PE' => 'pending',
	'wp-sub-tmrstatus-KO' => 'canceled',
	
	'wppatp-subpage_title' => 'Name',
    'wppatp-subpage_namespace' => 'Type',
    'wppatp-subpage_touched' => 'Modification Date',
    'wppatp-subpage_counter' => 'Total hits',

	
	// wp-transaction-plan-XXXX for each plan in database, with XXXX = wpp_name field value

	
	/** @todo: remove the next lines after tests complete */
	'wp-plan-name-test-normal' => "Test plan",
	'wp-plan-name-test-plus' => "Test plan +",
	'wp-plan-name-invitation' => "Invitation only test plan",
	'wp-transaction-test-add-10' => 'Credit 10 EUR for testing purpose',
	
);

/*  Message documentation
 */
$messages['qqq'] = array(

);

/*  French
 */
$messages['fr'] = array(

);