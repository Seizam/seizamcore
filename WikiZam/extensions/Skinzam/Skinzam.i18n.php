<?php

/**
 * Internationalisation for Skinzam extension
 * 
 * @file
 * @ingroup Extensions
 */
$messages = array();

/** 
 * English
 */
$messages['en'] = array(
    'main_page' => 'Special:Welcome',
    'skinzam' => 'SkinZam',
    'sz-skinzam' => 'UI elements for Seizam',
    'sz-skinzam-desc' => 'Holds UI elements for Seizam\'s skin.',
    'tagline' => 'From Seizam.com - For Art & Freedom',
    'sz-myseizam' => 'My Seizam',
    'welcome' => 'Welcome',
    
    'cur-euro' => '€',
    'contactlinks' => 'Project:Contact',
    
    'sz-back' => 'back',
    'sz-legalcontent' => 'Legal Content',
    'sz-gtcu' => '[[Project:GTCU|General Terms and Conditions of Use]]',
    'sz-astcu' => '[[Project:STCUA|Artist Specific Terms and Conditions of Use]]',
    'sz-legalinfo' => '[[Project:Legal_info|Legal Information]]',
    'sz-privacypolicy' => '[[Project:Privacy_policy|Privacy Policy]]',
    
    'sz-generalinfo' => 'General Information',
    'sz-discoverseizam' => '[[Project:Welcome|Discover Seizam]]',
    'sz-browseseizam' => '[[Special:AllPages|Browse Seizam]]',
    'sz-joinseizam' => '[[Special:UserLogin|Join Seizam]]',
    'sz-help' => '[[Help:Contents|Help]]',
    'sz-faq' => '[[Help:FAQ|Frequently Asked Questions]]',
    
    'sz-communicate' => 'Communicate with us',
    'sz-reportabuse' => '[[Project:Contact|Report Abuse]]',
    'sz-reportbug' => '[[Project:Contact|Report Bug]]',
    'sz-technicalsupport' => '[[Project:Contact|Technical Support]]',
    'sz-contactus' => '[[Project:Contact|Contact us]]',
    
    'sz-seizamonsocialnetworks' => 'Seizam on Social Networks',
    'sz-selectlang' => 'Select your Language',
    
    'sz-tagline' => '[[Help:Wikiplace|<span class="fade">Wiki</span>Places]] for Art & Freedom',
    
    'sz-htmlform-helpzonetitle' => 'Need Help?',
    'sz-htmlform-helpzonedefault' => 'This frame will display text to help you fill this form. Just fly over the "\'\'\'?\'\'\'" beside any field to obtain help or details! Visit [[Help:Contents|The Help Pages]] for more help.',
    'sz-report' => 'Please feel free to [[Project:Contact|Contact us]] to report and get this issue solved.',
    'sz-asap' => 'Our team will make it happen as fast as possible!',
    
    'tipsntricks' => 'Tips and Tricks',
    
    'fileurl' => 'File\'s address:',
    
    
    /* Errors */
    'sorry' => 'Sorry!',
	'sz-internal-error' => 'Sorry, an internal error occured. {{int:sz-report}}',
    'sz-invalid-request' => 'Sorry, we do not understand what you want to do OR you are not allowed to do it. {{int:sz-report}}',
	'sz-maintenance' => 'Sorry, this side of the website is closed for maintenance. Please try again later.',
    
    /* Mainpage */
    /* Slideshow */
    'sz-mp-ourfreedoms' => 'Our Freedoms',
    
    'sz-slideshow0' => 'The liberty of creation: ',
    'sz-slideshow0-href' => '{{canonicalurl:Project:Creation}}',
    'sz-slideshow0-src' => '{{STYLEPATH}}/skinzam/content/backflip.jpg',
    'sz-slideshow0-body' => 'Publish your art online, whatever your disciplines are, whatever your vision is.',
    
    'sz-slideshow1' => 'The absence of pollution: ',
    'sz-slideshow1-href' => '{{canonicalurl:Project:Pollution}}',
    'sz-slideshow1-src' => '{{STYLEPATH}}/skinzam/content/neon.jpg',
    'sz-slideshow1-body' => 'No advertisement, no spam. Nothing here will ever spoil your work.',
    
    'sz-slideshow2' => 'The openness of collaboration: ',
    'sz-slideshow2-href' => '{{canonicalurl:Project:Collaboration}}',
    'sz-slideshow2-src' => '{{STYLEPATH}}/skinzam/content/slam.jpg',
    'sz-slideshow2-body' => 'You decide how to share your work, and even who can participate.',
    
    /* Form */
    'sz-mp-joinus' => 'Join us',
    'sz-mp-enter' => 'Enter',
    
    'sz-mp-yourname' => 'Username',
    'sz-mp-yourpassword' => 'Password',
    'sz-mp-yourpasswordagain' => 'Retype password',
    'sz-mp-youremail' => 'E-mail',
    
    'sz-blockjoin0' => 'Our Offers',
    'sz-blockjoin0-href' => '{{canonicalurl:Project:Offers}}',
    'sz-blockjoin0-catch' => 'From <b>5.00€</b> per month.',
    'sz-blockjoin1' => '{{int:sz-myseizam}}',
    'sz-blockjoin1-href' => '{{canonicalurl:Special:MySeizam}}',
    'sz-blockjoin1-catch' => 'Everything you need is here.',
    'sz-blockjoin2' => 'Help us',
    'sz-blockjoin2-href' => '{{canonicalurl:Project:Contribute}}',
    'sz-blockjoin2-catch' => 'The community needs you.',
    
    /* Triptic */
    'sz-mp-triptic' => 'Discover Seizam',
    
    'sz-triptic0' => 'Visit',
    'sz-triptic0-href' => '{{canonicalurl:Project:Tour}}',
    'sz-triptic0-src' => '{{STYLEPATH}}/skinzam/content/tiptoe.jpg',
    'sz-triptic0-caption' => 'This quick guided tour will get you all the way through Seizam. From the philosophy to the technology, you will not miss anything.',
    
    'sz-triptic1' => 'Follow',
    'sz-triptic1-href' => 'http://www.davidcanwin.com',
    'sz-triptic1-src' => '{{STYLEPATH}}/skinzam/content/bambi.jpg',
    'sz-triptic1-caption' => 'Art, freedom and sling-shooting. <b>DavidCanWin.com</b> is the development blog and thought process of Seizam.',
    
    'sz-triptic2' => 'Learn',
    'sz-triptic2-href' => '{{canonicalurl:Help:Contents}}',
    'sz-triptic2-src' => '{{STYLEPATH}}/skinzam/content/shelf.jpg',
    'sz-triptic2-caption' => 'Frequently Asked Questions, tutorials, examples, documentation... Everything you need to learn how to build great wikiplaces is here.',

    /* i18n overwrite */
    'confirmemail_oncreate' => 'A confirmation code was sent to your e-mail address. Please check your e-mails and follow the provided instructions to enable e-mail features and subscriptions within Seizam.',
    'welcomecreation' => '== Welcome, $1! ==
Congratulations and thank you! Your Seizam account has been successfully created.
=== What is next? ===
* To enable Wikiplaces, pick a [[Special:Plans|plan]] and [[Special:Subscriptions|subscribe]] to Seizam.
* Visit [[Special:MySeizam|My Seizam]], everything you need is there, and much more!
* Check [[Special:Preferences|My Preferences]] for advanced setup.
* Look into [[Help:Contents|the help]] to learn how to use Seizam.
=== Remember! ===
Please, remember we are always available through [[Project:Contact]] for any questions, critics or advice...

Regards,

[[Project:Team|The Team]]'
);

