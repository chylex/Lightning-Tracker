CREATE TABLE IF NOT EXISTS `milestones` (
	`project_id`   INT NOT NULL,
	`milestone_id` INT NOT NULL,
	`ordering`     MEDIUMINT NOT NULL,
	`title`        VARCHAR(64) NOT NULL,
	PRIMARY KEY (`project_id`, `milestone_id`),
	KEY (`milestone_id`, `project_id`), # Needed for milestone-project pair checks.
	FOREIGN KEY (`project_id`)
		REFERENCES `projects` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
