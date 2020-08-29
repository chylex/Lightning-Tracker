CREATE TABLE IF NOT EXISTS `project_user_settings` (
	`project_id`       INT NOT NULL,
	`user_id`          CHAR(9) NOT NULL,
	`active_milestone` INT DEFAULT NULL,
	PRIMARY KEY (`project_id`, `user_id`),
	CONSTRAINT fk__project_user_setting__user FOREIGN KEY (`user_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	CONSTRAINT fk__project_user_setting__active_milestone FOREIGN KEY (`active_milestone`, `project_id`)
		# Ensures the milestone-project pair is always valid.
		REFERENCES `milestones` (`milestone_id`, `project_id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
