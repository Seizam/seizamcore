<?php

class SpecialWikiplaces extends SpecialPage {
	const ACTION_CREATE_WIKIPLACE = 'create_wikiplace';
	const ACTION_CREATE_WIKIPLACE_PAGE = 'create_page';
	const ACTION_LIST_WIKIPLACES = 'list_wikiplaces';
	const ACTION_CONSULT_WP = 'consult';

	private $newlyCreatedWikiplace;
	private $futurNewPage;

	public function __construct() {
		parent::__construct('Wikiplaces');
	}

	private static function generateLink($to, $text) {
		return Html::rawElement('a', array('href' => $to, 'class' => 'sz-wp-link'), $text);
	}

	/**
	 * Show the special page
	 *
	 * @param $par String subpage string, if one was specified
	 */
	public function execute($par) {

		$this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title

		$out = $this->getOutput();
		$user = $this->getUser();

		// Anons can't use this special page
		if ($user->isAnon()) {
			$out->setPageTitle(wfMessage('wp-nlogin-pagetitle')->text());
			$link = Linker::linkKnown(
							SpecialPage::getTitleFor('Userlogin'), wfMessage('wp-nlogin-link-text')->text(), array(), array('returnto' => $this->getTitle()->getPrefixedText())
			);
			$out->addHTML(wfMessage('wp-nlogin-text')->rawParams($link)->parse());
			return;
		}

		// check that the user is not blocked
		if ($user->isBlocked()) {
			$out->blockedPage();
		}

		if (!$this->userCanExecute($user)) {
			$this->displayRestrictionError();
			return;
		}

		// Starts display

		$out->setSubtitle($this->buildToolLinks($this->getLang())); // set a nav bar as subtitle
		// Handle request
		// what to do is specified in the url (as a subpage) or somewhere in the request (this has the priority)
		$do = strtolower($this->getRequest()->getText('action', $par));
		switch ($do) {


			case self::ACTION_CREATE_WIKIPLACE :

				$out->setPageTitle(wfMessage('wp-cwp-pagetitle')->text());

				if (WpSubscription::getActiveByUserId($this->getUser()->getId()) === null) {

					$out->addHTML(wfMessage('wp-need-active-sub')->text());
				} else {

					$form = $this->getCreateWikiplaceForm($this->getTitle(self::ACTION_CREATE_WIKIPLACE));

					if ($form->show()) { // true = submitted and correctly processed, false = not submited or error
						$out->addHTML(wfMessage('wp-cwp-success-link-wt', wfEscapeWikiText($this->futurNewPage->getText()))->parse());
					}
				}

				break;


			case self::ACTION_CREATE_WIKIPLACE_PAGE :

				$out->setPageTitle(wfMessage('wp-csp-pagetitle')->text());

				if (WpSubscription::getActiveByUserId($this->getUser()->getId()) === null) {

					$out->addHTML(wfMessage('wp-need-active-sub')->text());
				} else {

					$wikiplaces = WpWikiplace::getAllOwnedByUserId($this->getUser()->getId());

					if (count($wikiplaces) == 0) {
						// need at least one Wikiplace
						$out->addHTML(wfMessage('wp-csp-no-wp')->text());
					} else {

						$form = $this->getCreateSubPageForm(
								$this->getTitle(self::ACTION_CREATE_WIKIPLACE_PAGE), $wikiplaces);

						if ($form->show()) {
							// creation success
							$out->addHTML(wfMessage('wp-csp-success-link-wt', wfEscapeWikiText($this->futurNewPage->getPrefixedText()))->parse());
						}
					}
				}

				break;


			case self::ACTION_CONSULT_WP :

				// var_export(WpPage::countPagesOwnedByUser(12).' pages, '.WpPage::getDiskspaceUsageByUser(12).' Mb for files ');

				$name = $this->getRequest()->getText('name', '');

				if (strlen($name) > 1) {
					$out->setPageTitle(wfMessage('wp-consultwp-pagetitle', $name)->parse());
					$tp = new WpPagesTablePager();
                    $tp->setWPName($name);
					$tp->setSelectConds(array(
						'wpw_owner_user_id' => $user->getID(),
						'homepage.page_title' => $name));
					$out->addHTML($tp->getWholeHtml());
					break;
				} // if not, will display the default just below


			case self::ACTION_LIST_WIKIPLACES :
			default : // (default  =  action == nothing or "something we cannot handle")

				$out->setPageTitle(wfMessage('wp-lwp-pagetitle')->text());
				$tp = new WpWikiplacesTablePager();
				$tp->setSelectConds(array('wpw_owner_user_id' => $user->getId()));
				$out->addHTML($tp->getWholeHtml());

				break;
		}
	}

