<?php

$messages = array();

/*
 * English
 */
$messages['en'] = array(
    /*
     * Common Elements 
     */
    'wpm-renew' => 'Use
{{canonicalurl:{{#special:Subscriptiptions/renew}}}}
to select your next plan.',
    'action-required' => '(action required)',
    /*
     * Header & Footer 
     */
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
    /*
     * wpm-activation
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date) ); 
     */
    'wpm-activation-subj' => 'Your subscription is now active.',
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
    /*
     * renewal-soon-no
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date ) );
     */
    'wpm-renewal-soon-no-subj' => 'Your subscription is ending soon! {{int:action-required}}',
    'wpm-renewal-soon-no-body' => 'Your current subscription to the plan "{{int:$1}}" which started $2 will end $3.
        
You did not select any offer to follow the current one.
        
WARNING: You need to setup your subscription renewal or you will not be able to use {{SITENAME}} after $3!

What to do?

{{int:wpm-renew}}',
    /*
     * renwal-soon-warning
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date),
     * $old_next_plan->getName(),
     * $next_plan->getName() ,
     * $reason
     */
    'wpm-renewal-soon-warning-subj' => 'Your subscription will be renewed soon!',
    'wpm-renewal-soon-warning-body' => 'Your current subscription to the plan "{{int:$1}}" which started $2 will end $3.

Sadly, your renewal plan previously selected ("{{int:$4}}") is not valid for the following reason:
{{int:$6}}
We therefore replaced it with the equivalent plan : "{{int:$5}}".

WARNING: Your subscription will be automatically renewed from $3 with this plan ("{{int:$5}}")!

What to do?

{{int:wpm-renew}}',
    /*
     * renewal-soon-valid
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date),
     * $next_plan->getName());
     */
    'wpm-renewal-soon-valid-subj' => '{{int:wpm-renewal-soon-warning-subj}}',
    'wpm-renewal-soon-valid-body' => 'Your current subscription to the plan "{{int:$1}}" which started $2 will end $3.

Your renewal plan ("{{int:$3}}") will be automatically activated from $3. Although, you could still change it.

What to do?

{{int:wpm-renew}}',
    /*
     * renewal-pe
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date)
     */
    'wpm-renewal-pe-subj' => '{{int:wpm-renewal-ok-subj}} {{int:action-required}}',
    'wpm-renewal-pe-body' => 'Thank you very much! Your subscription to the plan "{{int:$1}}" is active since $2. It will end $3.

WARNING: Your subscription has not been paid for yet!

What to do?

Please use
{{canonicalurl:{{#special:ElectronicPayment}}}}
to credit your account.',
    /*
     * renewal-ok
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date)
     */
    'wpm-renewal-ok-subj' => 'Your subscription has been renewed.',
    'wpm-renewal-ok-body' => '{{int:wpm-activation-body}}',
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
    /*
     * Common Elements 
     */
    'wpm-renew' => 'Utilisez
{{canonicalurl:{{#special:Subscriptiptions/renew}}}}
pour sélectionner votre prochaine offre.',
    'action-required' => '(action requise)',
    /*
     * Header & Footer 
     */
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
    /*
     * wpm-activation
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date) ); 
     */
    'wpm-activation-subj' => 'Votre abonnement est maintenant actif.',
    'wpm-activation-body' => 'Merci beaucoup ! Votre abonnement à l\'offre "{{int:$1}}" est actif depuis le $2. Il finira le $3.

Que faire ?

Voir
{{canonicalurl:{{#special:Wikiplaces}}}}
pour créer et configurer vos pages.

Utilisez
{{canonicalurl:{{#special:Upload}}}}
pour importer votre travail en ligne.

Visitez
{{canonicalurl:{{#special:Subscriptions}}}}
pour gérer vos abonnements.',
    /*
     * renewal-soon-no
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date ) );
     */
    'wpm-renewal-soon-no-subj' => 'Votre abonnement se termine bientôt! {{int:action-required}}',
    'wpm-renewal-soon-no-body' => 'Votre abonnement courant à l\'offre "{{int:$1}}" qui a débutté le $2 se finira le $3.
        
Vous n\'avez pas sélectionné d\'offre pour succéder à l\'actuelle.
        
ATTENTION : Vous devez paramétrer le renouvellement de votre abonnement ou vous ne pourrez plus utiliser {{SITENAME}} après $3 !

Que faire ?

{{int:wpm-renew}}',
    /*
     * renwal-soon-warning
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date),
     * $old_next_plan->getName(),
     * $next_plan->getName() ,
     * $reason
     */
    'wpm-renewal-soon-warning-subj' => 'Votre abonnement va bientôt être renouvellé !',
    'wpm-renewal-soon-warning-body' => 'Votre abonnement actuel à l\'offre "{{int:$1}}" qui a débutté le $2 se finira le $3.

Malheureusement, l\'offre de renouvellement précédement sélectionnée ("{{int:$4}}") n\'est pas valide pour la raison suivante :
{{int:$6}}
Nous l\'avons donc remplacée par l\'offre équivalente : "{{int:$5}}".

ATTENTION : Votre abonnement va être automatiquement renouvellé à partir du $3 avec cette offre ("{{int:$5}}") !

Que faire ?

{{int:wpm-renew}}',
    /*
     * renewal-soon-valid
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date),
     * $next_plan->getName());
     */
    'wpm-renewal-soon-valid-subj' => '{{int:wpm-renewal-soon-warning-subj}}',
    'wpm-renewal-soon-valid-body' => 'Votre abonnement courant à l\'offre "{{int:$1}}" qui a débutté le $2 se finira le $3.

Votre offre de renouvellement ("{{int:$3}}") sera automatiquement activée à partir du $3. Cependant, vous pouvez toujours en changer.

Que faire ?

{{int:wpm-renew}}',
    /*
     * renewal-pe
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date)
     */
    'wpm-renewal-pe-subj' => '{{int:wpm-renewal-ok-subj}} {{int:action-required}}',
    'wpm-renewal-pe-body' => 'Merci beaucoup ! Votre abonnement à l\'offre "{{int:$1}}" est actif depuis le $2. Il finira le $3.

ATTENTION: Votre abonnement n\'a pas encore été réglé !

Que faire ?

Merci d\'utiliser
{{canonicalurl:{{#special:ElectronicPayment}}}}
pour créditer votre compte.',
    /*
     * renewal-ok
     * 
     * $plan->getName(),
     * self::timeAndDateUserLocalized($user, $this->wps_start_date ),
     * self::timeAndDateUserLocalized($user, $this->wps_end_date)
     */
    'wpm-renewal-ok-subj' => 'Votre abonnement a été renouvellé !',
    'wpm-renewal-ok-body' => '{{int:wpm-activation-body}}',
);