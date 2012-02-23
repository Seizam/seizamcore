-- (c) Clément Dietschy, 2011, GPL

--
-- Table structure for table `ep_message`
--

CREATE TABLE IF NOT EXISTS `ep_message` (
  `epm_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `epm_type` varchar(4) NOT NULL COMMENT 'Type of message (INcoming, OUTcoming)',
  `epm_date_created` datetime NOT NULL COMMENT 'DateTime',
  `epm_epo_id` int(10) unsigned NOT NULL COMMENT 'Order Reference',
  `epm_ept` int(8) unsigned NOT NULL DEFAULT '0' COMMENT 'EPT id',
  `epm_date_message` datetime NOT NULL COMMENT 'Message DateTime',
  `epm_free_text` mediumblob COMMENT 'Message Free Text',
  `epm_ip` tinyblob NOT NULL COMMENT 'Ordering user''s IP',
  `epm_mac` varchar(40) DEFAULT NULL COMMENT 'Message Verification Sum',
  `epm_options` mediumblob COMMENT 'Message Options',
  `epm_return_code` varchar(16) DEFAULT NULL COMMENT 'Message Return Code',
  `epm_cvx` varchar(3) DEFAULT NULL COMMENT 'Order CVX',
  `epm_vld` varchar(4) DEFAULT NULL COMMENT 'Ordering Card Validity',
  `epm_brand` varchar(2) DEFAULT NULL COMMENT 'Ordering Card Brand',
  `epm_status3ds` varchar(2) DEFAULT NULL COMMENT 'Order 3DSecure level',
  `epm_numauto` varchar(16) DEFAULT NULL COMMENT 'Order Confirmation number',
  `epm_whyrefused` varchar(16) DEFAULT NULL COMMENT 'Reason for order refusal',
  `epm_originecb` varchar(3) DEFAULT NULL COMMENT 'Geographic origin of card',
  `epm_bincb` varchar(16) DEFAULT NULL COMMENT 'Card''s bank''s bin',
  `epm_hpancb` varchar(40) DEFAULT NULL COMMENT 'Card''s number hash',
  `epm_originetr` varchar(3) DEFAULT NULL COMMENT 'Geographic origin of order',
  `epm_veres` varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure VERes',
  `epm_pares` varchar(1) DEFAULT NULL COMMENT 'State of 3DSecure PARes',
  `epm_filtercause` blob COMMENT 'Filter return array',
  `epm_filtervalue` blob COMMENT 'Filter return array values',
  PRIMARY KEY (`epm_id`),
  KEY `epm_epo_id` (`epm_epo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Table to hold all messages sent between Bank and server.' AUTO_INCREMENT=1 ;
