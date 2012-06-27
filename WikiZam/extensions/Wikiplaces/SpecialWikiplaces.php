<?php

class SpecialWikiplaces extends SpecialPage {

	const TITLE_NAME = 'Wikiplaces';
	const ACTION_CREATE_WIKIPLACE = 'Create';
	const ACTION_CREATE_SUBPAGE = 'CreatePage';
	const ACTION_LIST_WIKIPLACES = 'List';
	const ACTION_CONSULT_WIKIPLACE = 'Consult';
    
    

    private $action = self::ACTION_LIST_WIKIPLACES;
    private $name = null;
    private $msgType = null;
    private $msgKey = null;

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
        
        $request = $this->getRequest();

		$output = $this->getOutput();

		// Check rights
		if (!$this->userCanExecute($user)) {
			// If anon, redirect to login
			if ($user->isAnon()) {
				$output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto' => $this->getFullTitle())), '401');
				return;
			}
			// Else display an error page.
			$this->displayRestrictionError();
			return;
		}

		if (isset($par) & $par != '') {
			$explosion = explode(':', $par);
			if (count($explosion) == 1) {
				$this->action = $explosion[0];
                $this->name = $this->getRequest()->getText('name', null);
			} else if (count($explosion) == 2) {
				$this->action = $explosion[0];
				$this->name = $explosion[1];
			}
		} else {
			$this->action = $this->getRequest()->getText('action', null);
			$this->name = $this->getRequest()->getText('name', null);
		}
        $this->msgType = $request->getText('msgtype', $this->msgType);
        $this->msgKey = $request->getText('msgkey', $this->msgKey);
        
