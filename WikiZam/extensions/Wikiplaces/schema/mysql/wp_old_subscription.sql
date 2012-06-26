-- (c) Yann Missler, Seizam SARL, 2012, GPL

--
-- Structure de la table `wp_subscription`
--

CREATE TABLE IF NOT EXISTS `wp_old_subscription` (
  `wpos_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpos_wpp_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: primary key of the subscribed plan',
  `wpos_buyer_user_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the user who buyed the plan',
  `wpos_tmr_id` int(10) unsigned NOT NULL COMMENT 'Foreign key: the transaction record of the buy',
  `wpos_tmr_status` varbinary(2) NOT NULL COMMENT 'Paiement status when the record was saved (may be outdated) PE,KO = not paid, OK = paid',
  `wpos_start_date` datetime DEFAULT NULL COMMENT 'When the subscription starts',
  `wpos_end_date` datetime DEFAULT NULL COMMENT 'When the subscription ends',
  `wpos_wpi_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'if != 0 : invitation primary key that has been type (but maybe not consumed) when subscribing',
  PRIMARY KEY (`wpos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary COMMENT='Wikiplace subscriptions history table';
