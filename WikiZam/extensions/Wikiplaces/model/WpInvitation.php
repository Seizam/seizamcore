<?php

class WpInvitation {
	
	private	$wpi_id,
			$wpi_code,
			$wpi_to_email,
			$wpi_from_user_id,
			$wpi_date_created,
			$wpi_date_last_used,
			$wpi_counter, // -1 = unlimited
			$wpi_wpic_id;
	
	private $category;
	
	private function __construct( $id, $code, $toEmail, $fromUserId, $created, $lastUsed, $counter, $invitationCategoryId) {

        $this->wpi_id = intval($id);
        $this->wpi_code = $code;
        $this->wpi_to_email = $toEmail;
        $this->wpi_from_user_id = intval($fromUserId);
        $this->wpi_date_created = $created;
        $this->wpi_date_last_used = $lastUsed;
        $this->wpi_counter = intval($counter);
		$this->wpi_wpic_id = intval($invitationCategoryId);
		
		$this->category = null;

    }
	
	/**
	 *
	 * @param type $row
	 * @return \self
	 * @throws MWException 
	 */
	public static function constructFromDatabaseRow($row) {

        if ($row === null) {
            throw new MWException('No SQL row.');
        }

        return new self(
				$row->wpi_id,
				$row->wpi_code,
				$row->wpi_to_email,
				$row->wpi_from_user_id,
				$row->wpi_date_created,
				$row->wpi_date_last_used,
				$row->wpi_counter,
				$row->wpi_wpic_id );
    }
	
	public function getId() {
		return $this->wpi_id;
	}
	
	/**
	 *
	 * @return string 
	 */
	public function getCode() {
		return $this->wpi_code;
	}
	
	/**
	 *
	 * @return WpInvitationCategory 
	 */
	public function getCategory() {
		if ( $this->category == null ) {
			$this->category = WpInvitationCategory::newFromId($this->wpi_wpic_id);
		}
		return $this->category;
	}
	
	/**
	 *
	 * @param User $userFrom User creating the code
	 * @param string $to Email address to send to
	 * @param string $message The user message, to send along the code
	 * @param mixed $language The language to translate generic message
	 * @return boolean True = ok, false = error while sending
	 */
	public function sendCode($userFrom, $to, $message, $language) {

		$to = new MailAddress($to);
		
		$from = new MailAddress($userFrom);
		
        $subject = wfMessage('wpm-invitation-subj')->inLanguage($language)->text();

        $body = wfMessage('wpm-invitation-body', 
				$userFrom->getName(),
				$message,
				$this->wpi_code,
				wfMessage($this->getCategory()->getDescription())->inLanguage($language)->text()
				)->inLanguage($language)->text();
        $body .= wfMessage('wp-mail-footer')->inLanguage($language)->text();
		
		try {	
			UserMailer::send( 
					$to,
					$from, // from
					$subject,
					$body );
		} catch (Exception $e) {
            wfDebugLog('wikiplaces', 'WpInvitation::sendCode: ERROR SENDING EMAIL from ' . $from . '", to '
                    . $to . ', code ' . $this->wpi_code);
			return false;
        }

        return true;
	}

	/**
	 *
	 * @return boolean
	 * @throws MWException 
	 */
	public function consume() {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();
		
		$now = WpSubscription::now();

		$success = $dbw->update(
				'wp_invitation',
				array(
					'wpi_counter = wpi_counter - 1',
					'wpi_date_last_used' => $now ),
				array('wpi_id' => $this->wpi_id));

		$dbw->commit();

		if (!$success) {
			throw new MWException('Error while updating Invitation record.');
		}
		return true;
		
	}
	
	/**
	 *
	 * @param User $user
	 * @return string 
	 */
	public static function generateCode($user) {

		if ( $user instanceof User) {
			$userId = $user->getId();
		} else {
			$userId = mt_rand(0, 99);
		}
		$dt = new DateTime('now', new DateTimeZone('GMT'));
		$now = $dt->format('symiHd'); //length = 12

		$nb = mt_rand(0, 99);
		$nb = sprintf('%02d', $nb);
		
		$userId = sprintf('%02d', $userId);

		$code = $now{0} . $now{1}
				. $now{2} . $nb{0} . $now{3}
				. $now{4} . $now{5}
				. $now{6} . $nb{1} . $now{7}
				. $now{8} . $userId{strlen($userId)-1} . $now{9}
				. $now{10} . $userId{strlen($userId)-2} . $now{11};
				
		$human = dechex(substr($code, 0, 8)) . dechex(substr($code, 8, 8));
		//$human = base_convert(substr($code, 0, 8),10, 36) . base_convert(substr($code, 8, 8),10, 36);
		$human = strtoupper($human);
		return $human;
		
	}
	
