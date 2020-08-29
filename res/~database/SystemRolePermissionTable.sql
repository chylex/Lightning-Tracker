CREATE TABLE IF NOT EXISTS `system_role_permissions` (
	`role_id`    SMALLINT NOT NULL,
	`permission` ENUM (
		'settings',
		'projects.list',
		'projects.list.all',
		'projects.create',
		'projects.manage',
		'users.list',
		'users.view.emails',
		'users.create',
		'users.manage') NOT NULL,
	PRIMARY KEY (`role_id`, `permission`),
	CONSTRAINT fk__system_role_permission__role FOREIGN KEY (`role_id`)
		REFERENCES `system_roles` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
