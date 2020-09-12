CREATE TABLE IF NOT EXISTS `project_roles` (
	`project_id` INT NOT NULL,
	`role_id`    SMALLINT NOT NULL,
	`type`       ENUM ('normal', 'owner') NOT NULL DEFAULT 'normal',
	`title`      VARCHAR(32) NOT NULL,
	`ordering`   SMALLINT NOT NULL,
	PRIMARY KEY (`project_id`, `role_id`),
	UNIQUE KEY (`project_id`, `title`),
	UNIQUE KEY (`project_id`, `type`, `ordering`),
	KEY (`role_id`, `project_id`), # Needed for role-project pair checks.
	CONSTRAINT fk__project_role__project FOREIGN KEY (`project_id`)
		REFERENCES `projects` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
