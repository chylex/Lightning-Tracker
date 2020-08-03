CREATE TABLE `trackers` (
	`id`     INT NOT NULL AUTO_INCREMENT,
	`name`   VARCHAR(32) NOT NULL,
	`url`    VARCHAR(32) NOT NULL,
	`hidden` BOOL NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`url`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci