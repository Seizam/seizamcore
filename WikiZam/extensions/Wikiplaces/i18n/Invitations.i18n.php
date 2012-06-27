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
	'wp-inv-nosub' => 'You need an active subscription in order to create invitations.  [[Special:Subscriptions/new|You can subscribe here]] or [[Special:Invitation/use|use an invitation there]].',	
	'wp-inv-create' => 'You can <b>[[Special:Invitations/create|invite your friends]]</b>.',
	'wp-inv-limitreached' => 'Sorry, you have reached the invitation limit for the month.',
	'wp-inv-no' => 'Sorry, invitations are currently disabled.',
	'wp-inv-success' => 'Great! Your invitation was successfully created.',
	'wp-inv-success-sent' => '{{int:wp-inv-success}} It was sent to <b><nowiki>$1</nowiki></b> .',
    /* Create TP */
	'wp-inv-see-below' => 'Here is the list of all the invitation you created.',
	'wp-inv-list-header' => '$1 {{int:wp-inv-see-below}}',
	'wpi_code' => 'Code',
	'wpi_to_email' => 'Sent to',
	'wp_used' => 'Used by [[User:$1|$1]] on $2',
    /* Create Form */
    'wp-inv-create-section' => 'Create an invitation',
	'wp-inv-category-field' => 'Category:',
	'wp-inv-category-desc' => '$1 ($2 remaining)',
	'wp-inv-category-help' => 'Select here the type of invitation you wish to send. Be careful, the best invitations unlocks great special offers but they are rare!',	
    'wp-inv-mail-section' => 'Send the invitation (optional)',
    'wp-inv-email-help' => 'Type here the e-mail adress of the person you are inviting. {{int:optional-field}}',
    'wp-inv-msg-field' => 'Custom message:',
    'wp-inv-msg-help' => 'Type here a personal message you wish to send to the person you are inviting. {{int:optional-field}}',
    'wp-inv-msg-default' => 'Hello,
As promised, here is the invitation to Seizam.
Enjoy!
$1',
    'wp-inv-language-field' => 'Language:',
    'wp-inv-language-help' => 'Select here the language of the invitation e-mail. We provide this option in case the person you are inviting does not speak your language. {{int:optional-field}}',
	'wp-inv-code-field' => 'Code:',
	'wp-inv-code-help' => 'This is the code used to authenticate the invitation. You can change it and make it prettier if you wish to.',
	'wp-inv-counter-field' => 'Limit:',
	'wp-inv-counter-help' => 'How many times can the code be used. Type "-1" for unlimited.',
    
    /* Use Warnings */
	'wp-use-inv-notloggedin' => 'Sorry. You need to be logged in to use your invitation. Please $1 or $2.',
	'wp-use-inv-header' => 'Please fill the form below to use an invitation code. Or check out [[Special:Subscriptions/new|our regular offers]].',
	'wp-use-inv-ok' => 'Great! Your invitation is valid. You now have access to new special offers.',
	'wp-use-inv-invalid' => 'Sorry. This code is invalid. {{int:sz-report}}',
	'wp-use-inv-nolonger' => 'Sorry. Your invitation has expired. {{int:sz-report}}',
    
    /* Use TP */
    'wp-use-inv-field' => 'Code:',
    'wp-use-inv-help' => 'Type here the invitation code you received. Most likely a string looking like "3066E159C501F".',
	'wp-use-inv-go' => 'Use this code!',
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
    
);
