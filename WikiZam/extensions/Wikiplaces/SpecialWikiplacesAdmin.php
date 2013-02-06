<?php

class SpecialWikiplacesAdmin extends SpecialPage {
    const TITLE_NAME = 'WikiplacesAdmin';


    const ACTION_CANCEL_SUBSCRIPTION = 'Cancel';
    const ACTION_GET_INFOS = 'WPInfos';
    const ACTION_CHANGE_WIKIPLACE_OWNER = 'ChangeWikiplaceOwner';
    const ACTION_SUBSCRIBER_FOR = 'SubscribeFor';


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

        $output->addWikiText("== [[Special:WikiplacesAdmin|WikiPlace Administration Panel]] ==");

        switch (strtolower($this->action)) {
            case strtolower(self::ACTION_CANCEL_SUBSCRIPTION) :
                $this->cancelSubscription($request->getText('name', null), $request->getBool('confirm'));
                break;
            case strtolower(self::ACTION_GET_INFOS) :
                $this->getInfos($request->getText('wpw_id', null), $request->getText('wikiplace', null));
                break;
            case strtolower(self::ACTION_CHANGE_WIKIPLACE_OWNER) :
                $this->changeWikiplaceOwner($request->getText('wikiplace', null), $request->getText('user', null), $request->getBool('confirm'));
                break;
            case strtolower(self::ACTION_SUBSCRIBER_FOR) :
                $this->subscribeFor($request->getText('plan', null), $request->getText('user', null), $request->getBool('confirm'));
                break;
            default :
                $output->addWikiText("=== ERROR: Wrong action ===");
                $output->addWikiText("====Available actions:====");
                $output->addHTML('<p>' . $this->getLink(self::ACTION_CANCEL_SUBSCRIPTION, array('name' => 'string')) . '</p>');
                $output->addHTML('<p>' . $this->getLink(self::ACTION_GET_INFOS, array('wpw_id' => 'int', 'wikiplace' => 'string')) . '</p>');
                $output->addHTML('<p>' . $this->getLink(self::ACTION_CHANGE_WIKIPLACE_OWNER, array('wikiplace' => 'string', 'user' => 'string')) . '</p>');
                $output->addHTML('<p>' . $this->getLink(self::ACTION_SUBSCRIBER_FOR, array('plan' => 'id', 'user' => 'string')) . '</p>');
                break;
        }
    }

    private function getLink($action, $params, $confirm = false) {

        if ($confirm) {
            $displayParams = array_merge($params, array('confirm' => 'true'));
            $linkParams = array_merge(array('action' => $action), $params, array('confirm' => 'true'));
        } else {
            $displayParams = array_merge($params, array('confirm' => 'boolean'));
            $linkParams = array_merge(array('action' => $action), $params);
        }

        foreach ($displayParams as $key => $value) {
            if (isset($text)) {
                $text .= ', ';
            } else {
                $text = '(';
            }
            $text.= $confirm ? "$key=<b>$value</b>" : "$value <b>$key</b>";
        }
        if (isset($text)) {
            $text .= ')';
        } else {
            $text = '()';
        }

        return Linker::link($this->getTitle(), $action . $text, array(), $linkParams);
    }

    /**
     *
     * @param OutputPage $output REQUIRED
     * @param array|string $firt An array for multiple (name,value) , or the name as string
     * @param string $second When $first is a string (the name) , the value as string.
     */
    private function prettyOutput($output, $first, $second = null) {
        if (is_array($first)) {
            foreach ($first as $name => $value) {
                $this->prettyOutput($output, $name, $value);
            }
        } else {
            $output->addWikiText("$first = " . (is_null($second) ? "''NULL''" : "<code><nowiki>$second</nowiki></code>" ));
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
                $output->addWikiText("=== To confirm ===");
                $output->addHTML($this->getLink(self::ACTION_CANCEL_SUBSCRIPTION, array(
                            'name' => $user->getName(),
                                ), true));
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

    private function getInfos($wpw_id=null, $wpname=null) {

        $output = $this->getOutput();
        $output->addWikiText("=== Infos (wpw_id || wikiplace) ===");


        // WIKIPLACE

        $wikiplace = null;

        if ($wpw_id != null) {
            $wpw_id = intval($wpw_id);
            $output->addWikiText("Search by wikiplace id <code><nowiki>$wpw_id</nowiki></code>");
            $wikiplace = WpWikiplace::getById($wpw_id);
        } elseif ($wpname != null) {
            $output->addWikiText("Search by wikiplace name <code><nowiki>$wpname</nowiki></code>");
            $wikiplace = WpWikiplace::newFromName($wpname);
        }

        if ($wikiplace == null) {
            $output->addWikiText("=== No Wikiplace ===");
            return;
        }

        $id = $wikiplace->getId();
        $name = $wikiplace->getName();
        $report_expires = $wikiplace->getDateExpires();
        $report_updated = $wikiplace->getReportUpdated();

        $output->addWikiText("=== Wikiplace ===");

        $output->addWikiText("id = <code><nowiki>{$id}</nowiki></code>");
        $output->addWikiText("name = <code><nowiki>{$name}</nowiki></code>");
        $output->addWikiText("report expires = <code><nowiki>{$report_expires}</nowiki></code>");
        $output->addWikiText("report updated = <code><nowiki>{$report_updated}</nowiki></code>");

        // OWNER

        $owner = User::newFromId($wikiplace->getOwnerUserId());

        if ($owner == null || !$owner->getId()) {
            $output->addWikiText("=== No Owner ===");
            return;
        }

        $id = $owner->getId();
        $username = $owner->getName();
        $email = $owner->getEmail();

        $output->addWikiText("=== Owner ===");

        $output->addWikiText("id = <code><nowiki>{$id}</nowiki></code>");
        $output->addWikiText("username = <code><nowiki>{$username}</nowiki></code>");
        $output->addWikiText("email = <code><nowiki>{$email}</nowiki></code>");

        // SUBSCRIPTION

        $subscription = WpSubscription::newFromId($wikiplace->getSubscriptionId());

        if ($subscription == null) {
            $output->addWikiText("=== No Subscription ===");
            return;
        }

        $output->addWikiText("=== Subscription ===");
        $output->addWikiText("wps_id = " . $subscription->getId());
        $output->addWikiText("wps_start_date = " . $subscription->getStart());
        $output->addWikiText("wps_end_date = " . $subscription->getEnd());
        $output->addWikiText("wps_active = " . ($subscription->isActive() ? "true" : "false"));
        $output->addWikiText("wps_renewal_notified = " . ($subscription->isRenewalNotified() ? "true" : "false"));

        $plan = WpPlan::newFromId($subscription->getPlanId());
        $rplan = $plan = WpPlan::newFromId($subscription->getRenewalPlanId());

        $output->addWikiText("==== Plan ====");
        $output->addWikiText("wpp_id = " . $plan->getId());
        $output->addWikiText("wpp_name = " . $plan->getName());
        $output->addWikiText("renew_wpp_id = " . $rplan->getId());
        $output->addWikiText("renew_wpp_name = " . $rplan->getName());

        $tmr = TMRecord::getById($subscription->getTmrId());

        $output->addWikiText("==== TMR ====");
        $output->addWikiText("tmr_id = " . $tmr->getId());
        $output->addWikiText("tmr_status = " . $tmr->getStatus());

        $tmb = TMBill::newFromId($tmr->getTMBId());

        if ($tmb == null) {
            $output->addWikiText("==== No Bill ====");
            return;
        }

        $output->addWikiText("==== TMB ====");
        $output->addWikiText("tmb_id = " . $tmb->getId());
        $output->addWikiText("tmb_date_created = " . $tmb->getDateCreated());
    }

    private function changeWikiplaceOwner($wikiplace_name = null, $user_name = null, $confirm = false) {
        $output = $this->getOutput();

        $output->addWikiText("=== Change Wikiplace Owner ===");
        $this->prettyOutput($output, array(
            'wikiplace' => $wikiplace_name,
            'user' => $user_name,
        ));

        $output->addWikiText("----");

        if (empty($wikiplace_name)) {
            $output->addWikiText("=== Specify a wikiplace name. ===");
            return;
        }

        $wikiplace = WpWikiplace::newFromName($wikiplace_name);

        if (is_null($wikiplace)) {
            $output->addWikiText("== ERROR! No Wikiplace with that name was found. ==");
            return;
        }

        $output->addWikiText("=== Wikiplace ===");
        $this->prettyOutput($output, array(
            'id' => $wikiplace->getId(),
            'name' => $wikiplace->getName(),
            'report_expires' => $wikiplace->getDateExpires(),
            'report_updated' => $wikiplace->getReportUpdated(),
        ));

        $owner = User::newFromId($wikiplace->getOwnerUserId());
        if (!$owner->loadFromId()) {
            $output->addWikiText("== WARNING! Owner is not an existing user! ==");
        } else {
            $output->addWikiText("==== Current owner ====");
            $this->prettyOutput($output, array(
                'id' => $owner->getId(),
                'name' => $owner->getName(),
                'email' => $owner->getEmail(),
                'email confirmed' => $owner->isEmailConfirmed() ? 'yes' : 'no',
                'timestamp of account creation' => $owner->getRegistration(),
            ));
        }

        $output->addWikiText("----");

        if (empty($user_name)) {
            $output->addWikiText("=== Specify a user name. ===");
            return;
        }

        $user = User::newFromName($user_name);
        if (!$user || $user->getId() == 0) {
            $output->addWikiText("== ERROR! The user doesn't exist ! ==");
            return;
        }

        $output->addWikiText("=== New Owner ===");
        $this->prettyOutput($output, array(
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'email confirmed' => $user->isEmailConfirmed() ? 'yes' : 'no',
            'timestamp of account creation' => $user->getRegistration(),
        ));

        if (!$user->isEmailConfirmed()) {
            $output->addWikiText("== WARNING! Email is not confirmed! ==");
        }

        $last_subscription = WpSubscription::newByUserId($user->getId());
        if (is_null($last_subscription)) {
            $output->addWikiText("== ERROR! The user doesn't have any subscription! ==");
            return;
        }

        $output->addWikiText("==== Last subscription ====");
        $this->prettyOutput($output, array(
            'id' => $last_subscription->getId(),
            'starts' => $last_subscription->getStart(),
            'ends' => $last_subscription->getEnd(),
            'active' => $last_subscription->isActive() ? "yes" : "no",
            'transaction status' => $last_subscription->getTmrStatus(),
        ));

        if (!$last_subscription->isActive()) {
            $output->addWikiText("== WARNING! The subscription is not active! ==");
        }

        $plan = $last_subscription->getPlan();
        if (is_null($plan)) {
            $output->addWikiText("== ERROR! The subscribed plan doesn't exist! ==");
            return;
        }

        $output->addWikiText("==== Plan ====");
        $price = $plan->getPrice();

        $this->prettyOutput($output, array(
            'id' => $plan->getId(),
            'name' => $plan->getName(),
            'period' => $plan->getPeriod() . ' month(s)',
            'by invitation only' => $plan->isInvitationRequired() ? 'yes' : 'no',
            'price' => $price['amount'] . ' ' . $price['currency'],
        ));
        
        $output->addWikiText("==== Membership ====");
        $member = WpMember::GetFromWikiPlaceAndUser($wikiplace, $user);
        if ($member instanceof WpMember) {
            $output->addWikiText("Target user, {$user->getName()}, is a member of {$wikiplace->getName()}.");
            $output->addWikiText("Membership will be removed for ownership change.");
        } else {
            $output->addWikiText("{$user->getName()} is <b>NOT</b> a member of {$wikiplace->getName()}.");
        }

        $output->addWikiText("----");

        if ($confirm !== true) {

            $output->addWikiText("=== To confirm ===");
            $output->addHTML($this->getLink(self::ACTION_CHANGE_WIKIPLACE_OWNER, array(
                        'wikiplace' => $wikiplace_name,
                        'user' => $user_name
                            ), true));
        } else {
            if ($member instanceof WpMember) {
                $member->delete();
            }
            $wikiplace->setOwnerUserId($user->getId());
            $output->addWikiText("== Done ! ==");
        }
    }

    private function subscribeFor($plan_id = null, $user_name = null, $confirm = false) {
        $output = $this->getOutput();

        $output->addWikiText("=== Subscribe For ===");
        $this->prettyOutput($output, array(
            'plan' => $plan_id,
            'user' => $user_name,
        ));
        ;

        $output->addWikiText("----");

        if (empty($plan_id) || !is_numeric($plan_id)) {
            $output->addWikiText("=== Specify a plan id in integer format. ===");
            return;
        }

        $plan = WpPlan::newFromId($plan_id);
        if (is_null($plan)) {
            $output->addWikiText("=== No plan with that identifier was found. ===");
            return;
        }

        $output->addWikiText("=== Plan ===");
        $price = $plan->getPrice();
        $this->prettyOutput($output, array(
            'id' => $plan->getId(),
            'name' => $plan->getName(),
            'period' => $plan->getPeriod() . ' month(s)',
            'by invitation only' => $plan->isInvitationRequired() ? 'yes' : 'no',
            'price' => $price['amount'] . ' ' . $price['currency'],
        ));

        $output->addWikiText("----");

        if (empty($user_name)) {
            $output->addWikiText("=== Specify a user name. ===");
            return;
        }

        $user = User::newFromName($user_name);
        if (!$user || $user->getId() == 0) {
            $output->addWikiText("=== ERROR The user doesn't exist ! ===");
            return;
        }

        $output->addWikiText("=== User to subscribe for ===");
        $this->prettyOutput($output, array(
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'email confirmed' => $user->isEmailConfirmed() ? 'yes' : 'no',
            'timestamp of account creation' => $user->getRegistration(),
        ));

        if (!$user->isEmailConfirmed()) {
            $output->addWikiText("==== WARNING Email is not confirmed ! ====");
        }

        $last_subscription = WpSubscription::newByUserId($user->getId());

        if (is_null($last_subscription)) {
            $output->addWikiText("=== The user doesn't have any subscription. ===");
        } else {
            $output->addWikiText("==== Last subscription ====");
            $this->prettyOutput($output, array(
                'id' => $last_subscription->getId(),
                'starts' => $last_subscription->getStart(),
                'ends' => $last_subscription->getEnd(),
                'active' => $last_subscription->isActive() ? "yes" : "no",
                'transaction status' => $last_subscription->getTmrStatus(),
            ));

            if ($last_subscription->isActive()) {
                $output->addWikiText("==== ERROR Last subscription is still active ! ====");
                return;
            }

            $lastPlan = $last_subscription->getPlan();
            if (is_null($lastPlan)) {
                $output->addWikiText("=== ERROR The subscribed plan doesn't exist ! ===");
                return;
            }
            $output->addWikiText("==== Subscribed plan ====");
            $price = $lastPlan->getPrice();
            $this->prettyOutput($output, array(
                'id' => $lastPlan->getId(),
                'name' => $lastPlan->getName(),
                'period' => $lastPlan->getPeriod() . ' month(s)',
                'by invitation only' => $lastPlan->isInvitationRequired() ? 'yes' : 'no',
                'price' => $price['amount'] . ' ' . $price['currency'],
            ));
        }
        
        $check = WpSubscription::canSubscribe($user);
        if (is_string($check) ) {
            $output->addWikiText("=== ERROR The user cannot take a subscription ! ===");
            $output->addWikiText($check);
            return;
        } else {
            $output->addWikiText("=== The user can take a subscription. ===");
        }

        $output->addWikiText("----");

        if ($confirm !== true) {

            $output->addWikiText("=== To confirm ===");
            $output->addHTML($this->getLink(self::ACTION_SUBSCRIBER_FOR, array(
                        'plan' => $plan_id,
                        'user' => $user_name
                            ), true));
        } else {

            $subscription = WpSubscription::subscribe($user, $plan);

            if (is_null($subscription)) {
                $output->addWikiText("=== An error occured ! ===");
            } else {
                $output->addWikiText("== Done ! ==");
                $output->addWikiText("==== New subscription ====");
                $this->prettyOutput($output, array(
                    'id' => $subscription->getId(),
                    'buyer user id' => $subscription->getBuyerUserId(),
                    'starts' => $subscription->getStart(),
                    'ends' => $subscription->getEnd(),
                    'active' => $subscription->isActive() ? "yes" : "no",
                    'transaction status' => $subscription->getTmrStatus(),
                ));
            }
        }
    }

}
