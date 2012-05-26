<?php

class SpecialOffers extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'Offers' );
	}
	
	public function execute( $par ) {
		
		$this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title
		
		$offers = WpPlan::factoryAvailableForFirstSubscription();
		$display = '';
		foreach ($offers as $offer) {
			$display .= "\n".Html::rawElement( 'li', array(), SpecialSubscriptions::getLinkNew($offer->getName()) );
        }
        $this->getOutput()->addHTML( Html::rawElement('ul', array(), "$display\n") );
		
	}
	
}