<?php
/**
 * Seizam - Seizam skin based on the Vector Skin.
 *
 * @todo document
 * @file
 * @ingroup Skins
 * 
 * @author Clément Dietschy <clement@seizam.com>
 * 
 * Based on the original work from: The Vector/MediaWiki team.
 * 
 */
if (!defined('MEDIAWIKI')) {
    die(-1);
}

/* The Following registeration is advised at http://www.mediawiki.org/wiki/Manual:Skinning/Vector but seems redundant as skin.seizam is registered in resources/Resources.php.
 * 
  $wgResourceModules['skins.seizam'] = array(
  'styles' => array( 'seizam/screen.css' => array( 'media' => 'screen' ) ),
  'remoteBasePath' => $GLOBALS['wgStylePath'],
  'localBasePath' => $GLOBALS['wgStyleDirectory'],
  ); */

/**
 * SkinTemplate class for Vector skin
 * @ingroup Skins
 */
class SkinSkinzam extends SkinTemplate {
    /* Functions */

    var $skinname = 'skinzam', $stylename = 'skinzam',
    $template = 'SkinzamTemplate', $useHeadElement = true;

    /**
     * Initializes output page and sets up skin-specific parameters
     * @param $out OutputPage object to initialize
     */
    public function initPage(OutputPage $out) {
        global $wgLocalStylePath, $wgRequest;

        parent::initPage($out);

        /* Removed until necessary (SeizamDev 24/10/11)
          // Append CSS which includes IE only behavior fixes for hover support -
          // this is better than including this in a CSS fille since it doesn't
          // wait for the CSS file to load before fetching the HTC file.
          $min = $wgRequest->getFuzzyBool( 'debug' ) ? '' : '.min';
          $out->addHeadItem( 'csshover',
          '<!--[if lt IE 7]><style type="text/css">body{behavior:url("' .
          htmlspecialchars( $wgLocalStylePath ) .
          "/{$this->stylename}/csshover{$min}.htc\")}</style><![endif]-->"
          ); */
    }

    /**
     * Load skin and user CSS files in the correct order
     * fixes bug 22916
     * @param $out OutputPage object
     */
    function setupSkinUserCss(OutputPage $out) {
        parent::setupSkinUserCss($out);
        $out->addModuleStyles('skins.skinzam');
    }

    /**
     * Builds a structured array of links used for tabs and menus
     * @return array
     * @private
     */
    function buildNavigationUrls() {
        global $wgContLang, $wgLang, $wgOut, $wgUser, $wgRequest, $wgArticle;
        global $wgDisableLangConversion, $wgVectorUseIconWatch;

        wfProfileIn(__METHOD__);

        $links = array(
            'namespaces' => array(),
            'views' => array(),
            'actions' => array()
        );

        // Detects parameters
        $action = $wgRequest->getVal('action', 'view');
        $section = $wgRequest->getVal('section');

        $userCanRead = $this->getTitle()->userCanRead();

        // Checks if page is some kind of content
        if ($this->iscontent) {
            // Gets page objects for the related namespaces
            $subjectPage = $this->getTitle()->getSubjectPage();
            $talkPage = $this->getTitle()->getTalkPage();

            // Determines if this is a talk page
            $isTalk = $this->getTitle()->isTalkPage();

            // Generates XML IDs from namespace names
            $subjectId = $this->getTitle()->getNamespaceKey('');

            if ($subjectId == 'main') {
                $talkId = 'talk';
            } else {
                $talkId = "{$subjectId}_talk";
            }

            // Adds namespace links
            $links['namespaces'][$subjectId] = $this->tabAction(
                    $subjectPage, 'nstab-' . $subjectId, !$isTalk, '', $userCanRead
            );
            $links['namespaces'][$subjectId]['context'] = 'subject';
            $links['namespaces'][$talkId] = $this->tabAction(
                    $talkPage, 'talk', $isTalk, '', $userCanRead
            );
            $links['namespaces'][$talkId]['context'] = 'talk';

            // Adds view view link
            if ($this->getTitle()->exists() && $userCanRead) {
                $links['views']['view'] = $this->tabAction(
                        $isTalk ? $talkPage : $subjectPage, 'vector-view-view', ( $action == 'view' || $action == 'purge'), '', true
                );
            }

            wfProfileIn(__METHOD__ . '-edit');

            // Checks if user can...
            if (
            // read and edit the current page
                    $userCanRead && $this->getTitle()->quickUserCan('edit') &&
                    (
                    // if it exists
                    $this->getTitle()->exists() ||
                    // or they can create one here
                    $this->getTitle()->quickUserCan('create')
                    )
            ) {
                // Builds CSS class for talk page links
                $isTalkClass = $isTalk ? ' istalk' : '';

                // Determines if we're in edit mode
                $selected = (
                        ( $action == 'edit' || $action == 'submit' ) &&
                        ( $section != 'new' )
                        );
                $links['views']['edit'] = array(
                    'class' => ( $selected ? 'selected' : '' ) . $isTalkClass,
                    'text' => $this->getTitle()->exists() ? wfMsg('vector-view-edit') : wfMsg('vector-view-create'),
                    'href' =>
                    $this->getTitle()->getLocalURL($this->editUrlOptions())
                );
                // Checks if this is a current rev of talk page and we should show a new
                // section link
                if (( $isTalk && $wgArticle && $wgArticle->isCurrent() ) || ( $wgOut->showNewSectionLink() )) {
                    // Checks if we should ever show a new section link
                    if (!$wgOut->forceHideNewSectionLink()) {
                        // Adds new section link
                        //$links['actions']['addsection']
                        $links['views']['addsection'] = array(
                            'class' => 'collapsible ' . ( $section == 'new' ? 'selected' : false ),
                            'text' => wfMsg('vector-action-addsection'),
                            'href' => $this->getTitle()->getLocalURL(
                                    'action=edit&section=new'
                            )
                        );
                    }
                }
                // Checks if the page has some kind of viewable content
            } elseif ($this->getTitle()->hasSourceText() && $userCanRead) {
                // Adds view source view link
                $links['views']['viewsource'] = array(
                    'class' => ( $action == 'edit' ) ? 'selected' : false,
                    'text' => wfMsg('vector-view-viewsource'),
                    'href' =>
                    $this->getTitle()->getLocalURL($this->editUrlOptions())
                );
            }
            wfProfileOut(__METHOD__ . '-edit');

            wfProfileIn(__METHOD__ . '-live');

            // Checks if the page exists
            if ($this->getTitle()->exists() && $userCanRead) {
                // Adds history view link
                $links['views']['history'] = array(
                    'class' => 'collapsible ' . ( ( $action == 'history' ) ? 'selected' : false ),
                    'text' => wfMsg('vector-view-history'),
                    'href' => $this->getTitle()->getLocalURL('action=history'),
                    'rel' => 'archives',
                );

                if ($wgUser->isAllowed('delete')) {
                    $links['actions']['delete'] = array(
                        'class' => ( $action == 'delete' ) ? 'selected' : false,
                        'text' => wfMsg('vector-action-delete'),
                        'href' => $this->getTitle()->getLocalURL('action=delete')
                    );
                }
                if ($this->getTitle()->quickUserCan('move')) {
                    $moveTitle = SpecialPage::getTitleFor(
                                    'Movepage', $this->thispage
                    );
                    $links['actions']['move'] = array(
                        'class' => $this->getTitle()->isSpecial('Movepage') ?
                                'selected' : false,
                        'text' => wfMsg('vector-action-move'),
                        'href' => $moveTitle->getLocalURL()
                    );
                }

                if (
                        $this->getTitle()->getNamespace() !== NS_MEDIAWIKI &&
                        $wgUser->isAllowed('protect')
                ) {
                    if (!$this->getTitle()->isProtected()) {
                        $links['actions']['protect'] = array(
                            'class' => ( $action == 'protect' ) ?
                                    'selected' : false,
                            'text' => wfMsg('vector-action-protect'),
                            'href' =>
                            $this->getTitle()->getLocalURL('action=protect')
                        );
                    } else {
                        $links['actions']['unprotect'] = array(
                            'class' => ( $action == 'unprotect' ) ?
                                    'selected' : false,
                            'text' => wfMsg('vector-action-unprotect'),
                            'href' =>
                            $this->getTitle()->getLocalURL('action=unprotect')
                        );
                    }
                }
            } else {
                // article doesn't exist or is deleted
                if (
                        $wgUser->isAllowed('deletedhistory') &&
                        $wgUser->isAllowed('undelete')
                ) {
                    $n = $this->getTitle()->isDeleted();
                    if ($n) {
                        $undelTitle = SpecialPage::getTitleFor('Undelete');
                        $links['actions']['undelete'] = array(
                            'class' => false,
                            'text' => wfMsgExt(
                                    'vector-action-undelete', array('parsemag'), $wgLang->formatNum($n)
                            ),
                            'href' => $undelTitle->getLocalURL(
                                    'target=' . urlencode($this->thispage)
                            )
                        );
                    }
                }

                if (
                        $this->getTitle()->getNamespace() !== NS_MEDIAWIKI &&
                        $wgUser->isAllowed('protect')
                ) {
                    if (!$this->getTitle()->getRestrictions('create')) {
                        $links['actions']['protect'] = array(
                            'class' => ( $action == 'protect' ) ?
                                    'selected' : false,
                            'text' => wfMsg('vector-action-protect'),
                            'href' =>
                            $this->getTitle()->getLocalURL('action=protect')
                        );
                    } else {
                        $links['actions']['unprotect'] = array(
                            'class' => ( $action == 'unprotect' ) ?
                                    'selected' : false,
                            'text' => wfMsg('vector-action-unprotect'),
                            'href' =>
                            $this->getTitle()->getLocalURL('action=unprotect')
                        );
                    }
                }
            }
            wfProfileOut(__METHOD__ . '-live');
            /**
             * The following actions use messages which, if made particular to
             * the Vector skin, would break the Ajax code which makes this
             * action happen entirely inline. Skin::makeGlobalVariablesScript
             * defines a set of messages in a javascript object - and these
             * messages are assumed to be global for all skins. Without making
             * a change to that procedure these messages will have to remain as
             * the global versions.
             */
            // Checks if the user is logged in
            if ($this->loggedin) {
                $class = '';
                $place = 'actions';
                $mode = $this->getTitle()->userIsWatching() ? 'unwatch' : 'watch';
                $links[$place][$mode] = array(
                    'class' => $class . ( ( $action == 'watch' || $action == 'unwatch' ) ? ' selected' : false ),
                    'text' => wfMsg($mode), // uses 'watch' or 'unwatch' message
                    'href' => $this->getTitle()->getLocalURL('action=' . $mode)
                );
            }
            // This is instead of SkinTemplateTabs - which uses a flat array
            wfRunHooks('SkinTemplateNavigation', array(&$this, &$links));

            // If it's not content, it's got to be a special page
        } else {
            $links['namespaces']['special'] = array(
                'class' => 'selected',
                'text' => wfMsg('nstab-special'),
                'href' => $wgRequest->getRequestURL()
            );
            // Equiv to SkinTemplateBuildContentActionUrlsAfterSpecialPage
            wfRunHooks('SkinTemplateNavigation::SpecialPage', array(&$this, &$links));
        }


        // Equiv to SkinTemplateContentActions
        wfRunHooks('SkinTemplateNavigation::Universal', array(&$this, &$links));

        wfProfileOut(__METHOD__);


        return $links;
    }

