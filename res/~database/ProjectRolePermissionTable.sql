CREATE TABLE IF NOT EXISTS `project_role_permissions` (
	`project_id` INT NOT NULL,
	`role_id`    SMALLINT NOT NULL,
	`permission` ENUM (
		'settings.view',
		'settings.manage.general',
		'settings.manage.description',
		'settings.manage.roles',
		'members.list',
		'members.manage',
		'milestones.manage',
		'issues.create',
		'issues.fields.all',
		'issues.edit.all',
		'issues.delete.all') NOT NULL,
	PRIMARY KEY (`project_id`, `role_id`, `permission`),
	CONSTRAINT fk__project_role_permission__role FOREIGN KEY (`role_id`, `project_id`)
		REFERENCES `project_roles` (`role_id`, `project_id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
