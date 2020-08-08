CREATE TABLE `tracker_user_settings` (
	`tracker_id`       INT NOT NULL,
	`user_id`          INT NOT NULL,
	`active_milestone` INT DEFAULT NULL,
	PRIMARY KEY (`tracker_id`, `user_id`),
	FOREIGN KEY (`tracker_id`)
		REFERENCES `trackers` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`user_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`active_milestone`)
		REFERENCES `milestones` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	FOREIGN KEY (`active_milestone`, `tracker_id`) # Ensures the milestone-tracker pair is always valid.
		REFERENCES `milestones` (`id`, `tracker_id`)
		ON UPDATE NO ACTION
		ON DELETE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
