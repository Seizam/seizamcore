<?php

class SpecialInvitation extends SpecialSubscriptions {
	
	const ACTION_USE_INVITATION_SHORT = 'Use';
	
	public function __construct() {
        parent::__construct('Invitation');
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
                $output->addHTML(Html::rawElement('div', array('class' => "informations"), wfMessage('wp-use-inv-notloggedin')->parse()));
                return;
            }

            // Else display an error page.
            $this->displayRestrictionError();
            return;
        }
		
		$check = WpSubscription::canSubscribe($this->getUser());
		if ($check !== true) {
			$output->redirect($this->getTitleFor('Invitations')->getLocalURL(array('msgkey' => $check,'msgtype'=>'error')), '302');
			//$output->addHTML(Html::rawElement('div', array('class' => "informations error"), wfMessage($check)->parse()));
			return;
		}
		
        // Reading parameter from request
        if (isset($par) & $par != '') {
            $explosion = explode(':', $par);
			$arg = null;
            if (count($explosion) == 1) {
                $this->action = $explosion[0];
                $arg = $request->getText('plan', $request->getText('invitation', null) );
            } else if (count($explosion) == 2) {
                $this->action = $explosion[0];
                $arg = $explosion[1];
            }
			if ( ($this->action == self::ACTION_USE_INVITATION_SHORT) || ($this->action == self::ACTION_USE_INVITATION) ) {
				$this->invitationCode = $arg;
			} else {
				$this->planName = $arg;
			}
        } else {
            $this->action = $request->getText('action', null);
			if ( ($this->action == self::ACTION_USE_INVITATION_SHORT) || ($this->action == self::ACTION_USE_INVITATION) ) {
				$this->invitationCode = $request->getText('invitation', null);
			} else {
				$this->planName = $request->getText('plan', null);
			}
        }
		
        $this->msgType = $request->getText('msgtype', $this->msgType);
        $this->msgKey = $request->getText('msgkey', $this->msgKey);

        $this->display();
    }
		
    protected function display() {
        $output = $this->getOutput();

        // Top Infobox Messaging
        if ($this->msgType != null) {
            $msg = wfMessage($this->msgKey);
            if ($msg->exists()) {
                $output->addHTML(Html::rawElement('div', array('class' => "informations $this->msgType"), $msg->parse()));
            }
        }

        switch ($this->action) {
			
            case self::ACTION_NEW :
                $this->displayNew();
                break;
            case self::ACTION_CHANGE:
                $this->displayChange();
                break;
            case self::ACTION_RENEW:
                $this->displayRenew();
                break;
            case self::ACTION_LIST:
                $this->displayList();
                break;
			case self::ACTION_USE_INVITATION_SHORT :
			case self::ACTION_USE_INVITATION :
			default:
				$this->displayInvitation();
				//processOrDisplayInvitation();
                break;
			
        }  
		
    }

}