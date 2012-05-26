<?php

class SpecialWikiplaces extends SpecialPage {
    const TITLE_NAME = 'Wikiplaces';

    const ACTION_CREATE_WIKIPLACE = 'create';
    const ACTION_CREATE_SUBPAGE = 'create_page';
    const ACTION_LIST_WIKIPLACES = 'list';
    const ACTION_CONSULT_WIKIPLACE = 'consult';

    private $title_just_created;

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

        $name = $this->getRequest()->getText('name', null);
        switch (strtolower($this->getRequest()->getText('action', $par))) {

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

        $diskspace = WpPage::countDiskspaceUsageByUser($user_id);
        $output->addHTML(wfMessage(
                        'wp-your-total-diskspace', ($diskspace < 1) ? '< ' . wgformatSizeMB(1) : wgformatSizeMB($diskspace) )->text());

        $tp = new WpWikiplacesTablePager();
        $tp->setSelectConds(array('wpw_owner_user_id' => $user_id));
        $tp->setHeader(wfMessage('wp-list-header')->parse());
        $tp->setFooter(wfMessage('wp-list-footer')->parse());
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
                            'wp-create-wp-success', Linker::linkKnown($this->title_just_created))->text());
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

        $this->title_just_created = $homepage;

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
                'label-message' => 'wp-select-wp',
                'validation-callback' => array($this, 'validateUserWikiplaceID'),
                'options' => array(),
            ),
            'SpName' => array(
                'type' => 'text',
                'label-message' => 'wp-enter-new-sp-name',
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
        $htmlForm->setTitle($this->getTitle(self::ACTION_CREATE_SUBPAGE));
        $htmlForm->setSubmitCallback(array($this, 'processCreateSubpage'));
        $htmlForm->setSubmitText(wfMessage('wp-create-sp-go')->text());
        if ($htmlForm->show()) {
            $this->getOutput()->addHTML(wfMessage(
                            'wp-create-sp-success', Linker::linkKnown($this->title_just_created))->text());
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

        $this->title_just_created = $subpage;

        return true; // all ok :)
    }

    public function displayConsultWikiplace($wikiplace_name) {

        $tp = new WpPagesTablePager();
        $tp->setWPName($wikiplace_name);
        $tp->setSelectConds(array(
            'wpw_owner_user_id' => $this->getUser()->getID(),
            'homepage.page_title' => $wikiplace_name));
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
                        self::getTitleFor(self::TITLE_NAME), wfMessage('show')->text(), array(), array('name' => $homepage_title_name, 'action' => SpecialWikiplaces::ACTION_CONSULT_WIKIPLACE));
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
                        self::getTitleFor(self::TITLE_NAME), wfMessage($i18n_key)->text(), array(), $params);
    }

    public static function getLinkCreateWikiplace($i18n_key = 'create') {
        return Linker::linkKnown(
                        self::getTitleFor(self::TITLE_NAME), wfMessage($i18n_key)->text(), array(), array('action' => self::ACTION_CREATE_WIKIPLACE));
    }

}