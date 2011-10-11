<?php
/**
 * @file
 * @ingroup HaloACL_Special
 */

/*  Copyright 2009, ontoprise GmbH
* 
*   This file is part of the HaloACL-Extension.
*
*   The HaloACL-Extension is free software; you can redistribute 
*   it and/or modify it under the terms of the GNU General Public License as 
*   published by the Free Software Foundation; either version 3 of the License, 
*   or (at your option) any later version.
*
*   The HaloACL-Extension is distributed in the hope that it will 
*   be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * A special page for defining and managing Access Control Lists.
 *
 *
 * @author Thomas Schweitzer
 */

if (!defined('MEDIAWIKI')) die();


global $IP;
require_once( $IP . "/includes/SpecialPage.php" );

/*
 * Standard class that is resopnsible for the creation of the Special Page
 */
class HaloACLSpecial extends SpecialPage {

    public function __construct() {
        parent::__construct('HaloACL');

    }

    /**
     * Overloaded function that is responsible for the creation of the Special Page
     */
    public function execute($par) {

        global $wgOut, $wgRequest, $wgLang,$wgUser;

        if($wgUser->isLoggedIn()) {
            wfLoadExtensionMessages('HaloACL');
            $wgOut->setPageTitle(wfMsg('hacl_special_page'));

            $this->createMainTabContainer();
        }else {
            $html = <<<HTML
                <h3>Only registered users are allowed to create and manage access rights.
            </h3>
            
            <h3>
            Please login first!</h3>
            <p>
HTML;
            $wgOut->addHTML($html);
        }


    }

    private function createMainTabContainer() {
        global $wgOut;
        global $wgRequest;
        global $wgUser;

        global $haclWhitelistGroups;


        $spt = SpecialPage::getTitleFor("HaloACL");
        $url = $spt->getFullURL();

        // checking if user can access whitelist
        if(array_intersect($wgUser->getGroups(), $haclWhitelistGroups) != null) {
            $showWhitelist = "true";
        }else {
            $showWhitelist = "false";
        }

        $html = <<<HTML
            <div id="haloaclContent" class="yui-skin-sam">
            <div id="haloacl_panel_container"></div>

    <div id="haloaclmainView" class="yui-navset"></div>
</div>
<script type="text/javascript">
    // bugfix for ontoskin 3
    $("bodyContent").setAttribute("style","overflow:visible");

    YAHOO.haloacl.specialPageUrl = "$url";

HTML;
        $articleTitle = $wgRequest->getVal('articletitle');
        $activeTab = $wgRequest->getVal('activetab', 'createACL');
        $activeSubTab = $wgRequest->getVal('activesubtab');

        if($activeSubTab != null) {
            $html .="
            YAHOO.haloacl.activeSubTab = '$activeSubTab';
                ";
        }

        $html .="
            YAHOO.haloacl.buildMainTabView('haloaclmainView','$articleTitle','$showWhitelist','$activeTab');
            ";

        $html .= <<<HTML
            try{
                //var myLogReader = new YAHOO.widget.LogReader();
            }catch(e){}
            </script>
HTML;
        $wgOut->addHTML($html);
    }

    private function testPage() {
        global $wgOut, $wgRequest, $wgLang;

        $action = $wgRequest->getText('action');
        if ($action == "initHaloACLDB") {
        // Initialize the database tables for HaloACL and show the
        // results on the special page.
            global $haclgIP, $wgOut;
            require_once("$haclgIP/includes/HACL_Storage.php");

            $wgOut->disable(); // raw output
            ob_start();
            print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Setting up Storage for Semantic MediaWiki</title></head><body><p><pre>";
            header( "Content-type: text/html; charset=UTF-8" );
            print "Initializing the HaloACL database...";
            $result = HACLStorage::getDatabase()->initDatabaseTables();
            print '</pre></p>';
            if ($result === true) {
                print '<p><b>' . wfMsg('hacl_db_setupsuccess') . "</b></p>\n";
            }
            $returntitle = Title::makeTitle(NS_SPECIAL, 'HaloACL');
            $special = $wgLang->getNamespaces();
            $special = $special[NS_SPECIAL];
            print '<p> ' . wfMsg('hacl_haloacl_return'). '<a href="' .
                htmlspecialchars($returntitle->getFullURL()) .
                '">'.$special.":".wfMsg('hacl_special_page')."</a></p>\n";
            print '</body></html>';
            ob_flush();
            flush();
            return;

        } if ($action == "HaloACLTest") {
            self::test();
        } else {
            $ttInitializeDatabase = wfMsg('hacl_tt_initializeDatabase');
            $initializeDatabase   = wfMsg('hacl_initializeDatabase');
            $html = <<<HTML
<form name="initHaloACLDB" action="" method="POST">
<input type="hidden" name="action" value="initHaloACLDB" />
<input type="submit" value="$initializeDatabase"/>
</form>

<form name="HaloACLTest" action="" method="POST">
<input type="hidden" name="action" value="HaloACLTest" />
<input type="submit" value="Test"/>
</form>
HTML;
            //			  <button id="hacl-initdb-btn" style="float:left;"
            //			          onmouseover="Tip('$ttInitializeDatabase')">
            //	      	     $initializeDatabase
            //			  </button>
            $wgOut->addHTML($html);
        }
    }

    /**
     * Function for testing new stuff
     *
     */
    private function test() {

        global $haclgIP;
        require_once "$haclgIP/tests/testcases/TestDatabase.php";
        $tc = new TestDatabase();
        $tc->runTest();
    }

}
