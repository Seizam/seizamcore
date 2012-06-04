<?php

class WpSubscription {

    private $wps_id, // int(10) unsigned
    $wps_wpp_id, // int(10) unsigned
    $wps_buyer_user_id, // int(10) unsigned
    $wps_tmr_id, // int(10) unsigned
    $wps_tmr_status, // varchar(2)
    $wps_start_date, // datetime
    $wps_end_date, // datetime
    $wps_active, // tinyint(3) unsigned
    $wps_renew_wpp_id, // int(10) unsigned
    $wps_renewal_notified; // tinyint(3) unsigned
    private $attributes_to_update;

    /**
     *
     * @var WpPlan
     */
    private $plan;

    /**
     *
     * @var WpPlan
     */
    private $renewalPlan;

    /**
     *
     * @param type $id
     * @param type $name
     * @param type $periodMonths
     * @param type $price
     * @param type $currency
     * @param type $startDate
     * @param type $endDate
     * @param type $nbWikiplaces
     * @param type $nbWikiplacesPages
     * @param type $diskspace
     * @param type $monthlyPageHits
     * @param type $monthlyBandwidth 
     */
    private function __construct(
    $id, $planId, $buyerUserId, $transactionId, $transactionStatus, $startDate, $endDate, $active, $renewPlanId, $renewalNotified) {

        $this->wps_id = intval($id);
        $this->wps_wpp_id = intval($planId);
        $this->wps_buyer_user_id = intval($buyerUserId);
        $this->wps_tmr_id = intval($transactionId);
        $this->wps_tmr_status = $transactionStatus;
        $this->wps_start_date = $startDate;
        $this->wps_end_date = $endDate;
        $this->wps_active = $active !== '0';
        $this->wps_renew_wpp_id = intval($renewPlanId);
        $this->wps_renewal_notified = $renewalNotified !== '0';

        $this->attributes_to_update = array();
    }

    /**
     * Returns this subscription record identifier
     * @return int 
     */
    public function getId() {
        return $this->wps_id;
    }

    /**
     *
     * @return boolean 
     */
    public function isActive() {
        return $this->wps_active;
    }

    /**
     * Returns this subscription's plan identifier
     * @return int 
     */
    public function getPlanId() {
        return $this->wps_wpp_id;
    }

    /**
     * @return WpPlan the subscription's plan (and set if necessary)
     */
    public function getPlan() {
        if (!isset($this->plan))
            $this->setPlan();
        return $this->plan;
    }

    /**
     * Set plan object from wps_wpp_id
     */
    private function setPlan() {
        $this->plan = WpPlan::newFromId($this->getPlanId());
    }

    /**
     * Unset Plan object
     */
    private function unsetPlan() {
        unset($this->plan);
    }

    /**
     * Returns the buyer ID
     * @return int
     */
    public function getBuyerUserId() {
        return $this->wps_buyer_user_id;
    }

    /**
     * Returns the start date
     * @return string 
     */
    public function getStart() {
        return $this->wps_start_date;
    }

    /**
     * Returns the end date
     * @return string 
     */
    public function getEnd() {
        return $this->wps_end_date;
    }

    /**
     * Returns the TMR record identifier
     * @return int 
     */
    public function getTmrId() {
        return $this->wps_tmr_id;
    }

    /**
     * Returns the known TMR status for this subscription (doesn't check the TMR table)
     * @return string 
     */
    public function getTmrStatus() {
        return $this->wps_tmr_status;
    }

    /**
     * The renewal plan ID as stored in this subscription record (it doesn't ensures that
     * this plan can really be renewed to)
     * @return int 
     */
    public function getRenewalPlanId() {
        return $this->wps_renew_wpp_id;
    }

    /**
     * @return WpPlan The subscription's renewal plan and set if necessary
     */
    public function getRenewalPlan() {
        if (!isset($this->renewalPlan))
            $this->setRenewalPlan();

        return $this->renewalPlan;
    }

    /**
     * Set the renewal plan fro wps_renew_wpp_id
     */
    private function setRenewalPlan() {
        $this->renewalPlan = WpPlan::newFromId($this->getRenewalPlanId());
    }

    private function unsetRenewalPlan() {
        unset($this->renewalPlan);
    }

    /**
     * Tells if the email to notify, the buyer there is a problem with planned renewal, was already sent 
     * @return boolean 
     */
    public function isRenewalNotified() {
        return $this->wps_renewal_notified;
    }

    /**
     * Change the renewal plan and clear flag renewal_notified
     * @param int $plan_id 
     * @param boolean $flag_renewal_pb_reported Optional, set/clear flag renewal_notified. (default = clear)
     */
    public function setRenewalPlanId($plan_id, $flag_renewal_pb_reported = false) {
        $this->set('wps_renew_wpp_id', $plan_id, false);
        $this->unsetRenewalPlan();
        $this->setRenewalPlanNotified($flag_renewal_pb_reported);
    }

