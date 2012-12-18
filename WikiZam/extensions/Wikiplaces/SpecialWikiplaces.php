<?php

class SpecialWikiplaces extends SpecialPage {

	const TITLE_NAME = 'WikiPlaces';
	const ACTION_CREATE_WIKIPLACE = 'Create';
	const ACTION_CREATE_SUBPAGE = 'CreatePage';
	const ACTION_LIST_WIKIPLACES = 'List';
	const ACTION_CONSULT_WIKIPLACE = 'Consult';
    const ACTION_SET_BACKGROUND = 'SetBackground';
    const ACTION_LIST_MEMBERS = 'Members';
	const ACTION_ADD_MEMBER = 'AddMember';
	const ACTION_REMOVE_MEMBER = 'RemoveMember';
    
    
    private $action = self::ACTION_LIST_WIKIPLACES;
    private $name = null;
    private $msgType = null;
    private $msgKey = null;
    private $filePageName = null;
	private $user = null;

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

		if (isset($par) && $par != '') {
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
        $this->filePageName = $this->getRequest()->getText('filePageName', null);
		$this->user = $this->getRequest()->getText('user', null);
        
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

            case strtolower(self::ACTION_SET_BACKGROUND):
				$this->displaySetBackground();
				break;
			
			case strtolower(self::ACTION_LIST_MEMBERS):
				$this->displayListMembers();
				break;
			
			case strtolower(self::ACTION_ADD_MEMBER):
				$this->displayAddMember();
				break;
			
			case strtolower(self::ACTION_REMOVE_MEMBER):
				$this->processRemoveMember(); // this action has no display
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
		$diskspace = wfFormatSizeMB(WpPage::countDiskspaceUsageByUser($user_id));
		$pages = wfFormatNumber(WpPage::countPagesOwnedByUser($user_id));
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
			$content = '{{subst:' . $template;
			if ( strlen($license) > 0) {
				$content .= '|nolicense=nolicense' ;
			}
			$content .= '}}';
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
			$talkContent = '{{Subst:'.wfMessage('wp-default-talk')->text().'}}';
			$talk_page = WpPage::createTalk($homepage, $user, $talkContent);

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
				'label-message' => 'wp-parent-wikiplace-field',
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
		
		if (is_null($title)) {
			return wfMessage('wp-invalid-name')->text();
		}

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
			
			$talkContent = '{{Subst:'.wfMessage('wp-default-talk')->text().'}}';
			$talk_page = WpPage::createTalk($subpage, $user, $talkContent);

			if (!( $talk_page instanceof Title )) {
				// wikiplace was created, but, error on talk
				/** @todo show a warning ? */
			}

		}

		return true; // all ok :)
    }

    public function displaySetBackground() {

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
                'section' => 'setbackground-section',
                'help-message' => 'wp-setbackground-wikiplace-help',
                'validation-callback' => array($this, 'validateUserWikiplaceID'),
                'options' => array(),
            ),
            'FilePageName' => array(
                'type' => 'text',
                'label-message' => 'wp-filename-field',
                'section' => 'setbackground-section',
                'help-message' => 'wp-setbackground-filename-help',
                'validation-callback' => array($this, 'validateBackgroundFilePageName'),
                'size' => 60,
                'default' => $this->filePageName,
            )
        );

        foreach ($wikiplaces as $wikiplace) {
            $wpw_name = $wikiplace->getName();
            $formDescriptor['WpId']['options'][$wpw_name] = $wikiplace->getId();
            if ($this->name == $wpw_name) {
                $formDescriptor['WpId']['default'] = $wikiplace->getId();
            }
        }

        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->addHeaderText(wfMessage('wp-setbackground-header')->parse());
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->setTitle($this->getTitle(self::ACTION_SET_BACKGROUND));
        $htmlForm->setSubmitCallback(array($this, 'processSetBackground'));
        $htmlForm->setSubmitText(wfMessage('wp-setbackground-go')->text());
        if ($htmlForm->show()) {
            $this->action = self::ACTION_CONSULT_WIKIPLACE;
            $this->msgKey = 'wp-setbackground-success';
            $this->msgType = 'success';
            $this->display();
            return;
        }
    }

    public function validateBackgroundFilePageName($page_name, $allData) {

        if ($page_name == null) {
            return false;
        }

        $file_title = Title::newFromText($page_name);
        
        if (!$file_title->isKnown()) {
            return wfMessage('filepage-nofile');            
        }

        if (!WpWikiplace::isTitleValidForBackground($file_title)) {
            return wfMessage('wp-invalid-background')->text();
        }

        return true; // all ok
    }

    public function processSetBackground($formData) {

        if (!isset($formData['WpId']) || !isset($formData['FilePageName'])) {
            throw new MWException('Cannot set background, no data.');
        }

        $wikiplace = WpWikiplace::getById(intval($formData['WpId']));
        if (!$wikiplace instanceof WpWikiplace) {
            return wfMessage('wp-invalid-name')->text();
        }

        $this->name = $wikiplace->getName();
        
        global $wgUser;
        $fileTitle = Title::newFromText($formData['FilePageName']);
        $ok = $wikiplace->setBackground($fileTitle, $wgUser);

        return $ok;
    }
	

	/**
	 * Ensures <ul>
	 * <li>the wikiplace exists and</li>
	 * <li>the User executing this instance is the wikiplace owner</li>
	 * </ul>
	 * If WikiPlace is not good, this function displays ACTION_LIST_WIKIPLACES page with error message and returns <code>null</code>.
	 * @param string|int|WpWikiplace $wikiplace The wikiplace name (with spaces or undescores), or id, or instance
	 * @return WpWikiplace|null The WpWikiPlace if OK, or null
	 */
	protected function checkWikiPlace($wikiplace) {
		
		$error = 'internalerror'; // "Internal error", cleared if $wikiplace not null
		
		if ( is_string($wikiplace) ) {
			$title = Title::newFromText($wikiplace);
			if ( ! is_null($title) ) {
				$wikiplace = WpWikiplace::newFromName($title->getDBkey());
			}
			$error = 'wp-invalid-name'; // "This name is invalid.", cleared if $wikiplace not null
		} elseif ( is_int($wikiplace) ) {
			$wikiplace = WpWikiplace::getById($wikiplace);		
		} 
		
		if ( $wikiplace instanceof WpWikiplace) {
			if ( ! $wikiplace->isOwner( $this->getUser()->getId() ) ) {
				$error = 'wp-not-owner'; // "you are not the owner of this wp"
			} else {
				$error = null; // ok :)
			}
		}
		
		if ( !is_null($error) ) {
			$this->action = self::ACTION_LIST_WIKIPLACES;
			$this->msgKey = $error;
			$this->msgType = 'error';
			$this->display();
			return null;
		}
		
		// else : ok :)
		return $wikiplace;		
	}
	

	/**
	 * Populates the "option" key of the table $selectDescriptor, and returns it.
	 * @param array $selectDescriptor HTMLForm select field descriptor
	 * @param null|array $wikiplaces Optional, array of WpWikiplace instances
	 * (if null, uses all wikiplaces owned by the User executing this MediaWiki instance)
	 * @param string|null $selected Optional, the WikiPlace name (the text form 
	 * with spaces, not underscores, see WpWikiPlace->getName()) 
	 * to set as default (if null, no
	 * WikiPlace set as default)
	 */
	protected function populateWikiPlaceSelect($selectDescriptor, $wikiplaces = null, $default = null) {
		if (is_null($wikiplaces) ) {
			$wikiplaces = WpWikiplace::factoryAllOwnedByUserId($this->getUser()->getId());
		}
		foreach ($wikiplaces as $wikiplace) {
			if ( ! $wikiplace instanceof WpWikiplace ) {
				continue; // skip
			}
            $wpw_name = $wikiplace->getName();
            $selectDescriptor['options'][$wpw_name] = $wikiplace->getId();
            if ($default == $wpw_name) {
                $selectDescriptor['default'] = $wikiplace->getId();
            }
        }
		
		return $selectDescriptor;	
	}
	
	public function displayListMembers() {
		
		$wikiplace = $this->checkWikiPlace($this->name);
		if (is_null($wikiplace)) {
			return; // action ACTION_LIST_WIKIPLACES with error message already displayed
		}
		
		$wikiplaceName = $wikiplace->getName();
		$tp = new WpMembersTablePager();
		$tp->setSelectConds(array(
			'wpm_wpw_id' => $wikiplace->getId() ) );
		$tp->setWikiPlace($wikiplace); // used for generating links
		$tp->setHeader(wfMessage('wp-members-list-header', $wikiplaceName)->parse());
		$tp->setFooter(wfMessage('wp-members-list-footer', $wikiplaceName)->parse());
		$this->getOutput()->addHTML($tp->getWholeHtml());

	}
	
	public function displayAddMember() {
		
		// check and normalise WikiPlace name if sets
		if ( !empty($this->name) ) {
			if (is_null($wikiplace = $this->checkWikiPlace($this->name))) {
				return; // action ACTION_LIST_WIKIPLACES with error message already displayed
			}
			$this->name = $wikiplace->getName();
		}
		
		$wikiplaceSelect = $this->populateWikiPlaceSelect(
				array(
			'type' => 'select',
			'label-message' => 'wp-wikiplace-field',
			'section' => 'addmember-section',
			'help-message' => 'wp-setbackground-wikiplace-help',
			'validation-callback' => array($this, 'validateUserWikiplaceID'),
			'options' => array(),
				), null, $this->name);
		
		$usernameText = array(
			'type' => 'text',
			'label-message' => 'username',
			'section' => 'addmember-section',
			'help-message' => 'wp-addmember-username-help',
			'validation-callback' => array($this, 'validateUserName'),
			'size' => 60,
			'default' => '',
		);

		$htmlForm = new HTMLFormS(array(
					'WpId' => $wikiplaceSelect,
					'UserName' => $usernameText,
				));
		$htmlForm->addHeaderText(wfMessage('wp-members-add-header')->parse());
		$htmlForm->setMessagePrefix('wp');
		$htmlForm->setTitle($this->getTitle(self::ACTION_ADD_MEMBER));
		$htmlForm->setSubmitCallback(array($this, 'processAddMember'));
		$htmlForm->setSubmitText(wfMessage('wp-add-member')->text());
		
		if ($htmlForm->show()) {
			$this->action = self::ACTION_LIST_MEMBERS;
            $this->msgKey = 'wp-add-member-success';
            $this->msgType = 'success';
            $this->display();
            return;
		}
	}

	public function validateUserName($user_name, $allData) {

		if ($user_name == null) {
			return false;
		}
		
		$user = User::newFromName($user_name);
        if (!$user || $user->getId() == 0 ) {
			return wfMessage('nosuchusershort', $user_name);
		}

		return true; // ok
	}

	public function processAddMember($formData) {

		if (!isset($formData['WpId']) || !isset($formData['UserName'])) {
			throw new MWException('Cannot add member, no data.');
		}
		
		$wikiplace = WpWikiplace::getById(intval($formData['WpId']));
		$user = User::newFromName($formData['UserName']);
		
		if ( WpMember::IsMember($wikiplace, $user) ) {
			return wfMessage('wp-already-member');
		}
		
		if ( is_null( $member = WpMember::create($wikiplace, $user ) ) ) {
			return wfMessage('internalerror');
		}

		$this->name = $wikiplace->getName();
		return true;
	}

	/**
	 * Uses GET data
	 */
	public function processRemoveMember() {
		
		if (is_null($wikiplace = $this->checkWikiPlace($this->name))) {
			return ;
		}
		
		if ( ( $this->validateUserName($this->user, null) !== true )
				|| ( ! ($user = User::newFromName($this->user)) instanceof User ) // already validated, so always ok
				|| ( ! WpMember::IsMember($wikiplace, $user)) ) {
			$this->action = self::ACTION_LIST_MEMBERS;
            $this->msgKey = 'wp-not-member';
            $this->msgType = 'error';
            $this->display();
            return;
		}
		
		// remove
		
		$this->action = self::ACTION_LIST_MEMBERS;
		$this->msgKey = 'wp-remove-member-success';
		$this->msgType = 'success';
		$this->display();
		return;
	}
	
	public function displayConsultWikiplace() {
		$tp = new WpPagesTablePager();
		$title = Title::newFromText($this->name);
		$titleText = $title->getText();
		$tp->setWPName($titleText);
		$tp->setSelectConds(array(
			'wpw_owner_user_id' => $this->getUser()->getID(),
			'homepage.page_title' => $title->getDBkey()));
		$tp->setHeader(wfMessage('wp-consult-header', $titleText)->parse());
		$tp->setFooter(wfMessage('wp-consult-footer', $titleText)->parse());
		$this->getOutput()->addHTML($tp->getWholeHtml());
	}

	public static function getLinkToMyWikiplaces($i18n_key = 'wikiplaces') {
		return Linker::linkKnown(
						SpecialPage::getTitleFor(self::TITLE_NAME), wfMessage($i18n_key)->text());
	}

	/**
	 * Generate a link to consult a listing of a wikiplace all items.
	 * @param string $homepage_title_name should be $homepageTitle->getText()
	 * @return string a HTML link
	 */
	public static function getLinkConsultWikiplace($homepage_title_name, $displayName = false) {
        $message = $displayName ? wfUnUnderscore($homepage_title_name) : wfMessage('details')->text();
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CONSULT_WIKIPLACE . ':' . $homepage_title_name), $message);
	}

	/**
	 * Generate a link to the form for creating a subpage in a wikiplace
	 * @param String $homepage_title_name should be $homepageTitle->getText()
	 * @return string a HTML link
	 */
	public static function getLinkCreateSubpage($homepage_title_name = null, $i18n_key = 'wp-create-page') {
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CREATE_SUBPAGE . ':' . $homepage_title_name), wfMessage($i18n_key)->text());
	}

	public static function getLinkCreateWikiplace($i18n_key = 'wp-create-wikiplace') {
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CREATE_WIKIPLACE), wfMessage($i18n_key)->text());
	}
    
    /**
     * Get the url to set the $file_name as background for a wikiplace
     * @param string $file_page_name
     * @return Title
     */
    public static function getLocalUrlForSetAsBackground($file_page_name) {
        $title = self::getTitleFor(self::TITLE_NAME);
        return $title->getLocalURL(array(
                    'action' => self::ACTION_SET_BACKGROUND,
                    'filePageName' => $file_page_name));
    }
	
	/**
	 * Generate a link to the form for adding a member to a wikiplace
	 * @param String $wikiplace_name should be $homepageTitle->getText()
	 * @return string a HTML link
	 */
	public static function getLinkAddMember($wikiplace_name = null, $i18n_key = 'wp-add-member') {
		return Linker::linkKnown(self::getTitleFor(self::TITLE_NAME, self::ACTION_ADD_MEMBER . ':' . $wikiplace_name), wfMessage($i18n_key)->text());
	}

	/**
	 * 
	 * @param string $wikiplace_name
	 * @param string $username
	 * @param string $i18n_key
	 * @return string HTML link
	 */
	public static function getLinkRemoveMember($wikiplace_name = null, $user_name = null, $i18n_key = 'wp-remove-member') {
		$title = self::getTitleFor(self::TITLE_NAME);
		return Linker::linkKnown($title, $i18n_key, array(), array(
                    'action' => self::ACTION_REMOVE_MEMBER,
					'name' => $wikiplace_name,
					'user' => $user_name ) );
	}

}