CREATE TABLE IF NOT EXISTS `project_user_settings` (
	`project_id`       INT NOT NULL,
	`user_id`          INT NOT NULL,
	`active_milestone` INT DEFAULT NULL,
	PRIMARY KEY (`project_id`, `user_id`),
	FOREIGN KEY (`user_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`active_milestone`, `project_id`)
		# Ensures the milestone-project pair is always valid.
		REFERENCES `milestones` (`milestone_id`, `project_id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
