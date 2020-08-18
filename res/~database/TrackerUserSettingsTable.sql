CREATE TABLE IF NOT EXISTS `tracker_user_settings` (
	`tracker_id`       INT NOT NULL,
	`user_id`          INT NOT NULL,
	`active_milestone` INT DEFAULT NULL,
	PRIMARY KEY (`tracker_id`, `user_id`),
	FOREIGN KEY (`user_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`active_milestone`, `tracker_id`)
		# Ensures the milestone-tracker pair is always valid.
		REFERENCES `milestones` (`milestone_id`, `tracker_id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
