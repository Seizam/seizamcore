<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class UpdateUsages extends Maintenance {
	
	public function __construct() {
		
		parent::__construct();
		$this->addOption( "lifespan", "Lifespan above wich to consider a usage outdated (default = 60 minutes, min = 1 minutes).", false, true );
		$this->mDescription = "Update outdated usages + archive and reset ending usages.";
		
	}

	public function execute() {

		$lifespan = intval( $this->getOption( 'lifespan', 60 ) );
		if ( $lifespan < 1 ) {
			$lifespan = 60;
		}
		
		$this->output( "[".WpSubscription::now().": Updating usages, considering $lifespan minutes lifespan...]\n" );	
		
		// updates all users'wikiplaces having lifespan expired
		$ok = WpWikiplace::updateOutdatedUsages(null, $lifespan);	
		if ( $ok === false ) {
			$this->output("ERROR: $ok\n");
		} else {
			// $this->output("OK: $ok record(s) updated\n");
		}
			
		
		// $this->output( "[".WpSubscription::now().": Archiving and resetting monthly usages...]\n" );		
		$ok = WpWikiplace::archiveAndResetExpiredUsages();
		if ( $ok === false ) {
			$this->output("ERROR: $ok\n");
		} else {
			// $this->output("OK: $ok record(s) archived and reset\n");
		}
		$this->output( "[".WpSubscription::now().": END]\n" );
		
	}
}

$maintClass = "UpdateUsages";
require_once( RUN_MAINTENANCE_IF_MAIN );
