<?php

class SpecialWikiplacesAdmin extends SpecialPage {
    const TITLE_NAME = 'WikiplacesAdmin';


    const ACTION_CANCEL_SUBSCRIPTION = 'Cancel';
    const ACTION_TEST = 'Test';


    private $action = null;

    public function __construct($request = null) {
        parent::__construct(self::TITLE_NAME, WP_ADMIN_RIGHT);
    }

    public function execute($par) {

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

        $output->addWikiText("== WikiPlace Administration Panel ==");

        switch (strtolower($this->action)) {
            case strtolower(self::ACTION_CANCEL_SUBSCRIPTION) :
                $this->cancelSubscription($request->getText('name', null), $request->getBool('confirm'));
                break;
            case strtolower(self::ACTION_TEST) :
                $this->test($request->getText('arg1', null),$request->getText('arg2', null),$request->getText('arg3', null));
                break;
            default :
                $output->addWikiText("=== ERROR: Wrong action ===");
                $output->addWikiText("====Available actions:====");
                $output->addWikiText(self::ACTION_CANCEL_SUBSCRIPTION . "(string '''name''', boolean '''confirm''')");
                $output->addWikiText(self::ACTION_TEST . "(string '''arg1''', string '''arg2''', string '''arg3''')");
                break;
        }
    }

    private function cancelSubscription($name = null, $confirm = false) {
        $output = $this->getOutput();

        $output->addWikiText("=== Cancel Subscription (name, confirm) ===");
        $output->addWikiText("name = $name");

        $user = User::newFromName($name);
        if (!$user || $user->getId() == 0) {
            $output->addWikiText("=== ERROR: Invalid UserName ===");
            return false;
        }

        $output->addWikiText("=== User ===");
        $output->addWikiText("user_id = " . $user->getId());
        $output->addWikiText("user_name = " . $user->getName());
        $output->addWikiText("user_realname = " . $user->getRealName());
        $output->addWikiText("user_email = " . $user->getEmail());
        $output->addWikiText("True balance = " . TMRecord::getTrueBalanceFromDB($user->getId()));

        $subscription = WpSubscription::newByUserId($user->getId());

        if (!$subscription instanceof WpSubscription || $subscription == null) {
            $output->addWikiText("=== ERROR: No subscription ===");
            return false;
        }

        $output->addWikiText("=== Subscription ===");
        $output->addWikiText("==== Subscription ====");
        $output->addWikiText("wps_id = " . $subscription->getId());
        $output->addWikiText("wps_start_date = " . $subscription->getStart());
        $output->addWikiText("wps_end_date = " . $subscription->getEnd());
        $output->addWikiText("wps_active = " . ($subscription->isActive() ? "true" : "false"));
        $output->addWikiText("wps_renewal_notified = " . ($subscription->isRenewalNotified() ? "true" : "false"));
        $output->addWikiText("==== Plan ====");
        $output->addWikiText("wps_wpp_id = " . $subscription->getPlanId());
        $output->addWikiText("wps_renew_wpp_id = " . $subscription->getRenewalPlanId());
        $output->addWikiText("==== TMR ====");
        $output->addWikiText("wps_tmr_id = " . $subscription->getTmrId());
        $output->addWikiText("wps_tmr_status = " . $subscription->getTmrStatus());


        if ($subscription->getTmrStatus() == 'OK') {
            $output->addWikiText("== THE TRANSACTION STATUS = OK, YOU ARE DESTROYING MONEY !!! ==");
        }

        if (!$confirm) {
            $output->addWikiText("=== Cancel Subscription Test ===");
            $result = $subscription->canCancel($this->getUser());
            if ($result === true) {
                $output->addWikiText("You can cancel this subscription");
                $output->addWikiText("=== Add &confirm=true to really do the action ===");
            } else {
                $output->addWikiText("You cannot cancel this subscription:");
                $output->addWikiText("=== ERROR: CanCancel() did not return true ===");
            }
        } else if ($confirm) {
            $output->addWikiText("=== Cancel Subscription ===");
            $result = $subscription->cancel($this->getUser());
            if ($result === true) {
                $output->addWikiText("==== DONE - The Subscription has been cancelled ====");
                $output->addWikiText("==== Subscription ====");
                $output->addWikiText("wps_end_date = " . $subscription->getEnd());
                $output->addWikiText("wps_active = " . ($subscription->isActive() ? "true" : "false"));
                $output->addWikiText("==== TMR ====");
                $output->addWikiText("wps_tmr_status = " . $subscription->getTmrStatus());
                $output->addWikiText("== SUCCESS ==");
            } else {
                $output->addWikiText("==== Cancelation failed ====");
                $output->addWikiText($result);
                $output->addWikiText("==== Subscription ====");
                $output->addWikiText("wps_end_date = " . $subscription->getEnd());
                $output->addWikiText("wps_active = " . ($subscription->isActive() ? "true" : "false"));
                $output->addWikiText("==== TMR ====");
                $output->addWikiText("wps_tmr_status = " . $subscription->getTmrStatus());
                $output->addWikiText("== FAIL ==");
            }
        }
    }

    private function test($arg1='null', $arg2='null', $arg3='null') {
        $plan=WpPlan::newFromId($arg1);
        $price = $plan->getLocalizedPrice($this->getUser());
        $output = $this->getOutput();
        $output->addWikiText("=== Test (arg1, arg2, arg3) ===");
        $output->addWikiText("arg1 = $arg1");
        $output->addWikiText("arg2 = $arg2");
        $output->addWikiText("arg3 = $arg3");
        $output->addWikiText("=== RESULT ===");
        $output->addWikiText($price);
        $output->addWikiText("== DONE ==");
    }
}