	/**
	 *
	 * @param Language $language
	 * @return type 
	 */
	public function buildToolLinks($language) {

		if (($language == null) || !($language instanceof Language))
			return ''; //avoid error message on screen, but cannot display if $language not correct, nothing displayed is our error message

		return Html::rawElement('span', array(), wfMessage('parentheses', $language->pipeList(array(
									Linker::linkKnown($this->getTitle(self::ACTION_LIST_WIKIPLACES), wfMessage('wp-tl-lwp')->text()),
									Linker::linkKnown($this->getTitle(self::ACTION_CREATE_WIKIPLACE), wfMessage('wp-tl-cwp')->text()),
									Linker::linkKnown($this->getTitle(self::ACTION_CREATE_WIKIPLACE_PAGE), wfMessage('wp-tl-csp')->text()),
									Linker::linkKnown(SpecialPage::getTitleFor('WikiplacesPlan'), 'WikiplacesPlan'),
								)))->text());
	}

	/**
	 *
	 * @param type $submitTitle
	 * @param Array $wikiplaces Array of WikiPlace in wich the user can create the page
	 * @return HTMLForm 
	 */
	private function getCreateSubPageForm($submitTitle, $wikiplaces) {
		// http://seizam.localhost/index.php?title=Ploplop&action=edit

		$formDescriptor = array(
			'WikiplaceId' => array(
				'type' => 'select',
				'label-message' => 'wp-csp-f-swp',
				'validation-callback' => array($this, 'validateWikiplaceID'),
				'options' => array(),
			),
			'WikiplaceSubPageName' => array(
				'type' => 'text',
				'label-message' => 'wp-csp-f-tspname',
				'validation-callback' => array($this, 'validateNewSubpageName'),
			//              'size' => 16, # Display size of field
			//              'maxlength' => 16, # Input size of field  
			),
		);

		$wikiplaces = WpWikiplace::getAllOwnedByUserId($this->getUser()->getId());
		foreach ($wikiplaces as $wikiplace) {
			$formDescriptor['WikiplaceId']['options'][$wikiplace->get('name')] = $wikiplace->get('wpw_id');
		}

		$htmlForm = new HTMLFormS($formDescriptor);
		$htmlForm->setTitle($submitTitle);
		$htmlForm->setSubmitCallback(array($this, 'processCreateWikiplacePage'));

		$htmlForm->setSubmitText(wfMessage('wp-csp-submit')->text());

		return $htmlForm;
	}

	public static function validateWikiplaceID($id, $allData) {

		if (!is_string($id) || !preg_match('/^[1-9]{1}[0-9]{0,9}$/', $id)) {
			return wfMessage('wp-vlderr-exwpid-format')->text();
		}

		/* 		$wikiplace = WpWikiplace::getById(intval($id));

		  if ($wikiplace === null) { // doesn't exist
		  return wfMessage( 'wp-vlderr-exwpid-notex' )->text() ;
		  }

		  if ( $wikiplace->isOwner($this->getUser()->getId()) ) { // doesn't belong to current user
		  return wfMessage( 'wp-vlderr-exwpid-usernotowner' )->text() ;
		  }
		 */
		return true; // all ok
	}

