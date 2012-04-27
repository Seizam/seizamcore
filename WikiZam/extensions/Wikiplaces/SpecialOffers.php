<?php

class SpecialOffers extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'Offers' );
	}
	
	public function execute( $par ) {
		
		$this->setHeaders(); // sets robotPolicy = "noindex,nofollow" + set page title
		
		$offers = WpPlan::getAvailableOffersNow();
		$display = '';
		foreach ($offers as $offer) {
			$display .= "\n".Html::rawElement( 'li', array(), SpecialSubscriptions::getSubscribeLink($offer->get('wpp_name')) );
        }
        $this->getOutput()->addHTML( Html::rawElement('ul', array(), "$display\n") );
		
	}
	
}