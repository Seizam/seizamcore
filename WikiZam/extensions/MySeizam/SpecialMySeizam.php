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

    /**
     * Constructor : initialise object
     * Get data POSTed through the form and assign them to the object
     * @param $request WebRequest : data posted.
     */
    public function __construct($request = null) {
        parent::__construct('MySeizam');
    }

    /**
     * Special page entry point
     */
    public function execute($par) {
        $this->setHeaders();
        $user = $this->getUser();
        $output = $this->getOutput();

        if ($user->isAnon()) {
            $link = Linker::linkKnown(
                            SpecialPage::getTitleFor('Userlogin'), wfMessage('wp-nlogin-link-text')->text(), array(), array('returnto' => $this->getTitle()->getPrefixedText())
            );
            $output->addHTML('<p>' . wfMessage('wp-nlogin-text')->rawParams($link)->parse() . '</p>');
            return;
        }

        if (!$this->userCanExecute($user)) {
            $this->displayRestrictionError();
            return;
        }

        $output->addHTML('MySeizam');
        $output->addHTML('<div id="ms-quickwatchlist">'.$this->buildQuickWatchlist().'</div>');
        
    }

    # Just an array print fonction

    static function sayIt($in) {
        global $wgOut;
        $wgOut->addHTML('<pre>');
        $wgOut->addHTML(print_r($in, true));
        $wgOut->addHTML('</pre>');
    }
    
    // Makes a short html list of Watchlist items
    private function buildQuickWatchlist() {
        global $wgShowUpdatedMarker, $wgRCShowWatchingUsers;
        $user = $this->getUser();
        // Building the request
        $dbr = wfGetDB( DB_SLAVE, 'watchlist' );
        
        $tables = array( 'recentchanges', 'watchlist');
        
        $fields = array( $dbr->tableName( 'recentchanges' ) . '.*' );
        if( $wgShowUpdatedMarker ) {
			$fields[] = 'wl_notificationtimestamp';
		}
        $conds = array();
        $conds[] = 'rc_this_oldid=page_latest OR rc_type=' . RC_LOG;
			$limitWatchlist = 0;
			$usePage = true;
        
		$join_conds = array(
			'watchlist' => array('INNER JOIN',"wl_user='{$user->getId()}' AND wl_namespace=rc_namespace AND wl_title=rc_title")
		);
            
        $options = array( 'LIMIT' => 10, 'ORDER BY' => 'rc_timestamp DESC' );
        
        $rollbacker = $user->isAllowed('rollback');
		if ( $usePage || $rollbacker ) {
			$tables[] = 'page';
			$join_conds['page'] = array('LEFT JOIN','rc_cur_id=page_id');
			if ( $rollbacker ) {
				$fields[] = 'page_latest';
			}
		}
        
        ChangeTags::modifyDisplayQuery( $tables, $fields, $conds, $join_conds, $options, '' );

		$res = $dbr->select( $tables, $fields, $conds, __METHOD__, $options, $join_conds );
		$numRows = $dbr->numRows( $res );
        
        if( $numRows == 0 ) {
			$output->addWikiMsg( 'watchnochange' );
			return wfMessage('watchnochange')->parse();
		}
        
        /* Do link batch query */
		$linkBatch = new LinkBatch;
		foreach ( $res as $row ) {
			$userNameUnderscored = str_replace( ' ', '_', $row->rc_user_text );
			if ( $row->rc_user != 0 ) {
				$linkBatch->add( NS_USER, $userNameUnderscored );
			}
			$linkBatch->add( NS_USER_TALK, $userNameUnderscored );

			$linkBatch->add( $row->rc_namespace, $row->rc_title );
		}
		$linkBatch->execute();
		$dbr->dataSeek( $res, 0 );

		$list = ChangesList::newFromContext( $this->getContext() );
		$list->setWatchlistDivs();

		$s = $list->beginRecentChangesList();
        
        $counter = 1;
        
        foreach ( $res as $obj ) {
			# Make RC entry
			$rc = RecentChange::newFromRow( $obj );
			$rc->counter = $counter++;

			// Updated markers are always shown
            $updated = $obj->wl_notificationtimestamp;
            
            // We don't display the count of watching users
            $rc->numberofWatchingusers = 0;

			$s .= $list->recentChangesLine( $rc, $updated, $counter );
		}
		$s .= $list->endRecentChangesList();

		return $s;
    }

}