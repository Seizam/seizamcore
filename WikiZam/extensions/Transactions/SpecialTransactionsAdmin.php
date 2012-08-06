<?php

class SpecialTransactionsAdmin extends SpecialPage {
	
	const TITLE_NAME = 'TransactionsAdmin';
	
	const ACTION_CREDIT = 'Credit';
    
    
    private $action = null;
	
	public function __construct($request = null) {
		parent::__construct(self::TITLE_NAME, TM_ADMIN_RIGHT);
	}
	
	public function execute( $par ) {
		
		$this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title
		$output = $this->getOutput();
        
		$user = $this->getUser();
		
		if (!$this->userCanExecute($user)) {
            $this->displayRestrictionError();
            return;
        }
        
        $request = $this->getRequest();
		
		if (isset($par) && $par != '') {
            $this->action = $par;
		} else {
			$this->action = $request->getText('action', null);
		}
        
        $output->addWikiText("== Transaction Administration Panel ==");
        
        switch ($this->action) {
			case self::ACTION_CREDIT :
				$this->credit($request->getText('name',null), $request->getInt('amount', 0));
				break;
            default :
                $output->addWikiText("=== ERROR: Wrong action ===");
                $output->addWikiText("====Available actions:====");
                $output->addWikiText("Credit(string '''name''', int '''amount''')");
                break;
		}
		
	}
	
	/**
	 * FOR TEST ONLY: give $amount EUR in user account balance
	 */
	private function credit($name = null, $amount = 0) {
        $output = $this->getOutput();
        
        $output->addWikiText("=== Credit (name, amount) ===");
        $output->addWikiText("name = $name");
        $output->addWikiText("amount = $amount");
        
		$user = User::newFromName($name);
        if (!$user || $user->getId() == 0 ) {
            $output->addWikiText("=== ERROR: Invalid UserName ===");
            return;
        }
        
        $output->addWikiText("=== User ===");
        $output->addWikiText("user_id = ".$user->getId());
        $output->addWikiText("user_name = ".$user->getName());
        $output->addWikiText("user_realname = ".$user->getRealName());
        $output->addWikiText("user_email = ".$user->getEmail());
        
        $output->addWikiText("=== Transaction ===");
        $output->addWikiText("True balance before = ".TMRecord::getTrueBalanceFromDB($user->getId()));
        
        if (!is_int($amount) || $amount <= 0 || $amount > 1000 ) {
            $output->addWikiText("=== ERROR: Invalid Amount ===");
            return;
        }
        
            
		$tmr = array(
			# Params related to Message
			'tmr_type' => TM_REFUND_TYPE, # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
			# Paramas related to User
			'tmr_user_id' => $user->getId(), # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
			'tmr_mail' => $user->getEmail(), # tinyblob COMMENT 'User''s Mail',
			'tmr_ip' => IP::sanitizeIP(wfGetIP()), # tinyblob COMMENT 'User''s IP'
			# Params related to Record
			'tmr_amount' => $amount, # decimal(9,2) NOT NULL COMMENT 'Record Amount',
			'tmr_currency' => 'EUR', # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
			'tmr_desc' => 'tm-refund', # varchar(64) NOT NULL COMMENT 'Record Description',
			'tmr_status' => 'OK' # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding, TEst)',
		);
		wfRunHooks('CreateTransaction', array(&$tmr));
        $output->addWikiText("==== DONE ====");
        $output->addWikiText("True balance after = ".TMRecord::getTrueBalanceFromDB($user->getId()));
		$output->addWikiText("== SUCCESS ==");
		
	}
	
	
	
}