<?php 

class SpecialSubscriptions extends SpecialPage {
	
	const TITLE_NAME = 'Subscriptions';

	const ACTION_NEW = 'new';
	const ACTION_CHANGE = 'change';
	const ACTION_RENEW = 'renew';
	const ACTION_LIST = 'list';
	
	private $subscription_just_subscribed;
	
	/**
	 * Generate link to subscribe to plan $wpp_name
	 * @param type $wpp_name
	 * @return string HTML <a> attribute 
	 */
	public static function getLinkNew( $wpp_name ) {
		return Linker::linkKnown(
				self::getTitleFor( self::TITLE_NAME ),
				wfMessage('wp-plan-name-'.$wpp_name)->text(),
				array(),
				array( 'action' => self::ACTION_NEW, 'plan' => $wpp_name) );
	}
	
	public function __construct() {
		parent::__construct( self::TITLE_NAME );
	}
	
	public function userCanExecute( User $user ) {
		if ( wfReadOnly() ) {
			throw new ReadOnlyError();
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

		if (!$this->userCanExecute($user)) {
			$this->displayRestrictionError();
			return;
		}		
		
		/** @todo: replace this header with something nicer */
		$this->getOutput()->setSubtitle(Html::rawElement('span', array(), $this->getLang()->pipeList(array(
				Linker::linkKnown($this->getTitle(self::ACTION_NEW), wfMessage( 'wp-subscribe-new')->text()),
				Linker::linkKnown($this->getTitle(self::ACTION_CHANGE), wfMessage( 'wp-subscribe-change')->text()),
				Linker::linkKnown($this->getTitle(self::ACTION_RENEW), wfMessage( 'wp-subscribe-renew')->text()),
				Linker::linkKnown($this->getTitle(self::ACTION_LIST), wfMessage( 'wp-subscribe-list')->text()),
			))));
		
		// dispatch
		$action = strtolower( $this->getRequest()->getText( 'action', $par ) );
        switch ($action) { 
			case self::ACTION_NEW :
				$this->displayNew();
				break;
			case self::ACTION_CHANGE:	
			case self::ACTION_RENEW:
			case self::ACTION_LIST:
			default:
				$this->displayList();
				break;	
		}
		
	}
	
	/**
	 * User wants to take a new subscription
	 */
	private function displayNew() {
		
		// at this point, user is logged, so canSubscribe() never return message "wp-subscribe-loggedout"
		$check = WpSubscription::canSubscribe( $this->getUser() );
		if ($check !== true) {
			$this->getOutput()->addHTML( wfMessage( $check )->text() );
			return;
		}
		
		$formDescriptor = array(
			'Plan' => array(
                'type' => 'select',
                'label-message' => 'wp-select-a-plan',
				'validation-callback' => array( $this, 'validateSubscribePlanId' ),
                'options' => array(),
			),
		);
		
		$plans = WpPlan::getAvailableOffersNow();
		foreach ($plans as $plan) {
			$formDescriptor['Plan']['options'][ wfMessage( 
					'wp-plan-desc-short',
					wfMessage( 'wp-plan-name-'.$plan->get('wpp_name'))->text(), 
					$plan->get('wpp_price'),
					$plan->get('wpp_currency'),
					$plan->get('wpp_period_months') )->text() ] = $plan->get('wpp_id');
		}
		$htmlForm = new HTMLFormS( $formDescriptor );
		$htmlForm->setTitle( $this->getTitle( self::ACTION_NEW ) );
		$htmlForm->setSubmitCallback( array( $this, 'processNew' ) );
		$htmlForm->setSubmitText( wfMessage( 'wp-plan-subscribe-go' )->text() );
	
		// validate and process the form is data sent
		if( $htmlForm->show() ) {
			
			$out = $this->getOutput();
			
			$out->addHTML( wfMessage( 
					'wp-subscribe-success',
					wfMessage('wp-plan-name-'.$this->just_subscribed->get('plan')->get('wpp_name'))->text() )->text() . '<br/>' );

			switch ($this->just_subscribed->get('wps_tmr_status')) {
				case "OK":
					$out->addHTML(wfMessage('wp-subscribe-tmr-ok')->parse());
					break;
				case "PE":
					$out->addHTML(wfMessage('wp-subscribe-tmr-pe')->parse());
					break;
				default:
					$out->addHTML(wfMessage('wp-subscribe-tmr-other')->parse());
			}
			
		}
		
	}
	
	public function validateSubscribePlanId($id, $allData) {
		
		if ( ! preg_match('/^[0-9]{1,10}$/',$id) ) {
			return wfMessage( 'wp-invalid-plan' )->text();
		}
		
		if ( ! WpSubscription::canSubscribeTo($this->getUser()->getId(), $id) ) {
			return wfMessage( 'wp-cannot-subscribe-plan' )->text();
		}
			
        return true ;
		
	}
	
	public function processNew( $formData ) {
		
		if ( !isset($formData['Plan']) ) { //check the key exists and value is not NULL
			throw new MWException( 'Cannot process new subscription, no data.' );
		}
		
		$plan = WpPlan::getById($formData['Plan']);
		
		if ( $plan === null ) {
			throw new MWException( 'Cannot process new subscription, unknown plan.' );
		}
		
		// displayNew() checked $user->canSubscribe() and validatePlanId() checked $user->canSubscribeTo()
		// so now, the subscription can be really done
		
		$subscription = WpSubscription::subscribe( $this->getUser() , $plan );
		if ( $subscription == null ) {
			return wfMessage('wp-internal-error')->text();
		}
		
		$this->just_subscribed = $subscription;
		
		return true;
		
	}

	/**
	 * @todo: implement this functionality
	 */
	private function displayChange() {
		// not yet implemented		
	}
	
	
	/**
	 * @todo: implement this functionality
	 */
	private function displayRenew() {
		// not yet implemented		
	}
	
	private function displayList() {
		
		$table = new WpSubscriptionsTablePager();
		$table->setSelectConds(array('wps_buyer_user_id' => $this->getUser()->getId()));
		$this->getOutput()->addHTML($table->getWholeHtml());
		
	}
	
}