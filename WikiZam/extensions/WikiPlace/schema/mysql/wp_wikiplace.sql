-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_wikiplace`
--


CREATE TABLE IF NOT EXISTS `wp_wikiplace` (
  `wpw_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpw_owner_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the user who owns the WikiPlace',
  `wpw_name` varbinary(255) NOT NULL COMMENT 'Name of the WikiPlace',
  PRIMARY KEY (`wpw_id`),
  KEY `wpw_owner_user_id` (`wpw_owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary ;