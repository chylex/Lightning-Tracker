CREATE TABLE IF NOT EXISTS `project_members` (
	`project_id` INT NOT NULL,
	`user_id`    CHAR(9) NOT NULL,
	`role_id`    INT NULL,
	PRIMARY KEY (`project_id`, `user_id`),
	CONSTRAINT fk__project_member__project FOREIGN KEY (`project_id`)
		REFERENCES `projects` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	CONSTRAINT fk__project_member__user FOREIGN KEY (`user_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	CONSTRAINT fk__project_member__role FOREIGN KEY (`role_id`, `project_id`)
		# Ensures the role-project pair is always valid.
		REFERENCES `project_roles` (`role_id`, `project_id`)
		ON UPDATE CASCADE
		ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
