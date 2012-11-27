<?php

require_once( dirname( __FILE__ ) . '/../../maintenance/Maintenance.php' );

class WikiplaceMaintenance extends Maintenance {
    
    protected $test_only;
	
	public function __construct() {
		
		parent::__construct();
		$this->addOption( "fix_wpw_date_expires",             "Fix database records pre v1.2.4.", false, false );
        $this->addOption( "fix_wpou_monthly",                 "Fix database records pre v1.2.4.", false, false );
        $this->addOption( "fix_wpw_previous_total_page_hits", "Fix database records pre v1.2.4.", false, false );
        $this->addOption( "test_only",                        "Do not update database.",          false, false );
		$this->mDescription = "Maintenance script of Wikiplaces extension.";
		
	}

    public function isTest() {
        return $this->test_only;
    }
    
	public function execute() {
        
        $this->test_only = $this->hasOption( 'test_only' );

		if ( $this->hasOption( 'fix_wpw_date_expires' ) ) {
            $this->execute_fix_wpw_date_expires();
        } elseif ( $this->hasOption( 'fix_wpou_monthly' ) ) {
            $this->execute_fix_wpou_monthly();
        } elseif ( $this->hasOption( 'fix_wpw_previous_total_page_hits' ) ) {
            $this->execute_fix_wpw_previous_total_page_hits();
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
            
            
            if ($wikiplace->getDateExpires() != $should_ends) {
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
    
    public function execute_fix_wpou_monthly() {
        
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
            $bandwidth = 0;
            foreach ( $results as $row ) {
                $this->output(" > wpou_end_date={$row->wpou_end_date}\tpagehits={$row->wpou_monthly_page_hits}\tbandwidth={$row->wpou_monthly_bandwidth}\n");
                if ( ( intval($row->wpou_monthly_page_hits) < $hits) || 
                        ( intval($row->wpou_monthly_bandwidth) < $bandwidth) ) {
                    $this->output(" > this has been recorded with a fixed version of Wikizam, no fix possible\n");
                    $need_fix = false;
                    break;
                }
                
                $should_be[$row->wpou_id] = array ( // curr - pre
                    'hits'      => intval($row->wpou_monthly_page_hits) - $hits,         
                    'bandwidth' => intval($row->wpou_monthly_bandwidth) - $bandwidth ) ;
                $this->output("\t\t\tshould be\t\t{$should_be[$row->wpou_id]['hits']}\t\t{$should_be[$row->wpou_id]['bandwidth']}\n");
                
                $hits = intval($row->wpou_monthly_page_hits);
                $bandwidth = intval($row->wpou_monthly_bandwidth);
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
            foreach($should_be as $wpou_id => $values) {
                $this->output("fixing {$wpou_id}\thits={$values['hits']}\tbandwidth={$values['bandwidth']}\n...");
                $sql = "
                    UPDATE wp_old_usage
                    SET wpou_monthly_page_hits = {$values['hits']},
                    wpou_monthly_bandwidth = {$values['bandwidth']}
                    WHERE wpou_id = {$wpou_id} ; ";
                $result = $dbw->query($sql, __METHOD__);
                if ($result !== true) {
                    $this->error( "error while updating old usages", true );
                }
                $this->output(" ok\n");
            }
            
            $dbw->commit();
                    
            $this->output("fixed :)\n");
        }
        
    }
    
    public function execute_fix_wpw_previous_total_page_hits() {
        
        $updated = 0;
        
        if (!$this->isTest()) {
            $dbw = wfGetDB(DB_MASTER);
            $dbw->begin(); 
            $sql = "
                UPDATE wp_wikiplace
                SET wpw_previous_total_page_hits = (
                    SELECT sum(wpou_monthly_page_hits)
                    FROM wp_old_usage
                    WHERE wpou_wpw_id = wpw_id )
                WHERE 1 ;";
            $result = $dbw->query($sql, __METHOD__);
            if ($result !== true) {
                $this->error( "error while updating outdated wikiplace usages", true );
            }

            $updated = $dbw->affectedRows();
            $dbw->commit();
            $this->output($updated." wikiplace records updated\n");
            
        } else {
            $this->output("no modification done (test_only option)\n");
        }
        
    }
    
}

$maintClass = "WikiplaceMaintenance";
require_once( RUN_MAINTENANCE_IF_MAIN );
