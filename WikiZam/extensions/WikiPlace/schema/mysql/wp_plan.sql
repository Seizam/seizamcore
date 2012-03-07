-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_plan`
--


CREATE TABLE IF NOT EXISTS `wp_plan` (
  `wpp_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpp_name` varbinary(255) NOT NULL COMMENT 'Plan''s name',
  `wpp_period_months` tinyint(3) unsigned NOT NULL COMMENT 'Nb of month of plan''s period',
  `wpp_price` decimal(9,2) unsigned NOT NULL COMMENT 'Price per period',
  `wpp_currency` varbinary(3) NOT NULL COMMENT 'Currency of the price',
  `wpp_start_date` datetime NOT NULL COMMENT 'When begin to be available for subscriptions',
  `wpp_end_date` datetime NOT NULL COMMENT 'When end being available for subscriptions',
  `wpp_nb_wikiplaces` tinyint(3) unsigned NOT NULL COMMENT 'Nb of WikiPlaces ownable by the subscriber of the plan',
  `wpp_nb_wikiplace_pages` smallint(5) unsigned NOT NULL COMMENT 'Nb of total WikiPlace''s pages ownable by the subscriber of the plan',
  `wpp_diskspace` bigint(20) unsigned NOT NULL COMMENT 'In bytes, disk space quota',
  `wpp_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Quota, pages''hits per month',
  `wpp_monthly_bandwidth` bigint(20) unsigned NOT NULL COMMENT 'In bytes, quota, downloads bandwidth per month',
  PRIMARY KEY (`wpp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary COMMENT='WikiPlace plans table' AUTO_INCREMENT=1 ;


INSERT INTO `wikidb`.`wp_plan` (`wpp_id`, `wpp_name`, `wpp_period_months`, `wpp_price`, `wpp_currency`, `wpp_start_date`, `wpp_end_date`, `wpp_nb_wikiplaces`, `wpp_nb_wikiplace_pages`, `wpp_diskspace`, `wpp_monthly_page_hits`, `wpp_monthly_bandwidth`) VALUES (NULL, 'test_plan', '1', '10', 'EUR', '2012-01-01 00:00:01', '2044-12-31 23:59:59', '3', '10', '1000000', '30', '2000000');