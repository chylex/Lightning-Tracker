CREATE TABLE IF NOT EXISTS `project_role_perms` (
	`project_id` INT NOT NULL,
	`role_id`    INT NOT NULL,
	`permission` ENUM (
		'settings',
		'members.list',
		'members.manage',
		'milestones.manage',
		'issues.create',
		'issues.fields.all',
		'issues.edit.all',
		'issues.delete.all') NOT NULL,
	PRIMARY KEY (`project_id`, `role_id`, `permission`),
	FOREIGN KEY (`role_id`, `project_id`)
		REFERENCES `project_roles` (`role_id`, `project_id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
