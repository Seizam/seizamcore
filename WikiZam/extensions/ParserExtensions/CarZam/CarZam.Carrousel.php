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
    var $mPhotoHeight = 300, $mPhotoWidth = 786, $mThumbHeight = 120, $mThumbWidth = 120;

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

    /**
     * Add an image to the gallery.
     *
     * @param $title Title object of the image that is added to the gallery
     * @param $html  String: Additional HTML text to be shown. The name and size of the image are always shown.
     * @param $alt   String: Alt text for the image
     */
    public function add($title, $html = '', $alt = '') {
        $this->mImages[] = array($title, $html, $alt);
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
        global $wgLang;

        $attribs = Sanitizer::mergeAttributes(
                        array('id' => 'carrousel'), $this->mAttribs);

        $car_photos = '';

        $car_slider = '';
        $ThumbParams = array(
            'width' => $this->mThumbWidth,
            'height' => $this->mThumbHeight
        );

        # Output each image...
        foreach ($this->mImages as $pair) {
            $nt = $pair[0];
            $text = $pair[1]; # "text" means "caption" here
            $alt = $pair[2];

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
            
            $car_photos .= Html::rawElement('li', array(), $this->photoToHTML($img, $nt, $text, $alt, $descQuery));
            $car_slider .= Html::rawElement('li', array(), $this->thumbToHTML($img, $nt, $text, $alt, $descQuery));
        }

        $car_photos = Html::rawElement('ul', array(), $car_photos);
        $car_photos = Html::rawElement('div', array('id' => 'car_photos'), $car_photos);

        $car_slider = Html::rawElement('ul', array(), $car_slider);
        $car_slider = Html::rawElement('div', array('id' => 'car_slider'), $car_slider);

        $output = Html::rawElement('div', array('id' => 'carrousel'), $car_photos . $car_slider);

        return $output;
    }

    private function photoToHTML($img, $nt, $text = '', $alt = '', $descQuery = '') {
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
            );
            
            # In the absence of both alt text and caption, fall back on providing screen readers with the filename as alt text
            if ($alt == '' && $text == '') {
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
            'width' => $this->mThumbWidth,
            'height' => $this->mThumbHeight
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
            );
            
            # In the absence of both alt text and caption, fall back on providing screen readers with the filename as alt text
            if ($alt == '' && $text == '') {
                $imageParameters['alt'] = $nt->getText();
            }

            $html = $thumb->toHtml($imageParameters);

            // Call parser transform hook
            if ($this->mParser && $img->getHandler()) {
                $img->getHandler()->parserTransformHook($this->mParser, $img);
            }
        }
        return $html;
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
    public function toHTML2() {
        global $wgLang;

        if ($this->mPerRow > 0) {
            $maxwidth = $this->mPerRow * ( $this->mWidths + self::THUMB_PADDING + self::GB_PADDING + self::GB_BORDERS );
            $oldStyle = isset($this->mAttribs['style']) ? $this->mAttribs['style'] : '';
            # _width is ignored by any sane browser. IE6 doesn't know max-width so it uses _width instead
            $this->mAttribs['style'] = "max-width: {$maxwidth}px;_width: {$maxwidth}px;" . $oldStyle;
        }

        $attribs = Sanitizer::mergeAttributes(
                        array('class' => 'gallery'), $this->mAttribs);

        $output = Xml::openElement('ul', $attribs);
        if ($this->mCaption) {
            $output .= "\n\t<li class='gallerycaption'>{$this->mCaption}</li>";
        }

        $params = array(
            'width' => $this->mWidths,
            'height' => $this->mHeights
        );
        # Output each image...
        foreach ($this->mImages as $pair) {
            $nt = $pair[0];
            $text = $pair[1]; # "text" means "caption" here
            $alt = $pair[2];

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

            if (!$img) {
                # We're dealing with a non-image, spit out the name and be done with it.
                $thumbhtml = "\n\t\t\t" . '<div style="height: ' . ( self::THUMB_PADDING + $this->mHeights ) . 'px;">'
                        . htmlspecialchars($nt->getText()) . '</div>';
            } elseif ($this->mHideBadImages && wfIsBadImage($nt->getDBkey(), $this->getContextTitle())) {
                # The image is blacklisted, just show it as a text link.
                $thumbhtml = "\n\t\t\t" . '<div style="height: ' . ( self::THUMB_PADDING + $this->mHeights ) . 'px;">' .
                        Linker::link(
                                $nt, htmlspecialchars($nt->getText()), array(), array(), array('known', 'noclasses')
                        ) .
                        '</div>';
            } elseif (!( $thumb = $img->transform($params) )) {
                # Error generating thumbnail.
                $thumbhtml = "\n\t\t\t" . '<div style="height: ' . ( self::THUMB_PADDING + $this->mHeights ) . 'px;">'
                        . htmlspecialchars($img->getLastError()) . '</div>';
            } else {
                $vpad = ( self::THUMB_PADDING + $this->mHeights - $thumb->height ) / 2;

                $imageParameters = array(
                    'desc-link' => true,
                    'desc-query' => $descQuery,
                    'alt' => $alt,
                );
                # In the absence of both alt text and caption, fall back on providing screen readers with the filename as alt text
                if ($alt == '' && $text == '') {
                    $imageParameters['alt'] = $nt->getText();
                }

                # Set both fixed width and min-height.
                $thumbhtml = "\n\t\t\t" .
                        '<div class="thumb" style="width: ' . ( $this->mWidths + self::THUMB_PADDING ) . 'px;">'
                        # Auto-margin centering for block-level elements. Needed now that we have video
                        # handlers since they may emit block-level elements as opposed to simple <img> tags.
                        # ref http://css-discuss.incutio.com/?page=CenteringBlockElement
                        . '<div style="margin:' . $vpad . 'px auto;">'
                        . $thumb->toHtml($imageParameters) . '</div></div>';

                // Call parser transform hook
                if ($this->mParser && $img->getHandler()) {
                    $img->getHandler()->parserTransformHook($this->mParser, $img);
                }
            }

            //TODO
            // $linkTarget = Title::newFromText( $wgContLang->getNsText( MWNamespace::getUser() ) . ":{$ut}" );
            // $ul = Linker::link( $linkTarget, $ut );

            if ($this->mShowBytes) {
                if ($img) {
                    $fileSize = wfMsgExt('nbytes', array('parsemag', 'escape'), $wgLang->formatNum($img->getSize()));
                } else {
                    $fileSize = wfMsgHtml('filemissing');
                }
                $fileSize = "$fileSize<br />\n";
            } else {
                $fileSize = '';
            }

            $textlink = $this->mShowFilename ?
                    Linker::link(
                            $nt, htmlspecialchars($wgLang->truncate($nt->getText(), $this->mCaptionLength)), array(), array(), array('known', 'noclasses')
                    ) . "<br />\n" :
                    '';

            # ATTENTION: The newline after <div class="gallerytext"> is needed to accommodate htmltidy which
            # in version 4.8.6 generated crackpot html in its absence, see:
            # http://bugzilla.wikimedia.org/show_bug.cgi?id=1765 -Ævar
            # Weird double wrapping (the extra div inside the li) needed due to FF2 bug
            # Can be safely removed if FF2 falls completely out of existance
            $output .=
                    "\n\t\t" . '<li class="gallerybox" style="width: ' . ( $this->mWidths + self::THUMB_PADDING + self::GB_PADDING ) . 'px">'
                    . '<div style="width: ' . ( $this->mWidths + self::THUMB_PADDING + self::GB_PADDING ) . 'px">'
                    . $thumbhtml
                    . "\n\t\t\t" . '<div class="gallerytext">' . "\n"
                    . $textlink . $text . $fileSize
                    . "\n\t\t\t</div>"
                    . "\n\t\t</div></li>";
        }
        $output .= "\n</ul>";

        return $output;
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
