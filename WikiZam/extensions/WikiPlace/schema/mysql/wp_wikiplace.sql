-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_wikiplace`
--


CREATE TABLE IF NOT EXISTS `wp_wikiplace` (
  `wpw_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpw_owner_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the user who owns the WikiPlace',
  `wpw_home_page_id` int(10) unsigned NOT NULL COMMENT 'WikiPlace homepage WpPage identifier',
  PRIMARY KEY (`wpw_id`),
  UNIQUE KEY `wpw_home_wppa_id` (`wpw_home_wppa_id`),
  KEY `wpw_owner_user_id` (`wpw_owner_user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary;