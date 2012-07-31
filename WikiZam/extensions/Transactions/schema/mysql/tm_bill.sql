-- (c) Clément Dietschy, 2011, GPL

--
-- Table structure for table `tm_record`
--

CREATE TABLE IF NOT EXISTS `tm_bill` (
  `tmb_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `tmb_date_created` datetime NOT NULL COMMENT 'DateTime of creation',
  PRIMARY KEY (`tmb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Transaction Manager bill table' AUTO_INCREMENT=1 ;

