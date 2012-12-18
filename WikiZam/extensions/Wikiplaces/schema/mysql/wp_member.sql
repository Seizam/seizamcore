-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_member`
--


CREATE TABLE IF NOT EXISTS `wp_member` (
  `wpm_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpm_wpw_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated WikiPlace',
  `wpm_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: associated user',
  PRIMARY KEY (`wpm_id`),
  UNIQUE KEY `wpm_wpw_user` (`wpm_wpw_id`,`wpm_user_id`),
  KEY `wpm_wpw_id` (`wpm_wpw_id`),
  KEY `wpm_user_id` (`wpm_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary COMMENT='WikiPlace - Members association table';
