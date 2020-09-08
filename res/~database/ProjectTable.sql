CREATE TABLE IF NOT EXISTS `projects` (
	`id`          INT NOT NULL AUTO_INCREMENT,
	`name`        VARCHAR(32) NOT NULL,
	`url`         VARCHAR(32) NOT NULL,
	`description` TEXT NOT NULL,
	`owner_id`    CHAR(9) NOT NULL,
	`hidden`      BOOL NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`url`),
	CONSTRAINT fk__project__owner FOREIGN KEY (`owner_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
