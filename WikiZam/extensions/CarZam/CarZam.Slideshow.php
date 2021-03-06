<?php

if (!defined('MEDIAWIKI'))
    die(1);

/**
 * Image Carousel
 *
 * Add images to the carrousel using add(), then render that list to HTML using toHTML().
 *
 * @ingroup Extensions
 */
class CarZamSlideshow {
    const CAPTIONHEIGHT = 30;

    var $mImages = array();

    /**
     * Hide blacklisted images?
     */
    var $mHideBadImages = false;

    /**
     * Registered parser object for output callbacks
     * @var Parser
     */
    var $mParser = false;

    /**
     * Design var in px
     */
    var $mPhotoHeight = 441, $mPhotoWidth = 784, $mFloat = 'none';
    var $mMarginLeft = 0, $mMarginRight = 0;

    /**
     * Contextual title, used when images are being screened
     * against the bad image list
     */
    private $contextTitle = false;
    private $mAttribs = array();

    /**
     * Create a new image gallery object.
     */
    public function __construct() {
        
    }

    /**
     * Register a parser object
     *
     * @param $parser Parser
     */
    public function setParser($parser) {
        $this->mParser = $parser;
    }

    /**
     * Set bad image flag
     */
    public function setHideBadImages($flag = true) {
        $this->mHideBadImages = $flag;
    }

    public function setPhotoHeight($height = 441) {
        if (intval($height) && $height > 120)
            $this->mPhotoHeight = $height;
    }

    public function setPhotoWidth($width = 784) {
        if (intval($width) && $width > 120)
            $this->mPhotoWidth = $width;
    }

    public function setFloat($value = 'none') {
        $value = strtolower($value);
        if ($value == 'left') {
            $this->mFloat = $value;
            $this->mMarginRight = 18;
        } else if ($value == 'right') {
            $this->mFloat = $value;
            $this->mMarginLeft = 18;
        }
    }

    /**
     * Add an image to the gallery.
     *
     * @param $title Title object of the image that is added to the gallery
     * @param $html  String: Additional HTML text to be shown. The name and size of the image are always shown.
     * @param $alt   String: Alt text for the image
     */
    public function add($title, $html = '', $alt = '', $linkTitle = null) {
        $this->mImages[] = array($title, $html, $alt, $linkTitle);
    }

    /**
     * isEmpty() returns true if the gallery contains no images
     */
    public function isEmpty() {
        return empty($this->mImages);
    }

    /**
     * Set arbitrary attributes to go on the HTML gallery output element.
     * Should be suitable for a <ul> element.
     *
     * Note -- if taking from user input, you should probably run through
     * Sanitizer::validateAttributes() first.
     *
     * @param $attribs Array of HTML attribute pairs
     */
    public function setAttributes($attribs) {
        $this->mAttribs = $attribs;
    }

    /**
     * Return a HTML representation of the image gallery
     *
     * For each image in the gallery, display
     * - a thumbnail
     * - the image name
     * - the additional text provided when adding the image
     * - the size of the image
     *
     */
    public function toHTML() {

        $slides = '';

        # Output each image...
        foreach ($this->mImages as $pair) {
            $nt = $pair[0];
            $text = $pair[1]; # "text" means "caption" here
            $alt = $pair[2];
            $titleLink = $pair[3];

            // Searching the image
            $descQuery = false;
            if ($nt->getNamespace() == NS_FILE) {
                # Get the file...
                if ($this->mParser instanceof Parser) {
                    # Give extensions a chance to select the file revision for us
                    $time = $sha1 = false;
                    wfRunHooks('BeforeParserFetchFileAndTitle', array($this->mParser, $nt, &$time, &$sha1, &$descQuery));
                    # Fetch and register the file (file title may be different via hooks)
                    list( $img, $nt ) = $this->mParser->fetchFileAndTitle($nt, $time, $sha1);
                } else {
                    $img = wfFindFile($nt);
                }
            } else {
                $img = false;
            }

            $slides .= Html::rawElement('li', array(), $this->photoToHTML($img, $nt, $text, $alt, $titleLink, $descQuery));
        }

        $slides = Html::rawElement('ul', array(), $slides);
        $slidesHeight = $this->mPhotoHeight + self::CAPTIONHEIGHT;
        $slidesWidth = $this->mPhotoWidth + 2;

        $attribs = Sanitizer::mergeAttributes(
                        array('class' => 'slideshow', 'style' => 'height:' . $slidesHeight . 'px; width:' . $slidesWidth . 'px; float:' . $this->mFloat . '; margin-left:'. $this->mMarginLeft. 'px; margin-right:'. $this->mMarginRight .'px;'), $this->mAttribs);

        $output = Html::rawElement('div', $attribs, $slides);

        return $output;
    }

