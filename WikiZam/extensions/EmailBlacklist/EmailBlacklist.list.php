<?php

/**
 * Email Blacklist class
 * @author Clément Dietschy
 * @license GNU General Public License 3.0 or later
 * @file
 */
/**
 * @ingroup Extensions
 */

/**
 * Implements an Email blacklist for MediaWiki
 */
class EmailBlacklist {

    private $mBlacklist = null;
    
    /**
	 * Get an instance of this class
	 *
	 * @return EmailBlacklist
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new self;
		}
		return $instance;
	}

	/**
	 * Load all configured blacklist sources
	 */
	public function load() {
		global $wgMemc;
		wfProfileIn( __METHOD__ );
		//Try to find something in the cache
		$cachedBlacklist = $wgMemc->get( wfMemcKey( "email_blacklist_entries" ) );
		if( is_array( $cachedBlacklist ) && count( $cachedBlacklist ) > 0 ) {
			$this->mBlacklist = $cachedBlacklist;
			wfProfileOut( __METHOD__ );
			return;
		}
        
		$this->mBlacklist = $this->parseBlacklist( $this->getBlacklistText() );
		$wgMemc->set( wfMemcKey( "email_blacklist_entries" ), $this->mBlacklist, 900 );
		wfProfileOut( __METHOD__ );
	}

    /**
     * Get the text of a blacklist
     *
     * @return The content of the blacklist source as a string
     */
    private static function getBlacklistText() {
        return wfMsgForContent('emailblacklist');
    }

    /**
     * Parse blacklist from a string
     *
     * @param $list Text of a blacklist source, as a string
     * @return EmailBlacklist[] An array of EmailBlacklistEntry entries
     */
    public static function parseBlacklist($list) {
        wfProfileIn(__METHOD__);
        $lines = preg_split("/\r?\n/", $list);
        $result = array();
        foreach ($lines as $line) {
            $line = EmailBlacklistEntry :: newFromString($line);
            if ($line) {
                $result[] = $line;
            }
        }

        wfProfileOut(__METHOD__);
        return $result;
    }
    
    

	/**
	 * Check whether the blacklist restricts given user
	 *
	 * @param $email email to check
	 * @param $user User to check
	 * @param $override bool If set to true, overrides work
	 * @return EmailBlacklistEntry|false The corresponding EmailBlacklistEntry if blacklisted;
	 *         otherwise FALSE
	 */
	public function userCannot( $email, $user, $override = true ) {
		if( $override && self::userCanOverride( $user) ) {
			return false;
		} else {
			return $this->isBlacklisted($email);
		}
	}
    
    

	/**
	 * Check whether the blacklist restricts
	 * performing a specific action on the given email
	 *
	 * @param $email Email to check
	 * @return EmailBlacklistEntry|false The corresponding EmailBlacklistEntry if blacklisted;
	 *         otherwise FALSE
	 */
	public function isBlacklisted( $email ) {
        
		$blacklist = $this->getBlacklist();
		foreach ( $blacklist as $item ) {
			if( $item->matches( $email ) ) {
				return $item; // "returning true"
			}
		}
		return false;
	}

    /**
     * Get the current blacklist
     *
     * @return EmailBlacklistEntry[] Array of EmailBlacklistEntries items
     */
    public function getBlacklist() {
        if (is_null($this->mBlacklist)) {
            $this->load();
        }
        return $this->mBlacklist;
    }

    /**
     * Invalidate the blacklist cache
     */
    public function invalidate() {
        global $wgMemc;
        $wgMemc->delete(wfMemcKey("email_blacklist_entries"));
    }

    /**
     * Validate a new blacklist
     *
     * @param $blacklist array
     * @return Array of bad entries; empty array means blacklist is valid
     */
    public function validate($blacklist) {
        $badEntries = array();
        foreach ($blacklist as $e) {
            wfSuppressWarnings();
            $regex = $e->getRegex();
            if (preg_match("/{$regex}/u", '') === false) {
                $badEntries[] = $e->getRaw();
            }
            wfRestoreWarnings();
        }
        return $badEntries;
    }

    /**
     * Inidcates whether user can override blacklist on certain action.
     *
     * @param $action Action
     *
     * @return bool
     */
    public static function userCanOverride($user) {
        return $user->isAllowed('eboverride');
    }

}

/**
 * Represents an email blacklist entry
 */
class EmailBlacklistEntry {

    private
    $mRegex,
    $mRaw; ///< Raw line

    /**
     * Construct a new EmailBlacklistEntry.
     *
     * @param $regex Regular expression to match
     * @param $raw Raw contents of this line
     */

    private function __construct($regex, $raw) {
        $this->mRegex = $regex;
        $this->mRaw = $raw;
    }

    /**
     * Check whether email is blacklisted
     *
     * @param $email Email to check
     * @return bool TRUE if the the regex matches the email, and is not overridden
     * else false if it doesn't match (or was overridden)
     */
    public function matches($email) {
        if (!$email) {
            return false;
        }
        wfSuppressWarnings();
        $match = preg_match("/{$this->mRegex}$/ius", $email);
        wfRestoreWarnings();

        if ($match) {
            return true;
        }
        return false;
    }

    /**
     * Create a new EmailBlacklistEntry from a line of text
     *
     * @param $line String containing a line of blacklist text
     * @return A new corresponding EmailBlacklistEntry
     */
    public static function newFromString($line) {
        $raw = $line; // Keep line for raw data
        // Strip comments
        $line = preg_replace("/^\\s*([^#]*)\\s*((.*)?)$/", "\\1", $line);
        $line = trim($line);
        // Parse the rest of message
        preg_match('/^(.*?)(\s*<([^<>]*)>)?$/', $line, $pockets);
        @list( $full, $regex, $null, $opts_str ) = $pockets;
        $regex = trim($regex);
        $opts_str = trim($opts_str);
        
        // Return result
        if ($regex) {
            return new EmailBlacklistEntry($regex, $raw);
        } else {
            return null;
        }
    }

    /**
     * @return This entry's regular expression
     */
    public function getRegex() {
        return $this->mRegex;
    }

    /**
     * @return This entry's raw line
     */
    public function getRaw() {
        return $this->mRaw;
    }
    
    /**
     * Return the error message name for the blacklist entry.
     *
     * @return The error message name
     */
    public function getErrorMessage() {
        return "emailblacklist-forbidden";
    }

}
