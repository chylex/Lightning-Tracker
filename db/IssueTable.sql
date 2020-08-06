CREATE TABLE `issues` (
	`tracker_id`   INT NOT NULL,
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
	PRIMARY KEY (`tracker_id`, `issue_id`),
	FOREIGN KEY (`tracker_id`)
		REFERENCES `trackers` (`id`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`author_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	FOREIGN KEY (`assignee_id`)
		REFERENCES `users` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	FOREIGN KEY (`milestone_id`)
		REFERENCES `milestones` (`id`)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
	FOREIGN KEY (`scale`)
		REFERENCES `issue_weights` (`scale`)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
