CREATE TABLE IF NOT EXISTS `users` (
	`id`              CHAR(9) NOT NULL,
	`name`            VARCHAR(32) NOT NULL,
	`email`           VARCHAR(191) NOT NULL, # Size limit needed due to low key size limits in older versions of MySQL.
	`password`        VARCHAR(255) NOT NULL,
	`role_id`         SMALLINT DEFAULT NULL,
	`admin`           BOOL NOT NULL DEFAULT FALSE,
	`date_registered` DATETIME NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	UNIQUE KEY `email` (`email`),
	CONSTRAINT fk__user__role FOREIGN KEY (`role_id`)
		REFERENCES `system_roles` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
