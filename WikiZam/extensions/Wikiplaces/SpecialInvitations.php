<?php

class SpecialInvitations extends SpecialPage {
    const TITLE_NAME = 'Invitations';

    const ACTION_LIST = 'List';
    const ACTION_CREATE = 'Create';

    private $action = null;
    private $category_id = null;
    private $msgType = null;
    private $msg = null;

    /**
     * @var WpInvitationCategory[]
     */
    private $userInvitationsCategories = null;

    /**
     * Array wpic_id => used this month
     */
    private $userUsageThisMonth = null;

    /**
     * Array wpic_id => left this month
     */
    private $userUsageLeftThisMonth = null;
    private $userIsAdmin = false;
    private $userActiveSubscription = null;
    private $sentTo = null;

    public function __construct() {
        parent::__construct(self::TITLE_NAME, WP_ACCESS_RIGHT);
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

        if (isset($par) & $par != '') {
			$explosion = explode(':', $par);
			if (count($explosion) == 1) {
				$this->action = $explosion[0];
                $this->category_id = $this->getRequest()->getText('category', null);
			} else if (count($explosion) == 2) {
				$this->action = $explosion[0];
				$this->category_id = $explosion[1];
			}
		} else {
			$this->action = $this->getRequest()->getText('action', null);
			$this->category_id = $this->getRequest()->getText('category', null);
		}

        if ($user->isAllowed(WP_ADMIN_RIGHT)) {
            $this->userIsAdmin = true;
            $this->userInvitationsCategories = WpInvitationCategory::factoryAllAvailable(true); // with admin categories
        } else {
            $this->userIsAdmin = false;
            $this->userActiveSubscription = WpSubscription::newActiveByUserId($user->getId());
            $this->userInvitationsCategories = WpInvitationCategory::factoryAllAvailable(false); // without admin categories
            $this->setUsageThisMonth();
            $this->setUsageLeftThisMonth();
        }

        $this->msgType = $request->getText('msgtype', null);
        $msgKey = $request->getText('msgkey', null);
        if ($msgKey != null) {
			$msg = wfMessage($msgKey);
			if ($msg->exists()) {
				$this->msg = $msg;
			}
        }

        $this->display();
    }

    private function display() {

        $output = $this->getOutput();


        // Display requested message.
        if ($this->msg instanceof Message) {
            $output->addHTML(Html::rawElement(
                            'div', array('class' => "informations " . (($this->msgType != null) ? $this->msgType : '')), $this->msg->parse()));
        }

        // dispatch
        switch (strtolower($this->action)) {
            case strtolower(self::ACTION_CREATE):
                $this->displayCreate();
                break;
            case strtolower(self::ACTION_LIST):
            default:
                $this->displayList();
                break;
        }
    }

    /**
     *
     * @return Message The i18n error message, or true if no error
     */
    private function getCanCreateError() {

        if ((!$this->userIsAdmin ) && (!$this->userActiveSubscription instanceof WpSubscription )) {
            return wfMessage('wp-no-active-sub');
        } elseif (empty($this->userInvitationsCategories)) {
            return wfMessage('wp-inv-no');
        } elseif (!$this->userIsAdmin) {
            if (empty($this->userUsageLeftThisMonth)) {
                return wfMessage('wp-inv-limitreached');
            }
        }

        return true;
    }

    private function displayCreate() {

        $error = $this->getCanCreateError();
        if ($error instanceof Message) {
            $this->msgType = 'error';
            $this->msg = $error;
            $this->action = self::ACTION_LIST;
            $this->display();
            return;
        }

        $inviteForm = $this->getInviteForm();
        if ($inviteForm->show()) {

            $this->msgType = 'success';
            if ($this->sentTo != null) {
                $this->msg = wfMessage('wp-inv-success-sent', $this->sentTo);
            } else {
                $this->msg = wfMessage('wp-inv-success');
            }

            $this->action = self::ACTION_LIST;
            $this->display();
            return;
        }
    }

