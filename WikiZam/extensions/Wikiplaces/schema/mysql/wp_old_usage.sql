-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_usage`
--

CREATE TABLE IF NOT EXISTS `wp_old_usage` (
  `wpou_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpou_wpw_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated WikiPlace',
  `wpou_end_date` datetime NOT NULL COMMENT 'When the usage report ended',
  `wpou_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Number of page hits of the current report',
  `wpou_monthly_bandwidth` bigint(20) unsigned NOT NULL COMMENT 'Total size in bytes of all downloads WikiPlace s files',
  PRIMARY KEY (`wpou_id`),
  KEY `wpou_wpw_id` (`wpou_wpw_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary COMMENT='WikiPlace old usage reports table';





