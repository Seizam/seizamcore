<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
    /* Header & Footer */
    'wp-mail-header' => 'Dear $1,

',
    'wp-mail-footer' => '
Please, remember we are always available through
{{canonicalurl:{{#project:Contact}}}}
for any questions, critics or advice...

Regards,

             Your friendly {{SITENAME}} notification system

--
To access every services offered by {{SITENAME}}, visit
{{canonicalurl:{{#special:MySeizam}}}}

To change your e-mail notification settings, visit
{{canonicalurl:{{#special:Preferences}}}}

Feedback and further assistance:
{{canonicalurl:{{MediaWiki:Helppage}}}}

PS: You CAN reply to this email!',
    /* wpm-activation
	* $plan->getName(),
    * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
	* self::timeAndDateUserLocalized($user, $this->wps_end_date) );  */
    'wpm-activation-subj' => 'Your subscription is now active',
    'wpm-activation-body' => 'Thank you very much! Your subscription to the plan "{{int:$1}}" is active since $2. It will end $3.

What is next?

See
{{canonicalurl:{{#special:Wikiplaces}}}}
to create and setup your pages.

Use
{{canonicalurl:{{#special:Upload}}}}
to push your work online.

Visit
{{canonicalurl:{{#special:Subscriptions}}}}
to manage your subscriptions.',
    /* renewal-soon-no
     * $plan->getName(),
	 * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
	 * self::timeAndDateUserLocalized($user, $this->wps_end_date ) );
     */
    'wpm-renewal-soon-warning-subj' => 'Your subscription is ending soon! (action required)',
    'wpm-renewal-soon-no-body' => 'Your current subscription to the plan "{{int:$1}}" which started $2 will end $3.
        
WARNING: You need to setup your subscription renewal or you will not be able to use {{SITENAME}} after $3!

Use
{{canonicalurl:{{#special:Subscriptiptions/renew}}}}
to select your next plan.',
    'wpm-payfail-suj' => 'Payment error',
    'wpm-payfail-body' => '$1,
We inform you that your payment was rejected. Accordingly, your subscription is not active.
To continue using your services, please take out a new offer.',
    'wpm-renewal-soon-no-subj' => 'Are you sure you want to leave us?',
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
    'wpm-renewal-error-body' => '$1,
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
    /* Header & Footer */
    'wp-mail-header' => '$1,

',
    'wp-mail-footer' => '             Votre système de notification de {{SITENAME}}

--
Pour accéder à tous les services offerts par {{SITENAME}}, visitez
{{canonicalurl:{{#special:MySeizam}}}}

Pour modifier les paramètres de notification par courriel, visitez
{{canonicalurl:{{#special:Preferences}}}}

Retour et assistance :
{{canonicalurl:{{MediaWiki:Helppage}}}}

PS: Vous POUVEZ répondre à ce courriel !',
    /* */
    'wpm-renewal-soon-warning-subj' => 'Vérifiez votre plan de renouvellement',
    // username , wps_end_date, next_plan_name , reason
    'wpm-renewal-soon-warning-body' => '$1,
Nous avons détecté un problème avec votre plan de renouvellement, le motif est: {{int:$4}}.
Afin de corriger ce problème, nous vous sélectionné le plan {{int:$3}}.
Votre renouvellement aura lieu le $2, d\'ici là vous pouvez toujours choisir un autre abonnement.',
);