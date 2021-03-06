-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Table structure for table `wp_plan`
--


CREATE TABLE IF NOT EXISTS `wp_plan` (
  `wpp_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpp_name` varbinary(255) NOT NULL COMMENT 'Plan''s name',
  `wpp_period_months` tinyint(3) unsigned NOT NULL COMMENT 'Nb of month of plan''s period',
  `wpp_price` decimal(9,2) unsigned NOT NULL COMMENT 'Price for the complete period',
  `wpp_currency` varbinary(3) NOT NULL COMMENT 'Currency of the price',
  `wpp_start_date` datetime NOT NULL COMMENT 'When begin to be available for subscriptions',
  `wpp_end_date` datetime NOT NULL COMMENT 'When end being available for subscriptions',
  `wpp_nb_wikiplaces` tinyint(3) unsigned NOT NULL COMMENT 'Nb of WikiPlaces ownable by the subscriber of the plan',
  `wpp_nb_wikiplace_pages` smallint(5) unsigned NOT NULL COMMENT 'Nb of total WikiPlace''s pages ownable by the subscriber of the plan',
  `wpp_diskspace` int(10) unsigned NOT NULL COMMENT 'In megabytes, disk space quota',
  `wpp_monthly_page_hits` bigint(20) unsigned NOT NULL COMMENT 'Quota, pages''hits per month',
  `wpp_monthly_bandwidth` int(10) unsigned NOT NULL COMMENT 'In megabytes, quota, downloads bandwidth per month',
  `wpp_renew_wpp_id` int(10) unsigned NOT NULL COMMENT '0 = can be taken as renewal, x = can''t be taken as renewal and x is the default plan to renew to',
  `wpp_invitation_only` tinyint(3) unsigned NOT NULL COMMENT '0 = no constraint, 1 = require invitation code',
  PRIMARY KEY (`wpp_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=binary COMMENT='WikiPlace plans table';

--
-- Contenu de la table `wp_plan`
--

INSERT INTO `wp_plan` (`wpp_id`, `wpp_name`, `wpp_period_months`, `wpp_price`, `wpp_currency`, `wpp_start_date`, `wpp_end_date`, `wpp_nb_wikiplaces`, `wpp_nb_wikiplace_pages`, `wpp_diskspace`, `wpp_monthly_page_hits`, `wpp_monthly_bandwidth`, `wpp_renew_wpp_id`, `wpp_invitation_only`) VALUES

(1,  'basic1', 1,            '5.00' , 'EUR', '2012-01-01 00:00:01', '2014-01-01 00:00:01',  3,  100,    1024,  300000,  30720, 0, 0),
(2,  'basic3', 3,            '12.50', 'EUR', '2012-01-01 00:00:01', '2014-01-01 00:00:02',  3,  100,    1024,  300000,  30720, 0, 0),
(3,  'basic12', 12,          '40.00', 'EUR', '2012-01-01 00:00:01', '2014-01-01 00:00:03',  3,  100,    1024,  300000,  30720, 0, 0),

(4,  'pro1', 1,              '30.00', 'EUR', '2012-01-01 00:00:01', '2014-01-01 00:00:04', 10,  1000,  10240, 2000000, 204800, 0, 0),
(5,  'pro3', 3,              '75.00', 'EUR', '2012-01-01 00:00:01', '2014-01-01 00:00:05', 10,  1000,  10240, 2000000, 204800, 0, 0),
(6,  'pro12', 12,           '240.00', 'EUR', '2012-01-01 00:00:01', '2014-01-01 00:00:06', 10,  1000,  10240, 2000000, 204800, 0, 0),

(7,  'basic1-launch', 1,     '0.00' , 'EUR', '2012-01-01 00:00:01', '2012-11-01 00:00:01',  3,  100,    1024,  300000,  30720, 1, 0),
(8,  'basic3-launch', 3,      '6.25', 'EUR', '2012-01-01 00:00:01', '2012-11-01 00:00:02',  3,  100,    1024,  300000,  30720, 2, 0),
(9,  'basic12-launch', 12,   '20.00', 'EUR', '2012-01-01 00:00:01', '2012-11-01 00:00:03',  3,  100,    1024,  300000,  30720, 3, 0),

(10,  'pro1-launch', 1,      '15.00', 'EUR', '2012-01-01 00:00:01', '2012-11-01 00:00:04', 10,  1000,  10240, 2000000, 204800, 4, 0),
(11,  'pro3-launch', 3,      '37.50', 'EUR', '2012-01-01 00:00:01', '2012-11-01 00:00:05', 10,  1000,  10240, 2000000, 204800, 5, 0),
(12,  'pro12-launch', 12,   '120.00', 'EUR', '2012-01-01 00:00:01', '2012-11-01 00:00:06', 10,  1000,  10240, 2000000, 204800, 6, 0),


(13,  'test3-launch', 3,      '0.00', 'EUR', '2012-01-01 00:00:01', '2012-09-01 00:00:01', 2,     30,    300,  100000,  10240, 3, 0),
(14,  'test12-launch', 12,    '6.00', 'EUR', '2012-01-01 00:00:01', '2012-09-01 00:00:02', 2,     30,    300,  100000,  10240, 3, 0);