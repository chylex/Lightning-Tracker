CREATE TABLE `trackers` (
	`id`     INT NOT NULL AUTO_INCREMENT,
	`name`   VARCHAR(32) NOT NULL,
	`url`    VARCHAR(32) NOT NULL,
	`owner_id`  INT NOT NULL,
	`hidden` BOOL NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`url`),
	FOREIGN KEY (`owner_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
