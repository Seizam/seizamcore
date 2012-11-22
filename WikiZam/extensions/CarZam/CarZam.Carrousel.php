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
class CarZamCarrousel {
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
    var $mPhotoHeight = 441, $mPhotoWidth = 784, $mThumbHeight = 120, $mThumbWidth = 120;

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
        if (intval($height) && $height > $this->mThumbHeight)
            $this->mPhotoHeight = $height;
    }

    /**
     * Add an image to the gallery.
     *
     * @param $title Title object of the image that is added to the gallery
     * @param $html  String: Additional HTML text to be shown. The name and size of the image are always shown.
     * @param $alt   String: Alt text for the image
     * @param $titleLink Title object of the page the image links too.
     */
    public function add($title, $html = '', $alt = '', $titleLink = null) {
        $this->mImages[] = array($title, $html, $alt, $titleLink);
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
     * Return a HTML representation of the image Carrousel
     *
     * For each image in the gallery, display
     * - a thumbnail
     * - the image name
     * - the additional text provided when adding the image
     * - the size of the image
     *
     */
    public function toHTML() {

        $car_photos = '';

        $car_slider = '';

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

            $car_photos .= Html::rawElement('li', array(), $this->photoToHTML($img, $nt, $text, $alt, $titleLink, $descQuery));
            $car_slider .= Html::rawElement('li', array(), $this->thumbToHTML($img, $nt, $text, $alt, $descQuery));
        }

        $car_photos = Html::rawElement('ul', array(), $car_photos);
        $photoHeight = $this->mPhotoHeight + self::CAPTIONHEIGHT;
        $car_photos = Html::rawElement('div', array('id' => 'car_photos', 'style' => 'height:' . $photoHeight . 'px'), $car_photos);

        $car_slider = Html::rawElement('ul', array(), $car_slider);
        $car_slider = Html::rawElement('div', array('class' => 'car_slider_window'), $car_slider);
        $car_slider = Html::rawElement('div', array('id' => 'car_slider'), $car_slider);

        $attribs = Sanitizer::mergeAttributes(
                        array('id' => 'carrousel'), $this->mAttribs);
        
        $output = Html::rawElement('div', $attribs, $car_photos . $car_slider);

        return $output;
    }

    /**
     *
     * @param File $img
     * @param Title $nt
     * @param String $text
     * @param String $alt
     * @param Title $titleLink
     * @param type $descQuery
     * @return String 
     */
    private function photoToHTML($img, $nt, $text = '', $alt = '', $titleLink = null, $descQuery = '') {
        $params = array(
            'width' => $this->mPhotoWidth,
            'height' => $this->mPhotoHeight
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
        } else if (!( $thumb = $img->transform($params) )) {
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
                $imageParameters['alt'] = $nt->getText();
            }

            $html = $thumb->toHtml($imageParameters);

            // Call parser transform hook
            if ($this->mParser && $img->getHandler()) {
                $img->getHandler()->parserTransformHook($this->mParser, $img);
            }
        }
        $html .= Html::rawElement('figcaption', array(), $text);
        $html = Html::rawElement('figure', array(), $html);
        return $html;
    }

    private function thumbToHTML($img, $nt, $text = '', $alt = '', $descQuery = '') {
        $params = array(
            'height' => $this->mThumbHeight,
            'width' => $this->mPhotoWidth //We don't want to constraint width.
        );

        if (!$img) {
            $html = '<a class="CarError" style="height: ' . $params['height'] . 'px; width: ' . $this->mThumbWidth . 'px;">'
                    . htmlspecialchars($nt->getText()) . '</a>';
        } else if ($this->mHideBadImages && wfIsBadImage($nt->getDBkey(), $this->getContextTitle())) {
            $html = '<a class="CarError" style="height: ' . $params['height'] . 'px; width: ' . $this->mThumbWidth . 'px;">' .
                    Linker::link(
                            $nt, htmlspecialchars($nt->getText()), array(), array(), array('known', 'noclasses')
                    ) .
                    '</a>';
        } else if (!( $thumb = $img->transform($params) )) {
            # Error generating thumbnail.
            $html = '<a class="CarError" style="height: ' . $params['height'] . 'px; width: ' . $this->mThumbWidth . 'px;">' . plop . '</a>';
        } else {
            $imageParameters = array(
                'desc-link' => true,
                'desc-query' => $descQuery,
                'alt' => $alt,
            );

            # In the absence of both alt text and caption, fall back on providing screen readers with the filename as alt text
            if ($alt == '') {
                $imageParameters['alt'] = $nt->getText();
            }

            $html = $thumb->toHtml($imageParameters);
        }
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
