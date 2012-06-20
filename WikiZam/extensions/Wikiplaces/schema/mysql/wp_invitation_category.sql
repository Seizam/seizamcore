CREATE TABLE IF NOT EXISTS `wp_invitation_category` (
  `wpic_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpic_start_date` datetime NOT NULL COMMENT 'When the category starts to be available',
  `wpic_end_date` datetime NOT NULL COMMENT 'When the category is not available',
  `wpic_desc` varbinary(255) NOT NULL COMMENT 'i18n message',
  `wpic_monthly_limit` int(10) unsigned NOT NULL COMMENT 'How many codes for this category can be genereted by a user in a month',
  `wpic_public` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Is this category public (=1) ?',
  PRIMARY KEY (`wpic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary ;