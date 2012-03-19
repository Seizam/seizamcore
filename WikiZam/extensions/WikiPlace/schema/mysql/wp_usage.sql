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
  `wpu_active` tinyint(3) unsigned NOT NULL COMMENT '0 = not the current usage report, 1 = this is the active usage report (avoid checking dates)',
  `wpu_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Number of page hits of the current report',
  `wpu_monthly_bandwidth` bigint(20) unsigned NOT NULL COMMENT 'Total size in bytes of all downloads WikiPlace s files',
  `wpu_updated` datetime NOT NULL COMMENT 'When last updated',
  PRIMARY KEY (`wpu_id`),
  UNIQUE KEY `wpu_wpw_id` (`wpu_wpw_id`),
  KEY `wpu_wps_id` (`wpu_wps_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary COMMENT='WikiPlace usage reports table';