    /**
     * Change the renewal plan and clear flag renewal_notified
     * @param int $plan_id 
     * @param boolean $flag_renewal_pb_reported Optional, set/clear flag renewal_notified. (default = clear)
     */
    public function setRenewalPlanNotified($value = true) {
        $this->set('wps_renewal_notified', $value);
    }

    /**
     * 
     * @param string $attribut_name 
     * <ul>
     * <li>wps_renew_wpp_id</li>
     * <li>wps_tmr_id</li>
     * <li>wps_wpp_id</li>
     * <li>wps_active</li>
     * <li>wps_renewal_notified</li>
     * <li>wps_start_date</li>
     * <li>wps_end_date</li>
     * <li>wps_tmr_status</li>
     * </ul>
     * @param mixed $value
     * @param boolean $update_now By default, update the db now, but if multiple set() calls, the db can be updated only last time by setting 
     * this argument value to false for the first calls
     */
    public function set($attribut_name, $value, $update_now = true) {
        $db_value = null;
        switch ($attribut_name) {
            case 'wps_renew_wpp_id':
                if (!is_numeric($value) || ($value < 0)) {
                    throw new MWException('Value error (int >= 0 needed) for ' . $attribut_name);
                }
                $db_value = intval($value);
                $this->unsetRenewalPlan();
                break;
            case 'wps_wpp_id':
                if (!is_numeric($value) || ($value < 0)) {
                    throw new MWException('Value error (int >= 0 needed) for ' . $attribut_name);
                }
                $db_value = intval($value);
                $this->unsetPlan();
                break;
            case 'wps_tmr_id':
                if (!is_numeric($value) || ($value < 0)) {
                    throw new MWException('Value error (int >= 0 needed) for ' . $attribut_name);
                }
                $db_value = intval($value);
                break;
            case 'wps_active':
            case 'wps_renewal_notified';
                if (!is_bool($value)) {
                    throw new MWException('Value error (boolean needed) for ' . $attribut_name);
                }
                $db_value = ( $value ? 1 : 0 );
                break;
            case 'wps_start_date':
            case 'wps_end_date':
            case 'wps_tmr_status':
                if (!is_string($value)) {
                    throw new MWException('Value error (string needed) for ' . $attribut_name);
                }
                break;
            default:
                throw new MWException('Cannot change the value of attribut ' . $attribut_name);
        }

        $this->$attribut_name = $value;
        $this->attributes_to_update[$attribut_name] = ($db_value !== null) ? $db_value : $value; // used by wps_active to convert from boolean to int

        if ($update_now) {

            $dbw = wfGetDB(DB_MASTER);
            $dbw->begin();

            $success = $dbw->update(
                    'wp_subscription', $this->attributes_to_update, array('wps_id' => $this->wps_id));

            $dbw->commit();

            if (!$success) {
                throw new MWException('Error while updating Subscription to database.');
            }

            $this->attributes_to_update = array();
        }
    }

    /**
     * Renew the subscription. This method should be called by a cron, for ONLY each getAllOutdatedToRenew()<br/>
     * WARNING: this function assumes that the current subscription <b>can</b> AND <b>need</b> to be renewed<br/>
     * <b>only use it to renew the subscription when it ends normally</b> (this function doesn't re-credit
     * user account balance and it doesn't change the wikiplaces 'monthly tick')<br/>
     * It does:
     * <ul>
     * <li>load user and renewal plan (breaks if not found)<li>
     * <li>create a transaction</li>
     * <li>break if transaction is neither OK nor PE</li>
     * <li>archive the current subcription</li>
     * <li>update the current db record (start_date, ...) but wps_id stays untouched</li>
     * </ul>
     * Please note that if an error occured, the subscription wps_renew_wpp_id is set to DO_NOT_RENEW.
     * @return boolean/string True if ok, i18n message key string if an error occured:
     * <ul>
     * <li>'sz-internal-error' cannot find buyer user account (bad ID) or cannot find renewal plan (bad ID)</li>
     * <li>'wp-payment-error' tmr_status of new transaction is neither OK nor PE</li>
     * </ul>
     */
    public function renew() {

        // load user
        $user = User::newFromId($this->wps_buyer_user_id);

        // load renewal plan
        $next_plan = $this->getRenewalPlan();

        // ensure we know the user and renewal plan
        if (!$user->loadFromId() || ($next_plan == null)) {
            $this->set('wps_renew_wpp_id', WPP_ID_NORENEW);
            return 'sz-internal-error';
        }

        $user_email = $user->getEmail();

        // process even if the plan should not be taken ($this->wps_renew_wpp_id is supposed to be good)
        // payment
        $tmr = self::createTMR($user_id, $user_email, $next_plan);
        if (($tmr['tmr_status'] != 'OK') && ($tmr['tmr_status'] != 'PE')) { // not ( OK or PE ) so it cannot be renewed 			
            $this->set('wps_renew_wpp_id', WPP_ID_NORENEW);
            return 'sz-internal-error';
        }

        // everything is ok, let's renew!
        $this->archive();

        $start = self::calculateStartDateFromPreviousEnd($this->wps_end_date);
        $end = self::calculateEndDateFromStart($start, $next_plan->getPeriod());
        $renewal_plan_id = $next_plan->getRenewalPlan($end)->getId($end);

        $this->set('wps_wpp_id', $next_plan->getId(), false);
        $this->set('wps_tmr_id', $tmr['tmr_id'], false);
        $this->set('wps_tmr_status', $tmr['tmr_status'], false);
        $this->set('wps_start_date', $start, false);
        $this->set('wps_end_date', $end, false);
        $this->set('wps_renewal_notified', false, false); // clear this flag
        $this->set('wps_renew_wpp_id', $renewal_plan_id); // 3rd arg != false, so saving record now

        return true;
    }

