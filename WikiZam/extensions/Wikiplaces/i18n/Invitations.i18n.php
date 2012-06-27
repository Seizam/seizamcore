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
    'wp-inv-list-footer-li' => '<b>[[Special:Invitations/Create:$1|{{int:$2}}]]</b>: {{int:$2-desc}} ($3 out of $4 remaining this month)',
    'wp-inv-list-help' => '=== About Invitations ===
Each month, you can generate a few invitation codes for your friends and relatives. A code provides great discounts on first subscription offers, it can be used through [[Special:Invitation/Use]]. More informations about invitations at [[Help:Invitations]].',
	'wpi-code' => 'Code',
	'wpi-to-email' => 'Sent to',
    'wpi-type' => 'Type',
	'wpi-used' => 'Used by [[User:$1|$1]] on $2',
    'wpi-unlimited' => 'Unlimited, used $1 times.',
    'wpi-notsent' => 'Not Sent',
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
	'wp-inv-code-field' => 'Code:',
	'wp-inv-code-help' => 'This is the code used to authenticate the invitation. You can change it and make it prettier if you wish to.',
	'wp-inv-counter-field' => 'Limit:',
	'wp-inv-counter-help' => 'How many times can the code be used. Type "-1" for unlimited.',
    
    /* Use Warnings */
	'wp-use-inv-notloggedin' => 'Sorry. You need to be logged in to use your invitation. Please $1 or $2.',
	'wp-use-inv-header' => 'Please fill the form below to use an invitation code. Or check out [[Special:Subscriptions/new|our regular offers]].',
	'wp-use-inv-ok' => 'Great! Your invitation is valid. You now have access to new special offers.',
	'wp-use-inv-invalid' => 'This code is invalid',
	'wp-use-inv-nolonger' => 'Sorry. Your invitation has expired.',
    
    /* Use TP */
    'wp-use-inv-field' => 'Code:',
    'wp-use-inv-help' => 'Type here the invitation code you received. Most likely a 12 characters string looking like "GNXHGKH79XQA".',
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
