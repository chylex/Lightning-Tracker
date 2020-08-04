CREATE TABLE `system_role_perms` (
	`role_id`    SMALLINT NOT NULL,
	`permission` ENUM (
	    'settings',
		'users.list',
		'users.list.email',
		'users.add',
		'users.edit') NOT NULL,
	PRIMARY KEY (`role_id`, `permission`),
	FOREIGN KEY (`role_id`)
		REFERENCES system_roles (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
