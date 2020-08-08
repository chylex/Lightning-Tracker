CREATE TABLE IF NOT EXISTS `issue_weights` (
	`scale`        ENUM ('tiny', 'small', 'medium', 'large', 'massive') NOT NULL,
	`contribution` TINYINT NOT NULL,
	PRIMARY KEY (`scale`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci
