<?php

class SpecialWikiplacesAdmin extends SpecialPage {
	
	const TITLE_NAME = 'WikiplacesAdmin';
	
	const ACTION_10EUR = '10eur';
	const ACTION_CLEAR = 'clear';
	
	public function __construct() {
		parent::__construct( self::TITLE_NAME );
	}
	
	/**
	 * Checks if the given user can perform this action. 
	 *
	 * @param $user User: the user to check, or null to use the context user
	 * @return Bool true
	 * @throws ErrorPageError
	 */
	public function userCanExecute( User $user ) {
		if ( wfReadOnly() ) {
			throw new ReadOnlyError();
		}

		if ( !$user->isAllowed( WP_ADMIN_RIGHT ) ) {
			throw new PermissionsError( WP_ADMIN_RIGHT );
		}

		if ( $user->isBlocked() ) {
			$block = $user->mBlock;
			throw new UserBlockedError( $block );
		}

		return true;
	}
	
	public function execute( $par ) {
		
		$this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title
		
		$user = $this->getUser();
		
		if ( ! $user->isLoggedIn() ) {
			$this->getOutput()->showErrorPage( self::TITLE_NAME, 'wp-nologintext', array( $this->getTitle()->getPrefixedDBkey() ) );
			return;
		}
		
		// This will throw exceptions if there's a problem
		$this->userCanExecute( $this->getUser() );
			
		/** @todo: replace this header with something nicer */
		$this->getOutput()->setSubtitle(Html::rawElement('span', array(), $this->getLang()->pipeList(array(
				Linker::linkKnown($this->getTitle(self::ACTION_10EUR), 'give me 10 EUR'),
				Linker::linkKnown($this->getTitle(self::ACTION_CLEAR), 'clear'),
				Linker::linkKnown(SpecialPage::getTitleFor('Wikiplaces')),
				Linker::linkKnown(SpecialPage::getTitleFor('Subscriptions')),
				Linker::linkKnown(SpecialPage::getTitleFor('Offers')),
				Linker::linkKnown(SpecialPage::getTitleFor('Transactions'))
			))));
		
		// dispatch
		$action = strtolower( $this->getRequest()->getText( 'action', $par ) );
        switch ($action) { 
			case self::ACTION_10EUR :
				$this->action10Eur();
				break;
			case self::ACTION_CLEAR :
				$this->actionClear();
				break;			
		}
		
	}
	
	/**
	 * FOR TEST ONLY: give 10 EUR in user account balance
	 */
	private function action10Eur() {
		$user = $this->getUser();
		$tmr = array(
			# Params related to Message
			'tmr_type' => 'PAYTEST', # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
			# Paramas related to User
			'tmr_user_id' => $user->getId(), # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
			'tmr_mail' => $user->getEmail(), # tinyblob COMMENT 'User''s Mail',
			'tmr_ip' => IP::sanitizeIP(wfGetIP()), # tinyblob COMMENT 'User''s IP'
			# Params related to Record
			'tmr_amount' => 10, # decimal(9,2) NOT NULL COMMENT 'Record Amount',
			'tmr_currency' => 'EUR', # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
			'tmr_desc' => 'wp-transaction-test-add-10', # varchar(64) NOT NULL COMMENT 'Record Description',
			'tmr_status' => 'OK' # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding, TEst)',
		);
		wfRunHooks('CreateTransaction', array(&$tmr));
		$this->getOutput()->addHTML("10 EUR given to " . $user->getName());
		
	}
	
	/**
	 * FOR TEST ONLY: clear all extension content + all transactions
	 */
	private function actionClear() {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->query("TRUNCATE tm_record");
		$dbw->query("TRUNCATE wp_subscription");
		$dbw->query("TRUNCATE wp_old_subscription");
		$dbw->query("TRUNCATE wp_old_usage");
		$dbw->query("TRUNCATE wp_page");
		$dbw->query("TRUNCATE wp_wikiplace");
		$out->addHTML('All wikiplaces, pages, subscriptions, transactions have been deleted!');
	}
	
	
	
}