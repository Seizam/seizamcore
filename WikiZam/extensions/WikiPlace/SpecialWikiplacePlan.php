<?php 

class SpecialWikiplacePlan extends SpecialPage {

	const ACTION_SUBSCRIBE				= 'subscribe';
	const ACTION_LIST_OFFERS			= 'list_offers';
	const ACTION_LIST_SUBSCRIPTIONS		= 'my_subscriptions';
	
	private $newlySubscribedPlan;
	private $newSubscription;
	
	private $newlyCreatedTMR;
	
	
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

								
			case self::ACTION_SUBSCRIBE :
  
				$out->setPageTitle( wfMessage( 'wp-plan-subscribe-pagetitle' )->text());
				
				if (WpSubscription::canHaveNewSubscription($user->getId())) {
				
					$form = $this->getSubscribePlanForm($this->getTitle( self::ACTION_SUBSCRIBE ));

					if( $form->show() ){

						$out->addHTML(wfMessage(
								'wp-plan-subscribe-success-wikitext',
								wfEscapeWikiText( $this->newlySubscribedPlan->get('name') ) 
							)->parse() . '<br />' );	

						if ($this->newlyCreatedTMR['tmr_status'] != 'OK') {
							$out->addHTML(wfMessage( 'wp-plan-payment-pending' )->text());
						} else {
							$out->addHTML(wfMessage( 'wp-plan-payment-ok' )->text());
						}

					}
					
				} else {
					$out->addHTML(wfMessage( 'wp-plan-cannot-subs-anymore' )->text());
				}
				
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
				Linker::linkKnown( $this->getTitle( self::ACTION_LIST_SUBSCRIPTIONS ), wfMessage( 'wp-plan-linkto-mysubs' )->text() ) ,
				Linker::linkKnown( $this->getTitle( self::ACTION_SUBSCRIBE ), wfMessage( 'wp-plan-linkto-subscribe' )->text() ) ,
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
			$name = $offer->get('name');
			$display .= Html::rawElement( 'li', array(), Linker::linkKnown( $this->getTitle( self::ACTION_SUBSCRIBE ), wfMessage($name)->text(), array(), array( 'plan' => $name) ) );
        }

        return Html::rawElement('ul', array(), $display);

	}
	
	
	
	private function getUserSubscriptionsListing() {
		
		$user_id = $this->getUser()->getId();
		
		$pasts		= WpSubscription::getUserFormers($user_id);
		$current	= WpSubscription::getUserActive($user_id);
		$futurs		= WpSubscription::getUserFuturs($user_id);
		
		$lang = $this->getLang();
		$list = '';
		
		foreach ($pasts as $sub) {
			$list .= self::getSubscriptionLine($lang, $sub);
        }
		
		if ($current != null) {
			$list .= self::getSubscriptionLine($lang, $current);
		}

		foreach ($futurs as $sub) {
			$list .= self::getSubscriptionLine($lang, $sub);
        }

        return Html::rawElement('ul', array(), $list);

	}

	private static function getSubscriptionLine($lang, $sub) {
		$start		= self::getHumanDate($lang, $sub->get('startDate'));
		$end		= self::getHumanDate($lang, $sub->get('endDate'));
		$paid		= $sub->get('paid') ? 'paid' : 'not paid';
		$active		= $sub->get('active') ? 'active' : 'not active';
		return Html::rawElement( 'li', array(), $start . ' &gt; ' . $active . ', ' . $paid . ' &gt; ' . $end );
	}
	
	private static function getHumanDate($lang, $date) {
		if ($date == null)
			return '--';
		return $lang->timeanddate(wfTimestamp(TS_MW, $date, true));
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
			$line = wfMessage( 'wp-plan-' . $plan->get('name') . '-short' )->text() . 
					', '. $plan->get('price') . ' ' . $plan->get('currency') . '/' . wfMessage( 'wp-plan-month' )->text() ;
			$formDescriptor['Plan']['options'][$line] = $plan->get('id');
		}
		
		$htmlForm = new HTMLForm( $formDescriptor );
		$htmlForm->setTitle( $submitTitle );
		if (WpSubscription::getUserActive($this->getUser()->getId()) != null) {
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
		
		if ( !is_object($plan) || !($plan instanceof WpPlan) ) {
			throw new MWException( 'Cannot process to subscription, no data.' );
		}
		
		$user = $this->getUser();
				
		$tmr = array(
            # Params related to Message
            'tmr_type'		=> 'subscrip',
			
            # Paramas related to User
            'tmr_user_id'	=> $user->getId(), 
            'tmr_mail'		=> $user->getEmail(),
            'tmr_ip'		=> IP::sanitizeIP(wfGetIP()), 
			
            # Params related to Record
            'tmr_amount'	=> - $plan->get('price'),
            'tmr_currency'	=> $plan->get('currency'), 
            'tmr_desc'		=> $plan->get('name'), 
            'tmr_status'	=> 'PE', // PEnding
        );

        wfRunHooks('CreateTransaction', array(&$tmr));
		$this->newlyCreatedTMR = $tmr;
		
		switch ($tmr['tmr_status']) {
			
			case 'OK':
				$now =  wfTimestamp(TS_DB) ;
				$this->newSubscription = WpSubscription::create($plan->get('id'), $user->getId(), $tmr['tmr_id'], true, $now, WpSubscription::generateEndDate($now), true);
				$this->newlySubscribedPlan = $plan;
				return true;
				break;
			
			case 'PE':
				$this->newSubscription = WpSubscription::create($plan->get('id'), $user->getId(), $tmr['tmr_id'], false, null, null, false);
				$this->newlySubscribedPlan = $plan;
				return true ;
				break;
			
		}
		
		throw new MWException( 'Error while recording the transaction, unknwon status.' );
		
	}
	
	
}