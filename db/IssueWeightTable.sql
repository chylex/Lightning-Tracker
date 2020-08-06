CREATE TABLE `issue_weights` (
	`scale`        ENUM ('tiny', 'small', 'medium', 'large', 'massive') NOT NULL,
	`contribution` TINYINT NOT NULL,
	PRIMARY KEY (`scale`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_general_ci;

INSERT INTO tracker.issue_weights (`scale`, `contribution`)
VALUES ('tiny', 2),
       ('small', 5),
       ('medium', 13),
       ('large', 32),
       ('massive', 80);
