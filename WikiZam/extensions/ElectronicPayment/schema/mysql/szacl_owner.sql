-- (c) Clément Dietschy, 2011, GPL

/* Current version does not use a special table, but based on exisiting functions

-- Associate User to Page (owner)
CREATE TABLE IF NOT EXISTS `szacl_owner` (
  -- Foreign key to page.page_id
  `szaclo_page_id` int(10) unsigned NOT NULL,
  -- Foreign key to user.user_id
  `szaclo_user_id` int(10) unsigned NOT NULL DEFAULT '1', 
  -- Private or Public?
  `szaclo_page_ispublic` tinyint(1) NOT NULL DEFAULT '0',
  
  PRIMARY KEY (`szaclo_page_id`),
  KEY `szaclo_user_id` (`szaclo_user_id`)
) /*$wgDBTableOptions*/;
*/
