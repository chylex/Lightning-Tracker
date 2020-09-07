CREATE TABLE IF NOT EXISTS `system_roles` (
	`id`       SMALLINT NOT NULL AUTO_INCREMENT,
	`title`    VARCHAR(32) NOT NULL,
	`ordering` SMALLINT NOT NULL,
	`special`  BOOL NOT NULL DEFAULT FALSE,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`title`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
