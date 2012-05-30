-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_wikiplace`
--


CREATE TABLE IF NOT EXISTS `wp_wikiplace` (
  `wpw_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpw_owner_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the user who owns the WikiPlace',
  `wpw_home_page_id` int(10) unsigned NOT NULL COMMENT 'WikiPlace homepage WpPage identifier',
  `wpw_wps_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: current associated subscription',
  `wpw_previous_total_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Total page hits value when the usage was reset',
  `wpw_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Number of monthly page hits',
  `wpw_previous_total_bandwidth` int(10) unsigned NOT NULL COMMENT 'Total bandwidth value when the usage was reset',
  `wpw_monthly_bandwidth` bigint(20) unsigned NOT NULL COMMENT 'Total size in kB of monthly downloaded WikiPlace s files',
  `wpw_report_updated` datetime NOT NULL COMMENT 'When wpw_monthly_page_hits and wpw_monthly_bandwidth last updated',
  `wpw_date_expires` datetime NOT NULL COMMENT 'When the current report end (= usage reset date)',
  PRIMARY KEY (`wpw_id`),
  UNIQUE KEY `wpw_home_page_id` (`wpw_home_page_id`),
  KEY `wpw_owner_user_id` (`wpw_owner_user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary;