<?php

class SpecialInvitations extends SpecialPage {
	
	const TITLE_NAME = 'Invitations';
	
	const ACTION_LIST = 'list';
	const ACTION_CREATE = 'create';
	
	private $action = null;
	private $msgType = null;
    private $msg = null;
	
	private $selectedCategory = null;
	
	private $creationErrorAlreadydisplayed = false;
	
	private $userInvitationsCategories = null;
	private $userInvitationsCount = null;
	private $userIsAdmin = false;
	private $userActiveSubscription = null;
	
	private $sentTo = null;
	
    public function __construct() {
        parent::__construct(self::TITLE_NAME, WP_ACCESS_RIGHT);
    }
	
	
	public function execute($par) {

        $this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title

        $user = $this->getUser();

        $output = $this->getOutput();

        $request = $this->getRequest();

        // Check rights and block if necessary
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
		
		$this->action = $request->getText('action', $par);
		
		if ( $user->isAllowed(WP_ADMIN_RIGHT) ) {
			$this->userIsAdmin = true;
			$this->userInvitationsCategories = WpInvitationCategory::factoryAllAvailable();
		} else {
			$this->userIsAdmin = false;
			$this->userActiveSubscription = WpSubscription::newActiveByUserId($user->getId());
			$this->userInvitationsCategories = WpInvitationCategory::factoryPublicAvailable();
			$this->userInvitationsCount = WpInvitation::countMonthlyInvitations($user);
		}
		
		$this->creationErrorAlreadydisplayed = false;
		
		$this->msgType = $request->getText('msgtype', null);
        $msgKey = $request->getText('msgkey',null);
		if ( $msgKey != null ) {
			$this->msg = wfMessage($msgKey);
		}
		
	    $this->display();
    }

    private function display() {
		
        $output = $this->getOutput();

        // Display requested message.
		if ($this->msg instanceof Message) {
			$output->addHTML(Html::rawElement(
					'div',
					array('class' => "informations ".(($this->msgType != null)?$this->msgType:'')),
					$this->msg->parse()));
		}
		
		// dispatch
		switch ($this->action) {
			case self::ACTION_CREATE:
				$this->displayCreate();
				break;
			case self::ACTION_LIST:
			default:
				$this->displayList();
				break;
		}	    
		
    }
	
	/**
	 *
	 * @return Message The i18n error message, or true if no error
	 */
	private function getCanCreateError() {
		
		if (  ( ! $this->userIsAdmin ) && ( ! $this->userActiveSubscription instanceof WpSubscription ) ) {
			
			return wfMessage('wp-inv-nosub');
			
		} elseif ( empty($this->userInvitationsCategories) ) {
			
			return wfMessage('wp-inv-no');
			
		} elseif ( ! $this->userIsAdmin ) {
		
			$availableCategories = $this->filterCategories($this->userInvitationsCategories, $this->userInvitationsCount);

			if ( empty($availableCategories)) {
				return wfMessage('wp-inv-limitreached');
			}
			
		}
		
		return true;
		
	}
	
	private function displayCreate() {
		
		$error = $this->getCanCreateError();
		if ( $error instanceof Message ) {
			$this->msgType = 'error';
			$this->msg = $error;
			$this->creationErrorAlreadydisplayed = true; //ensure that LIST action won't talk about this error again
			$this->action = self::ACTION_LIST;
			$this->display();
			return;
		}
		
		$inviteForm = $this->getInviteForm();		
		if ($inviteForm->show()) {

			$this->msgType = 'succes';
			if ($this->sentTo != null) {
				$this->msg = wfMessage('wp-inv-success-sent', $this->sentTo);
			} else {
				$this->msg = wfMessage('wp-inv-success');
			}

			$this->action = self::ACTION_LIST;
			$this->display();
			return;

		}
		
	}
	
	/**
	 *
	 * @param array $allCategories Array: categoryId => Category
	 * @param array $userCounter Array: categoryId => monthly counter
	 * @return array Filtered categories array: categoryId => Category
	 */
	private function filterCategories($allCategories, $userCounter) {
		$availableCategories = $allCategories;
			
			foreach ($userCounter as $categoryId => $count ) {
				if ( (array_key_exists($categoryId, $allCategories))
						&& ($count >= $allCategories[$categoryId]->getMonthlyLimit()) ) {
					unset($availableCategories[$categoryId]); // this category will is no more available for this user
				}
			}
			
		return $availableCategories;
	}
	
	private function displayList() {
		
		// display invitiation list of this user
		if ( $this->userIsAdmin ) {
			$inviteList = new WpInvitationsTablePagerAdmin();
		} else {
			$inviteList = new WpInvitationsTablePager();
		}
		
		if ( $this->creationErrorAlreadydisplayed ) {
			$inviteList->setHeader(wfMessage('wp-inv-see-below')->text());
		} else {
			$createMessage = $this->getCanCreateError();
			if ( ! $createMessage instanceof Message ) {
				$createMessage = wfMessage('wp-inv-create');
			}
			$inviteList->setHeader(wfMessage('wp-inv-list-header', $createMessage->parse() )->text());
		}
		
		$inviteList->setSelectConds(array(
			'wpi_from_user_id' => $this->getUser()->getID(),
			// 'wpi_counter > 0',
				));
				
		$this->getOutput()->addHTML($inviteList->getWholeHtml());
		
	}
	
	/**
	 *
	 * @return \HTMLFormS 
	 */
	private function getInviteForm() {
			
		$formDesc = array(
			'Category' => array(
				'type' => 'select',
				'label-message' => 'wp-inv-category-field',
                'section' => 'create-section',
				'help-message' => 'wp-inv-category-help',
				'options' => array(),
			),
			'Email' => array(
				'type' => 'text',
				'label-message' => 'emailto',
                'section' => 'mail-section',
				'help-message' => 'wp-inv-email-help',
				'validation-callback' => array($this, 'validateEmail'),
			),
			'Message' => array(
                'type' => 'textarea',
                'label-message' => 'emailmessage',
                'section' => 'mail-section',
				'help-message' => 'wp-inv-msg-help',
                'default' => wfMessage('wp-inv-msg-default')->text(),
            ),
			'Language' => array (
				'type' => 'select',
				'label-message' => 'wp-inv-language-field',
                'section' => 'mail-section',
				'help-message' => 'wp-inv-language-help',
				'options' => array(),
			)
		);
		
		global $wgLanguageSelectorLanguagesShorthand;
        $language = $this->getLanguage();
		foreach ($wgLanguageSelectorLanguagesShorthand as $ln) {
			$formDesc['Language']['options'][$language->getLanguageName($ln)] = $ln;
			if ($ln == $language->getCode()) {
				$formDesc['Language']['default'] = $ln;
			}
		}
		
		if ($this->userIsAdmin) {
			
			$formDesc['Code'] = array(
				'type' => 'text',
				'label-message' => 'wp-inv-code-field',
				'help-message' => 'wp-inv-code-help',
				'default' => WpInvitation::generateCode($this->getUser()),
				'validation-callback' => array($this, 'validateCode'),
			);
			$formDesc['Counter'] = array(
				'type' => 'text',
				'label-message' => 'wp-inv-counter-field',
				'help-message' => 'wp-inv-counter-help',
				'default' => 1,
				'validation-callback' => array($this, 'validateCounter'),
			);
			foreach ( $this->userInvitationsCategories as $category ) {
				$formDesc['Category']['options'][$category->getDescription()] = $category->getId();
			}
			
		} else {
			
			$categories = $this->filterCategories($this->userInvitationsCategories, $this->userInvitationsCount);
			
			foreach ( $categories as $category ) {
				$formDesc['Category']['options'][wfMessage(
						'wp-inv-category-desc',
						wfMessage($category->getDescription())->text(),
						$category->getMonthlyLimit())->parse()] = $category->getId();
			}
		}
		
        
		$htmlForm = new HTMLFormS($formDesc);
		$htmlForm->setMessagePrefix('wp-inv');
		$htmlForm->setTitle($this->getTitle(self::ACTION_CREATE));
		$htmlForm->setSubmitText(wfMessage('wp-inv-go')->text());
		
		$htmlForm->setSubmitCallback(array($this, 'processInvite'));
		
		return $htmlForm;
		
	}
	
	public function processInvite($formData) {
		
		$user = $this->getUser();
		
		$category = $this->userInvitationsCategories[$formData['Category']];
		if (! $category instanceof WpInvitationCategory) {
			return wfMessage('sz-internal-error')->text();
		}
		
		$email = null;
		if ($formData['Email']!='') {
			$email = $formData['Email'];
		}
		
		$message = $formData['Message'];
		
		if ( $this->userIsAdmin ) {
			$code = $formData['Code'];
			$counter = $formData['Counter'];
			
		} else {
			$code = WpInvitation::generateCode($user);
			$counter = 1;
		}
		
		$language = $formData['Language'];
		
		$invitation = WpInvitation::create($category->getId(), $user, $code, $email, $counter);
		if ( ! $invitation instanceof WpInvitation ){
			return wfMessage('sz-internal-error')->text();
		}
		
		if ( ! $this->userIsAdmin ) {
			if ( isset($this->userInvitationsCount[$category->getId()]) ) {
				$this->userInvitationsCount[$category->getId()] ++;
			} else {
				$this->userInvitationsCount[$category->getId()] = 1;
			}
		}
		
		
		if ( ($email != null) && ( $invitation->sendCode($user, $email, $message, $language) ) ) {
			$this->sentTo = $email;
		} else {
			$this->sentTo = null;
		}

		return true; // say: all ok
		
	}
	
	public function validateCode($code, $alldata) {
		if (!preg_match('/^[0-9A-Z]+$/', $code)) {
			return 'Error: Code should be alphanumeric uppercased';
		}
		$invitation = WpInvitation::newFromCode($code);
		if ( $invitation instanceof WpInvitation ) {
			return 'Error: This code already exists.';
		}
        return true;
    }
	
	public function validateCounter($counter, $alldata) {
		if ( ($counter!='-1') && (!preg_match('/^([1-9])([0-9]{0,9})$/', $counter)) ) {
            return 'Error: Must be -1 or integer > 0';
        }
        return true;
    }
	
	
	public function validateEmail($email, $alldata) {
        if ($email && !Sanitizer::validateEmail($email)) {
            return wfMsgExt('invalidemailaddress', 'parseinline');
        } 
        return true;
    }
	
}