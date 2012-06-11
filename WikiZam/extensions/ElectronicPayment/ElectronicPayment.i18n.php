<?php
/**
 * Internationalisation for ElectronicPayment extension
 * 
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Trevor Parscal
 */
$messages['en'] = array(
	'electronicpayment'=>'Electronic Payment',
	'electronicpayment-desc'=>'Virtual Electronic Payment Terminal for Seizam.',
        'ep-attempt-formheader' => 'Please click on the button below (\'\'\'Connect\'\'\') to reach our bank (CIC) secure electronic payment interface. Payment amount: \'\'\'$1\'\'\'.',
        'ep-default-formheader' => 'Please fill the form below to credit your account.',
        'ep-default-formheader-pending' => 'Your pending orders amount for \'\'\'$1{{int:cur-euro}}\'\'\'. Here is the list:',
        'ep-default-formfooter-pending' => 'You can pay for your orders AND credit your account at the same time. Increase the amount of your payment, and banking fees will be saved!',
        'ep-attempt-formfooter' => '[[Special:ElectronicPayment|Click here to change the payment.]]',
        'ep-fail' => 'Sorry. The previous payment you attempted failed. Please try again!',
        'ep-success' => 'Thank you! Your payment has been recorded and your pending orders have been validated.',
        'ep-connect' => 'Connect',
        'ep-cd-amountlabel' => 'Amount in Euros (€):',
        'ep-tm-attempt' => 'Bank Card Payment Attempt',
        'ep-tm-fail' => 'Failed Bank Card Payment',
        'ep-tm-test' => 'Bank Card Payment Test',
        'ep-tm-success' => 'Bank Card Payment',
        'ep-help-amount' => 'Type here the amount of your payment (Format: 12.34 , minimum $1 €).',
        'ep-help-mail' => 'Type here the email adress where the payment confirmation will be sent by our bank (CIC). Change and validate your e-mail address [[Special:Preferences#mw-htmlform-email|here]].',
        'ep-cd-section1' => 'Make an electronic payment (1/3)',
        'ep-section2' => 'Make an electronic payment (2/3)'
);

/** Message documentation (Message documentation)
 *
 *
 *
 */
$messages['qqq'] = array(

);

/** French */
$messages['fr'] = array(
        'electronicpayment' => 'Paiement Électronique',
	'electronicpayment-desc' => 'Terminal de Paiement Électronique Virtuel pour Seizam.',
        'ep-attempt-formheader' => 'Cliquez sur le bouton ci-dessous  (\'\'\'Connexion\'\'\')  pour atteindre l\'interface de paiement sécurisée de notre banque (CIC). Montant du paiement : \'\'\'$1{{int:cur-euro}}\'\'\'',
        'ep-default-formheader' => 'Remplissez le formulaire ci-dessous pour créditer votre compte.',
        'ep-default-formheader-pending' => 'Vos commandes en attente s\'élèvent à \'\'\'$1{{int:cur-euro}}\'\'\'. Voici la liste :', 
        'ep-default-formfooter-pending' => 'Vous pouvez règler vos commandes ET créditer votre compte en même temps. Augmentez le montant de votre paiement, et des frais bancaires seront économisés !',
        'ep-attempt-formfooter' => '[[Special:ElectronicPayment|Cliquez ici pour modifier le paiement.]]',
        'ep-fail' => 'Désolé. La tentative de paiement précédente a échoué. Merci de réessayer !',
        'ep-success' => 'Merci ! Votre paiement a été enregistré et vos commandes en cours ont été validées.',
        'ep-connect' => 'Connexion',
        'ep-cd-amountlabel' => 'Montant en Euros (€) :',
        'ep-tm-attempt' => 'Tentative de paiement par carte bancaire',
        'ep-tm-fail' => 'Paiement par carte bancaire échoué',
        'ep-tm-test' => 'Test de paiement par carte bancaire',
        'ep-tm-success' => 'Paiement par carte bancaire',
        'ep-help-amount' => 'Tappez ici le montant de votre paiement (Format: 12.34 , $1 € minimum).',
        'ep-help-mail' => 'Tappez ici l\'adresse de courriel où la confirmation de paiement sera envoyée par notre banque (CIC). Changez et validez votre adresse de courriel [[Special:Preferences#mw-htmlform-email|ici]].',
        'ep-cd-section1' => 'Réaliser un paiement électronique (1/3)',
        'ep-section2' => 'Réaliser un paiement électronique (2/3)'
    
);