-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_usage`
--


CREATE TABLE IF NOT EXISTS `wp_usage` (
  `wpu_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpu_wps_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated subscription',
  `wpu_wpw_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated WikiPlace',
  `wpu_start_date` datetime NOT NULL COMMENT 'When the usage report report starts',
  `wpu_end_date` datetime NOT NULL COMMENT 'When the usage report ends',
  `wpu_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Number of page hits of the current report',
  `wpu_monthly_bandwidth` bigint(20) unsigned NOT NULL COMMENT 'Total size in bytes of all downloads WikiPlace s files',
  PRIMARY KEY (`wpu_id`),
  UNIQUE KEY `wpu_wps_id` (`wpu_wps_id`),
  UNIQUE KEY `wpu_wpw_id` (`wpu_wpw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='WikiPlace usage reports table' AUTO_INCREMENT=1 ;