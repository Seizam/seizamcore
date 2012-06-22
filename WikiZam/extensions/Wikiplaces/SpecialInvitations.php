<?php

class SpecialInvitations extends SpecialPage {
	
	const TITLE_NAME = 'Invitations';
	
	const ACTION_LIST = 'list';
	const ACTION_CREATE = 'create';
	
	private $action = null;
	private $msgType = null;
    private $msgKey = null;
	
	private $selectedCategory = null;
	
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
		

		$this->displayCreateInvitationForm();
		
		$this->displayInvitationsList();        
		
    }
	
	private function displayCreateInvitationForm() {
		
		$user = $this->getUser();
				
		if ( $user->isAllowed(WP_ADMIN_RIGHT)) {
			
			$inviteForm = $this->getAdminInviteForm();
			
			if ($inviteForm->show()) {
				
				$this->displayInformation( wfMessage('wp-invite-success')->text() );
				$inviteForm->setBlockSubmit(true);
				$inviteForm->displayForm('');

			}
			
		} elseif( WpSubscription::newActiveByUserId($user->getId()) instanceof WpSubscription ) {
			
			$category = WpInvitationCategory::newPublicCategory();
			if ( ! $category instanceof WpInvitationCategory ){
				$this->displayInformation( wfMessage('wp-invite-no')->text() );
				return;
			}
			
			$count = WpInvitation::countMonthlyInvitations($user, $category);
			$limit = $category->getMonthlyLimit();
			
			if ( $count >= $limit ) {
				$this->displayInformation( wfMessage('wp-invite-nomore', $limit)->parse() );
				return;
			}
			
			$inviteForm = $this->getPublicInviteForm();
			if ($inviteForm->show()) {
				
				$this->displayInformation( wfMessage('wp-invite-success')->text() );
				
				if ( ($count+1) < $limit ) {
					// redisplay form if counter not reached
					$inviteForm->displayForm('');
				} else {
					$this->displayInformation( wfMessage('wp-invite-nomore', $limit)->parse() );
				}
			}
			
		} else {
			
			$this->displayInformation( wfMessage('wp-no-active-sub')->parse() );
			
		}
		
	}
	
	public function displayInformation($info) {
		
		$this->getOutput()->addHTML(Html::rawElement('div', array('class' => "informations"), $info));
		
	}
	
	private function displayInvitationsList() {
		
		// display invitiation list of this user
		$user = $this->getUser();
		if ( $user->isAllowed(WP_ADMIN_RIGHT)) {
			$inviteList = new WpInvitationsTablePagerAdmin();
		} else {
			$inviteList = new WpInvitationsTablePager();
		}
		$inviteList->setSelectConds(array(
			'wpi_from_user_id' => $user->getID(),
			// 'wpi_counter > 0',
				));
		$this->getOutput()->addHTML($inviteList->getWholeHtml());
		
	}
	
	
	private function getAdminInviteForm() {
			
		$formDesc = array(
			'Category' => array(
				'type' => 'select',
				'label-message' => 'wp-inv-category-field',
				'help-message' => 'wp-inv-category-help',
				'validation-callback' => array($this, 'validateCategory'),
				'options' => array(),
			),
			'Code' => array(
				'type' => 'text',
				'label-message' => 'wp-inv-code-field',
				'help-message' => 'wp-inv-code-help',
				'default' => WpInvitation::generateCode($this->getUser()->getId()),
				'validation-callback' => array($this, 'validateCode'),
			),
			'Counter' => array(
				'type' => 'text',
				'label-message' => 'wp-inv-counter-field',
				'help-message' => 'wp-inv-counter-help',
				'default' => 1,
				'validation-callback' => array($this, 'validateCounter'),
			),
			'Email' => array(
				'type' => 'text',
				'label-message' => 'wp-inv-email-field',
				'help-message' => 'wp-inv-email-help',
				'validation-callback' => array($this, 'validateEmail'),
			),
		);
		
		$categories = WpInvitationCategory::factoryAllAvailableCategories();
		foreach ( $categories as $category ) {
            $formDesc['Category']['options'][$category->getDescription()] = $category->getId();
		}
		
		$htmlForm = new HTMLFormS($formDesc);
		$htmlForm->setMessagePrefix('wp');
		$htmlForm->setTitle($this->getTitle());
		$htmlForm->setSubmitCallback(array($this, 'processAdminInvite'));
		$htmlForm->setSubmitText(wfMessage('wp-invite')->text());
		
		return $htmlForm;
		
	}
	
	private function getPublicInviteForm() {
			
		$formDesc = array(
			'Email' => array(
				'type' => 'text',
				'label-message' => 'wp-inv-email-field',
				'help-message' => 'wp-inv-email-help',
				'validation-callback' => array($this, 'validateEmail'),
			),
		);
		
		$htmlForm = new HTMLFormS($formDesc);
		$htmlForm->setMessagePrefix('wp');
		$htmlForm->setTitle($this->getTitle());
		$htmlForm->setSubmitCallback(array($this, 'processPublicInvite'));
		$htmlForm->setSubmitText(wfMessage('wp-invite')->text());
		
		return $htmlForm;
		
	}
	

	
	public function processAdminInvite($formData) {
		
		$user = $this->getUser();
		$userId = $user->getId();
		
		$email = null;
		if ($formData['Email']!='') {
			$email = $formData['Email'];
		}
		
		$category = WpInvitationCategory::newFromId($formData['Category']);
		if (! $category instanceof WpInvitationCategory) {
			return wfMessage('sz-internal-error')->text();
		}
		
		$invitation = WpInvitation::create($category->getId(), $userId, $formData['Code'], $email, $formData['Counter']);
		if ( ! $invitation instanceof WpInvitation ){
			return wfMessage('sz-internal-error')->text();
		}
		
		if ($email != null) {
			$invitation->sendCode($user, $email);
		}

		return true; // say: all ok
		
	}
	
	public function processPublicInvite($formData) {
		
		$user = $this->getUser();
		$userId = $user->getId();
		
		$email = null;
		if ($formData['Email']!='') {
			$email = $formData['Email'];
		}
		
		$publicCategory = WpInvitationCategory::newPublicCategory();
		if (! $publicCategory instanceof WpInvitationCategory) {
			return wfMessage('sz-internal-error')->text();
		}
		
		$invitation = WpInvitation::create($publicCategory->getId(), $userId, WpInvitation::generateCode($userId), $email, 1);
		if ($invitation == null){
			return wfMessage('sz-internal-error')->text();
		}
		
		if ($email != null) {
			$invitation->sendCode($user, $email);
		}

		return true; // say: all ok
	}
	
	public function validateCategory($id, $alldata) {
		if (!preg_match('/^[0-9]{1,10}$/', $id)) {
            return 'Error: Invalid Category';
        }
		$category = WpInvitationCategory::newFromId($id);
		if ( ! $category instanceof WpInvitationCategory) {
			return 'Error: Invalid Category';
		}
		$this->selectedCategory = $category;
        return true;
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