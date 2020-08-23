CREATE TABLE IF NOT EXISTS `milestones` (
	`milestone_id` INT NOT NULL,
	`tracker_id`   INT NOT NULL,
	`ordering`     MEDIUMINT NOT NULL,
	`title`        VARCHAR(64) NOT NULL,
	PRIMARY KEY (`tracker_id`, `milestone_id`),
	KEY (`milestone_id`, `tracker_id`), # Needed for milestone-tracker pair checks.
	FOREIGN KEY (`tracker_id`)
		REFERENCES `trackers` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