    function getNamespace() {
        return $this->getTitle()->getNamespace();
    }

}

/**
 * QuickTemplate class for Vector skin
 * @ingroup Skins
 */
class SkinzamTemplate extends QuickTemplate {
    /* Members */

    /**
     * @var Cached skin object
     */
    var $skin;

    /* Functions */

    /**
     * Outputs the entire contents of the XHTML page
     */
    public function execute() {
        global $wgRequest, $wgLang;

        $this->skin = $this->data['skin'];
        $action = $wgRequest->getText('action');

        // Build additional attributes for navigation urls
        $nav = $this->skin->buildNavigationUrls();
        foreach ($nav as $section => $links) {
            foreach ($links as $key => $link) {
                $xmlID = $key;
                if (isset($link['context']) && $link['context'] == 'subject') {
                    $xmlID = 'ca-nstab-' . $xmlID;
                } else if (isset($link['context']) && $link['context'] == 'talk') {
                    $xmlID = 'ca-talk';
                } else {
                    $xmlID = 'ca-' . $xmlID;
                }
                $nav[$section][$key]['attributes'] =
                        ' id="' . Sanitizer::escapeId($xmlID) . '"';
                if ($nav[$section][$key]['class']) {
                    $nav[$section][$key]['attributes'] .=
                            ' class="' . htmlspecialchars($link['class']) . '"';
                    unset($nav[$section][$key]['class']);
                }
                // We don't want to give the watch tab an accesskey if the page
                // is being edited, because that conflicts with the accesskey on
                // the watch checkbox.  We also don't want to give the edit tab
                // an accesskey, because that's fairly superfluous and conflicts
                // with an accesskey (Ctrl-E) often used for editing in Safari.
                if (
                        in_array($action, array('edit', 'submit')) &&
                        in_array($key, array('edit', 'watch', 'unwatch'))
                ) {
                    $nav[$section][$key]['key'] =
                            $this->skin->tooltip($xmlID);
                } else {
                    $nav[$section][$key]['key'] =
                            $this->skin->tooltipAndAccesskeyAttribs($xmlID);
                }
            }
        }
        $this->data['namespace_urls'] = $nav['namespaces'];
        $this->data['view_urls'] = $nav['views'];
        $this->data['action_urls'] = $nav['actions'];
        // Build additional attributes for personal_urls
        foreach ($this->data['personal_urls'] as $key => $item) {
            $this->data['personal_urls'][$key]['attributes'] =
                    ' id="' . Sanitizer::escapeId("pt-$key") . '"';
            if (isset($item['active']) && $item['active']) {
                $this->data['personal_urls'][$key]['attributes'] .=
                        ' class="active"';
            }
            $this->data['personal_urls'][$key]['key'] =
                    $this->skin->tooltipAndAccesskeyAttribs('pt-' . $key);
        }

        // Generate additional footer links
        $footerlinks = $this->data["footerlinks"];

        // Reduce footer links down to only those which are being used
        $validFooterLinks = array();
        foreach ($footerlinks as $category => $links) {
            $validFooterLinks[$category] = array();
            foreach ($links as $link) {
                if (isset($this->data[$link]) && $this->data[$link]) {
                    $validFooterLinks[$category][] = $link;
                }
            }
        }

        // Generate additional footer icons
        $footericons = $this->data["footericons"];
        // Unset any icons which don't have an image
        foreach ($footericons as $footerIconsKey => &$footerIconsBlock) {
            foreach ($footerIconsBlock as $footerIconKey => $footerIcon) {
                if (!is_string($footerIcon) && !isset($footerIcon["src"])) {
                    unset($footerIconsBlock[$footerIconKey]);
                }
            }
        }
        // Redo removal of any empty blocks
        foreach ($footericons as $footerIconsKey => &$footerIconsBlock) {
            if (count($footerIconsBlock) <= 0) {
                unset($footericons[$footerIconsKey]);
            }
        }
        // Output HTML Page
        $this->html('headelement');
        $this->renderTop();
        ?>
        <!-- content -->
        <div id="content">
            <a id="top"></a>
            <?php $this->renderContent() ?>
            <!-- contentFooter -->
            <div id="self_general" class="block_flat block_full">
                <div class="inside">
                    <?php foreach ($validFooterLinks as $category => $links): ?>
                        <?php if (count($links) > 0): ?>
                            <ul id="footer-<?php echo $category ?>">
                                <?php foreach ($links as $link): ?>
                                    <?php if (isset($this->data[$link]) && $this->data[$link]): ?>
                                        <li id="footer-<?php echo $category ?>-<?php echo $link ?>"><?php $this->html($link) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (count($footericons) > 0): ?>
                        <ul id="footer-icons" class="noprint">
                            <?php foreach ($footericons as $blockName => $footerIcons): ?>
                                <li id="footer-<?php echo htmlspecialchars($blockName); ?>ico">
                                    <?php foreach ($footerIcons as $icon): ?>
                                        <?php echo $this->skin->makeFooterIcon($icon); ?>

                                    <?php endforeach; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div style="clear:both"></div>
                </div>
            </div>
            <!-- /contentFooter -->
        </div>
        <!-- /content -->

        <?php $this->renderFooter(); ?>
        </div>
        <!-- /container -->
        <!-- bottomScripts -->
        <?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
        <!-- /bottomScripts -->
        <?php $this->html('reporttime') ?>
        <?php if ($this->data['debug']): ?>
            <!-- Debug output: <?php $this->text('debug'); ?> -->
        <?php endif; ?>
        </body>
        </html>
        <?php
    }