        $this->display();
		
	}
    
    private function display() {
        $output = $this->getOutput();

        // Top Infobox Messaging
        if ($this->msgType != null) {
            $msg = wfMessage($this->msgKey);
            if ($msg->exists()) {
                $output->addHTML(Html::rawElement('div', array('class' => "informations $this->msgType"), $msg->parse()));
            }
        }

        switch (strtolower($this->action)) {

			case strtolower(self::ACTION_CONSULT_WIKIPLACE):
				if ($this->name != null) {
					$this->displayConsultWikiplace();
				} else {
					$this->displayList();
				}
				break;

			case strtolower(self::ACTION_CREATE_SUBPAGE):
				$this->displayCreateSubpage();
				break;

			case strtolower(self::ACTION_CREATE_WIKIPLACE):
				$this->displayCreateWikiplace();
				break;

			case strtolower(self::ACTION_LIST_WIKIPLACES):
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
            $this->action = self::ACTION_LIST_WIKIPLACES;
            $this->msgKey = $reason;
            $this->msgType = 'error';
            $this->display();
            return;
            return;
		}

		$formDescriptor = array(
			'Name' => array(
				'type' => 'text',
				'label-message' => 'wp-name-field',
				'section' => 'create-section',
				'help-message' => 'wp-create-name-help',
				'validation-callback' => array($this, 'validateNewWikiplaceName'),
			),
			'Template' => array(
				'label-message' => 'wp-template-field',
				'section' => 'create-section',
				'help-message' => 'wp-create-template-help',
				'class' => 'WpHomepageTemplate',
			),
			'License' => array(
				'label-message' => 'wp-license-field',
				'section' => 'create-section',
				'help-message' => 'wp-create-license-help',
				'class' => 'Licenses',
			),
			'CreateTalk' => array(
				'type' => 'check',
				'label-message' => 'wp-createtalk-field',
				'section' => 'create-section',
				'help-message' => 'wp-create-createtalk-help',
				'default' => true,
			),
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

	/**
	 * Returns the WikiText page content, build using $template, with $license.
	 * @param string $template Template name, which will be substituted ( {{subst:xxx}} )
	 * @param string $license Template name, which will be transcluded with internationalisation support ( {{int:xxx}} )
	 * @return string 
	 */
	private static function preparePageContent($template = '', $license = '') {
		$content = '';
		if ( strlen($template) > 0) {
			$content = '{{subst:' . $template . '}}';
		}
		if ( strlen($license) > 0) {
			$content .= "\n" . '{{' . $license . '}}';
		}
		return $content;
	}
	
	public function processCreateWikiplace($formData) {

		if (!isset($formData['Name'])) { //check the key exists and value is not NULL
			throw new MWException('Cannot create Wikiplace, no data.');
		}
		
		$user = $this->getUser();
		$content = self::preparePageContent( $formData['Template'], $formData['License']);

		$homepage = WpWikiplace::initiateCreation($formData['Name'], $user, $content);
		if (!( $homepage instanceof Title )) {
			$key = array_shift($homepage);
			return wfMessage($key, $homepage)->parse(); // error while creating
		}

		$homepage_dbkey = $homepage->getDBkey();
		$this->homepageString = $homepage_dbkey;
		
		if ( $formData['CreateTalk'] === true) {
			// The wikiplace was created by a hook and is not accessible from here, so we need to get the wikiplace this way
			
			$talk_page = WpPage::createTalk($homepage, $user, '{{Subst:Default_Talk}}');

			if (!( $talk_page instanceof Title )) {
				// wikiplace was created, but, error on talk
				/** @todo show a warning ? */
			} 

		}

		return true; // say: all ok
	}

	public function displayCreateSubpage() {

		if (( $reason = WpSubscription::userCanCreateNewPage($this->getUser()->getId())) !== true) {
            $this->action = self::ACTION_CONSULT_WIKIPLACE;
            $this->msgKey = $reason;
            $this->msgType = 'error';
            $this->display();
            return;
		}

		$wikiplaces = WpWikiplace::factoryAllOwnedByUserId($this->getUser()->getId());
		if (count($wikiplaces) == 0) {
            $this->action = self::ACTION_LIST_WIKIPLACES;
            $this->msgKey = 'wp-create-wp-first';
            $this->msgType = 'error';
            $this->display();
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
			'Template' => array(
				'label-message' => 'wp-template-field',
				'section' => 'createpage-section',
				'help-message' => 'wp-createpage-template-help',
				'class' => 'WpSubpageTemplate',
			),
			'License' => array(
				'label-message' => 'wp-license-field',
				'section' => 'createpage-section',
				'help-message' => 'wp-createpage-license-help',
				'class' => 'Licenses',
			),
			'CreateTalk' => array(
				'class' => 'HTMLCheckField',
				'label-message' => 'wp-createtalk-field',
				'section' => 'createpage-section',
				'help-message' => 'wp-createpage-createtalk-help',
				'default' => true,
			),
			
		);
        
        $name = null;
		if ($this->name != null) {
			$name = Title::newFromDBkey($this->name)->getText();
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
		if ( ! $wikiplace instanceof WpWikiplace ) {
			return wfMessage('wp-invalid-name')->text();
		}
		
		$content = self::preparePageContent( $formData['Template'], $formData['License']);
		$user = $this->getUser();
		
		$subpage = WpPage::createSubpage($wikiplace, $formData['SpName'], $user, $content);
		if (!( $subpage instanceof Title )) {
			$key = array_shift($subpage);
			return wfMessage($key, $subpage)->parse();
		}

		$this->homepageString = $wikiplace->getName();
		$this->subpageString = $formData['SpName'];
		
		if ( $formData['CreateTalk'] === true) {
			// The wikiplace was created by a hook and is not accessible from here, so we need to get the wikiplace this way
			
			$talk_page = WpPage::createTalk($subpage, $user, '{{Subst:Default_Talk}}');

			if (!( $talk_page instanceof Title )) {
				// wikiplace was created, but, error on talk
				/** @todo show a warning ? */
			}

		}

		return true; // all ok :)
	}

	public function displayConsultWikiplace() {
		$tp = new WpPagesTablePager();
		$tp->setWPName($this->name);
		$tp->setSelectConds(array(
			'wpw_owner_user_id' => $this->getUser()->getID(),
			'homepage.page_title' => $this->name));
		$tp->setHeader(wfMessage('wp-consult-header', $this->name)->parse());
		$tp->setFooter(wfMessage('wp-consult-footer', $this->name)->parse());
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
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CONSULT_WIKIPLACE . ':' . $homepage_title_name), wfMessage('show')->text());
	}

	/**
	 * Generate a link to the form for creating a subpage in a wikiplace
	 * @param String $homepage_title_name should be $homepageTitle->getText()
	 * @return string a HTML link
	 */
	public static function getLinkCreateSubpage($homepage_title_name = null, $i18n_key = 'create') {
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CREATE_SUBPAGE . ':' . $homepage_title_name), wfMessage($i18n_key)->text());
	}

	public static function getLinkCreateWikiplace($i18n_key = 'create') {
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CREATE_WIKIPLACE), wfMessage($i18n_key)->text());
	}

	/*
	  public static function getWikiplaceTemplates() {
	  return self::getTitleLinkedFromTitle( wfMessage( 'wp-templates-for-homepage' )->plain() );
	  }

	  public static function getSubpageTemplates() {
	  return self::getTitleLinkedFromTitle( wfMessage( 'wp-templates-for-subpage') ->plain() );
	  }
	 */

	/**
	 *
	 * @return array Array of titles 
	 *//*
	  private static function getTitleLinkedFromTitle( $title ) {

	  // code found from SpecialDisambiguations.php

	  $dbr = wfGetDB( DB_SLAVE );

	  // Get the template list page for the current user language
	  $templates_list = Title::newFromText( $title );

	  // Don't give fatal errors if the message is broken
	  if ( ! $templates_list instanceof Title ) {
	  return array(); // no templates list
	  }

	  $res = $dbr->select(
	  array('pagelinks'),
	  array('pl_title', 'pl_namespace'),
	  array('pl_from' => $templates_list->getArticleID()),
	  __METHOD__ );

	  $back = array();

	  foreach ( $res as $row ) {
	  $back[] = Title::makeTitle( $row->pl_namespace, $row->pl_title );
	  }

	  return $back;
	  } */
}