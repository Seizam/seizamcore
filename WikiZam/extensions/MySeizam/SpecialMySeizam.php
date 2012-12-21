<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Implements Special:Skinzam
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 * @ingroup Upload
 */

/**
 * Form for handling uploads and special page.
 *
 * @ingroup SpecialPage
 * @ingroup Upload
 */
class SpecialMySeizam extends SpecialPage {
    
    const TITLE_NAME = 'MySeizam';
    
    private $msgType = null;
    private $msgKey = null;
    private $mode = 'user';

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('MySeizam', MS_ACCESS_RIGHT);
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $this->setHeaders();
        $user = $this->getUser();
        $output = $this->getOutput();

        // Check rights
        if (!$this->userCanExecute($user)) {
            // If anon, redirect to login
            if ($user->isAnon()) {
                $output->redirect($this->getTitleFor('UserLogin')->getLocalURL(array('returnto'=>$this->getFullTitle())), '401');
                return;
            }
            // Else display an error page.
            $this->displayRestrictionError();
            return;
        }
        
        if ($user->isAllowed(WP_ARTIST_RIGHT)) {
            $this->mode = 'artist';
        }
        
        $this->display();
    }
    
    private function display() {
        $output = $this->getOutput();

        // Top Infobox Messaging
        if ($this->msgType != null) {
            $msg = wfMessage($this->msgKey);
            if (true) {
                $output->addHTML(Html::rawElement('div', array('class' => "informations $this->msgType"), $msg->parse()));
            }
        }

        switch ($this->mode) {
            case 'artist':
                $this->displayModeArtist();
                break;
            case 'user' :
            default:
                $this->displayModeUser();
                break;
        }
    }
    
    private function displayModeArtist() {
        $output = $this->getOutput();
        $output->addHTML('<div id="ms-quickaccount" class="content_block">' . $this->buildQuickAccount() . '</div>');
        $output->addHTML('<div id="ms-quickwikiplaces" class="topright">' . $this->buildQuickWikiplaces() . '</div>');
        $output->addHTML('<div id="ms-morelinks" class="content_block">' . $this->buildMoreLinks() . '</div>');
        $output->addHTML('<div id="ms-quickwatchlist" class="bottomleft">' . $this->buildQuickWatchlist() . '</div>');
    }
    
    private function displayModeUser() {
        $output = $this->getOutput();
        $output->addHTML('<div id="ms-quickaccount" class="content_block">' . $this->buildQuickAccount() . '</div>');
        $output->addHTML('<div id="ms-quickwatchlist" class="topright">' . $this->buildQuickWatchlist() . '</div>');
        $output->addHTML('<div id="ms-morelinks" class="content_block">' . $this->buildMoreLinks() . '</div>');
        $output->addHTML('<div id="ms-quickwikiplaces" class="bottomleft">'
                . '<div class="informations">'
                . wfMessage('wp-nosub')->parse()
                . '</div>'
                . $this->buildQuickWikiplaces()
                . '</div>');
    }

    private function buildQuickAccount() {
        $user = $this->getUser();

        $balance = TMRecord::getTrueBalanceFromDB($user->getId());

        $html = '<div class="ms-info-qa">' . wfMessage('ms-quickaccount')->parse() . '</div>';
        $html .= '<ul>';
        $html .= '<li>' . wfMessage('ms-accountbalance', $this->getLanguage()->formatNum($balance) , 'cur-euro')->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-electronicpayment')->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-subscriptions')->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-transactions')->parse() . '</li>';
        if ($this->mode == 'artist')
            $html .= '<li>' . wfMessage('ms-invitations')->parse() . '</li>';
        /** @todo Add private profile for adress, phone...
        $html .= '<li>' . wfMessage('ms-privateprofile')->parse() . '</li>';*/
        $html .= '</ul>';
        return $html;
    }

    private function buildQuickWikiplaces() {
        $user = $this->getUser();
        $tp = new WpWikiplacesTablePager();
		$tp->setShortDisplay();
        $tp->setLimit(3);
        $tp->setSelectConds(array('wpw_owner_user_id' => $user->getId()));
        $tp->setFieldSortable(array());
        $html = '<div class="ms-info-ws">' . wfMessage('ms-wikiplaces')->parse() . '</div>';
        $html .= $tp->getWholeHtml();
        return $html;
    }

    // Makes a short html list of Watchlist items
    private function buildQuickWatchlist() {
        global $wgShowUpdatedMarker, $wgRCShowWatchingUsers;
        $user = $this->getUser();
        
        // Building the request
        $dbr = wfGetDB(DB_SLAVE, 'watchlist');

        $tables = array('recentchanges', 'watchlist');

        $fields = array($dbr->tableName('recentchanges') . '.*');
        if ($wgShowUpdatedMarker) {
            $fields[] = 'wl_notificationtimestamp';
        }
        $conds = array();
        $conds[] = 'rc_this_oldid=page_latest OR rc_type=' . RC_LOG;
        $limitWatchlist = 0;
        $usePage = true;

        $join_conds = array(
            'watchlist' => array('INNER JOIN', "wl_user='{$user->getId()}' AND wl_namespace=rc_namespace AND wl_title=rc_title")
        );

        $options = array('LIMIT' => 5, 'ORDER BY' => 'rc_timestamp DESC');

        $rollbacker = $user->isAllowed('rollback');
        if ($usePage || $rollbacker) {
            $tables[] = 'page';
            $join_conds['page'] = array('LEFT JOIN', 'rc_cur_id=page_id');
            if ($rollbacker) {
                $fields[] = 'page_latest';
            }
        }

        ChangeTags::modifyDisplayQuery($tables, $fields, $conds, $join_conds, $options, '');

        $res = $dbr->select($tables, $fields, $conds, __METHOD__, $options, $join_conds);
        $numRows = $dbr->numRows($res);


        $s = '<div class="ms-info-wl">' . wfMessage('ms-watchlist')->parse() . '</div>';
        if ($numRows == 0) {
            $s .= '<p class="ms-wl-empty">'.wfMessage('watchnochange')->parse().'</p>';
        } else {

            /* Do link batch query */
            $linkBatch = new LinkBatch;
            foreach ($res as $row) {
                $userNameUnderscored = str_replace(' ', '_', $row->rc_user_text);
                if ($row->rc_user != 0) {
                    $linkBatch->add(NS_USER, $userNameUnderscored);
                }
                $linkBatch->add(NS_USER_TALK, $userNameUnderscored);

                $linkBatch->add($row->rc_namespace, $row->rc_title);
            }
            $linkBatch->execute();
            $dbr->dataSeek($res, 0);

            $list = ChangesList::newFromContext($this->getContext());
            $list->setWatchlistDivs();


            $s .= $list->beginRecentChangesList();

            $counter = 1;

            foreach ($res as $obj) {
                # Make RC entry
                $rc = RecentChange::newFromRow($obj);
                $rc->counter = $counter++;

                // Updated markers are always shown
                $updated = $obj->wl_notificationtimestamp;

                // We don't display the count of watching users
                $rc->numberofWatchingusers = 0;

                $s .= $list->recentChangesLine($rc, $updated, $counter);
            }
            $s .= $list->endRecentChangesList();
        }

        return $s;
    }

    private function buildMoreLinks() {
        $user = $this->getUser();
        $html = '<div class="ms-info-ml">' . wfMessage('ms-morelinks')->parse() . '</div>';
        $html .= '<ul>';
        $html .= '<li>' . wfMessage('ms-upload')->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-contributions', $user->getName())->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-preferences')->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-help')->parse() . '</li>';
        $html .= '<li>' . wfMessage('ms-specialpages')->parse() . '</li>';
        $html .= '</ul>';

        return $html;
    }
    

}