/** 
 * Message documentation (Message documentation)
 */
$messages['qqq'] = array(
    'vector' => 'UI means User Interface. Seizam is the name of an interface skin.',
    'vector-desc' => '{{desc}}'
);

/**
 * French
 */
$messages['fr'] = array(
    'sz-seizam' => 'Éléments IHM pout Seizam',
    'sz-seizam-desc' => 'Contient éléments IHM pour l\'interface Seizam.',
    'tagline' => 'Depuis Seizam.com - Art & Liberté',
    'sz-myseizam' => 'Mon Seizam',
    'welcome' => 'Bienvenue',
    
    'mycontris' => 'Mes Contributions',
    'mypreferences' => 'Mes Préférences',
    'mywatchlist' => 'Ma Liste de Suivi',
    'mytalk' => 'Ma Page de Discussion',
    
    'sz-back' => 'retour',
    'sz-legalcontent' => 'Contenu légal',
    'sz-gtcu' => '[[Project:CGU|Conditions Générales d\'Utilisation]]',
    'sz-astcu' => '[[Project:CPUA|Conditions Particulières d\'Utilisation pour les Artistes]]',
    'sz-legalinfo' => '[[Project:Mentions_légales|Mentions légales]]',
    'sz-privacypolicy' => '[[Project:Politique_de_confidentialité|Politique de confidentialité]]',
    
    'sz-generalinfo' => 'Informations générales',
    'sz-discoverseizam' => '[[Project:Bienvenue|Découvrez Seizam]]',
    'sz-browseseizam' => '[[Special:AllPages|Parcourez Seizam]]',
    'sz-joinseizam' => '[[Special:UserLogin|Rejoignez Seizam]]',
    'sz-help' => '[[Help:Contents/fr|Aide]]',
    'sz-faq' => '[[Help:FAQ/fr|Questions fréquentes]]',
    
    'sz-communicate' => 'Communiquer avec nous',
    'sz-reportabuse' => '[[Project:Contact/fr|Signaler un abus]]',
    'sz-reportbug' => '[[Project:Contact/fr|Signaler un bogue]]',
    'sz-technicalsupport' => '[[Project:Contact/fr|Support technique]]',
    'sz-contactus' => '[[Project:Contact/fr|Nous contacter]]',
    
    'sz-seizamonsocialnetworks' => 'Seizam sur les réseaux sociaux',
    'sz-selectlang' => 'Selectionnez votre language',
    
    'sz-tagline' => '[[Help:Wikiplace|<span class="fade">Wiki</span>Places]] d\'Art et de Liberté',
    
    'sz-htmlform-helpzonetitle' => 'Besoin d\'aide ?',
    'sz-htmlform-helpzonedefault' => 'Ce cadre affichera un texte d\'aide pour le remplissage de ce formulaire. Il vous suffit de passer sur le "\'\'\'?\'\'\'" à côté d\'un champ pour obtenir de l\'aide ou des précisions ! Visitez [[Help:Contents/fr|Les Pages d\'Aide]] pour plus d\'aide.',
    'sz-report' => 'N\'hésitez pas à [[Project:Contact/fr|nous contacter]] pour signaler et résoudre ce problème.',
    'sz-asap' => 'Notre équipe s\'en occupera le plus rapidement possible!',
    
    'tipsntricks' => 'Trucs et astuces',
    
    'fileurl' => 'Adresse du fichier :',
    
    /* Errors */
    'sorry' => 'Désolé!',
	'sz-internal-error' => 'Pardon, une erreur interne s\'est produite. {{int:sz-report}}',
    'sz-invalid-request' => 'Pardon, nous ne comprenons pas votre requête OU vous n\'êtes pas autorisé à la réaliser. {{int:sz-report}}',
	'sz-maintenance' => 'Pardon, cette partie du site est fermée pour maintenance. Merci de réessayer plus tard.',
    
    /* Mainpage */
    /* Slideshow */
    'sz-mp-ourfreedoms' => 'Nos libertés',
    
    'sz-slideshow0' => 'L\'espace pour la création : ',
    'sz-slideshow0-href' => '{{canonicalurl:Project:Creation/fr}}',
    'sz-slideshow0-src' => '{{STYLEPATH}}/skinzam/content/backflip.jpg',
    'sz-slideshow0-body' => 'Publiez votre art en ligne, quelles que soient vos disciplines, quelle que soit votre vision.',
    
    'sz-slideshow1' => 'L\'absence de pollution : ',
    'sz-slideshow1-href' => '{{canonicalurl:Project:Pollution/fr}}',
    'sz-slideshow1-src' => '{{STYLEPATH}}/skinzam/content/neon.jpg',
    'sz-slideshow1-body' => 'Pas de publicité, pas de spam. Ici, votre travail ne sera jamais pollué.',
    
    'sz-slideshow2' => 'L\'ouverture vers la collaboration : ',
    'sz-slideshow2-href' => '{{canonicalurl:Project:Collaboration/fr}}',
    'sz-slideshow2-src' => '{{STYLEPATH}}/skinzam/content/slam.jpg',
    'sz-slideshow2-body' => 'Vous décidez comment partager votre travail, mais aussi qui peut y participer.',
    
    /* Form */
    'sz-mp-joinus' => 'Nous rejoindre',
    'sz-mp-enter' => 'Entrez',
    
    'sz-mp-yourname' => 'Nom d’utilisateur',
    'sz-mp-yourpassword' => 'Mot de passe',
    'sz-mp-yourpasswordagain' => 'Confirmez le mot de passe',
    'sz-mp-youremail' => 'Courriel',
    
    'sz-blockjoin0' => 'Nos offres',
    'sz-blockjoin0-href' => '{{canonicalurl:Project:Offers}}',
    'sz-blockjoin0-catch' => 'À partir de <b>5,00€</b> par mois.',
    
    'sz-blockjoin1-catch' => 'Tout Seizam à portée de main.',
    
    'sz-blockjoin2' => 'Aidez-nous',
    'sz-blockjoin2-href' => '{{canonicalurl:Project:Contribute}}',
    'sz-blockjoin2-catch' => 'La communauté a besoin de vous.',
    
    /* Triptic */
    'sz-mp-triptic' => 'Découvrez Seizam',
    
    'sz-triptic0' => 'Visitez',
    'sz-triptic0-href' => '{{canonicalurl:Project:Tour/fr}}',
    'sz-triptic0-src' => '{{STYLEPATH}}/skinzam/content/tiptoe.jpg',
    'sz-triptic0-caption' => 'Cette visite rapide vous guidera à travers tout Seizam. De la philosophie à la technologie, vous ne manquerez rien.',
    
    'sz-triptic1' => 'Suivez',
    'sz-triptic1-href' => 'http://www.davidcanwin.com',
    'sz-triptic1-src' => '{{STYLEPATH}}/skinzam/content/bambi.jpg',
    'sz-triptic1-caption' => '<b>DavidCanWin.com</b>, c\'est notre blog de developpement mais aussi un lieu d\'échange et de réflexion à propos d\'art et de liberté.',
    
    'sz-triptic2' => 'Apprenez',
    'sz-triptic2-href' => '{{canonicalurl:Help:Contents/fr}}',
    'sz-triptic2-src' => '{{STYLEPATH}}/skinzam/content/shelf.jpg',
    'sz-triptic2-caption' => 'Tutoriaux, exemples, Foire Aux Questions, documentation... Tout ce dont vous avez besoin pour construire vos Wikiplaces se trouve là.',

    /* i18n overwrite */
    'confirmemail_oncreate' => 'Un code de confirmation a été envoyé à votre adresse de courriel. Merci de consulter vos courriels et de suivre les instuctions fournies pour activer les fonctions de messagerie et d\'abonnement.',
    'welcomecreation' => '== Bienvenue, $1! ==
Félicitation et merci ! Votre compte Seizam a été créé avec succès.
=== Que faire maintenant ? ===
* Pour activer les Wikiplaces, choisissez une [[Special:Plans|offre]] et [[Special:Subscriptions|abonnez-vous]] à Seizam.
* Visitez [[Special:MySeizam|Mon Seizam]], tout ce dont vous avez besoin est là, et bien plus !
* Vérifiez [[Special:Preferences|Mes Préférences]] pour les réglages avancés.
* Consultez [[Help:Contents|l\'aide]] pour apprendre à utiliser Seizam.
=== N\'oubliez pas ! ===
S\'il vous plaît, rappelez-vous que nous restons à votre disposition à travers [[Project:Contact|la page de contact]] pour toutes questions, critiques ou conseils...

Cordialement,

[[Project:Team|L\'équipe]]'
);