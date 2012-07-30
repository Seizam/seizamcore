<?php

class SpecialWikiplacesAdmin extends SpecialPage {
	
	const TITLE_NAME = 'WikiplacesAdmin';
    
    
    private $action = null;
	
	public function __construct($request = null) {
		parent::__construct(self::TITLE_NAME, WP_ADMIN_RIGHT);
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
		
		if (isset($par) & $par != '') {
            $this->action = $par;
		} else {
			$this->action = $request->getText('action', null);
		}
        
        $output->addWikiText("== WikiPlace Administration Panel ==");
        
        switch ($this->action) {
            default :
                $output->addWikiText("=== ERROR: Wrong action ===");
                $output->addWikiText("====Available actions:====");
                $output->addWikiText("None (see perhaps TransactionsAdmin)");
                break;
		}
		
	}
	
	
}