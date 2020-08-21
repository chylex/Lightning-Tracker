CREATE TABLE IF NOT EXISTS `tracker_role_perms` (
	`role_id`    INT NOT NULL,
	`permission` ENUM (
		'settings',
		'members.list',
		'members.manage',
		'milestones.manage',
		'issues.create',
		'issues.edit.all',
		'issues.delete.all') NOT NULL,
	PRIMARY KEY (`role_id`, `permission`),
	FOREIGN KEY (`role_id`)
		REFERENCES `tracker_roles` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
