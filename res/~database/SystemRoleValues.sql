INSERT INTO `system_roles` (id, type, title, ordering)
VALUES (1, 'admin', 'Admin', 0)
ON DUPLICATE KEY UPDATE id = id