    /**
     * Copy the subscriptions to the archive table
     * @param boolean $and_delete Do delete after archive?
     * @return boolean true if ok, false if an error occured
     */
    public function archive($and_delete = false) {

        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        // 3rd arg : must be an associative array of the form
        // array( 'dest1' => 'source1', ...). Source items may be literals
        // rather than field names, but strings should be quoted with
        // DatabaseBase::addQuotes()
        $success = $dbw->insertSelect('wp_old_subscription', 'wp_subscription', array(
            'wpos_wpp_id' => 'wps_wpp_id',
            'wpos_buyer_user_id' => 'wps_buyer_user_id',
            'wpos_tmr_id' => 'wps_tmr_id',
            'wpos_tmr_status' => 'wps_tmr_status',
            'wpos_start_date' => 'wps_start_date',
            'wpos_end_date' => 'wps_end_date'
                ), array('wps_id' => $this->wps_id), __METHOD__);

        $updated = $dbw->affectedRows();

        if (!$success || ( $updated != 1)) {
            return false;
        }

        if ($and_delete) {
            $success = $dbw->delete('wp_subscription', array('wps_id' => $this->wps_id), __METHOD__);
        }

        $dbw->commit();

        return $success;
    }

    /**
     * This function should be called by hook. 
     * Update the current subscription according to new tmr status. 
     * (can activate or unactive the subscription)
     * @param type $tmr
     * @return void
     */
    public function onTransactionUpdated($tmr) {

        switch ($this->wps_tmr_status) {

            case 'PE':
                // was pending
                switch ($tmr['tmr_status']) {

                    case 'OK':
                        // PE -> OK

                        if ($this->wps_start_date == null) {
                            // first subscription
                            $start = self::now();
                            $plan = $this->getPlan();
                            $period = $plan->getPeriod();
                            $end = self::calculateEndDateFromStart($start, $period);
                            $renewal_plan_id = $plan->getRenewalPlan($end)->getId();

                            $this->set('wps_start_date', $start, false); // 3rd param = false = do not update db now
                            $this->set('wps_end_date', $end, false);
                            $this->set('wps_active', true, false);
                            $this->set('wps_renew_wpp_id', $renewal_plan_id, false);
                            $this->set('wps_tmr_status', 'OK'); // no 3rd p = update db now

                            $user = User::newFromId($this->getBuyerUserId());
                            self::addSubscribersGroupToUser($user);
                            $this->sendOnFirstActivation();
                        } else {
                            // if startDate not null, this is a renewal so it's already activated
                            $this->set('wps_tmr_status', 'OK'); // no 3rd p = update db now
                        }

                        return false; // this is our transaction, no more hook process to be done	

                    case 'KO':
                        // PE -> KO
                        $this->set('wps_tmr_status', 'KO', false);
                        $this->set('wps_end_date', self::now(), false);
                        $this->set('wps_active', false);  // in case of a renewal, it can be activated even if pending, so need to ensure that is false

                        return false; // this is our transaction, no more process to be done	

                    case 'PE':
                        // PE -> PE   =>   don't care
                        return false;
                }
                break;
        }

        // if we arrive here, this transaction is about a subscription, but we do not know what to do
        wfDebugLog('wikiplaces', 'onTransactionUpdated: ERROR new status is not managed,'
                . ' tmr_id=' . $tmr['tmr_id']
                . ' wps_id=' . $this->wps_id
                . ' old_tmr_status=' . $this->wps_tmr_status
                . ' new_tmr_status=' . $tmr['tmr_status']);
    }

    /**
     * Contruct a new instance from a SQL row
     * @param ResultWrapper $row
     * @return WpSubscription 
     */
    public static function constructFromDatabaseRow($row) {

        if ($row === null) {
            throw new MWException('Cannot construct the Subscription from the supplied row (null given).');
        }

        // wps_start_date and wps_end_date can be null, but nothing else
        if (!isset($row->wps_id) || !isset($row->wps_wpp_id) || !isset($row->wps_buyer_user_id) ||
                !isset($row->wps_tmr_id) || !isset($row->wps_tmr_status) ||
                !isset($row->wps_active) || !isset($row->wps_renew_wpp_id) || !isset($row->wps_renewal_notified)) {
            throw new MWException('Cannot construct the Subscription from the supplied row (missing field).');
        }

        return new self($row->wps_id, $row->wps_wpp_id, $row->wps_buyer_user_id, $row->wps_tmr_id, $row->wps_tmr_status, $row->wps_start_date, $row->wps_end_date, $row->wps_active, $row->wps_renew_wpp_id, $row->wps_renewal_notified);
    }