    private function photoToHTML($img, $nt, $text = '', $alt = '', $titleLink = null, $descQuery = '') {


        //Some ugly alignment logic used later
        $verticalPadding = 0;
        $horizontalPadding = 0;

        $params = array(
            'width' => $this->mPhotoWidth,
            'height' => $this->mPhotoHeight,
        );

        if (!$img) {
            $html = '<div class="CarError" style="height: ' . $params['height'] . 'px; width: ' . $params['width'] . 'px;">'
                    . htmlspecialchars($nt->getText()) . '</div>';
        } else if ($this->mHideBadImages && wfIsBadImage($nt->getDBkey(), $this->getContextTitle())) {
            $html = '<div class="CarError" style="height: ' . $params['height'] . 'px; width: ' . $params['width'] . 'px;">' .
                    Linker::link(
                            $nt, htmlspecialchars($nt->getText()), array(), array(), array('known', 'noclasses')
                    ) .
                    '</div>';
        } else {
            if (!( $thumb = $img->transform($params) )) {
                # Error generating thumbnail.
                $html = '<div class="CarError" style="height: ' . $params['height'] . 'px; width: ' . $params['width'] . 'px;">' . htmlspecialchars($img->getLastError()) . '</div>';
            } else {
                $imageParameters = array(
                    'desc-link' => true,
                    'desc-query' => $descQuery,
                    'alt' => $alt,
                    'custom-title-link' => $titleLink
                );

                # In the absence of both alt text and caption, fall back on providing screen readers with the filename as alt text
                if ($alt == '') {
                    if ($text == '')
                        $imageParameters['alt'] = $nt->getText();
                    else {
                        $imageParameters['alt'] = htmlspecialchars($text);
                    }
                }

                $html = $thumb->toHtml($imageParameters);


                //Some ugly alignment logic
                $thumbHeight = $thumb->getHeight();
                $thumbWidth = $thumb->getWidth();
                if ($this->mPhotoHeight > $thumbHeight)
                    $verticalPadding = floor(($this->mPhotoHeight - $thumbHeight) / 2);
                if ($this->mPhotoWidth > $thumbWidth)
                    $horizontalPadding = floor(($this->mPhotoWidth - $thumbWidth) / 2);

                // Call parser transform hook
                if ($this->mParser && $img->getHandler()) {
                    $img->getHandler()->parserTransformHook($this->mParser, $img);
                }
            }
        }

        $wrapperHeight = $this->mPhotoHeight - 2 * $verticalPadding  + 2;
        $wrapperWidth = $this->mPhotoWidth - 2 * $horizontalPadding + 2;

        $html = Html::rawElement('div', array('class' => 'wrapper', 'style' => 'height:' . $wrapperHeight . 'px; width:' . $wrapperWidth . 'px; padding:' . $verticalPadding . 'px ' . $horizontalPadding . 'px;'), $html);

        $html .= Html::rawElement('div', array('class' => 'caption'), $text);
        return $html;
    }

    /**
     * @return Integer: number of images in the gallery
     */
    public function count() {
        return count($this->mImages);
    }

    /**
     * Set the contextual title
     *
     * @param $title Title: contextual title
     */
    public function setContextTitle($title) {
        $this->contextTitle = $title;
    }

    /**
     * Get the contextual title, if applicable
     *
     * @return mixed Title or false
     */
    public function getContextTitle() {
        return is_object($this->contextTitle) && $this->contextTitle instanceof Title ? $this->contextTitle : false;
    }

}

//CarZamCarrousel
