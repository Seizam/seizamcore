<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class InstallWpTemplates extends Maintenance {
	public function __construct() {

		parent::__construct();
		$this->mDescription = "Create default templates for Wikiplaces homepages and subpages.";
		$this->addOption( "test", "Doesn't really create articles, only displays content which would be written without this option." );
		
	}

	public function execute() {
		$test = intval( $this->getOption( 'onlytest', 1 ) ) == 1;

		$langs = array(
			'en', 'fr',
		);
		
		$pages = array (
			'wp-templates-for-homepage'
		);
		
		foreach ( $langs as $lang ) {
			foreach ( $pages as $page ) {
				$name = wfMessage( $page )->inLanguage( $lang )->useDatabase( false )->plain();
				$content = str_replace('-', '', "$page$lang"); // remove all '-'
				if (!method_exists( $this, $content )) {
					$this->output( "ERROR missing $name content, this page cannot be created.\n" );
				} else {
					$this->output( "Writing $name ... " );
					if ( $this->hasOption( 'test' ) ) {
						$this->output( " This is a dry run, rerun without --test to really write this:\n\n".$this->$content()."\n\n" );
					} else {
						$this->createPage( $name, $this->$content() );
					}
					$this->output( "\n" );
				}
			}
		}
	}
	
	private function createPage( $title, $content) {
		

		$title = Title::newFromText( $title );
		$this->output( "DBKey={$title->getPrefixedDBkey()} " );
		$article = new Article( $title );

		$article->doEdit( $content, '', EDIT_NEW | EDIT_AUTOSUMMARY );

		$this->output( "OK " );

	}

	private function wptemplatesforhomepageen() {
		return <<<EOT
==Hello, I am the english version of wptemplatesforhomepage==
* '''This template is kikoo:''' [[Template:homepage kikoo|a kikoo homepage template]]
* '''This one is so cuuuute:''' [[Template:homepage cute|nice template]]
* '''This one rocks:''' [[Template:homepage that rocks|oh yeah]]
EOT;
	}
	
}

$maintClass = "InstallWpTemplates";
require_once( RUN_MAINTENANCE_IF_MAIN );