    /**
     * Set $this->userUsageThisMonth from DB
     */
    private function setUsageThisMonth() {
        $this->userUsageThisMonth = WpInvitation::getUsageForUserThisMonth($this->getUser());
    }

    /**
     * Set $this->userUsageLeftThisMonth from $this->userUsageThisMonth
     */
    private function setUsageLeftThisMonth() {
        $this->userUsageLeftThisMonth = array();
        foreach ($this->userInvitationsCategories as $category_id => $category) {
            $left = $category->getMonthlyLimit() - (isset($this->userUsageThisMonth[$category_id]) ? $this->userUsageThisMonth[$category_id] : 0);
            if ($left > 0)
                $this->userUsageLeftThisMonth[$category_id] = $left;
        }
    }

    /**
     *
     * @param int $category_id 
     */
    private function incrementUsageThisMonth($category_id) {
        if (isset($this->userUsageThisMonth[$category_id])) {
            $this->userUsageThisMonth[$category_id]++;
        } else {
            $this->userUsageThisMonth[$category_id] = 1;
        }
    }

    private function displayList() {

        // display invitiation list of this user
        $inviteList = new WpInvitationsTablePager();

        $inviteList->setHeader(wfMessage('wp-inv-list-header')->text().($this->getCanCreateError()===TRUE?' '.wfMessage('wp-inv-create')->parse():''));
        $inviteList->setFooter(wfMessage('wp-inv-list-footer')->parse().$this->constructFooterUlHtml().wfMessage('wp-inv-list-help')->parse());
        
        $inviteList->setSelectConds(array(
            'wpi_from_user_id' => $this->getUser()->getID(),
        ));

        $this->getOutput()->addHTML($inviteList->getWholeHtml());
    }
    
