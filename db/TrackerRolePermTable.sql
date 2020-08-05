CREATE TABLE tracker_role_perms (
	`role_id`    INT NOT NULL,
	`permission` ENUM (
		'settings') NOT NULL,
	PRIMARY KEY (`role_id`, `permission`),
	FOREIGN KEY (`role_id`)
		REFERENCES tracker_roles (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
