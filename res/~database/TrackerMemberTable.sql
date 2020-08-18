CREATE TABLE IF NOT EXISTS `tracker_members` (
	`tracker_id` INT NOT NULL,
	`user_id`    INT NOT NULL,
	`role_id`    INT NULL,
	PRIMARY KEY (`tracker_id`, `user_id`),
	FOREIGN KEY (`tracker_id`)
		REFERENCES `trackers` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`user_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`role_id`)
		REFERENCES `tracker_roles` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	FOREIGN KEY (`role_id`, `tracker_id`)
		# Ensures the role-tracker pair is always valid.
		REFERENCES `tracker_roles` (`id`, `tracker_id`)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
