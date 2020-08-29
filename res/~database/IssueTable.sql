CREATE TABLE IF NOT EXISTS `issues` (
	`project_id`   INT NOT NULL,
	`issue_id`     INT NOT NULL,
	`author_id`    INT NULL,
	`assignee_id`  INT NULL,
	`milestone_id` INT DEFAULT NULL,
	`title`        VARCHAR(128) NOT NULL,
	`description`  TEXT NOT NULL,
	`type`         ENUM ('feature', 'enhancement', 'bug', 'crash', 'task') NOT NULL,
	`priority`     ENUM ('low', 'medium', 'high') NOT NULL,
	`scale`        ENUM ('tiny', 'small', 'medium', 'large', 'massive') NOT NULL,
	`status`       ENUM ('open', 'in-progress', 'ready-to-test', 'blocked', 'finished', 'rejected') DEFAULT 'open' NOT NULL,
	`progress`     TINYINT DEFAULT 0 NOT NULL,
	`date_created` DATETIME NOT NULL,
	`date_updated` DATETIME NOT NULL,
	PRIMARY KEY (`project_id`, `issue_id`),
	CONSTRAINT fk__issue__project FOREIGN KEY (`project_id`)
		REFERENCES `projects` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	CONSTRAINT fk__issue__author FOREIGN KEY (`author_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	CONSTRAINT fk__issue__assignee FOREIGN KEY (`assignee_id`)
		# Can be a non-member, but only if an already assigned user's membership got revoked.
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	CONSTRAINT fk__issue__milestone FOREIGN KEY (`milestone_id`, `project_id`)
		# Ensures the milestone-project pair is always valid.
		REFERENCES `milestones` (`milestone_id`, `project_id`)
		ON UPDATE CASCADE
		ON DELETE RESTRICT,
	CONSTRAINT fk__issue__scale FOREIGN KEY (`scale`)
		REFERENCES `issue_weights` (`scale`)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
