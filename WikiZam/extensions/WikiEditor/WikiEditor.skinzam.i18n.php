<?php

/**
 * Internationalisation for WikiEditor extension
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();

/** English
 * @author Yann Missler
 */
$messages['en'] = array(
    /* Toolbar - Help Section - Widget */
    'wikieditor-toolbar-help-page-widget' => 'Widgets',
    'wikieditor-toolbar-help-heading-help' => 'More help',
    'wikieditor-toolbar-help-content-widget1-description' => 'Vimeo',
    'wikieditor-toolbar-help-content-widget1-syntax' => '<nowiki>{{Vimeo:id=43347454|width=392|height=220}}</nowiki>',
    'wikieditor-toolbar-help-content-widget1-help' => '<a href="/Widget:Vimeo" title="Widget:Vimeo">Vimeo help page</a>',
    'wikieditor-toolbar-help-content-widget2-description' => '',
    'wikieditor-toolbar-help-content-widget2-syntax' => '<a href="/Widget:YouTube" title="Widget:YouTube">YouTube</a>, <a href="/Widget:Flickr" title="Widget:Flickr">Flickr</a>, <a href="/Widget:SoundCloud" title="Widget:SoundCloud">SoundCloud</a> ...',
    'wikieditor-toolbar-help-content-widget2-help' => '<a href="/Category:Media_Widgets" title="Category:Media Widgets">List of media widgets</a>',
    'wikieditor-toolbar-help-content-widget3-description' => 'Twitter',
    'wikieditor-toolbar-help-content-widget3-syntax' => '<nowiki>{{Twitter:user=seizam|scrollbar|live}}</nowiki>',
    'wikieditor-toolbar-help-content-widget3-help' => '<a href="/Widget:Twitter" title="Widget:Twitter">Twitter help page</a>',
    'wikieditor-toolbar-help-content-widget4-description' => '',
    'wikieditor-toolbar-help-content-widget4-syntax' => '<a href="/Widget:Google%2B" title="Widget:Google+">Google+</a>, <a href="/Widget:AddThis" title="Widget:AddThis">AddThis</a>, <a href="/Widget:Facebook" title="Widget:Facebook">Facebook</a> ...',
    'wikieditor-toolbar-help-content-widget4-help' => '<a href="/Category:Social_Widgets" title="Category:Social Widgets">List of social widgets</a>',
    /* Toolbar - Widget */
    'wikieditor-toolbar-tool-widget' => 'Widget',
    'wikieditor-toolbar-tool-widget-example' => 'Twitter:user=seizam',
    'wikieditor-toolbar-tool-widget-title' => 'Insert widget',
    'wikieditor-toolbar-widget-default' => 'Select a widget',
    'wikieditor-toolbar-widget-select' => 'Widget:',
    'wikieditor-toolbar-widget-arguments' => 'Parameters:',
    'wikieditor-toolbar-widget-insert' => 'Insert widget',
    'wikieditor-toolbar-widget-cancel' => 'Cancel',
    'wikieditor-toolbar-widget-help' => 'See help at <b><a target="_blank" href="/Widget:$1">Widget:$1</a></b>',
    'wikieditor-toolbar-widget-list' => '<nowiki>
{{:Media widgets}}
{{Vimeo:id = 43347454
| width = 392px
| height = 220px
| right}}
{{:Social widgets}}
{{Twitter:user = TechCrunch
| list = realtime-web
| width = 220
| height = 180
| scrollbar
| live
| right}}
</nowiki>',
);

/** English
 * @author Clément Dietschy
 */
$messages['fr'] = array(
    /* Toolbar - Help Section - Widget */
    'wikieditor-toolbar-help-heading-help' => 'Plus d\'aide',
    'wikieditor-toolbar-help-content-widget1-help' => '<a href="/Widget:Vimeo/fr" title="Widget:Vimeo">Page d\'aide pour Vimeo</a>',
    'wikieditor-toolbar-help-content-widget2-syntax' => '<a href="/Widget:YouTube/fr" title="Widget:YouTube">YouTube</a>, <a href="/Widget:Flickr/fr" title="Widget:Flickr">Flickr</a>, <a href="/Widget:SoundCloud/fr" title="Widget:SoundCloud">SoundCloud</a> ...',
    'wikieditor-toolbar-help-content-widget2-help' => '<a href="/Category:Widgets_médias" title="Categorie:Widgets_médias">Liste des widgets médias</a>',
    'wikieditor-toolbar-help-content-widget3-description' => 'Twitter',
    'wikieditor-toolbar-help-content-widget3-help' => '<a href="/Widget:Twitter/fr" title="Widget:Twitter">Page d\'aide pour Twitter</a>',
    'wikieditor-toolbar-help-content-widget4-syntax' => '<a href="/Widget:Google+/fr" title="Widget:Google+">Google+</a>, <a href="/Widget:AddThis/fr" title="Widget:AddThis">AddThis</a>, <a href="/Widget:Facebook/fr" title="Widget:Facebook">Facebook</a> ...',
    'wikieditor-toolbar-help-content-widget4-help' => '<a href="/Category:Widgets_sociaux" title="Categorie:Widgets sociaux">Liste des widgets sociaux</a>',
    /* Toolbar - Widget */
    'wikieditor-toolbar-tool-widget-title' => 'Insérer un widget',
    'wikieditor-toolbar-widget-default' => 'Sélectionnez un widget',
    'wikieditor-toolbar-widget-select' => 'Widget :',
    'wikieditor-toolbar-widget-arguments' => 'Paramètres :',
    'wikieditor-toolbar-widget-insert' => 'Insérer le widget',
    'wikieditor-toolbar-widget-cancel' => 'Annuler',
    'wikieditor-toolbar-widget-help' => 'Voir l\'aide sur <b><a target="_blank" href="/Widget:$1/fr">Widget:$1</a></b>',
    'wikieditor-toolbar-widget-list' => '<nowiki>
{{:Media}}
{{Vimeo:id = 43347454
| width = 392px
| height = 220px
| right}}
{{:Social}}
{{Twitter:user = TechCrunch
| list = realtime-web
| width = 220
| height = 180
| scrollbar
| live
| right}}
</nowiki>',
);
