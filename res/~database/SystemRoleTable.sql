CREATE TABLE IF NOT EXISTS `system_roles` (
	`id`       SMALLINT NOT NULL AUTO_INCREMENT,
	`type`     ENUM ('normal', 'admin') NOT NULL DEFAULT 'normal',
	`title`    VARCHAR(32) NOT NULL,
	`ordering` SMALLINT NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`title`),
	UNIQUE KEY (`type`, `ordering`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
