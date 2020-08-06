CREATE TABLE `user_logins` (
	`id`      INT NOT NULL,
	`token`   VARCHAR(255) NOT NULL,
	`expires` DATETIME NOT NULL,
	PRIMARY KEY (`id`, `token`),
	UNIQUE KEY (`token`),
	FOREIGN KEY (`id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