    /**
     * Render the #content content
     */
    private function renderContent() {
        switch ($this->skin->getNamespace()) {
            case NS_PROJECT_TALK :
            case NS_PROJECT :
                $this->renderContentNS4();
                break;
            case -1 ://NS_SPECIAL
                $this->renderContentNSSpecial();
                break;
            default :
                $this->renderContentDefault();
                break;
        }
    }

    /**
     * Render the Default #content content (ex: Main_Namespace)
     */
    private function renderContentDefault() {
        ?>
        <!-- header -->
        <div id="header" class="block_full">
            <?php $this->renderNav(); ?>
            <!-- firstHeading -->
            <div class="block_flat block_half">
                <div class="inside">
                    <h1 id="firstHeading" class="firstHeading"><?php $this->html('title') ?></h1>
                </div>
            </div>
            <!-- /firstHeading -->
        </div>
        <!-- /header -->
        <!-- bodyCcontent -->
        <div id="bodyContent" role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
            <!-- block_full -->
            <div class="block_flat block_full">
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderInsideContent(); ?>
                </div>
                <!-- /inside -->
            </div>
            <!-- /block_full -->
        </div>
        <!-- /bodyContent -->
        <?php
    }

    /**
     * Render the NS-4 (Project:) #content content
     */
    private function renderContentNS4() {
        ?>
        <!-- header -->
        <div id="header" class="block block_full homepage">

            <div class="hgroup inside">
                <h1><a id="logo_project" href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href']) ?>"></a></h1>
                <h2><?php $this->msg('sz-tagline') ?></h2>

            </div>
        </div>
        <!-- /header -->
        <!-- bodyCcontent -->
        <div id="bodyContent" role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
            <!-- block_full -->
            <div class="block block_full">
                <h3 class="title"><?php $this->html('title') ?></h3>
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderNav(); ?>
                    <?php $this->renderInsideContent(); ?>
                </div>
                <!-- /inside -->
            </div>
            <!-- /block_full -->
        </div>
        <!-- /bodyContent -->
        <?php if ($this->data['sitenotice']): ?>
            <!-- sitenotice -->
            <div class="block_flat block_full">
                <div class="inside">
                    <div id="siteNotice"><?php $this->html('sitenotice') ?></div>
                </div>
            </div>
            <!-- /sitenotice -->
            <?php
        endif;
    }

