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

        // Append CSS which includes IE only behavior fixes for hover support -
        // this is better than including this in a CSS fille since it doesn't
        // wait for the CSS file to load before fetching the HTC file.
        $min = $wgRequest->getFuzzyBool('debug') ? '' : '.min';
        $out->addHeadItem('csshover', '<!--[if lt IE 7]><style type="text/css">body{behavior:url("' .
                htmlspecialchars($wgLocalStylePath) .
                "/{$this->stylename}/csshover{$min}.htc\")}</style><![endif]-->"
        );
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

}

/**
 * QuickTemplate class for Skinzam skin
 * @ingroup Skins
 */
class SkinzamTemplate extends BaseTemplate {
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

        // Build additional attributes for navigation urls
        $nav = $this->data['content_navigation'];
        $xmlID = '';
        foreach ($nav as $section => $links) {
            foreach ($links as $key => $link) {
                if ($section == 'views' && !( isset($link['primary']) && $link['primary'] )) {
                    $link['class'] = rtrim('collapsible ' . $link['class'], ' ');
                }

                $class = '';
                $xmlID = isset($link['id']) ? $link['id'] : 'ca-' . $xmlID;
                $class .= $xmlID;
                /* $nav[$section][$key]['attributes'] =
                  ' id="' . Sanitizer::escapeId($xmlID) . '"'; */
                if ($link['class']) {
                    $class .= ' ' . htmlspecialchars($link['class']);
                    /* $nav[$section][$key]['attributes'] .=
                      ' class="' . htmlspecialchars($link['class']) . '"'; */
                    unset($nav[$section][$key]['class']);
                }
                $nav[$section][$key]['attributes'] = ' class="' . $class . '"';
                if (isset($link['tooltiponly']) && $link['tooltiponly']) {
                    $nav[$section][$key]['key'] =
                            Linker::tooltip($xmlID);
                } else {
                    $nav[$section][$key]['key'] =
                            Xml::expandAttributes(Linker::tooltipAndAccesskeyAttribs($xmlID));
                }
            }
        }
        $this->data['namespace_urls'] = $nav['namespaces'];
        $this->data['view_urls'] = $nav['views'];
        $this->data['action_urls'] = $nav['actions'];


