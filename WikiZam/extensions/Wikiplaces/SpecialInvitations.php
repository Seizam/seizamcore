<?php

class SpecialInvitations extends SpecialPage {
	
	const TITLE_NAME = 'Invitations';
	
	const ACTION_LIST = 'list';
	const ACTION_CREATE = 'create';
	
	private $action = null;
	private $msgType = null;
    private $msgKey = null;
	
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
		$output = $this->getOutput();
				
		if ( $user->isAllowed(WP_ADMIN_RIGHT)) {
			
			$inviteForm = $this->getAdminInviteForm();
			
			if ($inviteForm->show()) {
				
				$this->displayInformation( wfMessage('wp-invite-success')->text() );
				$inviteForm->displayForm('');

			}
			
		} else {
			
			$category = WpInvitationCategory::newPublicCategory();
			if ($category==null){
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
		}
		
	}
	
	public function displayInformation($info) {
		$this->getOutput()->addHTML(Html::rawElement('div', array('class' => "informations"), $info));
	}
	
	private function displayInvitationsList() {
		
		// display invitiation list of this user
		$inviteList = new WpInvitationsTablePager();
		$inviteList->setSelectConds(array(
			'wpi_from_user_id' => $this->getUser()->getID(),
			'wpi_counter > 0',
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
		
		$categories = WpInvitationCategory::factoryAdminCategories();
		foreach ( $categories as $category ) {
            $formDescriptor['Category']['options'][$category->getDescription()] = $category->getId();
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
		
		$email = null;
		if ($formData['Email']!='') {
			$email = $formData['Email'];
		}
		
		//WpInvitation::create($invitationCategoryId, $fromUserId, $toEmail)

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
		if ($publicCategory == null) {
			return wfMessage('sz-internal-error')->text();
		}
		
		$invitation = WpInvitation::create($publicCategory->getId(), $userId, WpInvitation::generateCode($userId), $email, 1);
		if ($invitation == null){
			return wfMessage('sz-internal-error')->text();
		}

		return true; // say: all ok
	}
	
	public function validateCategory($id, $alldata) {
		if (!preg_match('/^[0-9]{1,10}$/', $id)) {
            return 'Error: Invalid Category ID';
        }
        return true;
    }
	
	public function validateCode($code, $alldata) {
		if (!preg_match('/^[0-9A-Z]+$/', $code)) {
			return 'Error: Code should be alphanumeric uppercased';
		}
        return true;
    }
	
	public function validateCounter($counter, $alldata) {
		if (!preg_match('/^([1-9])([0-9]{0,9})$/', $counter)) {
            return 'Error: Counter must be numeric > 0';
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