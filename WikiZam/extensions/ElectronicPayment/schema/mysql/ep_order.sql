-- (c) Clément Dietschy, 2011, GPL

--
-- Table structure for table `ep_message`
--

CREATE TABLE IF NOT EXISTS `ep_order` (
  `epo_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key (the reference)',
  `epo_tmr_id` int(10) unsigned NULL COMMENT 'Foreign key to tm_record',
  `epo_date_created` datetime NOT NULL COMMENT 'DateTime created',
  `epo_date_modified` datetime NOT NULL COMMENT 'DateTime modified',
  `epo_date_paid` datetime NULL COMMENT 'DateTime paid',
  `epo_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
  `epo_mail` tinyblob NOT NULL COMMENT 'Ordering User''s Mail',
  `epo_amount` decimal(9,2) unsigned NOT NULL COMMENT 'Order Amount',
  `epo_currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `epo_language` varchar(2) NOT NULL DEFAULT 'EN' COMMENT 'Order Language',
  `epo_status` varchar(2) NOT NULL DEFAULT 'ko' COMMENT 'Record status (OK, KO, PEnding, TEst)',
  PRIMARY KEY (`epo_id`),
  KEY `epo_user_id` (`epo_user_id`),
  KEY `epo_tmr_id` (`epo_tmr_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Table to hold unique order for messages sent between Bank and server.' AUTO_INCREMENT=1 ;
