<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class WikiplaceMaintenance extends Maintenance {
    
    protected $test_only;
	
	public function __construct() {
		
		parent::__construct();
		$this->addOption( "fix_1"     , "Fix wpw_date_expires field records (<v1.2.4)", false, false );
        $this->addOption( "fix_2"     , "Fix wpou_monthly_page_hits field records (<v1.2.4)", false, false );
        $this->addOption( "fix_3"     , "Fix wpw_previous_total_page_hits field records (<v1.2.4)", false, false );
        $this->addOption( "fix_4"     , "Fix wpou_end_date field records (<v1.2.4)", false, false );
        $this->addOption( "test_only" , "Do not update database.",          false, false );
		$this->mDescription = "Maintenance script of Wikiplaces extension.";
		
	}

    public function isTest() {
        return $this->test_only;
    }
    
	public function execute() {
        
        $this->test_only = $this->hasOption( 'test_only' );

		if ( $this->hasOption( 'fix_1' ) ) {
            $this->execute_fix_wpw_date_expires();
        } elseif ( $this->hasOption( 'fix_2' ) ) {
            $this->execute_fix_wpou_monthly_page_hits();
        } elseif ( $this->hasOption( 'fix_3' ) ) {
            $this->execute_fix_wpw_previous_total_page_hits();
        } elseif ( $this->hasOption( 'fix_4' ) ) {
            $this->execute_fix_wpou_end_date();
        } else {
            $this->error( "missing option.", true );
        }
		
	}
    
    public function execute_fix_wpw_date_expires() {
        
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin();
        
        $this->output("looking for wikiplace records to fix...\n");
        
        $cond = 'wpw_date_expires > DATE_ADD(wpw_report_updated,INTERVAL 1 MONTH)';
        $wikiplaces = WpWikiplace::search( $cond, true);
        
        foreach ($wikiplaces as $wikiplace) {
            
            $subscription = WpSubscription::newFromId($wikiplace->getSubscriptionId());       
            $should_ends = WpWikiplace::calculateNextDateExpiresFromSubscription($subscription);
            
            if ( ($subscription->isActive()) && 
                    ($subscription->getTmrStatus() == 'OK') && 
                    ($wikiplace->getDateExpires() != $should_ends) ) {
                $this->output( "wpw_id=".$wikiplace->getId()."\tupdated=".$wikiplace->getReportUpdated()."\texpires=".$wikiplace->getDateExpires()."\n" );
                $this->output(" > wps\t\tstarts=".$subscription->getStart()."\tends=".$subscription->getEnd()."\n");
                $this->output(" > should expire $should_ends\n");
                
                if (!$this->isTest()) {
                    $this->output(" > fixing...\n");
                    $success = $dbw->update( 'wp_wikiplace', array('wpw_date_expires' => $should_ends), array('wpw_id' => $wikiplace->getId()));
                    if (!$success) {
                        $this->error( "Error while updating wikiplace id=".$wikiplace->getId(), true );
                    }
                    $this->output(" > fixed :)\n");
                }
                
                $this->output("\n");
            }
            
        }
        
        $this->output("end\n");
            
        $dbw->commit();
    }
    
    public function execute_fix_wpou_monthly_page_hits() {
        
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin(); 
        
        $this->output("checking...\n");
        
        $sql = "
            SELECT DISTINCT wpou_wpw_id
            FROM wp_old_usage
            WHERE 1
            ORDER BY wp_old_usage.wpou_wpw_id ASC ;";
        $results = $dbw->query($sql, __METHOD__);
        $wikiplace_ids  = array();
        foreach ( $results as $row ) {
            $wikiplace_ids[] = $row->wpou_wpw_id;
        }
        
        $need_fix = true;
        $should_be = array();
        
        foreach ($wikiplace_ids as $wikiplace_id) {
            $this->output("wpou_wpw_id=$wikiplace_id\n");
            $sql = "
                SELECT * FROM wp_old_usage
                WHERE wpou_wpw_id = $wikiplace_id
                ORDER BY wp_old_usage.wpou_end_date ASC ;";
            $results = $dbw->query($sql, __METHOD__);
            $hits = 0;
            foreach ( $results as $row ) {
                $this->output(" > wpou_end_date={$row->wpou_end_date}\tpagehits={$row->wpou_monthly_page_hits}\n");
                if ( intval($row->wpou_monthly_page_hits) < $hits ) {
                    $this->output(" > this has been recorded with a fixed version of Wikizam, no fix possible\n");
                    $need_fix = false;
                    break;
                }
                
                $should_be[$row->wpou_id] = intval($row->wpou_monthly_page_hits) - $hits;
                $this->output("\t\t\tshould be\t\t{$should_be[$row->wpou_id]}\n");
                
                $hits = intval($row->wpou_monthly_page_hits);
            }
            if (!$need_fix) {
                break;
            }
        }
        
        $this->output("\n");
        
        if (!$need_fix) {
            $this->output("no fix needed\n");
        } elseif ($this->isTest()) {
            $this->output("fix needed, but no modification done (test_only option)\n");
        } else {
            // do fix
            $this->output("fixing...\n");
            foreach($should_be as $wpou_id => $hits) {
                $this->output("fixing wpou_id={$wpou_id}\thits={$hits}\n...");
                $sql = "
                    UPDATE wp_old_usage
                    SET wpou_monthly_page_hits = {$hits}
                    WHERE wpou_id = {$wpou_id} ; ";
                $result = $dbw->query($sql, __METHOD__);
                if ($result !== true) {
                    $this->error( "error while updating old usages", true );
                }
                $this->output(" ok\n");
            }
                    
            $this->output("fixed :)\n");
        }
        
        $dbw->commit();
        
    }
    
    public function execute_fix_wpw_previous_total_page_hits() {
                     
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin(); 
        
        $this->output("checking...\n");
        
        $sql = "
            SELECT DISTINCT *
            FROM wp_wikiplace
            WHERE 1
            ORDER BY wpw_id ASC ;";
        $results = $dbw->query($sql, __METHOD__);

        foreach ( $results as $row ) {
            
            $wpw_id = $row->wpw_id;
            $previous_total_page_hits = intval($row->wpw_previous_total_page_hits);
            
            $sql = "
                SELECT sum(wpou_monthly_page_hits) as hits
                FROM wp_old_usage
                WHERE wpou_wpw_id = $wpw_id ;";
            $result = $dbw->query($sql, __METHOD__);
            $result = $dbw->fetchObject( $result );
            $should_be = intval($result->hits);

            $this->output("wpw_id=$wpw_id\tprevious_total_page_hits=$previous_total_page_hits\n");
            if ( $should_be != $previous_total_page_hits) {
                $this->output(" > should be $should_be\n");
                
                if (!$this->isTest()) {
                    $this->output(" > fixing...\n");
                    $sql = "
                        UPDATE wp_wikiplace
                        SET wpw_previous_total_page_hits = $should_be
                        WHERE wpw_id = $wpw_id ;";
                    $result = $dbw->query($sql, __METHOD__);
                    if ($result !== true) {
                        $this->error( "error while updating outdated wikiplace usages", true );
                    }
                    $this->output(" > fixed\n");
                } 
            }

        }
        
        if ($this->isTest()) {
            $this->output("no modification done (test_only option)\n");
        }
        
        $dbw->commit();
        
    }
    
    public function execute_fix_wpou_end_date() {
        
        $dbw = wfGetDB(DB_MASTER);
        $dbw->begin(); 
        
        $date_zero = $dbw->addQuotes("0000-00-00 00:00:00");
        
        $sql = "
            SELECT *, min(revision.rev_timestamp)
            FROM wp_old_usage, wp_wikiplace, revision
            WHERE wp_old_usage.wpou_end_date = $date_zero
            AND wp_wikiplace.wpw_id = wp_old_usage.wpou_wpw_id
            AND revision.rev_page = wp_wikiplace.wpw_home_page_id
            GROUP BY wp_wikiplace.wpw_home_page_id
            ORDER BY wpou_wpw_id ASC ;";
        $results = $dbw->query($sql, __METHOD__);

        foreach ( $results as $row ) {
            $wpou_id = $row->wpou_id;
            $wpw_id = $row->wpw_id;
            $wpou_end_date = $row->wpou_end_date;
            $home_page_created_timestamp = $row->rev_timestamp;
            $this->output("wpw_id=$wpw_id\twpou_id=$wpou_id\twpou_end_date=$wpou_end_date\t\n");
            $new_wpou_end_date = $dbw->addQuotes(wfTimestamp(TS_DB,$home_page_created_timestamp));
            $this->output(" > $wpw_id\thome_page_created_timestamp=$home_page_created_timestamp\n");
            $this->output(" > wpou_end_date should be $new_wpou_end_date\n");
            
            if (!$this->isTest()) {

                $this->output(" > fixing...\n");
                $sql = "
                    UPDATE wp_old_usage
                    SET wpou_end_date = $new_wpou_end_date
                    WHERE wpou_id = $wpou_id ;";
                $result = $dbw->query($sql, __METHOD__);
                if ($result !== true) {
                    $this->error( "error while updating old usage $wpou_id", true );
                }
                $this->output(" > fixed\n");
   
            }
            
        }
        
        if ($this->isTest()) {
            $this->output("no modification done (test_only option)\n");
        }
        
        $dbw->commit();
    }
    
}

$maintClass = "WikiplaceMaintenance";
require_once( RUN_MAINTENANCE_IF_MAIN );
