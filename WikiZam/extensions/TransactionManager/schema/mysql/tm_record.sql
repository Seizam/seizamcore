-- (c) Clément Dietschy, 2011, GPL

--
-- Table structure for table `tm_record`
--

CREATE TABLE IF NOT EXISTS `tm_record` (
  `tmr_id` int(12) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `tmr_type` varchar(8) NOT NULL COMMENT 'Type of message (Payment, Sale, Plan)',
  `tmr_date_created` datetime NOT NULL COMMENT 'DateTime of creation',
  `tmr_date_modified` datetime NOT NULL COMMENT 'DateTime of last modification',
  `tmr_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
  `tmr_mail` tinyblob COMMENT 'User''s Mail',
  `tmr_ip` tinyblob COMMENT 'User''s IP',
  `tmr_amount` decimal(9,2) NOT NULL COMMENT 'Record Amount',
  `tmr_currency` varchar(3) NOT NULL DEFAULT 'EUR' COMMENT 'Record Currency',
  `tmr_mac` varchar(40) DEFAULT NULL COMMENT 'Record Verification Sum',
  `tmr_desc` varchar(64) NOT NULL COMMENT 'Record Description',
  `tmr_status` varchar(2) NOT NULL DEFAULT 'ko' COMMENT 'Record status (ok, ko, pending)',
  PRIMARY KEY (`tmr_id`),
  KEY `tmr_user_id` (`tmr_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Transaction Manager Main Table' AUTO_INCREMENT=1 ;
