<?php 

class SpecialWikiplacePlan extends SpecialPage {

	const ACTION_CREATE			= 'create';
	const ACTION_SUBSCRIBE		= 'subscribe';
	const ACTION_LIST_OFFERS	= 'list_offers';
	
	private $newlyCreatedPlan;
	private $newlySubscribedPlan;
	
	

	public function __construct() {
		parent::__construct( 'WikiPlace Plan' );
	}
	

	
	
	
	private static function generateLink($to, $text) {
		 return Html::rawElement( 'a', array( 'href' => $to, 'class' => 'sz-wikiplace-link' ), $text  );
	}
	
	
	
	/*
	 * The validates method ONLY check if the inputs are well formed, but DO NOT check if the corresponding
	 * process will accept them<br />
	 * ie: validateWikiPlaceName only check if the name contains authorized caracters, but the create wikiplace process
	 * can fail later if the name is already used
	 */

	public static function validatePlanName($name, $allData) {
        return ( is_string($name) && preg_match('/^[a-zA-Z0-9]{3,16}$/',$name) ) ? true : wfMessage( 'wikiplace-validate-error-planname' )->text() ;
	}
	
	public static function validatePlanID($id, $allData) {
        return self::validateGenericId($id) ? true : wfMessage( 'wikiplace-validate-error-wikiplaceid' )->text() ;
	}
	
	private static function validateGenericId($id) {
		return ( is_string($id) && preg_match('/^[1-9]{1}[0-9]{0,9}$/',$id) ) ;
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

			
			case self::ACTION_CREATE :
  
				$out->setPageTitle( wfMessage( 'wikiplaceplan-createplan-pagetitle' )->text() );
				
				$form = $this->getCreatePlanForm($this->getTitle( self::ACTION_SUBSCRIBE ));
				
				if( $form->show() ){
					$out->addHTML(wfMessage(
							'wikiplaceplan-createplan-success-wikitext',
							wfEscapeWikiText( $this->$newlyCreatedPlan->getName() ) 
						)->parse());	
				}
				
                break;
							
			case self::ACTION_SUBSCRIBE :
  
				$out->setPageTitle( wfMessage( 'wikiplaceplan-subscribe-pagetitle' )->text() );
				
				$form = $this->getSubscribePlanForm($this->getTitle( self::ACTION_SUBSCRIBE ));
				
				if( $form->show() ){
					$out->addHTML(wfMessage(
							'wikiplaceplan-subscribe-success-wikitext',
							wfEscapeWikiText( $this->newlySubscribedPlan->getName() ) 
						)->parse());	
				}
				
                break;
 
				
			case self::ACTION_LIST_OFFERS :
            default : // (default  =  action == nothing or "something we cannot handle")
				
				$out->setPageTitle( wfMessage( 'wikiplaceplan-listoffers-pagetitle' )->text() );
				
				$out->addHTML( $this->getCurrentPlansOffersListing() );
				
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
				Linker::linkKnown( $this->getTitle( self::ACTION_SUBSCRIBE ), wfMessage( 'wikiplaceplan-linkto-subscribe' )->text() ) ,
				Linker::linkKnown( $this->getTitle( self::ACTION_LIST_OFFERS ), wfMessage( 'wikiplaceplan-linkto-listoffers' )->text() ) ,
		) ) )->text() );
		
	}
	
	
	
	/**
	 *
	 * @param Mixed $user The user (User object) or his id (int value)
	 * @return string An ul / li HTML list
	 */
	private function getCurrentPlansOffersListing() {
		

		$display = '';
		
		$wikiplaces = WpWikiPlace::getAllOwnedByUserId($userid);
		foreach ($wikiplaces as $wikiplace) {
			$display .= Html::rawElement( 'li', array(), $wikiplace->getName() );
        }

        return Html::rawElement('ul', array(), $display);

	}
	

	
	private function getCreatePlanForm($submitTitle) {
		
        $formDescriptor = array(
			'PlanName' => array(
				'type'					=> 'text',	
				'label-message'			=> 'wikiplaceplan-createplan-form-textboxplanname',	
				'validation-callback'	=> array(__CLASS__, 'validatePlanName'),
                'size'					=> 16, # Display size of field
                'maxlength'				=> 16, # Input size of field  
			),
		);
		
		$htmlForm = new HTMLForm( $formDescriptor );
		$htmlForm->setTitle( $submitTitle );
		$htmlForm->setSubmitCallback( array( $this, 'processCreatePlan' ) );
		
		$htmlForm->setWrapperLegend(	wfMessage( 'wikiplaceplan-createplan-form-legend' )->text() );
		$htmlForm->addHeaderText(		wfMessage( 'wikiplaceplan-createplan-form-explain' )->parse() );
		$htmlForm->setSubmitText(		wfMessage( 'wikiplaceplan-createplan-form-submit' )->text() );
	
		return $htmlForm;
		
	}
	
	public function processCreatePlan( $formData ) {
		
		if ( !isset($formData['PlanName']) ) { //check that the keys exist and values are not NULL
			return wfMessage('wikiplace-error-unknown')->text(); //invalid form, so maybe a bug, maybe a hack
		}
		
		$new = WpPlan::create($formData['PlanName']);
		
		if ( !is_object($new) || !($new instanceof WpPlan) ) {
			return wfMessage( 'wikiplace-error-unknown')->text(); // error while saving
		}
			
		$this->newlyCreatedPlan = $new;
		return true; // all ok :)
	}
	
	
	

	private function getSubscribePlanForm( $submitTitle ) {

		return null;
		
	}
	
	public function processCreateWikiPlaceForCurrentUser( $formData ) {

		return wfMessage('wikiplace-error-unknown')->text(); //invalid form, so maybe a bug, maybe a hack
				
	}
	
	
	
}