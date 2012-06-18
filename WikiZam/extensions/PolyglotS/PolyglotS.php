<?php
/**
 * Polyglot extension - automatic redirects based on user language
 *
 * Features:
 *  * Magic redirects to localized page version
 *  * Interlanguage links in the sidebar point to localized local pages
 *
 * This can be combined with LanguageSelector and MultiLang to provide more internationalization support.
 *
 * See the README file for more information
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgExtensionCredits['other'][] = array( 
	'path' => __FILE__,
	'name' => 'PolyglotS', 
	'author' => 'Daniel Kinzler & Clément Dietschy', 
	'url' => 'http://mediawiki.org/wiki/Extension:Polyglot',
	'description' => 'Support for content in multiple languages in a single MediaWiki',
);

/**
* Set languages with polyglot support; applies to negotiation of interface language, 
* and to lookups for loclaized pages.
* Set this to a small set of languages that are likely to be used on your site to
* improve performance. Leave NULL to allow all languages known to MediaWiki via
* languages/Names.php.
* If the LanguageSelector extension is installed, $wgLanguageSelectorLanguages is used
* as a fallback.
*/
$wgPolyglotLanguages = null;

/**
* Namespaces to excempt from polyglot support, with respect to automatic redirects.
* All "magic" namespaces are excempt per default. There should be no reason to change this.
* Note: internationalizing templates is best done on-page, using the MultiLang extension.
*/
$wgPolyglotExcemptNamespaces = array(NS_CATEGORY, NS_TEMPLATE, NS_IMAGE, NS_MEDIA, NS_SPECIAL, NS_MEDIAWIKI);

/**
* Wether talk pages should be excempt from automatic polyglot support, with respect to
* automatic redirects. True per default.
*/
$wgPolyglotExcemptTalkPages = true;

/**
* Set to true if polyglot should resolve redirects that are encountered when applying an
* automatic redirect to a localized page. This requires additional database access every
* time a locaized page is accessed.
*/
$wgPolyglotFollowRedirects = false;

/**
 * Do we always consider the parent of any variant to be of $wgLanguageCode language ?
 * And do we force it to appear on lang variant menu?
 * 
 */
$wgPolyglotForceParentLang = false;

///// hook it up /////////////////////////////////////////////////////
$wgExtensionFunctions[] = "wfPolyglotExtension";
$wgHooks['InitializeArticleMaybeRedirect'][] = 'wfPolyglotInitializeArticleMaybeRedirect';
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'wfPolyglotAddVariantsToTemplate';

/**
 * Takes all available languages if none are specified.
 * 
 * @global array $wgPolyglotLanguages 
 */
function wfPolyglotExtension() {
	global $wgPolyglotLanguages;
	
	if ( $wgPolyglotLanguages === null ) {
		$wgPolyglotLanguages = array_keys( Language::getLanguageNames() );
	}
}

function wfPolyglotInitializeArticleMaybeRedirect( &$title, &$request, &$ignoreRedirect, &$target, &$article ) {
	global $wgPolyglotExcemptNamespaces, $wgPolyglotExcemptTalkPages, $wgPolyglotFollowRedirects;
	global $wgLang, $wgContLang;

	$ns = $title->getNamespace();

	if ( $ns < 0 || in_array( $ns, $wgPolyglotExcemptNamespaces )
		|| ( $wgPolyglotExcemptTalkPages && MWNamespace::isTalk( $ns ) ) ) {
		return true;
	}

	$dbkey = $title->getDBkey();
	$force = false;

	//TODO: when user-defined language links start working (see below),
	//      we need to look at the langlinks table here.
	if ( !$title->exists() && strlen( $dbkey ) > 1 ) {
		$escContLang = preg_quote( $wgContLang->getCode(),  '!' );
		if ( preg_match( '!/$!', $dbkey ) ) {
			$force = true;
			$remove = 1;
		} elseif ( preg_match( "!/{$escContLang}$!", $dbkey ) ) {
			$force = true;
			$remove = strlen( $wgContLang->getCode() ) + 1;
		}
	}

	if ( $force ) {
		$t = Title::makeTitle( $ns, substr( $dbkey, 0, strlen( $dbkey ) - $remove ) );
	} else {
		$lang = $wgLang->getCode();
		$t = Title::makeTitle( $ns, $dbkey . '/' . $lang );
	}

	if ( !$t->exists() ) {
		return true;
	}

	if ( $wgPolyglotFollowRedirects && !$force ) {
		$page = WikiPage::factory( $t );

		if ( $page->isRedirect() ) {
			$rt = $page->getRedirectTarget();
			if ( $rt && $rt->exists() ) {
				//TODO: make "redirected from" show $source, not $title, if we followed a redirect internally.
				//     there seems to be no clean way to do that, though.
				//$source = $t;
				$t = $rt;
			}
		}
	}

	$target = $t;

	return true;
}

