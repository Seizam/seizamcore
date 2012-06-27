<?php

class SpecialPlans extends SpecialPage {
    
    const ACTION_LIST = 'List';
    
    private $action = self::ACTION_LIST;
    private $planName = null;
    private $msgType = null;
    private $msgKey = null;
	
	public function __construct() {
		parent::__construct( 'Plans' );
	}
	
	public function execute( $par ) {
		$this->setHeaders();
        
        $user = $this->getUser();
        
        $output = $this->getOutput();
        
        $request = $this->getRequest();
        
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
        
        // Reading parameter from request
        if (isset($par) & $par != '') {
            $explosion = explode(':', $par);
            if (count($explosion) == 1) {
                $this->action = $explosion[0];
                $this->planName = null;
            } else if (count($explosion) == 2) {
                $this->action = $explosion[0];
                $this->planName = $explosion[1];
            }
        } else {
            $this->action = $request->getText('action', null);
            $this->planName = $request->getText('plan', null);
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

        switch ($this->action) {
            case self::ACTION_LIST:
            default:
                $this->displayList();
                break;
        }
    }
    
    private function displayList() {
        $output = $this->getOutput();
        $tp = new WpPlansTablePager();
        $tp->setSelectConds(array('wpp_start_date < now()', 'wpp_end_date > now()', 'wpp_invitation_only' => 0));
        $tp->setHeader(wfMessage('wp-planslist-header')->parse());
        $output->addHTML($tp->getWholeHtml());
    }
	
}