<?php

class SpecialSubscriptions extends SpecialPage {
    const TITLE_NAME = 'Subscriptions';

    const ACTION_NEW = 'New';
	const ACTION_USE_INVITATION = 'Invited';
    const ACTION_CHANGE = 'Change';
    const ACTION_RENEW = 'Renew';
    const ACTION_LIST = 'List';

    protected $action = self::ACTION_LIST;
    protected $planName = null;
	protected $invitationCode = null;
    protected $msgType = null;
    protected $msgKey = null;
	protected $invitation = null;

    public function __construct($name = self::TITLE_NAME) {
        parent::__construct($name, WP_ACCESS_RIGHT);
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
			if ($this->action == self::ACTION_USE_INVITATION) {
				$this->invitationCode = $arg;
			} else {
				$this->planName = $arg;
			}
        } else {
            $this->action = $request->getText('action', null);
			if ($this->action == self::ACTION_USE_INVITATION) {
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
			
            case self::ACTION_USE_INVITATION :
                $this->displayInvitation();
                break;
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
            default:
                $this->displayList();
                break;
        }
    }

	   /**
     * User wants to take a new subscription
     */
    protected function displayInvitation() {

        $check = WpSubscription::canSubscribe($this->getUser());
        if ($check !== true) {
            $this->action = self::ACTION_LIST;
            $this->msgKey = $check;
            $this->msgType = 'error';
            $this->display();
            return;
        }

		$invitationForm = array(
			'InvitationCode' => array(
				'type' => 'text',
				'default' => ($this->invitationCode != null) ? $this->invitationCode : '', 
				'label-message' => 'wp-use-inv-field',
				'help-message' => 'wp-use-inv-help',
				'validation-callback' => array($this, 'validateInvitationCode'),
			) );
		$invitationHtml = new HTMLFormS($invitationForm);
        $invitationHtml->addHeaderText(wfMessage('wp-use-inv-header')->parse());
        $invitationHtml->setMessagePrefix('wp');
        $invitationHtml->setTitle($this->getTitleFor(self::TITLE_NAME, self::ACTION_USE_INVITATION));
        $invitationHtml->setSubmitCallback(array($this, 'processInvitation'));
        $invitationHtml->setSubmitText(wfMessage('wp-use-inv-go')->text());

        
        if ( $this->invitationCode != null ) { // handle invitation code from url 
			$invitationHtml->prepareForm(); // fill form with default value specified in descriptor
			$result = $invitationHtml->trySubmit(); // validate and process
			if ( $result === true ) {
				// code ok
				$this->action = self::ACTION_NEW;
				$this->msgKey = 'wp-use-inv-ok';
				$this->msgType = 'success';
				$this->display();
			} else {
				$invitationHtml->displayForm($result);
			}
		} else { // or display and process form from post
			if ($invitationHtml->show()) {
				$this->action = self::ACTION_NEW;
				$this->msgKey = 'wp-use-inv-ok';
				$this->msgType = 'success';
				$this->display();
			}
		}
	}
	
	
	public function validateInvitationCode($code, $allData) {
		if ( ( $code === null) || ( $code === '') ) {
			return wfMessage('htmlform-required')->text();
		}
		if (!preg_match('/^[0-9A-Za-z]+$/', $code)) {
			return wfMessage('wp-use-inv-invalid')->text();
		}
		$code = strtoupper($code);
		$invitation = WpInvitation::newFromCode($code);
		if ( ! $invitation instanceof WpInvitation ) {
			return wfMessage('wp-use-inv-invalid')->text();
		}
		if ( ! $invitation->canBeUsed() ) {
			return wfMessage('wp-use-inv-nolonger')->text();
		}
		
		$this->invitation = $invitation;
		return true;
    }