/**
 * Returns all titles of languages variant available for $originTile
 * 
 * @global string $wgPolyglotLanguages
 * @global string $wgLanguageCode
 * @global boolean $wgPolyglotForceParentLang
 * @param Title $originTitle
 * @return Array Array of title indexed by Language Code 
 */
function wfPolyglotGetVariantTitles($originTitle) {
    global $wgPolyglotLanguages, $wgLanguageCode, $wgPolyglotForceParentLang;
    if (!$wgPolyglotLanguages) return null;
    
    /* Identification of the current page ($title) */
    // The current lang, is it assumed english (default)? Or not...
    $currentLang = $wgPolyglotForceParentLang ? $wgLanguageCode : null;
	$ns = $originTitle->getNamespace();
    $DBKey = $originTitle->getDBkey();
    
    // Trying to extract the language of the page
    if (preg_match('!(.+)/(\w[-\w]*\w)$!', $DBKey, $m)) {
        // Do we have a valid language extension?
        if (in_array($m[2], $wgPolyglotLanguages)) {
            // YES! We will now consider the variants of the parent page 
            $DBKey = $m[1];
            // But we do not forget to save the language of current page
            $currentLang = $m[2];
        }
                
    }
    
    /* Construction the array of all available variants for page */
    // LinkBatch provides caching of titles
    $batch = new LinkBatch;
    $variants = array();
    // Shame, we have to do this for EACH language
    foreach ( $wgPolyglotLanguages as $lang ) {
		$title = Title::makeTitle( $ns, $DBKey . '/' . $lang );
		$batch->addObj( $title );
		$variants[$lang] = $title;
	}
    
    // Remember the current Lang? We don't need this variant.
    unset ($variants[$currentLang]);
    // LinkBatch builds the query and look into the DB :-(
    /** @todo Look into how much is cached */
    $ids = $batch->execute();
    
    /* Trashing variants that do not exist */
    foreach( $variants as $lang => $title ) {
		if ( !$title->exists() ) {
			unset($variants[$lang]);
		}
	}
    
    // We force the parent to appear as English (better solution is create redirect)
    if ($wgPolyglotForceParentLang && !isset($variants[$wgLanguageCode])) {
        $title = Title::makeTitle($ns, $DBKey);
        $batch->addObj($title);
        $variants = array_merge(array($wgLanguageCode=>$title),$variants);
    }
    
    /* TADAAA */
    return $variants;
}


/**
 * Replaces the language variant menu by the Polyglot one.
 *
 * @global type $wgOut
 * @global type $wgContLang
 * @param SkinTemplate $skin
 * @param QuickTemplate $tpl
 * @return type 
 */
function wfPolyglotAddVariantsToTemplate($skin, $tpl) {
	global $wgOut, $wgContLang;
    
    $variants = wfPolyglotGetVariantTitles($skin->getRelevantTitle());

	$language_urls = array();
    
	foreach( $variants as $lang => $title ) {
		$language_urls[] = array(
			'href' => $title->getFullURL(),
			'text' => $wgContLang->getLanguageName( $lang ),
			'class' => 'interwiki-' . $lang,
		);
	}

	if(count($language_urls)) {
		$tpl->setRef( 'language_urls', $language_urls);
	} else {
		$tpl->set('language_urls', false);
	}

	return true;
}

