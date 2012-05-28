<?php

class SpecialWikiplaces extends SpecialPage {
    const TITLE_NAME = 'Wikiplaces';

    const ACTION_CREATE_WIKIPLACE = 'Create';
    const ACTION_CREATE_SUBPAGE = 'CreatePage';
    const ACTION_LIST_WIKIPLACES = 'List';
    const ACTION_CONSULT_WIKIPLACE = 'Consult';

    private $homepageString;
    private $subpageString;

    public function __construct() {
        parent::__construct(self::TITLE_NAME, WP_ACCESS_RIGHT);
    }

    /**
     * Show the special page
     *
     * @param $par String subpage string, if one was specified
     */
    public function execute($par) {

        $this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title

        $user = $this->getUser();
        
        $output = $this->getOutput();

        // Check rights
        if (!$this->userCanExecute($user)) {
            // If anon, redirect to login
            if ($user->isAnon()) {
                $output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto'=>$this->getFullTitle())), '401');
                return;
            }
            // Else display an error page.
            $this->displayRestrictionError();
            return;
        }

        if (isset($par) & $par != '') {
            $explosion = explode(':', $par);
            if (count($explosion) == 1) {
                $action = $explosion[0];
                $name = null;
            } else if (count($explosion) == 2) {
                $action = $explosion[0];
                $name = $explosion[1];
            }
        } else {
            $action = $this->getRequest()->getText('action',null);
            $name = $this->getRequest()->getText('name', null);
        }
        
        switch ($action) {

            case self::ACTION_CONSULT_WIKIPLACE:
                if ($name != null) {
                    $this->displayConsultWikiplace($name);
                } else {
                    $this->displayList();
                }
                break;

            case self::ACTION_CREATE_SUBPAGE:
                $this->displayCreateSubpage($name);
                break;

            case self::ACTION_CREATE_WIKIPLACE:
                $this->displayCreateWikiplace();
                break;

            case self::ACTION_LIST_WIKIPLACES:
            default:
                $this->displayList();
                break;
        }
    }

    private function displayList() {

        $user_id = $this->getUser()->getId();
        $output = $this->getOutput();

        $tp = new WpWikiplacesTablePager();
        $tp->setSelectConds(array('wpw_owner_user_id' => $user_id));
        $tp->setHeader(wfMessage('wp-list-header')->parse());
        $diskspace = wgformatSizeMB(WpPage::countDiskspaceUsageByUser($user_id));
        $pages = wgFormatNumber(WpPage::countPagesOwnedByUser($user_id));
        $tp->setFooter(wfMessage('wp-list-footer', $diskspace, $pages)->parse());
        /** @TODO Add Total Hits, Total Bandwidth & Report Updated, ie. Make pretty getters and factories in WpWikiplace that can take the result/row from the pager as argument */
        $output->addHTML($tp->getWholeHtml());
    }

    private function displayCreateWikiplace() {

        if (( $reason = WpSubscription::userCanCreateWikiplace($this->getUser()->getId())) !== true) {
            $this->getOutput()->showErrorPage('sorry', $reason); // no active subscription or quotas exceeded
            return;
        }

        $formDescriptor = array(
            'Name' => array(
                'type' => 'text',
                'label-message' => 'wp-name-field',
                'section' => 'create-section',
                'help-message' => 'wp-create-name-help',
                'validation-callback' => array($this, 'validateNewWikiplaceName'),
            )
        );
        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->addHeaderText(wfMessage('wp-create-header')->parse());
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->setTitle($this->getTitle(self::ACTION_CREATE_WIKIPLACE));
        $htmlForm->setSubmitCallback(array($this, 'processCreateWikiplace'));
        $htmlForm->setSubmitText(wfMessage('wp-create')->text());
        if ($htmlForm->show()) {
            $this->getOutput()->addHTML(wfMessage(
                            'wp-create-wp-success', $this->homepageString)->parse());
        }
    }

    public function validateNewWikiplaceName($name, $allData) {

        if (!is_string($name) || preg_match('/[.\\/]/', $name)) {
            return wfMessage('wp-invalid-name')->text();
        }

        $title = Title::newFromText($name);
        if ($title == null) {
            return wfMessage('wp-invalid-name')->text();
        }

        if ($title->isKnown()) {
            return wfMessage('wp-name-already-exists')->text();
        }

        return true;
    }

    public function processCreateWikiplace($formData) {

        if (!isset($formData['Name'])) { //check the key exists and value is not NULL
            throw new MWException('Cannot create Wikiplace, no data.');
        }

        $homepage = WpWikiplace::initiateCreation($formData['Name']);
        if (!( $homepage instanceof Title )) {
            return wfMessage('sz-internal-error')->parse(); // error while creating
        }

        $this->homepageString = $homepage;

        return true; // all ok :)
    }

