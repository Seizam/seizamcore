<?php 

class SpecialWikiplace extends SpecialPage {
	
	const ACTION_CREATE_WIKIPLACE		= 'create_wikiplace';
	const ACTION_CREATE_WIKIPLACE_PAGE	= 'create_page';
	const ACTION_LIST_WIKIPLACES		= 'list_wikiplaces';
	const ACTION_SUBSCRIBE_PLAN			= 'subscribe';
	
	private $newlyCreatedWikiplace;
	private $futurNewPage;

	public function __construct() {
		parent::__construct( 'WikiPlace' );
	}
	
	public static function validateUserId($id, $allData) {
        return self::validateGenericId($id) ? true : wfMessage( 'wikiplace-validate-error-userid' )->text() ;
	}
	
	public static function validateWikiplaceSubPageName($name, $allData) {
        return ( is_string($name) && preg_match('/^[a-zA-Z0-9]{3,16}$/',$name) ) ? true : wfMessage( 'wikiplace-validate-error-wikiplacepagename' )->text() ;
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

			
			case self::ACTION_CREATE_WIKIPLACE :
  
				$out->setPageTitle( wfMessage( 'wikiplace-createwp-pagetitle' )->text() );
				
				$form = $this->getCreateWikiplaceForCurrentUserForm( $this->getTitle( self::ACTION_CREATE_WIKIPLACE ) );
				
				if( $form->show() ){ // true = submitted and correctly processed, false = not submited or error
					$out->addHTML( wfMessage( 'wikiplace-createwp-success' )->parse() );
					$out->addHTML( $this->getWikiplacesOwnedListing($user->getID()) );
				}
				
                break;
				
				
			case self::ACTION_CREATE_WIKIPLACE_PAGE :
  
				$out->setPageTitle( wfMessage( 'wikiplace-createpage-pagetitle' )->text() );
				
				$form = $this->getCreateWikiplacePageForCurrentUserForm($this->getTitle( self::ACTION_CREATE_WIKIPLACE_PAGE));
				
				if( $form->show() ){
					$out->addHTML(wfMessage('wikiplace-createpage-link-wikitext', wfEscapeWikiText( $this->futurNewPage->getPrefixedText() ) )->parse());	
				}
				
                break;
 
				
			case self::ACTION_LIST_WIKIPLACES :
            default : // (default  =  action == nothing or "something we cannot handle")
				
				$out->setPageTitle( wfMessage( 'wikiplace-listwp-pagetitle' )->text() );
				
				$out->addHTML( $this->getWikiplacesOwnedListing($user->getID()) );
				
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
				Linker::linkKnown( $this->getTitle( self::ACTION_LIST_WIKIPLACES ), wfMessage( 'wikiplace-linkto-listwp' )->text() ),
				Linker::linkKnown( $this->getTitle( self::ACTION_CREATE_WIKIPLACE ), wfMessage( 'wikiplace-linkto-createwp' )->text() ),
				Linker::linkKnown( $this->getTitle( self::ACTION_CREATE_WIKIPLACE_PAGE ), wfMessage( 'wikiplace-linkto-createpage' )->text() ) ) ) )->text() );
		
	}
	
	
	
	/**
	 *
	 * @param Mixed $user The user (User object) or his id (int value)
	 * @return string An ul / li HTML list
	 */
	private function getWikiplacesOwnedListing($user) {
		
		$userid = null;
		$display = '';
		
		if ($user != null) {
			if ( is_object($user) && ($user instanceof User) )
				$userid = $user->getId();
			elseif ( is_int($user) )
				$userid = $user;
		}
		
		if ( ($userid == null) || ($user<1) )		// no anons
			return $display;
		
		$wikiplaces = WpWikiplace::getAllOwnedByUserId($userid);
		foreach ($wikiplaces as $wikiplace) {
			$display .= Html::rawElement( 'li', array(), $wikiplace->getName() );
        }

        return Html::rawElement('ul', array(), $display);

	}
	

	
	private function getCreateWikiplacePageForCurrentUserForm($submitTitle) {
		// http://seizam.localhost/index.php?title=Ploplop&action=edit
		
        $formDescriptor = array(
			'WikiplaceId' => array(
                'type'					=> 'select',
                'label-message'			=> 'wikiplace-createpage-form-selectwp',
				'validation-callback'	=> array(__CLASS__, 'validateWikiplaceId'),
                'options'				=> array(),
			),
			'WikiplaceSubPageName' => array(
				'type'					=> 'text',	
				'label-message'			=> 'wikiplace-createpage-form-textboxwpsubpagename',	
				'validation-callback'	=> array(__CLASS__, 'validateWikiplaceSubPageName'),
                'size'					=> 16, # Display size of field
                'maxlength'				=> 16, # Input size of field  
			),
		);
		
		$wikiplaces = WpWikiplace::getAllOwnedByUserId($this->getUser()->getId());
		foreach ($wikiplaces as $wikiplace) {
			$formDescriptor['WikiplaceId']['options'][$wikiplace->getName()] = $wikiplace->getId();
		}
		
		$htmlForm = new HTMLForm( $formDescriptor );
		$htmlForm->setTitle( $submitTitle );
		$htmlForm->setSubmitCallback( array( $this, 'processCreateWikiplacePageForCurrentUser' ) );
		
		$htmlForm->setWrapperLegend(	wfMessage( 'wikiplace-createpage-form-legend' )->text() );
		$htmlForm->addHeaderText(		wfMessage( 'wikiplace-createpage-form-explain' )->parse() );
		$htmlForm->setSubmitText(		wfMessage( 'wikiplace-createpage-form-submit' )->text() );
	
		return $htmlForm;
		
	}
	
	public function processCreateWikiplacePageForCurrentUser( $formData ) {
		
		if ( !isset($formData['WikiplaceId']) || !isset($formData['WikiplaceSubPageName']) ) { //check that the keys exist and values are not NULL
			return wfMessage('wikiplace-error-unknown')->text(); //invalid form, so maybe a bug, maybe a hack
		}
		
		$wikiplace = WpWikiplace::getById(intval($formData['WikiplaceId']));
		
		if ( !is_object($wikiplace) || !($wikiplace instanceof WpWikiplace) || ($wikiplace->getOwnerUserId() != $this->getUser()->getId()) ) {
			// for security reason, same message if wikiplace doesn't exist or the submited wikiplace is not owned by the user
			// this case only occurs if the form submited bas datas (ie: the visitor hack the selectbox options)
			return wfMessage( 'wikiplace-createpage-error-notvalidwp')->text(); 
		}
		
		$title = Title::newFromText( $wikiplace->getName() . '/' . $formData['WikiplaceSubPageName'] );
		
		if (!($title instanceof Title)) {
			// not good syntax, but this case should not occurs because the validate passes
			return wfMessage( 'wikiplace-createpage-error-notvalidtitle')->text(); 
		}
		
		if ($title->isKnown()) {
			return wfMessage( 'wikiplace-createpage-error-alreadyexists')->text();			
		}
		
		//ok, let's go!
		$this->futurNewPage = $title;
		
		return true;
				
	}
	
	
	
	/**
	 *
	 * @param type $submitTitle Where the form will submit
	 * @param User $user 
	 * @return HTMLForm 
	 */
	private function getCreateWikiplaceForCurrentUserForm( $submitTitle ) {
		
        $formDescriptor = array(
			'WikiplaceName' => array(
				'label-message'			=> 'wikiplace-createwp-form-textboxwpname',
				'type'					=> 'text',			
                'size'					=> 16, # Display size of field
                'maxlength'				=> 16, # Input size of field
				'validation-callback'	=> array( __CLASS__, 'validateWikiplaceName'),
			)
		);
		
		$htmlForm = new HTMLForm( $formDescriptor );
		$htmlForm->setTitle( $submitTitle );
		$htmlForm->setSubmitCallback( array( $this, 'processCreateWikiplaceForCurrentUser' ) );
		
		$htmlForm->setWrapperLegend(	wfMessage( 'wikiplace-createwp-form-legend' )->text() );
		$htmlForm->addHeaderText(		wfMessage( 'wikiplace-createwp-form-explain' )->parse() );
		$htmlForm->setSubmitText(		wfMessage( 'wikiplace-createwp-form-submit' )->text() );
	
		return $htmlForm;
		
	}
	
	/**
	 *
	 * @global type $wgUser
	 * @param type $formData
	 * @return boolean true = the form won't display again / false = the form will be redisplayed  / anything else = error to display
	 * true == Successful submission, false == No submission attempted, .
	 */
	public function processCreateWikiplaceForCurrentUser( $formData ) {
		
		if ( !isset($formData['WikiplaceName']) ) { //check that the keys exist and values are not NULL
			return wfMessage('wikiplace-error-unknown')->text(); //invalid form, so maybe a bug, maybe a hack
		}
		
		$name = $formData['WikiplaceName'];

		if (WpWikiplace::getByName($name) !== null) {
			return wfMessage( 'wikiplace-createwp-error-alreadyexists')->text(); // this wikiplace already exists
		}
		
		$new = WpWikiplace::create($this->getUser()->getId(), $name);
		
		if ( !is_object($new) || !($new instanceof WpWikiplace) ) {
			return wfMessage( 'wikiplace-error-unknown')->text(); // error while saving
		}
			
		$this->newlyCreatedWikiplace = $new;
		return true; // all ok :)
					
	}
	
	
	
}