	public function processInvitation($formData) {
		// nothing to do
		return true;
	}
    /**
     * User wants to take a new subscription
     */
    protected function displayNew() {

        $check = WpSubscription::canSubscribe($this->getUser());
        if ($check !== true) {
            $this->action = self::ACTION_LIST;
            $this->msgKey = $check;
            $this->msgType = 'error';
            $this->display();
            return;
        }
		
		// load invitation using the hidden field
		// maybe there is a better way to do this?
		$request = $this->getRequest();
		$invitation = null;
		if ( $this->invitation != null ) {
			$invitation = $this->invitation;
		}elseif ( $request->getCheck('wpInvitation') ) {
			$invitation = WpInvitation::newFromCode($request->getText('wpInvitation'));
			if ($invitation instanceof WpInvitation ) {
				if ( ! $invitation->canBeUsed() ) {
					$invitation = null; // can no longer be used
				}
			}
		}

		$formDescriptor = array();
		
		if ($invitation instanceof WpInvitation) {
			$formDescriptor['UseInvitation'] = array(
				'type' => 'info',
				'section' => 'sub-new-section',
				'label-message' => 'wp-use-inv-field',
				'default' => $invitation->getCode(),
				'raw' => true # don't escape
			);
		}
		
		$formDescriptor['Invitation'] = array(
				'type' => 'hidden',
				'label' => 'hidden',
				'default' => ($invitation != null ? $invitation->getCode() : ''),
			);
		$formDescriptor['Plan'] = array(
				'type' => 'select',
				'section' => 'sub-new-section',
				'label-message' => 'wp-planfield',
				'help-message' => 'wp-planfield-help',
				'validation-callback' => array($this, 'validateSubscribeNewPlanId'),
				'options' => array(),
            );
        $formDescriptor['Check'] = array(
                'type' => 'check',
                'section' => 'sub-new-section',
                'label-message' => 'wp-checkfield',
                'validation-callback' => array($this, 'validateSubscribeCheck'),
                'required' => 'true'
            );

        $plans = WpPlan::factoryAvailableForFirstSubscription($invitation);
        foreach ($plans as $plan) {
            $wpp_name = $plan->getName();
            $price = $plan->getPrice();
            $formDescriptor['Plan']['options'][wfMessage('wp-plan-desc-short', wfMessage('wpp-' . $wpp_name)->text(), $price['amount'])->text()] = $plan->getId();
            if ($this->planName == $wpp_name) {
                $formDescriptor['Plan']['default'] = $plan->getId();
            }
        }
        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->addHeaderText(wfMessage('wp-sub-new-header')->parse());
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->setTitle($this->getTitleFor(self::TITLE_NAME, self::ACTION_NEW));
        $htmlForm->setSubmitCallback(array($this, 'processNew'));
        $htmlForm->setSubmitText(wfMessage('wp-plan-subscribe-go')->text());

		if ( $this->invitation != null ) {
			// invitation code comes from another form, so only diplay form and do not process submission 
			$htmlForm->setBlockSubmit(true);
		} else {
			// store invitation code if it has been loaded from the current form
			$this->invitation = $invitation;
		}
		
		if ($htmlForm->show()) {
			// form validated and processed OK
            switch ($this->just_subscribed->getTmrStatus()) {
                case 'PE':
                    $this->getOutput()->redirect($this->getTitleFor('ElectronicPayment')->getLocalURL());
                    return;
                default:
                    $this->msgType = 'success';
                    $this->msgKey = 'wp-new-success-ok';
                    break;
            }
            $this->action = self::ACTION_LIST;
            $this->display();
        }
    }

		
    public function validateSubscribeCheck($check, $allData) {
        if (!$check)
            return wfMessage('wp-checkfield-unchecked')->text();

        return true;
    }

    public function validateSubscribeNewPlanId($id, $allData) {
        if (!preg_match('/^[0-9]{1,10}$/', $id)) {
            return 'Error: Invalid Plan ID';
        }

        $plan = WpPlan::newFromId($id);
        if ($plan == null) {
            return 'Error: Invalid Plan ID';
        }

        if (!$plan->canBeTakenAsFirst($this->getUser(), $this->invitation)) {
            return 'Error: Plan Forbidden';
        }

        return true;
    }

    public function processNew($formData) {

        if (!isset($formData['Plan'])) { //check the key exists and value is not NULL
            throw new MWException('Cannot process new subscription, no data.');
        }

        $plan = WpPlan::newFromId($formData['Plan']);

        if ($plan === null) {
            throw new MWException('Cannot process new subscription, unknown plan.');
        }

        // displayNew() checked $user->canSubscribe() and validatePlanId() checked $user->canSubscribeTo()
        // so now, the subscription can be really done

		// NOTE: we record that the invitation code has been typed, even if it will not be consumed
        $subscription = WpSubscription::subscribe($this->getUser(), $plan, $this->invitation);
        if ($subscription == null) {
            return wfMessage('sz-internal-error')->text();
        }
		
		if ( ( $this->invitation instanceof WpInvitation ) && ( $plan->isInvitationRequired()) ){
			$this->invitation->consume();
		}

        $this->just_subscribed = $subscription;

        return true;
    }

    /**
     * @todo: implement this functionality
     */
    protected function displayChange() {
        $this->action = self::ACTION_LIST;
        $this->msgType = 'error';
        $this->msgKey = 'wp-subscribe-change';
        $this->display();
    }

    protected function displayRenew() {

        // at this point, user is logged in
        $user_id = $this->getUser()->getId();
        $sub = WpSubscription::newActiveByUserId($user_id);
        if ($sub == null) {
            // "need an active subscription"
            $this->action = self::ACTION_LIST;
            $this->msgType = 'error';
            $this->msgKey = 'wp-no-active-sub';
            $this->display();
            return;
        }
        $renewal_plan_id = $sub->getRenewalPlanId();

        $formDescriptor = array(
            'Plan' => array(
                'type' => 'select',
                'section' => 'sub-renew-section',
                'label-message' => 'wp-planfield',
                'help-message' => 'wp-planfield-help',
                'validation-callback' => array($this, 'validateRenewPlanId'),
                'options' => array(),
                'default' => $renewal_plan_id,
            ),
            'Check' => array(
                'type' => 'check',
                'section' => 'sub-renew-section',
                'label-message' => 'wp-checkfield',
                'validation-callback' => array($this, 'validateSubscribeCheck'),
                'required' => 'true'
            )
        );

        $nb_wikiplaces = WpWikiplace::countWikiplacesOwnedByUser($user_id);
        $nb_wikiplace_pages = WpPage::countPagesOwnedByUser($user_id);
        $diskspace = WpPage::countDiskspaceUsageByUser($user_id);
        $when = $sub->getEnd();

        $plans = WpPlan::factoryAvailableForRenewal($nb_wikiplaces, $nb_wikiplace_pages, $diskspace, $when);
        foreach ($plans as $plan) {
            $wpp_name = $plan->getName();
            $price = $plan->getPrice();
            $formDescriptor['Plan']['options'][wfMessage('wp-plan-desc-short', wfMessage('wpp-' . $wpp_name)->text(), $price['amount'])->text()] = $plan->getId();
        }

        // add "do not renew" at the end;
        $formDescriptor['Plan']['options'][wfMessage('wp-do-not-renew')->text()] = '0';

        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->addHeaderText(wfMessage('wp-sub-renew-header')->parse());
        $htmlForm->setTitle($this->getTitleFor(self::TITLE_NAME, self::ACTION_RENEW));
        $htmlForm->setSubmitCallback(array($this, 'processRenew'));
        $htmlForm->setSubmitText(wfMessage('wp-plan-renew-go')->text());

        // validate and process the form is data sent
        if ($htmlForm->show()) {
            $this->action = self::ACTION_LIST;
            $this->msgKey = 'wp-renew-success';
            $this->msgType = 'success';
            $this->display();
        }
    }

    public function validateRenewPlanId($id, $allData) {

        if (!preg_match('/^[0-9]{1,10}$/', $id)) {
            return 'Error: Invalid Renewal Plan ID';
        }

        if ($id == WPP_ID_NORENEW) {
            return true; // "no next plan"
        }

        $plan = WpPlan::newFromId($id);
        if ($plan == null) {
            return 'Error: Invalid Plan ID';
        }

        $user_id = $this->getUser()->getId();
        $curr_sub = WpSubscription::newActiveByUserId($user_id);
        if ($curr_sub == null) {
            return 'Error: No Active Subscription';
        }

        if (!$plan->isAvailableForRenewal($curr_sub->getEnd())) {
            return 'Error: Plan Not Available For Renewal';
        }

        if (!$plan->hasSufficientQuotas(
                        WpWikiplace::countWikiplacesOwnedByUser($user_id), WpPage::countPagesOwnedByUser($user_id), WpPage::countDiskspaceUsageByUser($user_id))) {
            return 'Error: Plan Quotas Unsufficients';
        }

        return true;
    }

    public function processRenew($formData) {

        if (!isset($formData['Plan'])) { //check the key exists and value is not NULL
            throw new MWException('Cannot set next plan, no data.');
        }

        $sub = WpSubscription::newActiveByUserId($this->getUser()->getId());

        if ($sub == null) {
            throw new MWException('Cannot set next plan, no active subscription.');
        }

        $sub->setRenewalPlanId(intval($formData['Plan']));

        return true;
    }

    protected function displayList() {
        $user = $this->getUser();
        $output = $this->getOutput();

        /**
         *  @Todo BAD, does 2 db query for same results! And uses TP for just 1 line!
         */
        $tp = new WpSubscriptionsTablePager();
        $tp->setSelectConds(array('wps_buyer_user_id' => $this->getUser()->getId()));

        $subs = WpSubscription::newByUserId($user->getId());

        if (!isset($subs)) {
            if ($this->msgKey != null) {
                $tp->setHeader(wfMessage('wp-subscriptionslist-noactive-header')->parse());
            } else {
                $this->action = self::ACTION_NEW;
                $this->display();
                return;
            }
        } elseif ($subs->getTmrStatus() == 'PE') {
            $tp->setHeader(wfMessage('wp-subscriptionslist-pending-header')->parse());
        } elseif (!$subs->isActive()) {
            $tp->setHeader(wfMessage('wp-subscriptionslist-noactive-header')->parse());
        } else {
            $tp->setHeader(wfMessage('wp-subscriptionslist-header')->parse());
            $tp->setFooter(wfMessage('wp-subscriptionslist-footer')->parse());
        }

        $output->addHTML($tp->getWholeHtml());
    }

    /**
     *  Generate Link to Special:MySubscriptions
     * 
     * @param String $i18n_key
     * @return String HTML <a> link 
     */
    public static function getLinkToMySubscriptions($i18n_key = 'subscriptions') {
        return Linker::linkKnown(
                        SpecialPage::getTitleFor(self::TITLE_NAME), wfMessage($i18n_key)->text());
    }
	
	public static function getLinkUseInvitation($i18n_key = 'link-use-invitation') {
        return Linker::linkKnown(
                        SpecialPage::getTitleFor(self::TITLE_NAME, self::ACTION_USE_INVITATION), wfMessage($i18n_key)->parse());
    }

    /**
     * Generate link to subscribe to plan $wpp_name
     * @param String $wpp_name
     * @param String $i18n_key
     * @return string HTML <a> link 
     */
    public static function getLinkNew($wpp_name = null, $i18n_key = null) {
        if ($wpp_name == null) {
            if ($i18n_key == null) {
                $i18n_key = 'wp-subscribe-new';
            }
            return Linker::linkKnown(
                            self::getTitleFor(self::TITLE_NAME, self::ACTION_NEW), wfMessage($i18n_key)->text());
        } else {
            if ($i18n_key == null) {
                $i18n_key = 'wpp-' . $wpp_name;
            }
            return Linker::linkKnown(
                            self::getTitleFor(self::TITLE_NAME, self::ACTION_NEW . ':' . $wpp_name), wfMessage($i18n_key)->text());
        }
    }

}