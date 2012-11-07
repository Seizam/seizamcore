<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

class TitleExplosion {

    /**
     * @var Title 
     */
    private $title;

    /**
     * @var Title[] 
     */
    private $titles = array();

    /**
     * @var Boolean 
     */
    private $hasLanguageTitle = null;

    /**
     * @var Boolean
     */
    private $isFile = null;

    /**
     * @var String 
     */
    private $extension = null;
    
    /**
     * @var type 
     */
    private $fileName = null;

    /**
     * @param Title $title
     */
    private function __construct($title) {
        $this->title = $title;
    }

    private function setTitlesForFile() {
        $titles = array();
        $explosion = explode('.', $this->title->getText());

        $this->extension = array_pop($explosion);

        $text = array_shift($explosion);
        $titles[] = Title::newFromText($text, NS_MAIN);

        $titles[] = $this->title;

        $this->fileName = implode('.', $explosion);
        $this->titles = $titles;
    }

    private function setTitlesDefault() {
        $ns = $this->title->getNamespace();

        $titles = array();
        $explosion = explode('/', $this->title->getText());
        $text = '';
        foreach ($explosion as $atom) {
            $text .= $atom;
            $titles[] = Title::newFromText($text, $ns);
            $text .= '/';
        }

        $this->titles = $titles;
    }

    /**
     * @param Title[] $titles
     */
    private function setTitles() {
        if ($this->isFile()) {
            $this->setTitlesForFile();
        } else {
            $this->setTitlesDefault();
        }
    }

    /**
     * @param Title $title 
     * @return TitleExplosion
     */
    public static function newFromTitle($title) {
        $titleExplosion = new TitleExplosion($title);
        $titleExplosion->setTitles();
        return $titleExplosion;
    }

    private function getHtmlForFile() {
        $html = Linker::linkKnown($this->titles[0], $this->titles[0]->getText(), array('class' => 'fh-homepage'));
        $html .= self::getHtmlSeparator('.') . Linker::linkKnown($this->titles[1], $this->fileName, array('class' => 'fh-current'));
        $html .= self::getHtmlSeparator('.' . $this->extension);
        return $html;
    }

    private function getHtmlDefault() {
        $html = '';
        $position = 0;
        foreach ($this->titles as $title) {
            $html .= Linker::linkKnown($title, $title->getSubpageText(), array('class' => $this->getDefaultLinkClasses($position)));
            $html .= self::getHtmlSeparator('/');
            $position++;
        }
        $html = substr($html, 0, - strlen(self::getHtmlSeparator('/')));
        return $html;
    }

    public function getHtml() {
        $html = '';
        
        if ($this->title->getNamespace() != NS_MAIN) {
            $html .= self::getHtmlSeparator($this->title->getNsText() . ':');
        }

        if ($this->isFile()) {
            $html .= $this->getHtmlForFile();
        } else {
            $html .= $this->getHtmlDefault();
        }

        return $html;
    }

    /**
     *
     * @param Int $position
     * @return String 
     */
    private function getDefaultLinkClasses($position) {
        $classes = array();

        if ($position == 0) {
            $classes[] = 'fh-homepage';
        }

        $lastPosition = count($this->titles) - 1;

        if ($position == $lastPosition - 1 && $this->hasLanguageTitle()) {
            $classes[] = 'fh-current';
        } else if ($position == $lastPosition) {
            if ($this->hasLanguageTitle()) {
                $classes[] = 'fh-language';
            } else {
                $classes[] = 'fh-current';
            }
        }

        return implode(' ', $classes);
    }

    private function hasLanguageTitle() {
        if ($this->hasLanguageTitle == null) {
            if (strlen($this->titles[count($this->titles) - 1]->getSubpageText()) == 2) {
                $this->hasLanguageTitle = true;
            } else {
                $this->hasLanguageTitle = false;
            }
        }
        return $this->hasLanguageTitle;
    }

    private function isFile() {
        if ($this->isFile == null) {
            $ns = $this->title->getNamespace();
            if ($ns == NS_FILE || $ns == NS_FILE_TALK) {
                $this->isFile = true;
            } else {
                $this->isFile = false;
            }
        }
        return $this->isFile;
    }
    
    private static function getHtmlSeparator($separator) {
        return '<span>'.$separator.'</span>';
    }

}