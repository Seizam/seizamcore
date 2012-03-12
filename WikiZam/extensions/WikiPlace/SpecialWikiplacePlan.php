<?php 

class SpecialWikiplacePlan extends SpecialPage {

	const ACTION_SUBSCRIBE				= 'subscribe';
	const ACTION_LIST_SUBSCRIPTIONS		= 'my_subscriptions';
	const ACTION_CHANGE					= 'change_plan';
	const ACTION_LIST_OFFERS			= 'list_offers';
	
	const ACTION_TEST_GIVE_CREDIT		= 'test';

	
	/**
	 *
	 * @var WpSubscription The newly created subscription
	 */
	private $newlySubscribed;
	
	
	
	public function __construct() {
		parent::__construct( 'WikiPlacePlan' );
	}
	

	
	
	
	private static function generateLink($to, $text) {
		 return Html::rawElement( 'a', array( 'href' => $to, 'class' => 'sz-wikiplace-link' ), $text  );
	}
	
	
		
	
	/**
	 * Show the special page
	 *
	 * @param $par String subpage string, if one was specified
	 */
	public function execute( $par ) {
		
		$out = $this->getOutput();
		$user = $this->getUser();
		
		// Anons can't use this special page
		if( $user->isAnon() ) {
			$out->setPageTitle( wfMessage( 'wikiplace-pleaselogin-pagetitle' )->text() );
			$link = Linker::linkKnown(
				SpecialPage::getTitleFor( 'Userlogin' ),
				wfMessage( 'wikiplace-pleaselogin-link-text' )->text(),
				array(),
				array( 'returnto' => $this->getTitle()->getPrefixedText() )
			);
			$out->addHTML( wfMessage( 'wikiplace-pleaselogin-text' )->rawParams( $link )->parse() );
			return;
		}

		// check that the user is not blocked
		if( $user->isBlocked() ){
			$out->blockedPage();
		}
		
		if( !$this->userCanExecute( $user ) ){
			$this->displayRestrictionError();
			return;
		}

		// Starts display
		
		$this->setHeaders();											// sets robotPolicy = "noindex,nofollow" + set page title
		$this->outputHeader();											// outputs a summary message on top of special pages
		$out->setSubtitle( $this->buildToolLinks( $this->getLang()) );	// set a nav bar as subtitle
		
		// Handle request
				
		// what to do is specified in the url (as a subpage) or somewhere in the request (this has the priority)
		$do = strtolower( $this->getRequest()->getText( 'action', $par ) );
        switch ($do) { 

			/** @todo TODO: remove this test action !!!! */
			case self::ACTION_TEST_GIVE_CREDIT:
			    $tmr = array(
					# Params related to Message
					'tmr_type'		=> 'PAYTEST', # varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
					# Paramas related to User
					'tmr_user_id'	=> $user->getId(), # int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
					'tmr_mail'		=> $user->getEmail(), # tinyblob COMMENT 'User''s Mail',
					'tmr_ip'		=> IP::sanitizeIP(wfGetIP()), # tinyblob COMMENT 'User''s IP'
					# Params related to Record
					'tmr_amount'	=> 10 , # decimal(9,2) NOT NULL COMMENT 'Record Amount',
					'tmr_currency'	=> 'EUR', # varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
					'tmr_desc'		=> 'WikiPlace plan test, simulate 10 EUR credit to user', # varchar(64) NOT NULL COMMENT 'Record Description',
					'tmr_status'	=> 'OK' # varchar(2) NOT NULL COMMENT 'Record status (OK, KO, PEnding, TEst)',
				);
				wfRunHooks('CreateTransaction', array(&$tmr));
				$out->addHTML("10 EUR gived to ".$user->getName());
				break;
								
			case self::ACTION_SUBSCRIBE :
  
				$out->setPageTitle( wfMessage( 'wp-plan-subscribe-pagetitle' )->text());
				
				if (!WpSubscription::canMakeAFirstSubscription($user->getId())) { 
					// do not process submitted datas if cannot make a first sub
					$out->addHTML(wfMessage( 'wp-plan-cannot-subs-anymore' )->text());

					
				} else {
					// can subscribe
					$form = $this->getSubscribePlanForm($this->getTitle( self::ACTION_SUBSCRIBE ));

					if( $form->show() ){

						$out->addHTML(wfMessage(
								'wp-plan-subscribe-success-wikitext',
								wfEscapeWikiText( wfMessage('wp-plan-name-'.$this->newlySubscribed->get('plan')->get('wpp_name'))->text() ) 
							)->parse() . '<br />' );	

						$status = $this->newlySubscribed->get('transactionStatus');
						switch ($this->newlySubscribed->get('transactionStatus')) {
							case "OK":
								$out->addHTML(wfMessage( 'wp-plan-payment-ok' )->text());
								break;
							case "PE":
								$out->addHTML(wfMessage( 'wp-plan-payment-pending' )->parse());
								break;
							default:
								$out->addHTML(wfMessage( 'wp-plan-unknwon-status' )->text());
								break;
						}

					}

				}
				
                break;
				
			case self::ACTION_CHANGE :
				$out->setPageTitle( wfMessage( 'wp-plan-change-pagetitle' )->text());
				
				break;
 
				
			case self::ACTION_LIST_OFFERS :
				
				$out->setPageTitle( wfMessage( 'wp-plan-listoffers-pagetitle' )->text() );
				
				$out->addHTML( $this->getCurrentPlansOffersListing() );
				
                break;
			
			
			case self::ACTION_LIST_SUBSCRIPTIONS :
			default : // (default  =  action == nothing or "something we cannot handle")
				
				$out->setPageTitle( wfMessage( 'wp-plan-listsubs-pagetitle' )->text() );
				
				$out->addHTML( $this->getUserSubscriptionsListing() );
				
				break;
			

		}
		
		
	}
	
	
	/**
	 *
	 * @param Language $language
	 * @return type 
	 */
	public function buildToolLinks($language) {
		
		if ( ($language==null) || !($language instanceof Language) )
			return '';	//avoid error message on screen, but cannot display if $language not correct, nothing displayed is our error message

		return Html::rawElement( 'span', array(), wfMessage( 'parentheses', $language->pipeList(array(

				Linker::linkKnown( $this->getTitle( self::ACTION_SUBSCRIBE ), wfMessage( 'wp-plan-linkto-subscribe' )->text() ) ,
				Linker::linkKnown( $this->getTitle( self::ACTION_LIST_SUBSCRIPTIONS ), wfMessage( 'wp-plan-linkto-mysubs' )->text() ) ,
				Linker::linkKnown( $this->getTitle( self::ACTION_CHANGE ), wfMessage( 'wp-plan-linkto-change' )->text() ) ,
				Linker::linkKnown( $this->getTitle( self::ACTION_LIST_OFFERS ), wfMessage( 'wp-plan-linkto-listoffers' )->text() ) ,
		) ) )->text() );
		
	}
	
	
	