    /**
     * Get the subscription associated to the given TMR_id
     * @param int $id TMR_id
     * @return WpSubscription The requested WpSubscription, or null if none associated to the TMR_id 
     */
    public static function newFromTransactionId($id) {
        if (($id === null) || !is_numeric($id) || ($id < 1)) {
            throw new MWException('Cannot search subscription, invalid transaction identifier.');
        }

        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow('wp_subscription', '*', array('wps_tmr_id' => $id), __METHOD__);

        if ($result === false) {
            // not found, so return null
            return null;
        }

        return self::constructFromDatabaseRow($result);
    }

    /**
     * Get the subscription having the given id
     * @param int $id 
     * @return WpSubscription if found, or null if not
     */
    public static function newFromId($id) {

        if (($id === null) || !is_numeric($id) || ($id < 1)) {
            throw new MWException('Cannot search subscription, invalid identifier.');
        }

        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->selectRow('wp_subscription', '*', array('wps_id' => $id), __METHOD__);

        if ($result === false) {
            // not found, so return null
            return null;
        }

        return self::constructFromDatabaseRow($result);
    }

    /**
     * Get the last subscription of a user, which can be unactive.
     * @param int $user_id
     * @return WpSubscription The user active subscription or null if she has none 
     */
    public static function newByUserId($user_id) {

        if (($user_id === null) || !is_numeric($user_id) || ($user_id < 1)) {
            throw new MWException('Cannot search subscription, invalid user identifier.');
        }

        $dbr = wfGetDB(DB_SLAVE);

        $conds = $dbr->makeList(array(
            "wps_buyer_user_id" => $user_id,
                ), LIST_AND);

        $result = $dbr->selectRow('wp_subscription', '*', $conds, __METHOD__);

        if ($result === false) {
            return null;
        }

        return self::constructFromDatabaseRow($result);
    }

    /**
     * Can the user take a subscription? (not renewal or a plan change, but a simple subscription)
     * Can be a first subscription, or a new subscription with an unactive ended one
     * @param User $user
     * @return boolean/string true = can subscribe , string = reason (i18n message key) why cannot subscribe:
     * <ul>
     * <li>wp-subscribe-loggedout</li>User need to be logged in to subscribe
     * <li>wp-subscribe-email</li>User has not yet confirmed her email address
     * <li>wp-subscribe-already</li>User has already an active or a "payment pending" subscription
     * </ul>
     */
    public static function canSubscribe($user) {

        if (!$user instanceof User) {
            throw new MWException('Invalid user argument.');
        }

        if (!$user->isLoggedIn()) {
            return 'wp-subscribe-loggedout';
        }

        if (!$user->isEmailConfirmed()) {
            return 'wp-subscribe-email';
        }

        $dbr = wfGetDB(DB_MASTER);

        $now = $dbr->addQuotes(self::now());
        $conds = $dbr->makeList(array(
            "wps_buyer_user_id" => $user->getId(),
            $dbr->makeList(array(
                "wps_active" => 1,
                $dbr->makeList(array(
                    "wps_active" => 0,
                    "wps_tmr_status != 'KO'",
                    $dbr->makeList(array(
                        "wps_start_date IS NULL",
                        "wps_start_date >= $now",
                            ), LIST_OR),
                        ), LIST_AND)
                    ), LIST_OR)
                ), LIST_AND);

        $results = $dbr->select('wp_subscription', '*', $conds, __METHOD__);

        if ($dbr->numRows($results) != 0) {
            return 'wp-subscribe-already';
        }

        return true;
    }

    /**
     * get current actives, having a renewal plan selected, with end_date < $when
     * @param string $when MySQL datetime string (can be WpSubscription::getNow() )
     * @return Array Array of WpSubscription
     */
    public static function factoryActiveEndSoonToNotify($when) {

        $dbr = wfGetDB(DB_MASTER);
        $when = $dbr->addQuotes($when);
        $conds = $dbr->makeList(array(
            /* 'wps_renew_wpp_id != 0', */
            'wps_active ' => 1,
            'wps_renewal_notified' => 0,
            "wps_end_date < $when"
                ), LIST_AND);

        $results = $dbr->select(
                array('wp_subscription', 'wp_plan'), '*', $conds, __METHOD__, array(), array('wp_plan' => array('LEFT JOIN', 'wps_renew_wpp_id = wpp_id')));

        $subs = array();
        foreach ($results as $row) {
            $sub = self::constructFromDatabaseRow($row);
            $subs[] = $sub;
        }

        return $subs;
    }

    /**
     * get current actives, having a renewal plan selected, with end_date < $when
     * @param string $when MySQL datetime string (can be WpSubscription::getNow() )
     * @return Array Array of WpSubscription
     */
    public static function factoryAllOutdatedToRenew($when) {

        $dbr = wfGetDB(DB_MASTER);
        $when = $dbr->addQuotes($when);
        $conds = $dbr->makeList(array(
            'wps_renew_wpp_id != 0',
            'wps_active ' => 1,
            "wps_end_date < $when"
                ), LIST_AND);

        $results = $dbr->select(
                array('wp_subscription', 'wp_plan'), '*', $conds, __METHOD__, array(), array('wp_plan' => array('LEFT JOIN', 'wps_renew_wpp_id = wpp_id')));

        $subs = array();
        foreach ($results as $row) {
            $sub = self::constructFromDatabaseRow($row);
            $subs[] = $sub;
        }

        return $subs;
    }

    /**
     * Unactive all subscriptions having wps_end_date < $now. Their records are not archived
     * because they can still have Wikiplaces attached to them.
     * @param string $now SQL DATETIME (can be WpSubscription::getNow() )
     * @return int/boolean nb of unactivated subscriptions if ok, "false" if an error occured
     */
    public static function deactivateAllOutdated($now = null) {

        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        $now = $dbw->addQuotes($now);

        // renewing all active outdated records
        $success = $dbw->update(
                'wp_subscription', array('wps_active' => 0), // value
                array('wps_active' => 1, "wps_end_date < $now"), // conds
                __METHOD__ );

        if (!$success) {
            return false;
        }

        $updated = $dbw->affectedRows();

        $dbw->commit();

        return $updated;
    }

    /**
     * Subscribe to a plan (= no current active plan)
     * WARNING, you should ensure the user can subscribe before calling this: use canSubscribe() and canSubscribeTo()
     * @param User $use The user who buy the plan, and will use it 
     * @param WpPlan $plan
     * @return WpSubscription the newly created subscription if ok, or null if an error occured (db error)
     */
    public static function subscribe($user, $plan) {

        if (($user === null) || !($user instanceof User) ||
                ($plan === null) || !($plan instanceof WpPlan)) {
            throw new MWException('Cannot subscribe, invalid argument.');
        }

        $user_id = $user->getId();
        $db_master = $dbw = wfGetDB(DB_MASTER);

        // archive the current sub if necessary
        // not that even if this sub is active, it will be archived
        // so, be sure that you need to call this subscribe() !
        $current_sub = self::newByUserId($user_id);

        $tmr = self::createTMR($user_id, $user->getEmail(), $plan);

        // already paid, or waiting a payment ?
        switch ($tmr['tmr_status']) {

            case 'OK': // already paid by user
                $now = self::now();
                if ($current_sub != null) {
                    $current_sub->archive(true);
                }
                $end = self::calculateEndDateFromStart($now, $plan->getPeriod());
                $renewal_plan_id = $plan->getRenewalPlan($end)->getId();
                $sub = self::create(
                                $plan->getId(), $user_id, $tmr['tmr_id'], 'OK', // paid
                                $now, // start
                                $end, // end
                                true, // active
                                $renewal_plan_id, false, // no email sent about renewal
                                $db_master
				);
                if ($sub == null) {
                    return null;
                }
                self::addSubscribersGroupToUser($user);
                $sub->sendOnFirstActivation();
                return $sub;

            case 'PE': // waiting payment
                if ($current_sub != null) {
                    $current_sub->archive(true);
                }
                return self::create(
                                $plan->getId(), $user_id, $tmr['tmr_id'], 'PE', // not paid
                                null, // will start when paid
                                null, // unknown for now
                                false, // not active
                                WPP_ID_NORENEW, // renewal, this value need to be updated as soon as we know the start date
                                false, // no email sent about renewal
                                $db_master
				);
        }

        // if we arrive here, the payment status is unknown
        wfDebugLog('wikiplaces', 'WpSubscription::subscribe() ERROR the transaction status is not handled: "' . $tmr['tmr_status'] . '" of tmr_id[' . $tmr['tmr_id'] . ']');

        return null;
    }

    /**
     * Put the user in the effective group 'artist' if she is not already in.
     * @param User $user 
     * @return boolean false if she is already in the group, true if just added
     */
    private static function addSubscribersGroupToUser($user) {
        if (!in_array(WP_SUBSCRIBERS_USER_GROUP, $user->getGroups())) {
            $user->addGroup(WP_SUBSCRIBERS_USER_GROUP);
            return true;
        }
        return false;
    }