    public function displayCreateSubpage($name) {

        if (( $reason = WpSubscription::userCanCreateNewPage($this->getUser()->getId())) !== true) {
            $this->getOutput()->showErrorPage('sorry', $reason);  // no active subscription or quotas exceeded 
            return;
        }

        $wikiplaces = WpWikiplace::factoryAllOwnedByUserId($this->getUser()->getId());
        if (count($wikiplaces) == 0) {
            $this->getOutput()->showErrorPage('sorry', 'wp-create-wp-first');
            return;
        }

        $formDescriptor = array(
            'WpId' => array(
                'type' => 'select',
                'label-message' => 'wp-wikiplace-field',
                'section' => 'createpage-section',
                'help-message' => 'wp-createpage-wikiplace-help',
                'validation-callback' => array($this, 'validateUserWikiplaceID'),
                'options' => array(),
            ),
            'SpName' => array(
                'type' => 'text',
                'label-message' => 'wp-name-field',
                'section' => 'createpage-section',
                'help-message' => 'wp-createpage-name-help',
                'validation-callback' => array($this, 'validateNewSubpageName'),
            ),
        );

        if ($name != null) {
            $name = Title::newFromDBkey($name)->getText();
        }

        foreach ($wikiplaces as $wikiplace) {
            $wpw_name = $wikiplace->getName();
            $formDescriptor['WpId']['options'][$wpw_name] = $wikiplace->getId();
            if ($name == $wpw_name) {
                $formDescriptor['WpId']['default'] = $wikiplace->getId();
            }
        }

        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->addHeaderText(wfMessage('wp-createpage-header')->parse());
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->setTitle($this->getTitle(self::ACTION_CREATE_SUBPAGE));
        $htmlForm->setSubmitCallback(array($this, 'processCreateSubpage'));
        $htmlForm->setSubmitText(wfMessage('wp-create')->text());
        if ($htmlForm->show()) {
            $this->getOutput()->addHTML(wfMessage(
                            'wp-create-sp-success', $this->homepageString, $this->subpageString)->parse());
        }
    }

    public function validateUserWikiplaceID($id, $allData) {

        if (!is_string($id) || !preg_match('/^[1-9]{1}[0-9]{0,9}$/', $id)) {
            return 'Error: Invalid Wikiplace ID';
        }

        $wikiplace = WpWikiplace::getById(intval($id));

        if (($wikiplace === null) || !($wikiplace->isOwner($this->getUser()->getId()))) {
            return 'Error: Invalid Wikiplace';
        }

        return true; // all ok
    }

    public function validateNewSubpageName($name, $allData) {

        if ($name == null) {
            return false;
        }

        // $allData['WikiplaceId'] is already checked, because it is declared before the subpage name in the form descriptor
        $wikiplace = WpWikiplace::getById(intval($allData['WpId']));
        if ($wikiplace == null) {
            return false;
        }

        $title = Title::newFromText($wikiplace->getName() . '/' . $name);

        if ($title->isKnown()) {
            return wfMessage('wp-name-already-exists')->text();
        }

        return true; // all ok
    }

    public function processCreateSubpage($formData) {

        if (!isset($formData['WpId']) || !isset($formData['SpName'])) {
            throw new MWException('Cannot create Wikiplace, no data.');
        }

        $wikiplace = WpWikiplace::getById(intval($formData['WpId']));

        $subpage = WpPage::createSubpage($wikiplace, $formData['SpName']);

        if (!( $subpage instanceof Title )) {
            return wfMessage('sz-internal-error')->parse();
        }

        $this->homepageString = $wikiplace->getName();
        $this->subpageString = $formData['SpName'];

        return true; // all ok :)
    }

    public function displayConsultWikiplace($wikiplace_name) {
        $tp = new WpPagesTablePager();
        $tp->setWPName($wikiplace_name);
        $tp->setSelectConds(array(
            'wpw_owner_user_id' => $this->getUser()->getID(),
            'homepage.page_title' => $wikiplace_name));
        $tp->setHeader(wfMessage('wp-consult-header', $wikiplace_name)->parse());
        $tp->setFooter(wfMessage('wp-consult-footer', $wikiplace_name)->parse());
        $this->getOutput()->addHTML($tp->getWholeHtml());
    }

    public static function getLinkToMyWikiplaces($i18n_key = 'wikiplaces') {
        return Linker::linkKnown(
                        SpecialPage::getTitleFor(self::TITLE_NAME), wfMessage($i18n_key)->text());
    }

    /**
     * Generate a link to consult a listing of a wikiplace all items.
     * @param String $homepage_title_name should be $homepageTitle->getText()
     * @return string a HTML link
     */
    public static function getLinkConsultWikiplace($homepage_title_name) {
        return Linker::linkKnown(
                        self::getTitleFor(self::TITLE_NAME,self::ACTION_CONSULT_WIKIPLACE.':'.$homepage_title_name), wfMessage('show')->text());
    }

    /**
     * Generate a link to the form for creating a subpage in a wikiplace
     * @param String $homepage_title_name should be $homepageTitle->getText()
     * @return string a HTML link
     */
    public static function getLinkCreateSubpage($homepage_title_name = null, $i18n_key = 'create') {
        $params = array('action' => self::ACTION_CREATE_SUBPAGE);
        if ($homepage_title_name != null) {
            $params['name'] = $homepage_title_name;
        }
        return Linker::linkKnown(
                        self::getTitleFor(self::TITLE_NAME,self::ACTION_CREATE_SUBPAGE.':'.$homepage_title_name), wfMessage($i18n_key)->text());
    }

    public static function getLinkCreateWikiplace($i18n_key = 'create') {
        return Linker::linkKnown(
                        self::getTitleFor(self::TITLE_NAME,self::ACTION_CREATE_WIKIPLACE), wfMessage($i18n_key)->text());
    }

}