	/**
	 *
	 * @param Mixed $user The user (User object) or his id (int value)
	 * @return string An ul / li HTML list
	 */
	private function getCurrentPlansOffersListing() {
		

		$display = '';
		
		$offers = WpPlan::getAvailableOffersNow();
		foreach ($offers as $offer) {
			$name = $offer->get('wpp_name');
			$display .= Html::rawElement( 'li', array(), Linker::linkKnown( $this->getTitle( self::ACTION_SUBSCRIBE ), wfMessage('wp-plan-name-'.$name)->text(), array(), array( 'plan' => $name) ) );
        }

        return Html::rawElement('ul', array(), $display);

	}
	
	
	
	private function getUserSubscriptionsListing() {
		
		$user_id = $this->getUser()->getId();
		
		$pasts		= WpSubscription::getUserFormers($user_id);
		$actives	= WpSubscription::getUserActives($user_id);
		$futurs		= WpSubscription::getUserFuturs($user_id);
		
		$lang = $this->getLang();
		
		return 'todo';
		
		$return = '';
		
		$list = '';
		foreach ($pasts as $sub) {
			$list .= self::getSubscriptionLine($lang, $sub);
        }
		$return .= 'Pasts:'.Html::rawElement('ul', array(), $list);
		
		$list = '';
		foreach ($actives as $sub) {
			$list .= self::getSubscriptionLine($lang, $sub);
        }
		$return .= 'Actives:'.Html::rawElement('ul', array(), $list);

		$list = '';
		foreach ($futurs as $sub) {
			$list .= self::getSubscriptionLine($lang, $sub);
        }
		$return .= 'Futures:'.Html::rawElement('ul', array(), $list);

        return $return;

	}

	
	
	private function getSubscribePlanForm( $submitTitle ) {

        $formDescriptor = array(
			'Plan' => array(
                'type'					=> 'select',
                'label-message'			=> 'wp-plan-subscribe-select',
				'validation-callback'	=> array('WpPlan', 'validateSubscribePlanId'),
                'options'				=> array(),
			),
		);
		
		$plans = WpPlan::getAvailableOffersNow();
		foreach ($plans as $plan) {
			$line = wfMessage( 'wp-plan-name-' . $plan->get('name') )->text() . 
					', '. $plan->get('price') . ' ' . $plan->get('currency') . '/' . wfMessage( 'wp-plan-month' )->text() ;
			$formDescriptor['Plan']['options'][$line] = $plan->get('id');
		}
		
		$htmlForm = new HTMLForm( $formDescriptor );
		$htmlForm->setTitle( $submitTitle );
		if (WpSubscription::getUserActives($this->getUser()->getId()) !== array()) {
			$htmlForm->addHeaderText( wfMessage( 'wp-plan-subscribe-future' )->text() );
		}
		$htmlForm->setSubmitCallback( array( $this, 'processSubscribePlan' ) );
		
		$htmlForm->setSubmitText(		wfMessage( 'wp-plan-subscribe-submit' )->text() );
	
		return $htmlForm;
		
	}
	
	/**
	 *
	 * @param type $formData 
	 * @return boolean true = the form won't display again / false = the form will be redisplayed  / anything else = error to display
	 */
	public function processSubscribePlan( $formData ) {
		
		if ( !isset($formData['Plan']) ) { //check that the keys exist and values are not NULL
			throw new MWException( 'Cannot process to subscription, no data.' );
		}
		
		$plan = WpPlan::getById($formData['Plan']);
		
		if ( $plan === null ) {
			throw new MWException( 'Cannot process to subscription, plan not found data.' );
		}
		
		$this->newlySubscribed = WpSubscription::subscribe( $this->getUser() , $plan );
		
		return ( ($this->newlySubscribed === null) ? wfMessage('cannot subscribe') : true );
		
	}
	
	
}