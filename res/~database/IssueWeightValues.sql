INSERT INTO `issue_weights` (`scale`, `contribution`)
VALUES ('tiny', 2),
       ('small', 5),
       ('medium', 13),
       ('large', 32),
       ('massive', 80)
ON DUPLICATE KEY UPDATE contribution = VALUES(contribution)
