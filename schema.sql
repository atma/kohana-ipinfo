CREATE TABLE `ipinfo` (
    `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `ipAddress` VARCHAR( 45 ) NOT NULL COMMENT 'max length can be 45 if enabled tunneling features for IPv6 0000:0000:0000:0000:0000:0000:192.168.0.1',
    `countryCode` VARCHAR( 3 ) NOT NULL ,
    `countryName` VARCHAR( 255 ) NOT NULL ,
    `regionName` VARCHAR( 255 ) NOT NULL ,
    `cityName` VARCHAR( 255 ) NOT NULL ,
    `zipCode` VARCHAR( 10 ) NULL ,
    `latitude` decimal(10,6) NULL ,
    `longitude` decimal(10,6) NULL ,
    `timeZone` VARCHAR( 10 ) NULL ,
    `lastCheck` INT( 11 ) UNSIGNED NOT NULL,
    INDEX ( `countryCode` , `countryName` , `cityName` ),
    UNIQUE (
        `ipAddress`
    )
) ENGINE = InnoDB;

CREATE TABLE `ipinfo_localization` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lang` varchar(3) NOT NULL,
  `canonical_country` varchar(255) NOT NULL,
  `canonical_region` varchar(255) NOT NULL,
  `canonical_city` varchar(255) NOT NULL,
  `translated_country` varchar(255) NOT NULL,
  `translated_region` varchar(255) NOT NULL,
  `translated_city` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uniq` (`lang`,`canonical_country`,`canonical_region`,`canonical_city`)
) ENGINE=InnoDB;