    private function constructFooterUlHtml() {
        $html = '<ul>';
        foreach ($this->userInvitationsCategories as $category_id => $category) {
            $html .= '<li>';
            $html .= wfMessage('wp-inv-list-footer-li',
                    $category_id,
                    $category->getDescription(),
                    isset($this->userUsageLeftThisMonth[$category_id]) ? $this->userUsageLeftThisMonth[$category_id] : 0,
                    $category->getMonthlyLimit()
                    )->parse();
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     *
     * @return \HTMLFormS 
     */
    private function getInviteForm() {

        $user = $this->getUser();
        $signature = ($user->getRealName() == '' ? $user->getName() : $user->getRealName());

        $formDesc = array(
            'Category' => array(
                'type' => 'select',
                'label-message' => 'wp-inv-category-field',
                'section' => 'create-section',
                'help-message' => 'wp-inv-category-help',
                'options' => array(),
            ),
            'Email' => array(
                'type' => 'text',
                'label-message' => 'emailto',
                'section' => 'mail-section',
                'help-message' => 'wp-inv-email-help',
                'validation-callback' => array($this, 'validateEmail'),
            ),
            'Message' => array(
                'type' => 'textarea',
                'label-message' => 'emailmessage',
                'section' => 'mail-section',
                'help-message' => 'wp-inv-msg-help',
                'default' => wfMessage('wp-inv-msg-default', $signature)->text(),
                'rows' => 4,
            ),
            'Language' => array(
                'type' => 'select',
                'label-message' => 'wp-inv-language-field',
                'section' => 'mail-section',
                'help-message' => 'wp-inv-language-help',
                'options' => array(),
            )
        );

        global $wgLanguageSelectorLanguagesShorthand;
        $language = $this->getLanguage();
        foreach ($wgLanguageSelectorLanguagesShorthand as $ln) {
            $formDesc['Language']['options'][$language->getLanguageName($ln)] = $ln;
            if ($ln == $language->getCode()) {
                $formDesc['Language']['default'] = $ln;
            }
        }

        if ($this->userIsAdmin) {

            $formDesc['Code'] = array(
                'type' => 'text',
                'label-message' => 'wp-inv-code-field',
                'help-message' => 'wp-inv-code-help',
                'default' => WpInvitation::generateCode($this->getUser()),
                'validation-callback' => array($this, 'validateCode'),
            );
            $formDesc['Counter'] = array(
                'type' => 'text',
                'label-message' => 'wp-inv-counter-field',
                'help-message' => 'wp-inv-counter-help',
                'default' => 1,
                'validation-callback' => array($this, 'validateCounter'),
            );
            foreach ($this->userInvitationsCategories as $category) {
                $formDesc['Category']['options'][$category->getDescription()] = $category->getId();
            }
        } else {

            foreach ($this->userUsageLeftThisMonth as $category_id => $usageLeft) {
                $formDesc['Category']['options'][wfMessage(
                                'wp-inv-category-desc', wfMessage($this->userInvitationsCategories[$category_id]->getDescription())->text(), $usageLeft, $this->userInvitationsCategories[$category_id]->getMonthlyLimit()
                        )->parse()] = $category_id;
            }
        }


        $htmlForm = new HTMLFormS($formDesc);
        $htmlForm->setMessagePrefix('wp-inv');
        $htmlForm->addHeaderText(wfMessage('wp-inv-create-header')->parse());
        $htmlForm->setTitle($this->getTitle(self::ACTION_CREATE));
        $htmlForm->setSubmitText(wfMessage('wp-create')->text());

        $htmlForm->setSubmitCallback(array($this, 'processInvite'));

        return $htmlForm;
    }

    public function processInvite($formData) {

        $user = $this->getUser();

        $category = $this->userInvitationsCategories[$formData['Category']];
        if (!$category instanceof WpInvitationCategory) {
            return wfMessage('sz-internal-error')->text();
        }

        $email = null;
        if ($formData['Email'] != '') {
            $email = $formData['Email'];
        }

        $message = $formData['Message'];

        if ($this->userIsAdmin) {
            $code = $formData['Code'];
            $counter = $formData['Counter'];
        } else {
            $code = WpInvitation::generateCode($user);
            $counter = 1;
        }

        $language = $formData['Language'];

        $invitation = WpInvitation::create($category->getId(), $user, $code, $email, $counter);
        if (!$invitation instanceof WpInvitation) {
            return wfMessage('sz-internal-error')->text();
        }

        if (!$this->userIsAdmin) {
            $this->incrementUsageThisMonth($category->getId());
            $this->setUsageLeftThisMonth();
        }


        if (($email != null) && ( $invitation->sendCode($user, $email, $message, $language) )) {
            $this->sentTo = $email;
        } else {
            $this->sentTo = null;
        }

        return true; // say: all ok
    }

    public function validateCode($code, $alldata) {
        if (!preg_match('/^[\w]+$/', $code)) {
            return 'Error: Code should be alphanumeric';
        }
        $invitation = WpInvitation::newFromCode($code);
        if ($invitation instanceof WpInvitation) {
            return 'Error: This code already exists.';
        }
        return true;
    }

    public function validateCounter($counter, $alldata) {
        if (($counter != '-1') && (!preg_match('/^([1-9])([0-9]{0,9})$/', $counter))) {
            return 'Error: Must be -1 or integer > 0';
        }
        return true;
    }

    public function validateEmail($email, $alldata) {
        if ($email && !Sanitizer::validateEmail($email)) {
            return wfMsgExt('invalidemailaddress', 'parseinline');
        }
        return true;
    }
    
    /**
	 * Generate a link to the form for creating an invitation
	 * @param int $category_id
	 * @return string a HTML link
	 */
	public static function getLinkInvite($category_id = null, $i18n_key = 'create') {
		return Linker::linkKnown(
						self::getTitleFor(self::TITLE_NAME, self::ACTION_CREATE . ':' . $category_id), wfMessage($i18n_key)->text());
	}

}