    /**
     * Render the NS--1 (Special:) #content content
     */
    private function renderContentNSSpecial() {
        ?>
        <!-- header -->

        <div id="header" class="block block_full profil">
            <div class="inside">
                <h1><a id="logo_special" href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href']) ?>"></a></h1>

                <div id="nav">
                    <ul>
                        <?php $this->renderNavigation(array('PERSONAL')); ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- /header -->
        <!-- bodyCcontent -->
        <div id="bodyContent" role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
            <!-- block_full -->
            <div class="block block_full">
                <h3 class="title"><?php $this->html('title') ?></h3>
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderInsideContent(); ?>
                </div>
                <!-- /inside -->
            </div>
            <!-- /block_full -->
        </div>
        <!-- /bodyContent -->
        <?php
    }

    /**
     * Render the footer (main menu)
     */
    private function renderFooter() {
        ?>
        <!-- footer -->
        <div id="footer">
            <div class="inside">
                <div class="content">
                    <!-- logo -->
                    <a id="logo_mini" href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href']) ?>" <?php echo $this->skin->tooltipAndAccesskeyAttribs('p-logo') ?>></a>
                    <!-- /logo -->
                    <!-- search -->
                    <?php $this->renderNavigation(array('SEARCH')); ?>
                    <!-- /search -->
                    <!-- quicklinks -->
                    <ul>
                        <li>
                            <a href="/Special:AllPages"><?php echo $this->msg('sz-browse') ?></a>
                        </li>
                        <li>
                            <a href="/Special:Preferences"><?php echo $this->msg('sz-myseizam') ?></a>
                        </li>
                        <li class="more">
                            <a href="#">
                                <span class="show_more"><?php echo $this->msg('moredotdotdot') ?></span>
                                <span class="show_less" aria-hidden="true"><?php echo $this->msg('lessdotdotdot') ?></span>
                            </a>
                        </li>
                    </ul>
                    <!-- /quicklinks -->
                    <!-- moreInfo -->
                    <div class="more_infos" style="display: none;">
                        <?php $this->renderMore(); ?>
                    </div>
                    <!-- /moreInfo -->
                </div>
            </div>
        </div>
        <!-- /footer -->
        <?php
    }

