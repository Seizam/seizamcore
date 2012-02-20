-- (c) Clément Dietschy, 2011, GPL

--
-- Table structure for table `ep_message`
--

CREATE TABLE IF NOT EXISTS `ep_message` (
  `epm_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `epm_type` varchar(4) NOT NULL COMMENT 'Type of message (INcoming, OUTcoming)',
  `epm_date` datetime NOT NULL COMMENT 'DateTime',
  `epm_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Foreign key to user.user_id',
  `epm_o_ept` int(8) unsigned NOT NULL DEFAULT '0' COMMENT 'EPT id',
  `epm_o_date` datetime NOT NULL COMMENT 'Order DateTime',
  `epm_o_amount` decimal(9,2) unsigned NOT NULL COMMENT 'Order Amount',
  `epm_o_currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `epm_o_reference` int(10) unsigned NOT NULL COMMENT 'Order Reference',
  `epm_o_free_text` mediumblob COMMENT 'Order Free Text',
  `epm_o_mail` tinyblob NOT NULL COMMENT 'Ordering User''s Mail',
  `epm_o_language` varchar(2) NOT NULL DEFAULT 'EN' COMMENT 'Order Language',
  `epm_o_mac` varchar(40) DEFAULT NULL COMMENT 'Order Verification Sum',
  `epm_o_options` mediumblob COMMENT 'Order Options',
  `epm_o_return_code` varchar(16) DEFAULT NULL COMMENT 'Order Return Code',
  `epm_o_cvx` varchar(3) DEFAULT NULL COMMENT 'Order CVX',
  `epm_o_vld` varchar(4) DEFAULT NULL COMMENT 'Ordering Card Validity',
  `epm_o_brand` varchar(2) DEFAULT NULL COMMENT 'Ordering Card Brand',
  `epm_o_status3ds` varchar(2) DEFAULT NULL COMMENT 'Order 3DSecure level',
  `epm_o_numauto` varchar(16) DEFAULT NULL COMMENT 'Order Confirmation number',
  `epm_o_ip` tinyblob NOT NULL COMMENT 'Ordering user''s IP',
  `epm_o_whyrefused` varchar(16) DEFAULT NULL COMMENT 'Reason for order refusal',
  `epm_o_originecb` varchar(3) DEFAULT NULL COMMENT 'Geographic origin of card',
  `epm_o_bincb` varchar(16) DEFAULT NULL COMMENT 'Card''s bank''s bin',
  `epm_o_hpancb` varchar(40) DEFAULT NULL COMMENT 'Card''s number hash',
  `epm_o_originetr` varchar(3) DEFAULT NULL COMMENT 'Geographic origin of order',
  `epm_o_veres` varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure VERes',
  `epm_o_pares` varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure PARes',
  `epm_o_filtercause` blob COMMENT 'Filter return array',
  `epm_o_filtervalue` blob COMMENT 'Filter return array values',
  PRIMARY KEY (`epm_id`),
  KEY `epm_user_id` (`epm_user_id`),
  KEY `epm_o_reference` (`epm_o_reference`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Table to hold all messages sent between Bank and server.' AUTO_INCREMENT=1 ;
