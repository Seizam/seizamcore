CREATE TABLE IF NOT EXISTS `wp_wpi_wpp` (
  `wpip_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpip_wpic_id` int(10) unsigned NOT NULL COMMENT 'Invitation category primary key',
  `wpip_wpp_id` int(10) unsigned NOT NULL COMMENT 'Plan primary key',
  PRIMARY KEY (`wpip_id`),
  KEY `wpip_wpic_id` (`wpip_wpic_id`,`wpip_wpp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary ;