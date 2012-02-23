-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_page`
--


CREATE TABLE IF NOT EXISTS `wp_page` (
  `wppa_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wppa_wpw_id` int(10) NOT NULL COMMENT 'Foreign key: associated WikiPlace',
  `wppa_page_id` int(10) NOT NULL COMMENT 'Foreign key: associated MediaWiki''s page',
  PRIMARY KEY (`wppa_id`),
  UNIQUE KEY `wppa_wpw_id` (`wppa_wpw_id`),
  UNIQUE KEY `wppa_page_id` (`wppa_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary COMMENT='WikiPlace - Pages association table' AUTO_INCREMENT=1 ;