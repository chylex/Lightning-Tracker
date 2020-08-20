CREATE TABLE IF NOT EXISTS `tracker_roles` (
	`id`         INT NOT NULL AUTO_INCREMENT,
	`tracker_id` INT NOT NULL,
	`title`      VARCHAR(32) NOT NULL,
	`ordering`   MEDIUMINT NOT NULL,
	`special`    BOOL NOT NULL DEFAULT FALSE,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`tracker_id`, `title`),
	KEY (`id`, `tracker_id`),
	# Needed for role-tracker pair checks.
	FOREIGN KEY (`tracker_id`)
		REFERENCES `trackers` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
