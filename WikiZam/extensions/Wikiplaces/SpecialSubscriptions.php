<?php

class SpecialSubscriptions extends SpecialPage {
    const TITLE_NAME = 'Subscriptions';

    const ACTION_NEW = 'new';
    const ACTION_CHANGE = 'change';
    const ACTION_RENEW = 'renew';
    const ACTION_LIST = 'list';

    public function __construct() {
        parent::__construct(self::TITLE_NAME, WP_ACCESS_RIGHT);
    }

    public function execute($par) {

        $this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title

        $user = $this->getUser();
        
        $output = $this->getOutput();

        // Check rights
        if (!$this->userCanExecute($user)) {
            // If anon, redirect to login
            if ($user->isAnon()) {
                $output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto'=>$this->getFullTitle())), '401');
                return;
            }
            // Else display an error page.
            $this->displayRestrictionError();
            return;
        }
        
        if (isset($par) & $par != '') {
            $explosion = explode(':', $par);
            if (count($explosion) == 1) {
                $action = $explosion[0];
                $plan = null;
            } else if (count($explosion) == 2) {
                $action = $explosion[0];
                $plan = $explosion[1];
            }
        } else {
            $action = $this->getRequest()->getText('action',null);
            $plan = $this->getRequest()->getText('plan', null);
        }
        switch ($action) {
            case self::ACTION_NEW :
                $this->displayNew($plan);
                break;
            case self::ACTION_CHANGE:
                $this->displayChange($plan);
                break;
            case self::ACTION_RENEW:
                $this->displayRenew($plan);
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
    private function displayNew($plan_name = null) {

        $check = WpSubscription::canSubscribe($this->getUser());
        if ($check !== true) {
            $this->getOutput()->showErrorPage('sorry', $check);
            return;
        }

        $formDescriptor = array(
            'Plan' => array(
                'type' => 'select',
                'section' => 'sub-new-section',
                'label-message' => 'wp-planfield',
                'help-message' => 'wp-planfield-help',
                'validation-callback' => array($this, 'validateSubscribePlanId'),
                'options' => array(),
            ),
            'Check' => array(
                'type' => 'check',
                'section' => 'sub-new-section',
                'label-message' => 'wp-checkfield',
                'validation-callback' => array($this, 'validateSubscribeCheck'),
                'required' => 'true'
            )
        );

        $plans = WpPlan::factoryAvailableForFirstSubscription();
        foreach ($plans as $plan) {
            $wpp_name = $plan->getName();
			$price = $plan->getPrice();
            $formDescriptor['Plan']['options'][wfMessage( 'wp-plan-desc-short',
					wfMessage('wp-plan-name-' . $wpp_name)->text(),
					$price['amount'],
					$price['currency'],
					$plan->getPeriod() )->text()] = $plan->getId();
            if ($plan_name == $wpp_name) {
                $formDescriptor['Plan']['default'] = $plan->getId();
            }
        }
        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->addHeaderText(wfMessage('wp-sub-new-header')->parse());
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->setTitle($this->getTitle(self::ACTION_NEW));
        $htmlForm->setSubmitCallback(array($this, 'processNew'));
        $htmlForm->setSubmitText(wfMessage('wp-plan-subscribe-go')->text());

        // validate and process the form is data sent
        if ($htmlForm->show()) {

            $out = $this->getOutput();
			$plan_name = WpPlan::newFromId($this->just_subscribed->getPlanId())->getName();
			
            $out->addHTML(wfMessage('wp-subscribe-success', 
					wfMessage('wp-plan-name-'.$plan_name)->text() 
					)->text().'<br/>');

            switch ($this->just_subscribed->getTmrStatus()) {
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
    
    public function validateSubscribeCheck($check, $allData) {
        if (!$check)
            return wfMessage('wp-checkfield-unchecked')->text();

        return true;
    }

    public function validateSubscribePlanId($id, $allData) {

        if (!preg_match('/^[0-9]{1,10}$/', $id)) {
            return 'Error: Invalid Plan ID';
        }

		$plan = WpPlan::newFromId($id);
		if ( $plan == null ) {
			return 'Error: Invalid Plan ID';
		}
		
        if (!$plan->canBeTakenAsFirst($this->getUser())) {
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

        $subscription = WpSubscription::subscribe($this->getUser(), $plan);
        if ($subscription == null) {
            return wfMessage('sz-internal-error')->text();
        }

        $this->just_subscribed = $subscription;

        return true;
    }

    /**
     * @todo: implement this functionality
     */
    private function displayChange($plan_name = null) {
        $this->getOutput()->showErrorPage('sorry', 'wp-subscribe-change');
    }

    private function displayRenew($plan_name = null) {

        // at this point, user is logged in
        $user_id = $this->getUser()->getId();
        $sub = WpSubscription::factoryActiveByUserId($user_id);
        if ($sub == null) {
            // "need an active subscription"
            $this->getOutput()->showErrorPage('sorry','wp-no-active-sub');
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
            $formDescriptor['Plan']['options'][wfMessage('wp-plan-desc-short',
					wfMessage('wp-plan-name-' . $wpp_name)->text(),
					$price['amount'],
					$price['currency'],
					$plan->getPeriod() )->text() ] = $plan->getId();
        }
		
		// add "do not renew" at the end;
		$formDescriptor['Plan']['options'][wfMessage('wp-do-not-renew')->text()] = '0';

        $htmlForm = new HTMLFormS($formDescriptor);
        $htmlForm->setMessagePrefix('wp');
        $htmlForm->addHeaderText(wfMessage('wp-sub-renew-header')->parse());
        $htmlForm->setTitle($this->getTitle(self::ACTION_RENEW));
        $htmlForm->setSubmitCallback(array($this, 'processRenew'));
        $htmlForm->setSubmitText(wfMessage('wp-plan-renew-go')->text());

        // validate and process the form is data sent
        if ($htmlForm->show()) {
            $this->getOutput()->addHTML(wfMessage('wp-renew-success')->text());
        }
    }

    public function validateRenewPlanId($id, $allData) {

        if (!preg_match('/^[0-9]{1,10}$/', $id)) {
            return 'Error: Invalid Renewal Plan ID';
        }

        if ($id == WPS_RENEW_WPP_ID__DO_NOT_RENEW) {
            return true; // "no next plan"
        }
		
		$plan = WpPlan::newFromId($id);
		if ( $plan == null ) {
			return 'Error: Invalid Plan ID';
		}

        $user_id = $this->getUser()->getId();
		$curr_sub = WpSubscription::factoryActiveByUserId($user_id);
		if ($curr_sub == null) {
			return 'Error: No Active Subscription';
		}
		
		if (!$plan->isAvailableForRenewal($curr_sub->getEnd())) {
			return 'Error: Plan Not Available For Renewal';
		}
		
		if (!$plan->hasSufficientQuotas(
				WpWikiplace::countWikiplacesOwnedByUser($user_id),
				WpPage::countPagesOwnedByUser($user_id),
				WpPage::countDiskspaceUsageByUser($user_id) ) ){
			return 'Error: Plan Quotas Unsufficients';
		}

        return true;
    }

    public function processRenew($formData) {

        if (!isset($formData['Plan'])) { //check the key exists and value is not NULL
            throw new MWException('Cannot set next plan, no data.');
        }

        $sub = WpSubscription::factoryActiveByUserId($this->getUser()->getId());

        if ($sub == null) {
            throw new MWException('Cannot set next plan, no active subscription.');
        }

        $sub->setRenewalPlan(intval($formData['Plan']));

        return true;
    }

    private function displayList() {
        $user = $this->getUser();
        $output = $this->getOutput();
        
        $tp = new WpSubscriptionsTablePager();
        $tp->setSelectConds(array('wps_buyer_user_id' => $this->getUser()->getId()));
        // We do not directly output the table because we still need to cook the layout using the request just done
        $html = $tp->getBody().$tp->getNavigationBar();
        
        
        $count = $tp->getNumRows();
        
        // If the table is empty, we redirect to Special:Subscription/new
        if ($count < 1) {
            $output->redirect($this->getTitle(self::ACTION_NEW)->getInternalURL(), '303');
        }
        
        $active = WpSubscription::factoryActiveByUserId($user->getId());
        
        // If the user has no active subs, we want to invite him to subscribe ASAP
        if ($active == null) {
            $html = $tp->makeHeader(wfMessage('wp-subscriptionslist-noactive-header')->parse()).$html;
        } else {
            $html = $tp->makeHeader(wfMessage('wp-subscriptionslist-header')->parse()).$html;
            $html .= $tp->makeFooter(wfMessage('wp-subscriptionslist-footer')->parse());
        }
        $this->getOutput()->addHTML($html);
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

    /**
     * Generate link to subscribe to plan $wpp_name
     * @param type $wpp_name
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
                $i18n_key = 'wp-plan-name-' . $wpp_name;
            }
            return Linker::linkKnown(
                            self::getTitleFor(self::TITLE_NAME, self::ACTION_NEW.':'.$wpp_name ), wfMessage($i18n_key)->text());
        }
    }

}