    /**
     * Render the "more..." footer panel content
     */
    private function renderMore() {
        ?>
        <div class="section">
            <p><?php echo $this->msg('sz-legalcontent') ?></p>
            <ul>
                <li><?php echo $this->msgHtml('sz-gtcu') ?></li>
                <li><?php echo $this->msgHtml('sz-astcu') ?></li>
                <li><?php echo $this->msgHtml('sz-legalinfo') ?></li>
                <li><?php echo $this->msgHtml('sz-privacypolicy') ?></li>
            </ul>
        </div>

        <div class="section">
            <p><?php echo $this->msg('sz-generalinfo') ?></p>
            <ul>
                <li><?php echo $this->msgHtml('sz-discoverseizam') ?></li>
                <li><?php echo $this->msgHtml('sz-joinseizam') ?></li>
                <li><?php echo $this->msgHtml('sz-help') ?></li>
                <li><?php echo $this->msgHtml('sz-faq') ?></li>
            </ul>
        </div>

        <div class="section">
            <p><?php echo $this->msg('sz-communicate') ?></p>
            <ul>
                <li><?php echo $this->msgHtml('sz-reportabuse') ?></li>
                <li><?php echo $this->msgHtml('sz-reportbug') ?></li>
                <li><?php echo $this->msgHtml('sz-technicalsupport') ?></li>
                <li><?php echo $this->msgHtml('sz-contactus') ?></li>
            </ul>
        </div>

        <div class="section">
            <p class="sread"><?php echo $this->msg('sz-selectlang') ?></p>
            <?php echo wfLanguageSelectorHTML($this->skin->getTitle(), null, 'selectLang'); ?>
            <p class="sread"><?php echo $this->msg('sz-seizamonsocialnetworks') ?></p>
            <ul class="socials">
                <li class="tumblr"><a href="http://www.davidcanwin.com">Tumblr</a></li>
                <li class="twitter"><a href="http://twitter.com/davidcanwin">Twitter</a></li>
                <li class="fcbk"><a href="http://www.facebook.com/davidcanwin">Facebook</a></li>
                <li class="linkedin"><a href="http://www.linkedin.com/company/seizam">LinkedIn</a></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render #nav (#nav_artist + #nav_plus)
     */
    private function renderNav() {
        ?>
        <div id="nav">
            <ul id="nav_plus">
                <li>
                    <a href="#"><?php echo $this->msg('actions') ?></a>
                    <ul>
                        <?php $this->renderNavigation(array('NAMESPACES', 'VIEWS', 'ACTIONS')); ?>
                    </ul>
                </li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render the real content of a page
     */
    private function renderInsideContent() {
        ?>
        <!-- tagline (invisible)-->
        <div id="siteSub"><?php $this->msg('tagline') ?></div>
        <!-- /tagline -->
        <!-- subtitle -->
        <div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
        <!-- /subtitle -->
        <?php if ($this->data['undelete']): ?>
            <!-- undelete -->
            <div id="contentSub2"><?php $this->html('undelete') ?></div>
            <!-- /undelete -->
        <?php endif; ?>
        <?php if ($this->data['newtalk']): ?>
            <!-- newtalk -->
            <div class="usermessage"><?php $this->html('newtalk') ?></div>
            <!-- /newtalk -->
        <?php endif; ?>
        <?php if ($this->data['showjumplinks']): ?>
            <!-- jumpto (invisible)-->
            <div id="jump-to-nav">
                <?php $this->msg('jumpto') ?> <a href="#mw-head"><?php $this->msg('jumptonavigation') ?></a>,
                <a href="#p-search"><?php $this->msg('jumptosearch') ?></a>
            </div>
            <!-- /jumpto -->
        <?php endif; ?>
        <!-- bodytext -->
        <?php $this->html('bodytext') ?>
        <!-- /bodytext -->
        <?php if ($this->data['catlinks']): ?>
            <!-- catlinks -->
            <?php $this->html('catlinks'); ?>
            <!-- /catlinks -->
        <?php endif; ?>
        <?php if ($this->data['dataAfterContent']): ?>
            <!-- dataAfterContent -->
            <?php $this->html('dataAfterContent'); ?>
            <!-- /dataAfterContent -->
            <?php
        endif;
    }

    /**
     * Render top of the page (between </head> and <div id="content">
     */
    private function renderTop() {
        ?>
        <div id="mw-js-message" style="display:none;"<?php $this->html('userlangattributes') ?>></div>
        <?php
    }

    /**
     * Render one or more navigations elements by name, automatically reveresed
     * when UI is in RTL mode
     */
    private function renderNavigation($elements) {

        global $wgVectorUseSimpleSearch, $wgVectorShowVariantName, $wgUser;

        // If only one element was given, wrap it in an array, allowing more
        // flexible arguments
        if (!is_array($elements)) {
            $elements = array($elements);
            // If there's a series of elements, reverse them when in RTL mode
        }
        // Render elements
        foreach ($elements as $name => $element) {
            echo "\n<!-- {$name} -->\n";
            switch ($element) {
                case 'NAMESPACES':
                    foreach ($this->data['namespace_urls'] as $link):
                        ?>
                        <li <?php echo $link['attributes'] ?>><a href="<?php echo htmlspecialchars($link['href']) ?>" <?php echo $link['key'] ?>><?php echo htmlspecialchars($link['text']) ?></a></li>
                        <?php
                    endforeach;
                    break;
                case 'VIEWS':
                    foreach ($this->data['view_urls'] as $link):
                        ?>
                        <li<?php echo $link['attributes'] ?>><a href="<?php echo htmlspecialchars($link['href']) ?>" <?php echo $link['key'] ?>><?php echo htmlspecialchars($link['text']) ?></a></li>
                        <?php
                    endforeach;
                    break;
                case 'ACTIONS':
                    foreach ($this->data['action_urls'] as $link):
                        ?>
                        <li<?php echo $link['attributes'] ?>><a href="<?php echo htmlspecialchars($link['href']) ?>" <?php echo $link['key'] ?>><?php echo htmlspecialchars($link['text']) ?></a></li>
                        <?php
                    endforeach;
                    break;
                case 'PERSONAL':
                    foreach ($this->data['personal_urls'] as $item):
                        ?>
                        <li <?php echo $item['attributes'] ?>><a href="<?php echo htmlspecialchars($item['href']) ?>"<?php echo $item['key'] ?><?php if (!empty($item['class'])): ?> class="<?php echo htmlspecialchars($item['class']) ?>"<?php endif; ?>><?php echo htmlspecialchars($item['text']) ?></a></li>
                        <?php
                    endforeach;
                    break;
                case 'SEARCH':
                    ?>
                    <div id="p-search">
                        <h5<?php $this->html('userlangattributes') ?>><label for="searchInput"><?php $this->msg('search') ?></label></h5>
                        <form action="<?php $this->text('wgScript') ?>" id="searchform">
                            <input type='hidden' name="title" value="<?php $this->text('searchtitle') ?>"/>
                            <?php if ($wgVectorUseSimpleSearch && $wgUser->getOption('vector-simplesearch')): ?>
                                <div id="simpleSearch">
                                    <?php if ($this->data['rtl']): ?>
                                        <button id="searchButton" type='submit' name='button' <?php echo $this->skin->tooltipAndAccesskeyAttribs('search-fulltext'); ?>><img src="<?php echo $this->skin->getSkinStylePath('images/search-rtl.png'); ?>" alt="<?php $this->msg('searchbutton') ?>" /></button>
                                    <?php endif; ?>
                                    <input id="searchInput" name="search" type="text" <?php echo $this->skin->tooltipAndAccesskeyAttribs('search'); ?> <?php if (isset($this->data['search'])): ?> value="<?php $this->text('search') ?>"<?php endif; ?> />
                                    <?php if (!$this->data['rtl']): ?>
                                        <button id="searchButton" type='submit' name='button' <?php echo $this->skin->tooltipAndAccesskeyAttribs('search-fulltext'); ?>><img src="<?php echo $this->skin->getSkinStylePath('images/search-ltr.png'); ?>" alt="<?php $this->msg('searchbutton') ?>" /></button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <input id="searchInput" name="search" type="text" <?php echo $this->skin->tooltipAndAccesskeyAttribs('search'); ?> <?php if (isset($this->data['search'])): ?> value="<?php $this->text('search') ?>"<?php endif; ?> />
                                <input type='submit' name="go" class="searchButton" id="searchGoButton"	value="<?php $this->msg('searcharticle') ?>"<?php echo $this->skin->tooltipAndAccesskeyAttribs('search-go'); ?> />
                                <input type="submit" name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg('searchbutton') ?>"<?php echo $this->skin->tooltipAndAccesskeyAttribs('search-fulltext'); ?> />
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php
                    break;
            }
            echo "\n<!-- /{$name} -->\n";
        }
    }

}