	public static function validateNewSubpageName($name, $allData) {

		if ($name == null) {
			return false;
		}

		// $allData['WikiplaceId'] is already checked, because it is declared before the subpage name in the form descriptor
		$wikiplace = WpWikiplace::getById(intval($allData['WikiplaceId']));
		if ($wikiplace == null) {
			return false;
		}

		$title = Title::newFromText($wikiplace->get('name') . '/' . $name);

		if ($title->isKnown()) {
			return wfMessage('wp-name-already-exists')->text();
		}

		return true; // all ok
	}

	public function processCreateWikiplacePage($formData) {

		if (!isset($formData['WikiplaceId']) || !isset($formData['WikiplaceSubPageName'])) {
			return wfMessage('wp-err-unknown')->text();
		}

		$wikiplace = WpWikiplace::getById(intval($formData['WikiplaceId']));

		// check that the user owns the wikiplace
		if (!($wikiplace instanceof WpWikiplace) || ($wikiplace->get('wpw_owner_user_id') != $this->getUser()->getId())) {
			return wfMessage('wp-csp-perr-notvalidwp')->text();
		}

		$subpage = WpPage::createSubpage($wikiplace, $formData['WikiplaceSubPageName']);

		if ( ! ( $subpage instanceof Title ) ) {
			return wfMessage($subpage)->text();
		}

		$this->futurNewPage = $subpage;
		return true; // all ok
	}

	/**
	 *
	 * @param type $submitTitle Where the form will submit
	 * @param User $user 
	 * @return HTMLForm 
	 */
	private function getCreateWikiplaceForm($submitTitle) {

		$formDescriptor = array(
			'WikiplaceName' => array(
				'label-message' => 'wp-cwp-f-twpname',
				'type' => 'text',
				//               'size' => 16, # Display size of field
				//               'maxlength'	=> 16, # Input size of field
				'validation-callback' => array($this, 'validateNewWikiplaceName'),
			)
		);

		$htmlForm = new HTMLFormS($formDescriptor);
		$htmlForm->setTitle($submitTitle);
		$htmlForm->setSubmitCallback(array($this, 'processCreateWikiplace'));

//		$htmlForm->setWrapperLegend( wfMessage( 'wp-cwp-f-legend' )->text() );
//		$htmlForm->addHeaderText( wfMessage( 'wp-cwp-f-explain' )->parse() );
		$htmlForm->setSubmitText(wfMessage('wp-cwp-f-submit')->text());

		return $htmlForm;
	}

	/**
	 * check that the WikiPlace doesn't already exist
	 * @param type $name
	 * @param type $allData
	 * @return type 
	 */
	public static function validateNewWikiplaceName($name, $allData) {

		if (!is_string($name) || preg_match('/[.\\/]/', $name)) {
			return wfMessage('wp-vlderr-nwpname-format')->text();
		}

		$title = Title::newFromText($name);

		if ($title->isKnown()) {
			return wfMessage('wp-name-already-exists')->text();
		}

		/* 		$wp = WpWikiplace::getByName($name);

		  if ( $wp !== null ) {
		  return wfMessage( 'wp-vlderr-nwpname-dup' )->text();
		  }
		 */
		return true;
	}

	/**
	 *
	 * @global type $wgUser
	 * @param type $formData
	 * @return boolean true = the form won't display again / false = the form will be redisplayed  / anything else = error to display
	 * true == Successful submission, false == No submission attempted, .
	 */
	public function processCreateWikiplace($formData) {

		if (!isset($formData['WikiplaceName'])) { // might be useless... but just in case
			return wfMessage('wp-err-unknown')->text(); // invalid form, so maybe a bug, maybe a hack
		}

		$name = $formData['WikiplaceName'];
		$user_id = $this->getUser()->getId();

		if (!WpWikiplace::userCanCreateWikiplace($user_id)) {
			return wfMessage('wp-cwp-err-cannot-create')->text(); // no active subscription or quotas exceeded ?
		}

		$homepage = WpWikiplace::initiateCreation($name);

		if ( ! ( $homepage instanceof Title ) ) {
			return wfMessage($homepage)->text(); // error while creating
		}

		$this->futurNewPage = $homepage;

		return true; // all ok :)
	}

}
