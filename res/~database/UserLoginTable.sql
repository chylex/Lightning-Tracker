CREATE TABLE IF NOT EXISTS `user_logins` (
	`id`      INT NOT NULL,
	`token`   VARCHAR(191) NOT NULL, # Size limit needed due to low key size limits in older versions of MySQL.
	`expires` DATETIME NOT NULL,
	PRIMARY KEY (`id`, `token`),
	UNIQUE KEY (`token`),
	CONSTRAINT fk__user_login__user FOREIGN KEY (`id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