	/**
	 *
	 * @param User $user
	 * @param WpInvitationCategory $invitationCategory
	 * @return array Array of InvitationCategoryId => count
	 */
	public static function countMonthlyInvitations($user) {

		if ( ! $user instanceof User ) {
			throw new MWException( 'Invalid user.' );
		}	
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$userId = $user->getId();
		$oneMonthAgo =  $dbr->addQuotes( WpSubscription::now(0, 0, 0, 0, -1) );
		
		// count all intation generated from one month ago for this category
		$results = $dbr->select( 
				'wp_invitation',
				array( 'wpi_wpic_id' , 'count(*) as total' ),
				array( 
					'wpi_from_user_id' => $userId,
					'wpi_date_created > '.$oneMonthAgo ),
				__METHOD__,
				array('GROUP BY' => 'wpi_wpic_id'),
				array() );
		
		if ( ! $results instanceof ResultWrapper) {
			return array();
		}
		
		$back = array();
		foreach ($results as $result) {
			$back[$result->wpi_wpic_id] = $result->total;
		}
		return $back;
		
	}
	
	public function canBeUsed() {
		
		if ( $this->wpi_counter == 0 ) {
			return false;
		}
		
		$category = $this->getCategory();
		if ( ! $category instanceof WpInvitationCategory ) {
			return false;
		}
		
		$start = wfTimestamp( TS_MW, $category->getStartDate() );
		$end = wfTimestamp( TS_MW, $category->getEndDate() );
		$now = wfTimestampNow( TS_MW, WpSubscription::now());
		
		return ( ( $start <= $now ) && ( $end >= $now ) );
				
	}
	
	/**
	 * Contruct the invitation having this code.
	 * @param String $code
	 * @return WpInvitation 
	 */
	public static function newFromCode($code) {
		
		$databaseBase = wfGetDB(DB_SLAVE);
		$now = $databaseBase->addQuotes(WpSubscription::now());
		$tables = array( 'wp_invitation', 'wp_invitation_category' );
		$vars = array( '*' );
		$conds = array(	'wpi_code' => $code);
		$fname = __METHOD__;
		$options = array();
		$join_conds = array('wp_invitation_category' => array('LEFT JOIN', 'wpi_wpic_id = wpic_id'));
		
		$result = $databaseBase->selectRow($tables, $vars, $conds, $fname, $options, $join_conds);
		if ($result === false) {
			// not found, so return null
			return null;
		}
		
		$invitation = self::constructFromDatabaseRow($result);
		if ( $result->wpic_id != null ) {
			$invitation->category = WpInvitationCategory::constructFromDatabaseRow($result);
		}
		
		return $invitation;
		
	}

	/**
	 *
	 * @param int $invitationCategoryId
	 * @param User $fromUser
	 * 
	 * @param string $code
	 * @param string $toEmail
	 * @param int $counter
	 * @return WpInvitation 
	 */
	public static function create( $invitationCategoryId, $fromUser, $code, $toEmail=null, $counter=1) {

		$dbw = wfGetDB(DB_MASTER);
		
		$created = WpSubscription::now();
		
        $dbw->begin();
		
		$fromUserId = $fromUser->getId();

        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_invitation_wpi_id_seq');
		
        $success = $dbw->insert('wp_invitation', array(
			'wpi_id' => $id,
			'wpi_code' => $code,
			'wpi_to_email' => $toEmail,
			'wpi_from_user_id' => $fromUserId,
			'wpi_date_created' => $created,
			// 'wpi_date_last_used' => null,
			'wpi_counter' => $counter,
			'wpi_wpic_id' => $invitationCategoryId,
				));

        // Setting id from auto incremented id in DB
        $id = $dbw->insertId();

        $dbw->commit();

        if (!$success) {
            return null;
        }

        return new self($id, $code, $toEmail, $fromUserId, $created, null, $counter, $invitationCategoryId);
		
	}
	
}