        // Output HTML Page
        $this->html('headelement');
        ?>
        <div id="mw-js-message" style="display:none;"<?php $this->html('userlangattributes') ?>></div>
        <!-- tagline -->
        <div id="siteSub"><?php $this->msg('tagline') ?></div>
        <!-- /tagline -->
        <?php if ($this->data['showjumplinks']): ?>
            <!-- jumpto -->
            <div id="jump-to-nav">
                <?php $this->msg('jumpto') ?> <a href="#mw-head"><?php $this->msg('jumptonavigation') ?></a>,
                <a href="#p-search"><?php $this->msg('jumptosearch') ?></a>
            </div>
            <!-- /jumpto -->
        <?php endif; ?>
        <?php if ($this->data['newtalk']): ?>
            <!-- newtalk -->
            <div class="usermessage"><?php $this->html('newtalk') ?></div>
            <!-- /newtalk -->
        <?php endif; ?>
        <!-- content -->
        <?php $this->renderContent() ?>
        <!-- contentFooter -->
        </div>
        <!-- /content -->
        <!-- footer -->
        <?php $this->renderFooter(); ?>
        <!-- /footer -->
        <!-- bottomScripts -->
        <?php if ($this->data['dataAfterContent']): ?>
            <!-- dataAfterContent -->
            <?php $this->html('dataAfterContent'); ?>
            <!-- /dataAfterContent -->
        <?php endif; ?>
        <!-- fixalpha -->
        <script type="<?php $this->text('jsmimetype') ?>"> if ( window.isMSIE55 ) fixalpha(); </script>
        <!-- /fixalpha -->
        <!-- background -->
        <?php if ($this->data['wp_background']['url']): ?>
            <script>$.backstretch("<?php echo $this->data['wp_background']['url']; ?>");</script>
        <?php endif; ?>
        <!-- /background -->
        <!-- Trail -->
        <?php $this->printTrail(); ?>
        <!-- /Trail -->
        <!-- /bottomScripts -->
        </body>
        </html>
        <?php
        // End of Output HTML Page
    }

    /**
     * Render the #content content
     */
    private function renderContent() {
        $title = $this->getSkin()->getRelevantTitle();
        $ns = $title->getNamespace();

        switch ($ns) {
            case NS_PROJECT_TALK :
            case NS_PROJECT :
                $this->renderContentNS4();
                break;
            case NS_SPECIAL :
                if ($title->getDBkey() === SZ_MAIN_PAGE)
                    $this->renderContentMainpage();
                else
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
        <!-- content -->
        <div id="content">
            <a id="top"></a>
            <!-- header -->
            <div id="header" class="block_full noprint">
                <div id="nav">
                    <?php if ($this->data['wp_navigation']['content']): ?>
                        <div class="nav_artist">
                            <?php echo $this->data['wp_navigation']['content']; ?>
                        </div>
                    <?php endif; ?>
                    <ul class="nav_actions">
                        <li>
                            <a href="#"><?php echo wfMessage('actions')->text() ?></a>
                            <ul>
                                <?php $this->renderNavigation(array('NAMESPACES', 'VIEWS', 'ACTIONS')); ?>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- /header -->
            <!-- bodyCcontent -->
            <!-- firstHeading -->
            <?php if ($this->data['wp_headertitle']['content']): ?>
                <h1 class="firstHeading"><?php echo $this->data['wp_headertitle']['content']; ?></h1>
            <?php else: ?>
                <h1 class="firstHeading notWikiPlace"><?php $this->html('title') ?></h1>
            <?php endif; ?>
            <!-- /firstHeading -->
            <div id="bodyContent" class="block block_full block_flat " role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
                <!-- inside -->
                <div class="inside">
                    <!-- bodytext -->
                    <?php $this->html('bodytext') ?>
                    <!-- /bodytext -->
                </div>
                <!-- /inside -->
            </div>
            <!-- /bodyContent -->
            <!-- contentFooter -->
            <div id="contentFooter" class="block block_full block_flat">
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderContentFooter(); ?>
                </div>
            </div>
            <!-- /contentFooter -->
            <!-- Horizontal actions -->
            <div id="nav_horizontal" class="block block_full block_flat noprint">
                <ul class="nav_actions">
                    <?php $this->renderNavigation(array('NAMESPACES', 'VIEWS', 'ACTIONS')); ?>
                </ul>
            </div>
            <!-- /Horizontal actions -->
            <!-- contentOther -->
            <div id="contentOther" class="block block_full block_flat noprint">
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderContentOther(); ?>
                </div>
            </div>
            <!-- contentOther -->
        </div>
        <!-- /content -->
        <?php
    }

    /**
     * Render the NS-4 (Project:) #content content
     */
    private function renderContentNS4() {
        ?>
        <!-- content -->
        <div id="content" class="noframe">
            <a id="top"></a>
            <!-- header -->
            <div id="header" class="block block_full project noprint">
                <div class="hgroup inside">
                    <h1><a id="logo_project" href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href']) ?>"></a></h1>
                    <h2><?php echo wfMessage('sz-tagline')->text() ?></h2>
                </div>
            </div>
            <!-- /header -->
            <!-- bodyCcontent -->
            <div id="bodyContent" class="block block_full" role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
                <h3 class="title"><?php $this->html('title') ?></h3>
                <!-- inside -->
                <div class="inside">
                    <?php /*
                      <div id="nav" class="noprint">
                      <ul class="nav_actions">
                      <li>
                      <a href="#"><?php echo wfMessage('actions')->text() ?></a>
                      <ul>
                      <?php $this->renderNavigation(array('NAMESPACES', 'VIEWS', 'ACTIONS')); ?>
                      </ul>
                      </li>
                      </ul>
                      </div>
                     */ ?>
                    <!-- bodytext -->
                    <?php $this->html('bodytext') ?>
                    <!-- /bodytext -->
                </div>
                <!-- /inside -->
            </div>
            <!-- /bodyContent -->
            <!-- contentFooter -->
            <div id="contentFooter" class="block block_full block_flat ">
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderContentFooter(); ?>
                </div>
                <!-- /inside -->
            </div>
            <!-- /contentFooter -->
            <!-- Horizontal actions -->
            <div id="nav_horizontal" class="block block_full block_flat noprint">
                <ul class="nav_actions">
                    <?php $this->renderNavigation(array('NAMESPACES', 'VIEWS', 'ACTIONS')); ?>
                </ul>
            </div>
            <!-- /Horizontal actions -->
            <!-- contentOther -->
            <div id="contentOther" class="block block_full noprint">
                <!-- inside -->
                <div class="inside">
                    <?php $this->renderContentOther(); ?>
                </div>
            </div>
            <!-- contentOther -->
        </div>
        <!-- /content -->
        <?php
    }

    /**
     * Render the NS--1 (Special:) #content content
     */
    private function renderContentNSSpecial() {
        ?>
        <!-- content -->
        <div id="content" class="noframe">
            <a id="top"></a>
            <!-- header -->
            <div id="header" class="block block_full special noprint">
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
            <div id="bodyContent"  class="block block_full" role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
                <h3 class="title"><?php $this->html('title') ?></h3>
                <!-- inside -->
                <div class="inside">
                    <?php if ($this->data['subtitle']): ?>
                        <!-- subtitle -->
                        <div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
                        <!-- /subtitle -->
                    <?php endif; ?>
                    <!-- bodytext -->
                    <?php $this->html('bodytext') ?>
                    <!-- /bodytext -->
                </div>
                <!-- /inside -->
            </div>
            <!-- /bodyContent -->
        </div>
        <!-- /content -->
        <?php
    }

    /**
     * Render the NS-4 (Project:) #content content
     */
    private function renderContentMainpage() {
        ?>
        <!-- content -->
        <div id="content" class="noframe">
            <a id="top"></a>
            <!-- header -->
            <div id="header" class="block block_full project">
                <div class="hgroup inside">
                    <h1><a id="logo_project" href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href']) ?>"></a></h1>
                    <h2><?php echo wfMessage('sz-tagline')->text() ?></h2>
                </div>
            </div>
            <!-- /header -->
            <!-- bodyCcontent -->
            <div id="bodyContent" class="block block_full block_flat mainpage" role="main"<?php $this->html('specialpageattributes') ?>> <!--<div id="main" role="main">-->
                <!-- bodytext -->
                <?php $this->html('bodytext') ?>
                <!-- /bodytext -->
            </div>
            <!-- /bodyContent -->
        </div>
        <!-- /content -->
        <?php
    }

    /**
     * Render the ContentOther (Languages, Categories, Toolbox...)
     */
    private function renderContentOther() {
        if ($this->data['language_urls']):
            ?>
            <!-- language_urls -->
            <div class="portal" id="p-lang"<?php echo Linker::tooltip('p-lang') ?>>
                <h5<?php $this->html('userlangattributes') ?>><?php echo wfMessage('otherlanguages')->text() . wfMsgExt('colon-separator', 'escapenoentities'); ?></h5>

                <ul>
            <?php $this->renderNavigation(array('LANG')); ?>
                </ul>
            </div>
            <!-- /language_urls -->
        <?php endif; ?>
        <?php if ($this->data['catlinks']): ?>
            <!-- catlinks -->
            <?php $this->html('catlinks'); ?>
            <!-- /catlinks -->
        <?php endif; ?>
        <!-- toolbox -->
        <div class="portal" id="p-tb"<?php echo Linker::tooltip('p-tb') ?>>
            <h5<?php $this->html('userlangattributes') ?>><?php echo wfMessage('toolbox')->text() . wfMsgExt('colon-separator', 'escapenoentities'); ?></h5>
            <ul>
        <?php $this->renderNavigation(array('TOOLBOX')); ?>
            </ul>
        </div>
        <!-- /toolbox -->
        <?php
    }

    /**
     * Render the Contentfooter (content related infos)
     */
    private function renderContentFooter() {
        if ($this->data['subtitle']):
            ?>
            <!-- subtitle -->
            <div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
            <!-- /subtitle -->
        <?php endif; ?>
        <?php if ($this->data['undelete']): ?>
            <!-- undelete -->
            <div id="contentSub2"><?php $this->html('undelete') ?></div>
            <!-- /undelete -->
            <?php
        endif;
        foreach ($this->getFooterLinks() as $category => $links):
            ?>
            <ul id="footer-<?php echo $category ?>">
            <?php foreach ($links as $link): ?>
                    <li id="footer-<?php echo $category ?>-<?php echo $link ?>"><?php $this->html($link) ?></li>
                <?php endforeach; ?>
            </ul>
                <?php
            endforeach;
        }

        /**
         * Render the footer (main menu)
         */
        private function renderFooter() {
            ?>
        <!-- regular footer -->
        <div id="footer"  class="noprint">
            <div class="inside">
                <div class="content">
        <?php $this->renderMore(); ?>
                </div>
            </div>
        </div>
        <!-- /regular footer -->

        <!-- Absolute bottom menu -->
        <div id="absoluteFooter"  class="noprint">
            <div class="inside">
                <div class="content">
        <?php if (isset($this->data['sz_pretty_username'])): ?>
                        <span id="prettyUserName"><?php $this->text('sz_pretty_username') ?></span>
                    <?php endif; ?>
                    <!-- logo -->
                    <a id="logo_mini" href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href']); ?>"></a>
                    <!-- /logo -->
                    <!-- search -->
        <?php $this->renderNavigation(array('SEARCH')); ?>
                    <!-- /search -->
                    <!-- quicklinks -->
                    <ul>
        <?php $this->renderNavigation(array('SZ-FOOTER')); ?>
                        <li class="more">
                            <a href="#">
                                <span class="show_footer"><?php echo wfMessage('moredotdotdot')->text(); ?></span>
                                <span class="show_back" aria-hidden="true"><?php echo wfMessage('sz-back')->text(); ?></span>
                            </a>
                        </li>
                    </ul>
                    <!-- /quicklinks -->
                </div>
            </div>
        </div>
        <!-- /Absolute bottom menu -->
        <?php
    }

    /**
     * Render the "more..." footer panel content
     * @TODO optimize with better caching (wfMessage()->parse() is heavy)
     */
    private function renderMore() {
        ?>
        <div class="section">
            <p><?php echo wfMessage('sz-legalcontent')->text() ?></p>
            <ul>
                <li><?php echo wfMessage('sz-gtcu')->parse() ?></li>
                <li><?php echo wfMessage('sz-astcu')->parse() ?></li>
                <li><?php echo wfMessage('sz-legalinfo')->parse() ?></li>
                <li><?php echo wfMessage('sz-privacypolicy')->parse() ?></li>
            </ul>
        </div>

        <div class="section">
            <p><?php echo wfMessage('sz-generalinfo')->text() ?></p>
            <ul>
                <li><?php echo wfMessage('sz-discoverseizam')->parse() ?></li>
                <li><?php echo wfMessage('sz-browseseizam')->parse() ?></li>
                <li><?php echo wfMessage('sz-joinseizam')->parse() ?></li>
                <li><?php echo wfMessage('sz-help')->parse() ?></li>
                <li><?php echo wfMessage('sz-faq')->parse() ?></li>
            </ul>
        </div>

        <div class="section">
            <p><?php echo wfMessage('sz-communicate')->text() ?></p>
            <ul>
                <li><?php echo wfMessage('sz-reportabuse')->parse() ?></li>
                <li><?php echo wfMessage('sz-reportbug')->parse() ?></li>
                <li><?php echo wfMessage('sz-technicalsupport')->parse() ?></li>
                <li><?php echo wfMessage('sz-contactus')->parse() ?></li>
            </ul>
        </div>

        <div class="section">
            <p class="sread"><?php echo wfMessage('sz-selectlang')->text() ?></p>
        <?php echo wfLanguageSelectorHTML($this->skin->getTitle(), null, 'selectLang'); ?>
            <p class="sread"><?php echo wfMessage('sz-seizamonsocialnetworks')->text() ?></p>
            <ul class="socials">
                <li class="tumblr"><a href="http://www.davidcanwin.com">Tumblr</a></li>
                <li class="twitter"><a href="http://twitter.com/davidcanwin">Twitter</a></li>
                <li class="fcbk"><a href="http://www.facebook.com/davidcanwin">Facebook</a></li>
                <li class="linkedin"><a href="http://www.linkedin.com/company/seizam">LinkedIn</a></li>
            </ul>
        <?php $footericons = $this->getFooterIcons("icononly");
        if (count($footericons) > 0): ?>
                <ul id="footer-icons">
                <?php foreach ($footericons as $blockName => $footerIcons): ?>
                        <li id="footer-<?php echo htmlspecialchars($blockName); ?>ico">
                        <?php foreach ($footerIcons as $icon): ?>
                                <?php echo $this->skin->makeFooterIcon($icon); ?>

                            <?php endforeach; ?>
                        </li>
                        <?php endforeach; ?>
                </ul>
                <?php endif; ?>
        </div>
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
                    foreach ($this->getPersonalTools() as $key => $item):
                        echo $this->makeListItem($key, $item);
                    endforeach;
                    break;
                case 'SZ-FOOTER' :
                    foreach ($this->getSzAbsoluteFooterUrls() as $key => $item):
                        echo $this->makeListItem($key, $item);
                    endforeach;
                    break;
                case 'TOOLBOX' :
                    foreach ($this->getToolbox() as $key => $item):
                        echo $this->makeListItem($key, $item);
                    endforeach;
                    break;
                case 'LANG' :
                    foreach ($this->data['language_urls'] as $key => $item):
                        echo $this->makeListItem($key, $item);
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
                                        <?php echo $this->makeSearchButton('image', array('id' => 'searchButton', 'src' => $this->skin->getSkinStylePath('images/search-rtl.png'))); ?>
                                    <?php endif; ?>
                                    <?php echo $this->makeSearchInput(array('id' => 'searchInput', 'type' => 'text')); ?>
                                    <?php if (!$this->data['rtl']): ?>
                                        <?php echo $this->makeSearchButton('image', array('id' => 'searchButton', 'src' => $this->skin->getSkinStylePath('images/search-ltr.png'))); ?>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <?php echo $this->makeSearchInput(array('id' => 'searchInput')); ?>
                                <?php echo $this->makeSearchButton('go', array('id' => 'searchGoButton', 'class' => 'searchButton')); ?>
                                <?php echo $this->makeSearchButton('fulltext', array('id' => 'mw-searchButton', 'class' => 'searchButton')); ?>
                            <?php endif; ?>
                        </form>
                    </div>
                    <?php
                    break;
            }
        }
    }

    /**
     * Create an array of footer links items from the data in the quicktemplate
     * stored by the SkinTemplate Hook in extensions/Skinzam/Skinzam.hooks.php.
     * The resulting array is built acording to a format intended to be passed
     * through makeListItem to generate the html.
     * This is in reality the same list as already stored in absolute_footer_urls
     * however it is reformatted so that you can just pass the individual items
     * to makeListItem instead of hardcoding the element creation boilerplate.
     * 
     * @return array
     */
    private function getSzAbsoluteFooterUrls() {
        $szFooterUrls = array();
        foreach ($this->data['absolute_footer_urls'] as $key => $szfurl) {
            # The class on a personal_urls item is meant to go on the <a> instead
            # of the <li> so we have to use a single item "links" array instead
            # of using most of the personal_url's keys directly
            $szFooterUrls[$key] = array();
            $szFooterUrls[$key]["links"][] = array();
            $szFooterUrls[$key]["links"][0]["single-id"] = $szFooterUrls[$key]["id"] = "szf-$key";


            if (isset($szfurl["active"])) {
                $szFooterUrls[$key]["active"] = $szfurl["active"];
            }
            foreach (array("href", "class", "text") as $k) {
                if (isset($szfurl[$k]))
                    $szFooterUrls[$key]["links"][0][$k] = $szfurl[$k];
            }
        }
        return $szFooterUrls;
    }

}

