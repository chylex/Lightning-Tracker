CREATE TABLE IF NOT EXISTS `users` (
	`id`              INT NOT NULL AUTO_INCREMENT,
	`name`            VARCHAR(32) NOT NULL,
	`email`           VARCHAR(255) NOT NULL,
	`password`        VARCHAR(255) NOT NULL,
	`date_registered` DATETIME NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	UNIQUE KEY `email` (`email`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
