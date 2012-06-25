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
	 * @return boolean True = ok, false = error while sending
	 */
	public function sendCode($userFrom, $to) {
		
		$language = $userFrom->getOption('language');

		$to = new MailAddress($to);
		
		$from = new MailAddress($userFrom);
		
        $subject = wfMessage('wpm-invitation-subj')->inLanguage($language)->text();

        $body = wfMessage('wpm-invitation-body', $userFrom->getName(), $this->wpi_code)->inLanguage($language)->text();
        $body .= wfMessage('wpm-footer')->inLanguage($language)->text();
		
		try {	
			UserMailer::send( 
					$to,
					$from, // from
					$subject,
					$body );
		} catch (Exception $e) {
            wfDebugLog('wikiplaces', 'WpInvitation::sendCode: ERROR SENDING EMAIL from ' . $from . '", to '
                    . $to . ', code ' . $code);
			return false;
        }

        return true;
	}

	public function consume() {
		
		$dbw = wfGetDB(DB_MASTER);
		$dbw->begin();

		$success = $dbw->update(
				'wp_invitation',
				array('wpi_counter = wpi_counter - 1'),
				array('wpi_id' => $this->wpi_id));

		$dbw->commit();

		if (!$success) {
			throw new MWException('Error while updating Invitation record.');
		}
		return true;
		
	}
	
	/**
	 *
	 * @param int $userId
	 * @return string 
	 */
	public static function generateCode($userId=0) {

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
	 * @return int
	 */
	public static function countMonthlyInvitations($user, $invitationCategory) {

		if ( ! $user instanceof User ) {
			throw new MWException( 'Invalid user.' );
		}	
		if ( ! $invitationCategory instanceof WpInvitationCategory) {
			throw new MWException('Invalid invitation category.');
		}
		
		$dbr = wfGetDB(DB_SLAVE);
		
		$userId = $user->getId();
		$categoryId = $invitationCategory->getId();
		$oneMonthAgo =  $dbr->addQuotes( WpSubscription::now(0, 0, 0, 0, -1) );
		
		// count all intation generated from one month ago for this category
		$result = $dbr->selectRow( 
				'wp_invitation',
				array( 'count(*) as total' ),
				array( 
					'wpi_from_user_id' => $userId,
					'wpi_wpic_id' => $categoryId,
					'wpi_date_created > '.$oneMonthAgo ),
				__METHOD__,
				array(),
				array() );
		
		$generated = 0;
		if ($result !== null) {
			$generated = $result->total;
		}
		return $generated;
		
	}
	
	/**
	 * Contruct the invitation having this code if valid, ie category 
	 * started and not ended and counter not empty.
	 * @param String $code
	 * @return WpInvitation 
	 */
	public static function newValidFromCode($code) {
		
		$databaseBase = wfGetDB(DB_SLAVE);
		$now = $databaseBase->addQuotes(WpSubscription::now());
		$tables = array( 'wp_invitation', 'wp_invitation_category' );
		$vars = array( '*' );
		$conds = array(
					'wpi_code' => $code,
					'wpi_counter != 0');
		$fname = __METHOD__;
		$options = array();
		$join_conds = array('wp_invitation_category' => array('INNER JOIN', 
			'wpi_wpic_id = wpic_id'
			.' AND wpic_start_date <= '.$now
			.' AND wpic_end_date >= '.$now
			));
		
		$result = $databaseBase->selectRow($tables, $vars, $conds, $fname, $options, $join_conds);
		if ($result === false) {
			// not found, so return null
			return null;
		}
		
		$invitation = self::constructFromDatabaseRow($result);
		$invitation->category = WpInvitationCategory::constructFromDatabaseRow($result);
		
		return $invitation;
	}
	
	/**
	 * Contruct the invitation having this code.
	 * @param String $code
	 * @return WpInvitation 
	 */
	public static function newFromCode($code) {
		
		$databaseBase = wfGetDB(DB_SLAVE);

		$tables = array( 'wp_invitation' );
		$vars = array( '*' );
		$conds = array( 'wpi_code' => $code );
		$fname = __METHOD__;
		$options = array();
		$join_conds = array();
		
		$result = $databaseBase->selectRow($tables, $vars, $conds, $fname, $options, $join_conds);
		if ($result === false) {
			// not found, so return null
			return null;
		}
		
		$invitation = self::constructFromDatabaseRow($result);
		
		return $invitation;
	}
	
/*
	private static function factoryFromConds($conds, $databaseBase = null) {
		
		if ($databaseBase == null) {
			$databaseBase = wfGetDB(DB_SLAVE);
		}

		$tables = array('wp_invitation', 'wp_invitation_category');
		$vars = array('wp_invitation.*');
		$fname = __METHOD__;
		$options = array();
		$join_conds = array();

		$results = $databaseBase->select($tables, $vars, $conds, $fname, $options, $join_conds);
		$invitations = array();
		foreach ($results as $row) {
			$invitations[] = self::constructFromDatabaseRow($row);
		}

		$databaseBase->freeResult($results);

		return $invitations;
	}
 */

	/**
	 *
	 * @param int $invitationCategoryId
	 * @param int $fromUserId
	 * @param string $code
	 * @param string $toEmail
	 * @param int $counter
	 * @return WpInvitation 
	 */
	public static function create( $invitationCategoryId, $fromUserId, $code, $toEmail=null, $counter=1) {

		$dbw = wfGetDB(DB_MASTER);
        $dbw->begin();

        // With PostgreSQL, a value is returned, but null returned for MySQL because of autoincrement system
        $id = $dbw->nextSequenceValue('wp_invitation_wpi_id_seq');
		$created = WpSubscription::now();
		
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