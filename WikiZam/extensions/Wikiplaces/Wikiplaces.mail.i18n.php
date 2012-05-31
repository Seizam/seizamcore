<?php


$messages = array();

/*  
 * English
 */
$messages['en'] = array(
	'wpm-activation-subj' => 'Your subscription is now active',
	'wpm-activation-body' => '$1,
Your subscription to the offer "{{int:$2}}" is now active.
It ends $3.',
    	
	'wpm-payfail-suj' => 'Payment error',
	'wpm-payfail-body' => '$1,
We inform you that your payment was rejected. Accordingly, your subscription is not active.
To continue using your services, please take out a new offer.',
	
	/** @todo change the following messages */
	
	'wpm-renewal-soon-no-subj' => 'Are you sure you want to leave us?',
	// username , wps_end_date
	'wpm-renewal-soon-no-body' => '$1,
Currently, you have no renewal plan selected. Your current subscription ends $2.',
	
	'wpm-renewal-soon-warning-subj' => 'You should check your renewal plan',
	// username , wps_end_date, next_plan_name , reason
	'wpm-renewal-soon-warning-body' => '$1,
We have detected a problem with your renewal plan: {{int:$4}}.
In order to correct this, we have set your renewal plan to {{int:$3}}.
Your current subscription will be renewed $2. You can still change your renewal plan.',
	
	'wpm-renewal-soon-ok-subj' => 'Your subscription will be renewed soon',
	// username , wps_end_date, next_plan_name
	'wpm-renewal-soon-ok-body' => '$1,
Your current subscription ends $2.
Your renewal plan is {{int:$3}}. You can still change it.',
	
	// username , reason
	'wpm-renewal-error-subj' => 'There was a problem when you renew',
	'wpm-renewal-error-body' =>'$1,
We haven\'t be able to process your renewal because {{int:$2}}',
	
	'wpm-renewal-pe-subj' => 'You renewed, but your payment is pending',
	// username , wps_end_date, plan_name
	'wpm-renewal-pe-body' => '$1,
Your subscription has been renewed with the plan {{int:$3}}. It ends $2.
Your payment is pending, please credit your Seizam account.',
	
	'wpm-renewal-ok-subj' => 'Your succesfully renewed',
	// username , wps_end_date, plan_name
	'wpm-renewal-ok-body' => '$1,
Your subscription has been renewed using plan {{int:$3}}. It ends $2.',
	
	
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
	
	/** @todo change the following messages */
	
	'wpm-renewal-soon-warning-subj' => 'Vérifiez votre plan de renouvellement',
	// username , wps_end_date, next_plan_name , reason
	'wpm-renewal-soon-warning-body' => '$1,
Nous avons détecté un problème avec votre plan de renouvellement, le motif est: {{int:$4}}.
Afin de corriger ce problème, nous vous sélectionné le plan {{int:$3}}.
Votre renouvellement aura lieu le $2, d\'ici là vous pouvez toujours choisir un autre abonnement.',
	
);