    /**
     * Create a transaction record and return it. 
     * @param int $user_id
     * @param string $user_email
     * @param WpPlan $plan
     * @return array TMR as array
     */
    private static function createTMR($user_id, $user_email, $plan) {

        $price = $plan->getPrice();

        $tmr = array(
            # Params related to Message
            'tmr_type' => WP_SUBSCRIPTION_TMR_TYPE,
            # Paramas related to User
            'tmr_user_id' => $user_id,
            'tmr_mail' => $user_email,
            'tmr_ip' => IP::sanitizeIP(wfGetIP()),
            # Params related to Record
            'tmr_amount' => - $price['amount'],
            'tmr_currency' => $price['currency'],
            'tmr_desc' => 'wpp-' . $plan->getName(),
            'tmr_status' => 'PE', // PEnding
        );

        wfRunHooks('CreateTransaction', array(&$tmr));

        return $tmr;
    }

    /**
     *
     * @param type $planId
     * @param type $buyerUserId
     * @param type $transactionId
     * @param type $transactionStatus
     * @param type $startDate
     * @param type $endDate
     * @param type $active
     * @param type $db_master The wfGetDB(DB_MASTER) if already have (avoid multiple master db connection)
     * @return the created WpSubscription, or null if a db error occured
     */
    private static function create($planId, $buyerUserId, $transactionId, $transactionStatus, $startDate, $endDate, $active, $renewPlanId, $renewalNotified, $db_master = null) {

        if (($planId === null) || ($buyerUserId === null) || ($transactionId === null) ||
                ($transactionStatus === null) || ($active === null) || ($renewPlanId === null)) {
            throw new MWException('Cannot create Subscription, missing argument.');
        }

        if (!is_numeric($planId) || !is_numeric($buyerUserId) || !is_numeric($transactionId) || !is_string($transactionStatus) ||
                ( ($startDate !== null) && !preg_match('/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $startDate) ) ||
                ( ($endDate !== null) && !preg_match('/^(\d{4})\-(\d\d)\-(\d\d) (\d\d):(\d\d):(\d\d)$/D', $endDate) ) ||
                !is_bool($active) || !is_bool($renewalNotified) ||
                !is_numeric($renewPlanId)) {
            throw new MWException('Cannot create Subscription, invalid argument.');
        }

        $dbw = ( ($db_master != null) ? $db_master : wfGetDB(DB_MASTER) );
        $dbw->begin();

        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_subscription_wps_id_seq');

        $success = $dbw->insert('wp_subscription', array(
            'wps_id' => $id,
            'wps_wpp_id' => $planId,
            'wps_buyer_user_id' => $buyerUserId,
            'wps_tmr_id' => $transactionId,
            'wps_tmr_status' => $transactionStatus,
            'wps_start_date' => $startDate,
            'wps_end_date' => $endDate,
            'wps_active' => $active ? 1 : 0,
            'wps_renew_wpp_id' => $renewPlanId,
            'wps_renewal_notified' => $renewalNotified ? 1 : 0
                ));

        // Setting id from auto incremented id in DB
        $id = $dbw->insertId();

        $dbw->commit();

        if (!$success) {
            return false;
        }

        return new self($id, $planId, $buyerUserId,
                        $transactionId, $transactionStatus,
                        $startDate, $endDate, $active, $renewPlanId, $renewalNotified);
    }

    /**
     *
     * @param string $start_date MySQL DATETIME formated date
     * @param type $nb_of_month 
     * @return string MySQL DATETIME formated end date
     */
    public static function calculateEndDateFromStart($start_date, $nb_of_month) {

        $end = date_create_from_format('Y-m-d H:i:s', $start_date, new DateTimeZone('GMT'));
        if ($end->format('j') > 28) { // if day > 28
            $end->modify('first day of next month');
        }
        $end->modify("+$nb_of_month month -1 second");
        return $end->format('Y-m-d H:i:s');
    }

    /**
     *
     * @param string $start_date MySQL DATETIME formated date
     * @param type $nb_of_month 
     * @return string MySQL DATETIME formated start date
     */
    public static function calculateStartDateFromPreviousEnd($previous_end_date) {

        $start = date_create_from_format('Y-m-d H:i:s', $previous_end_date, new DateTimeZone('GMT'));
        $start->modify("+1 second");
        return $start->format('Y-m-d H:i:s');
    }

    /**
     *
     * @param int $seconds + or - seconds shift
     * @param int $minutes + or - minutes shift
     * @param int $hours + or - hours shift
     * @param int $days + or - $days shift
     * @return string MySQL DATETIME string
     */
    public static function now($seconds = 0, $minutes = 0, $hours = 0, $days = 0) {

        if (!is_int($seconds) || !is_int($minutes) || !is_int($hours) || !is_int($days)) {
            throw new MWException("Cannot compute 'now with delay', invalid argument.");
        }

        $start = new DateTime('now', new DateTimeZone('GMT'));

        if (($seconds != 0) || ($minutes != 0) || ($hours != 0) || ($days != 0)) {
            $start->modify("$seconds second $minutes minute $hours hour $days day");
        }

        return $start->format('Y-m-d H:i:s');
    }

    /**
     * Get the active subscription of a user
     * @param int $user_id
     * @return WpSubscription The user active subscription or null if she has no active one 
     */
    public static function newActiveByUserId($user_id) {

        if (($user_id === null) || !is_numeric($user_id) || ($user_id < 1)) {
            throw new MWException('Cannot search subscription, invalid user identifier.');
        }

        $dbr = wfGetDB(DB_SLAVE);

        $now = $dbr->addQuotes(self::now());
        $conds = $dbr->makeList(array(
            "wps_buyer_user_id" => $user_id,
            "wps_active" => 1,
                ), LIST_AND);

        $result = $dbr->selectRow('wp_subscription', '*', $conds, __METHOD__);

        if ($result === false) {
            return null;
        }

        return self::constructFromDatabaseRow($result);
    }

    /**
     * Check the user has an active subscription, page creation quota is not exceeded and
     * diskpace quota is not exceeded.
     * @param int $user_id
     * @return boolean/string True if user can, string message explaining why she can't
     * <ul>
     * <li><b>wp-no-active-sub</b> user has no active subscription</li>
     * <li><b>wp-page-quota-exceeded</b> page quota exceeded</li>
     * <li><b>wp-diskspace-quota-exceeded</b> diskspace quota exceeded</li>
     * </ul>
     */
    public static function userCanUploadNewFile($user_id) {

        $sub = self::newActiveByUserId($user_id);

        if ($sub === null) {
            return 'wp-no-active-sub';
        }

        $plan = $sub->getPlan();

        $max_pages = $plan->getNbWikiplacePages();
        $user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

        if ($user_pages_nb >= $max_pages) {
            return 'wp-page-quota-exceeded';
        }

        $max_diskspace = $plan->getDiskspace();
        $user_diskspace_usage = WpPage::countDiskspaceUsageByUser($user_id);

        if ($user_diskspace_usage >= $max_diskspace) {
            return 'wp-diskspace-quota-exceeded';
        }

        return true;
    }

    /**
     * Check the user has an active subscription and page creation quota is not exceeded
     * @param int $user_id
     * @return boolean/string True if user can, string message explaining why she can't
     * <ul>
     * <li><b>wp-no-active-sub</b> user has no active subscription</li>
     * <li><b>wp-page-quota-exceeded</b> page quota exceeded</li>
     * </ul>
     */
    public static function userCanCreateNewPage($user_id) {

        global $wgLang;

        $sub = self::newActiveByUserId($user_id);

        if ($sub === null) {
            return 'wp-no-active-sub';
        }

        $max_pages = $sub->getPlan()->getNbWikiplacePages();
        $user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

        if ($user_pages_nb >= $max_pages) {
            return 'wp-page-quota-exceeded';
        }

        return true;
    }

    /**
     * Check the user has an active subscription and 
     * wikiplace creation quota is not exceeded and 
     * page creation quota is not exceeded
     * @param type $user_id
     * @return boolean/string True if user can, string message explaining why she can't:
     * <ul>
     * <li><b>wp-no-active-sub</b> user has no active subscription</li>
     * <li><b>wp-wikiplace-quota-exceeded</b> wikiplace creation quota exceeded</li>
     * <li><b>wp-page-quota-exceeded</b> page creation quota exceeded</li>
     * </ul>
     */
    public static function userCanCreateWikiplace($user_id) {

        if (!is_int($user_id) || ($user_id < 1)) {
            throw new MWException('Cannot check if user can create a Wikiplace, invalid user identifier.');
        }

        $sub = self::newActiveByUserId($user_id);
        if ($sub == null) {
            return 'wp-no-active-sub';
        }

        $plan = $sub->getPlan();

        $max_wikiplaces = $plan->getNbWikiplaces();
        $user_wikiplaces_nb = WpWikiplace::countWikiplacesOwnedByUser($user_id);

        if ($user_wikiplaces_nb >= $max_wikiplaces) {
            return 'wp-wikiplace-quota-exceeded';
        }

        $max_pages = $plan->getNbWikiplacePages();
        $user_pages_nb = WpPage::countPagesOwnedByUser($user_id);

        if ($user_pages_nb >= $max_pages) {
            return 'wp-page-quota-exceeded';
        }

        return true; // all ok
    }

    /**
     *
     * @param User $user
     * @param Message $subject
     * @param Message $body 
     */
    private static function timeAndDateUserLocalized($user, $timeanddate) {

        $language = Language::factory($user->getOption('language'));
        $time_correction = $user->getOption('timecorrection');
        $date_format = $user->getOption('date');
        return $language->timeanddate($timeanddate, true, $date_format, $time_correction);
    }

    /**
     *
     * @param User $user
     * @param Message $subject
     * @param Message $body 
     */
    private static function sendEmailToUserLocalized($user, $subject, $body) {

        $language = $user->getOption('language');

        $subject = $subject->inLanguage($language)->text();
        $body = $body->inLanguage($language)->text();
        $body = wfMessage('wp-mail-header', $user->getName())->inLanguage($language)->text() . $body;
        $body .= wfMessage('wp-mail-footer')->inLanguage($language)->text();

        $ok = false;

        try {

            $ok = $user->sendMail($subject, $body)->isGood();
        } catch (Exception $e) {
            wfDebugLog('wikiplaces', 'sendEmailToUserLocalized(): ERROR SENDING EMAIL "' . $e->getMessage() . '",'
                    . ' subject="' . $subject . '" to user[' . $user->getId() . ']"' . $user->getName() . '"');
        }

        return $ok;
    }

    /**
     * Send an email when the first subscription is activated, when:
     * <ul>
     * <li>On the fly when user makes a first subscription, and she has enought to pay</li>
     * <li>Delayed, when user makes a first subscription, and credit her account after</li>
     * </ul>
     * @return boolean true=ok, false=error 
     */
    public function sendOnFirstActivation() {

        $user = User::newFromId($this->wps_buyer_user_id);
        $plan = $this->getPlan();

        $subject = wfMessage('wpm-activation-subj');
        $body = wfMessage('wpm-activation-body', $plan->getName(), self::timeAndDateUserLocalized($user, $this->wps_start_date), self::timeAndDateUserLocalized($user, $this->wps_end_date));

        return self::sendEmailToUserLocalized($user, $subject, $body);
    }

    /**
     * Send an email when a subscription will end soon and has no renewal.
     * @return boolean true=ok, false=error 
     */
    public function sendOnNoRenewalSoon() {

        $user = User::newFromId($this->wps_buyer_user_id);
        $plan = $this->getPlan();

        $subject = wfMessage("wpm-renewal-soon-no-subj");
        $body = wfMessage("wpm-renewal-soon-no-body", $plan->getName(), self::timeAndDateUserLocalized($user, $this->wps_start_date), self::timeAndDateUserLocalized($user, $this->wps_end_date));

        return self::sendEmailToUserLocalized($user, $subject, $body);
    }

    /**
     * Send an email when a subscription renewal will occur soon and the renewal plan should not pass.
     * @param string $reason i18 message key, should be 'wp-insufficient-quota' or 'wp-plan-not-available-renewal'
     * @return boolean true=ok, false=error 
     */
    public function sendOnRenewalSoonWarning($reason, $old_next_plan) {

        $user = User::newFromId($this->wps_buyer_user_id);
        $plan = $this->getPlan();
        $next_plan = $this->getRenewalPlan();

        $subject = wfMessage("wpm-renewal-soon-warning-subj");
        $body = wfMessage("wpm-renewal-soon-warning-body", $plan->getName(), self::timeAndDateUserLocalized($user, $this->wps_start_date), self::timeAndDateUserLocalized($user, $this->wps_end_date), $old_next_plan->getName(), $next_plan->getName(), $reason);

        return self::sendEmailToUserLocalized($user, $subject, $body);
    }

    /**
     * Send an email when a subscription renewal will occur soon and seems to be ok.
     * @return boolean true=ok, false=error 
     */
    public function sendOnRenewalSoonValid() {

        $user = User::newFromId($this->wps_buyer_user_id);
        $plan = $this->getPlan();
        $next_plan = $this->getRenewalPlan();

        $subject = wfMessage("wpm-renewal-soon-valid-subj");
        $body = wfMessage("wpm-renewal-soon-valid-body", $plan->getName(), self::timeAndDateUserLocalized($user, $this->wps_start_date), self::timeAndDateUserLocalized($user, $this->wps_end_date), $next_plan->getName());

        return self::sendEmailToUserLocalized($user, $subject, $body);
    }

    /**
     * Send an email when a subscription has been renewed, but the transaction is PE
     * @return boolean true=ok, false=error 
     */
    public function sendOnPlanRenewalPE() {

        $user = User::newFromId($this->wps_buyer_user_id);
        $plan = $this->getPlan();

        $subject = wfMessage('wpm-renewal-pe-subj');
        $body = wfMessage('wpm-renewal-pe-body', $plan->getName(), self::timeAndDateUserLocalized($user, $this->wps_start_date), self::timeAndDateUserLocalized($user, $this->wps_end_date));

        return self::sendEmailToUserLocalized($user, $subject, $body);
    }

    /**
     * Send an email when a subscription has been renewed, and the transaction is OK
     * @return boolean true=ok, false=error 
     */
    public function sendOnPlanRenewalOK() {

        $user = User::newFromId($this->wps_buyer_user_id);
        $plan = $this->getPlan();

        $subject = wfMessage('wpm-renewal-ok-subj');
        $body = wfMessage('wpm-renewal-ok-body', $plan->getName(), self::timeAndDateUserLocalized($user, $this->wps_start_date), self::timeAndDateUserLocalized($user, $this->wps_end_date));

        return self::sendEmailToUserLocalized($user, $subject, $body);
    }

}