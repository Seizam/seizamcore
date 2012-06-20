CREATE TABLE IF NOT EXISTS `wp_invitation` (
  `wpi_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `wpi_code` varbinary(32) NOT NULL COMMENT 'Invitation code',
  `wpi_to_email` tinyblob COMMENT 'Email sent to',
  `wpi_from_user_id` int(10) unsigned NOT NULL COMMENT 'User who created the code',
  `wpi_date_created` datetime NOT NULL COMMENT 'When the code was created',
  `wpi_date_last_used` datetime DEFAULT NULL COMMENT 'Last time the code was used',
  `wpi_counter` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'How many time the code can be used',
  `wpi_wpic_id` int(10) unsigned NOT NULL COMMENT 'Invitation category primary key',
  PRIMARY KEY (`wpi_id`),
  UNIQUE KEY `wpi_code` (`wpi_code`)
) ENGINE=InnoDB DEFAULT CHARSET=binary ;