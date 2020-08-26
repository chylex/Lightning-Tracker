CREATE TABLE IF NOT EXISTS `project_roles` (
	`project_id` INT NOT NULL,
	`role_id`    INT NOT NULL,
	`title`      VARCHAR(32) NOT NULL,
	`ordering`   MEDIUMINT NOT NULL,
	`special`    BOOL NOT NULL DEFAULT FALSE,
	PRIMARY KEY (`project_id`, `role_id`),
	UNIQUE KEY (`project_id`, `title`),
	KEY (`role_id`, `project_id`),
	# Needed for role-project pair checks.
	FOREIGN KEY (`project_id`)
		REFERENCES `projects` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
