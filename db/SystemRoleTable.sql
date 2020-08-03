CREATE TABLE `system_roles` (
	`id`    SMALLINT NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(32) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`title`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
