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
  `wpp_diskspace` int(10) unsigned NOT NULL COMMENT 'In megabytes, disk space quota',
  `wpp_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Quota, pages''hits per month',
  `wpp_monthly_bandwidth` int(10) unsigned NOT NULL COMMENT 'In megabytes, quota, downloads bandwidth per month',
  `wpp_renew_wpp_id` int(10) unsigned NOT NULL COMMENT '0 = not renewable, x = wpp_id of the next plan',
  `wpp_invitation_only` tinyint(3) unsigned NOT NULL COMMENT '0 = no constraint, 1 = require invitation code',
  PRIMARY KEY (`wpp_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary COMMENT='WikiPlace plans table';

--
-- Contenu de la table `wp_plan`
--

INSERT INTO `wp_plan` (`wpp_id`, `wpp_name`, `wpp_period_months`, `wpp_price`, `wpp_currency`, `wpp_start_date`, `wpp_end_date`, `wpp_nb_wikiplaces`, `wpp_nb_wikiplace_pages`, `wpp_diskspace`, `wpp_monthly_page_hits`, `wpp_monthly_bandwidth`, `wpp_renew_wpp_id`, `wpp_invitation_only`) VALUES
(1, 'test_plan_normal', 1, '10.00', 'EUR', '2012-01-01 00:00:01', '2044-12-31 23:59:59', 3, 10, 1, 30, 2, -1, 0),
(2, 'test_plan_plus', 1, '20.00', 'EUR', '2012-01-01 00:00:01', '2044-12-31 23:59:59', 5, 20, 2, 50, 5, -1, 0),
(3, 'test_plan_invitation', 1, '0.00', 'EUR', '2012-01-01 00:00:01', '2022-12-31 23:59:30', 1, 10, 0, 30, 0, 0, 1);
