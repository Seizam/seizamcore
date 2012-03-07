-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_subscription`
--


CREATE TABLE IF NOT EXISTS `wp_subscription` (
  `wps_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wps_wpp_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: primary key of the subscribed plan',
  `wps_buyer_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the user who buyed the plan',
  `wps_tmr_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the transaction record of the buy',
  `wps_paid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 = not paid, 1 = paid',
  `wps_date_created` datetime NOT NULL COMMENT 'When the record was created',
  `wps_start_date` datetime DEFAULT NULL COMMENT 'When the subscription starts (can be different from wps_date_created) ',
  `wps_end_date` datetime DEFAULT NULL COMMENT 'When the subscription ends',
  `wps_active` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 = currently not used, 1 = currently in use',
  PRIMARY KEY (`wps_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Wikiplace subscriptions table';